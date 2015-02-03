Configuration
=============

```php

return array(
    'default' => array(
        'logger' => array(
            'logFormat' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        ),

        'dateFormat' => "Y-m-d H:i:s",

        'git' => array(
            'executable' => trim(`which git`),
            'commitFile' => '.current_release',
            'branch' => 'master',
        ),

        'deploy' => array(
            'user' => 'www-data',
            'group' => 'www-data',
            'symlink' => 'current',
        )
    ),

    'console' => array(
        'logger' => array(
            'logFormat' => "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n"
        )
    ),

    'system' => array(
        'pid' => sys_get_temp_dir().'/reliv_deploy.pid', // Pid file for deploy
        'config' => '/etc/deploy' // Location of the system config file.  This is a json config
    ),

    'auto' => array(
        'daemon' => 'Reliv\Deploy\Xmpp\Daemon\XmppDaemon' // Daemon service to use for auto
    ),

    'xmpp' => array(
        'connection' => array(
            'jid' => 'user@xmpp-server.com',
            'pass' => 'password',
            'force_tls' => true, // Use TLS
            'auth_type' => 'DIGEST-MD5', // Auth Type
            'priv_dir' => sys_get_temp_dir(), // Jaxl Private DIR
            'strict' => false, // Keep set at false
            'log_level' => 'JAXL_ERROR', // Jaxl messages are rerouted to the correct logger.  Leave setting here
        ),

        'start' => array(
            '--with-debug-shell' => false,  // Jaxl config.  Generally you do not need to change this
            '--with-unix-sock' => false // Jaxl config.  Generally you do not need to change this
        ),

        'notifications' => array(
            'defaultReportingLevel' => 'notice',
            'recipients' => array(
                array(
                    'jid' => 'user@xmpp-server.com',
                    'reportingLevel' => 'myReportingLevel',
                ),
                array(
                    'jid' => 'user@xmpp-server.com',
                    'reportingLevel' => 'myReportingLevel',
                ),
            )
        ),
        'logger' => array(
            'logFormat' => gethostname()." - %level_name%: %message% %context% %extra%\n",
        ),

        'cron' => array(
            'deploy' => array(
                'command' => 'deploy',
                'delay'   => '120000000'
            ),
        ),
    ),

    'events' => array (
        'subscribers' => array(
            'deploy' => '\Reliv\Deploy\EventSubscriber\SymfonyConsoleEventSubscriber',
            'xmpp'   => '\Reliv\Deploy\Xmpp\EventSubscriber\XmppEventSubscriber',
        ),
    ),
);
```

Application Config
==================

```php
return array(
    'applications' => array(
        'Test' => array(
            'name' => 'Test',
            'repositories' => array (
                'main' => array(
                    'type' => 'git',
                    'origin' => '/tmp/test',
                    'directory' => '/'
                )
            ),
            'deploy' => array(
                'location' => '/tmp/testDeploy',
                'hooks' => array(
                    'pre_deploy' => 'php preDeploy.php',
                    'post_deploy' => 'php postDeploy.php',
                    'pre_rollback' => 'php preRollback.php',
                    'post_rollback' => 'php postRollback.php'
                ),
            ),
        ),
    ),
),
```