<?php

namespace nickdekh\queueMonitor\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use yii\di\Instance;
use yii\mail\MailerInterface;

final class QueueMonitorController extends Controller
{
    public int $delay = 60;
    public array $to = [];
    public string $subject = 'Alarm - queue task is waiting';
    public string $cacheKey = 'monitor-queue-alarmed';
    public string $tableName = '{{%queue}}';
    public string $db = 'db';
    public string $cache = 'cache';
    public string $mailer = 'mailer';

    public function actionIndex(): int
    {
        $this->applyParams();

        $threshold = time() - $this->delay;
        $waitingJobs = (int)(new Query())
            ->from($this->tableName)
            ->where(['done_at' => null])
            ->andWhere(['<=', 'pushed_at', $threshold])
            ->count('*', Instance::ensure($this->db, \yii\db\Connection::class));

        $cache = Instance::ensure($this->cache, \yii\caching\CacheInterface::class);

        if ($waitingJobs === 0) {
            $cache->delete($this->cacheKey);
            return ExitCode::OK;
        }

        if ($cache->get($this->cacheKey)) {
            return ExitCode::OK;
        }

        if ($this->to === []) {
            $this->stderr("Queue monitor recipients are not configured.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $mailer = Instance::ensure($this->mailer, MailerInterface::class);
        $mailer->compose()
            ->setFrom([
                Yii::$app->params['fromEmail'] => Yii::$app->params['fromName'],
            ])
            ->setTo($this->to)
            ->setSubject($this->subject)
            ->setTextBody(
                "Queue has {$waitingJobs} task(s) waiting for more than {$this->delay} seconds."
            )
            ->send();

        $cache->set($this->cacheKey, true);

        return ExitCode::OK;
    }

    private function applyParams(): void
    {
        $params = Yii::$app->params['monitor-queue'] ?? [];

        $this->delay = (int)($params['delay'] ?? $this->delay);
        $this->to = $params['to'] ?? $this->to;
        $this->subject = $params['subject'] ?? $this->subject;
        $this->cacheKey = $params['cacheKey'] ?? $this->cacheKey;
        $this->tableName = $params['tableName'] ?? $this->tableName;
    }
}
