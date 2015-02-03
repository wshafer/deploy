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
            'jid' => 'bot@im.reliv.com',
            'pass' => 'the1end',
            'force_tls' => true,
            'auth_type' => 'DIGEST-MD5',
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
            'recipients' => array(
                array(
                    'jid' => 'wshafer@im.reliv.com',
                ),
                array(
                    'jid' => 'rmcnew@im.reliv.com',
                ),
            )
        ),
        'logger' => array(
            'logFormat' => gethostname()." - %level_name%: %message% %context% %extra%\n",
        ),

        'cron' => array(
            'deploy' => array(
                'command' => 'deploy',
                //'delay'   => '120000000'
                'delay'   => '1200'
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
