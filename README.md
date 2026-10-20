# Yii2 Queue Monitor

Reusable Yii2 console command that sends an email when an unfinished database
queue job has been waiting longer than the configured delay.

The package registers the `monitor-queue` console command automatically:

```bash
php yii monitor-queue
```

Configuration can be supplied through application parameters:

```php
'monitor-queue' => [
    'delay' => 60,
    'to' => [
        'ops@example.com',
    ],
    'PROJECT_NAME' => 'My Project',
    'subject' => 'Alarm - queue task is waiting',
    'cacheKey' => 'monitor-queue-alarmed',
    'tableName' => '{{%queue}}',
],
```

The application must provide `db`, `cache`, and `mailer` components. The mailer
also uses `fromEmail` and `fromName` application parameters.
