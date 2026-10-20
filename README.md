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
    'fromEmail' => 'queue-monitor@example.com',
    'fromName' => 'Queue Monitor',
    'subject' => 'Alarm - queue task is waiting',
    'cacheKey' => 'monitor-queue-alarmed',
    'tableName' => '{{%queue}}',
    'db' => 'db',
    'cache' => 'cache',
    'mailer' => 'mailer',
],
```

The `monitor-queue` configuration is optional. The application must provide
`db`, `cache`, and `mailer` components. The default component IDs are `db`,
`cache`, and `mailer`, respectively.

`to` is required for sending notifications. `PROJECT_NAME`, `fromEmail`,
`fromName`, `subject`, `cacheKey`, and `tableName` are optional.

The sender can be configured globally:

```php
'params' => [
    'fromEmail' => 'noreply@example.com',
    'fromName' => 'Queue Monitor',
],
```

Values configured inside `monitor-queue` override the global sender settings.

All controller parameters can also be passed through the command line. CLI
values have priority over values from the application configuration:

```bash
php yii monitor-queue \
    --delay=300 \
    --to="ops@example.com,admin@example.com" \
    --PROJECT_NAME="Production" \
    --fromEmail="alerts@example.com" \
    --fromName="Production Queue" \
    --subject="Queue alarm" \
    --cacheKey="production-queue" \
    --tableName="{{%queue}}" \
    --db="db" \
    --cache="cache" \
    --mailer="mailer"
```

Multiple recipients passed through the command line must be separated by
commas.
