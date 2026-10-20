<?php

namespace nickdekh\queueMonitor\commands;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mail\MailerInterface;

final class QueueMonitorController extends Controller
{
    public int $delay = 60;
    public array $to = [];
    public string $subject = 'Alarm - queue task is waiting';
    public string $PROJECT_NAME = '';
    public string $fromEmail = '';
    public string $fromName = '';
    public string $cacheKey = 'monitor-queue-alarmed';
    public string $tableName = '{{%queue}}';
    public string $db = 'db';
    public string $cache = 'cache';
    public string $mailer = 'mailer';

    /**
     * @noinspection PhpUnused
     * @throws InvalidConfigException
     */
    public function actionIndex(): int
    {
        $this->applyParams();

        $threshold = time() - $this->delay;
        $waitingJobs = (int)(new Query())
            ->from($this->tableName)
            ->where(['done_at' => null])
            ->andWhere(['<=', 'pushed_at', $threshold])
            ->count('*', Instance::ensure($this->db, Connection::class));

        $cache = Instance::ensure($this->cache, CacheInterface::class);

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

        $fromEmail = $this->fromEmail !== ''
            ? $this->fromEmail
            : Yii::$app->params['fromEmail'];

        $fromName = $this->fromName !== ''
            ? $this->fromName
            : Yii::$app->params['fromName'];

        $subject = ($this->PROJECT_NAME !== '' ? "[$this->PROJECT_NAME] " : '')
            . $this->subject;

        $textBody = ($this->PROJECT_NAME !== '' ? "Project: $this->PROJECT_NAME\n" : '')
            . "Queue has $waitingJobs task(s) waiting for more than $this->delay seconds.";

        $mailer->compose()
            ->setFrom([$fromEmail => $fromName])
            ->setTo($this->to)
            ->setSubject($subject)
            ->setTextBody($textBody)
            ->send();

        $cache->set($this->cacheKey, true);

        return ExitCode::OK;
    }

    private function applyParams(): void
    {
        $params = Yii::$app->params['monitor-queue'] ?? [];

        if (!in_array('delay', $this->passedOptions, true)) {
            $this->delay = (int)($params['delay'] ?? $this->delay);
        }
        if (!in_array('to', $this->passedOptions, true)) {
            $this->to = $params['to'] ?? $this->to;
        }
        if (!in_array('subject', $this->passedOptions, true)) {
            $this->subject = $params['subject'] ?? $this->subject;
        }
        if (!in_array('PROJECT_NAME', $this->passedOptions, true)) {
            $this->PROJECT_NAME = (string)($params['PROJECT_NAME'] ?? $this->PROJECT_NAME);
        }
        if (!in_array('fromEmail', $this->passedOptions, true)) {
            $this->fromEmail = (string)($params['fromEmail'] ?? $this->fromEmail);
        }
        if (!in_array('fromName', $this->passedOptions, true)) {
            $this->fromName = (string)($params['fromName'] ?? $this->fromName);
        }
        if (!in_array('cacheKey', $this->passedOptions, true)) {
            $this->cacheKey = $params['cacheKey'] ?? $this->cacheKey;
        }
        if (!in_array('tableName', $this->passedOptions, true)) {
            $this->tableName = $params['tableName'] ?? $this->tableName;
        }
        if (!in_array('db', $this->passedOptions, true)) {
            $this->db = $params['db'] ?? $this->db;
        }
        if (!in_array('cache', $this->passedOptions, true)) {
            $this->cache = $params['cache'] ?? $this->cache;
        }
        if (!in_array('mailer', $this->passedOptions, true)) {
            $this->mailer = $params['mailer'] ?? $this->mailer;
        }
    }
}
