<?php

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
            'revisions' => 2,
        )
    ),

    'console' => array(
        'logger' => array(
            'logFormat' => "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n"
        )
    ),

    'system' => array(
        'pid' => sys_get_temp_dir().'/reliv_deploy.pid',
        'config' => '/etc/deploy'
    ),

    'auto' => array(
        'daemon' => 'Reliv\Deploy\Xmpp\Daemon\XmppDaemon'
    ),

    'xmpp' => array(
        'connection' => array(
            'priv_dir' => sys_get_temp_dir(),
            'strict' => false,
            'log_level' => 'JAXL_ERROR',
        ),

        'start' => array(
            '--with-debug-shell' => false,
            '--with-unix-sock' => false
        ),

        'notifications' => array(
            'defaultReportingLevel' => 'notice',
            'recipients' => array(),
        ),
        'logger' => array(
            'logFormat' => gethostname()." - %level_name%: %message% %context% %extra%\n",
        ),

        'cron' => array(
            'deploy' => array(
                'command' => 'deploy',
                'delay'   => '120000000', // In Micro seconds
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
