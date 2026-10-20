<?php

namespace nickdekh\queueMonitor;

use nickdekh\queueMonitor\commands\QueueMonitorController;
use Yii;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;

final class QueueMonitorBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app instanceof ConsoleApplication) {
            return;
        }

        if (!isset($app->controllerMap['monitor-queue'])) {
            $app->controllerMap['monitor-queue'] = [
                'class' => QueueMonitorController::class,
            ];
        }
    }
}
