<?php

return array(
    'default' => array(
        'logger' => array(
            'console' => array(
                'logFormat' => "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n",
                'dateFormat' => "Y-m-d H:i:s",
            )
        ),
        'git' => array(
            'executable' => trim(`which git`),
            'commitFile' => '.current_release',
            'branch' => 'master',
//            'tags' => '/\d{4}\.\d{2}.*/'
        ),
        'deploy' => array(
            'user' => 'www-data',
            'group' => 'www-data',
            'symlink' => 'current',
        )
    ),
    'apps' => array(
        'RWriter' => array(
            'name' => 'R-Writer',
            'repositories' => array(
                'main' => array(
                    'type' => 'git',
                    'origin' => '/www/apps/rwriter',
                    'directory' => '/',
                ),

                'rcm' => array(
                    'type' => 'git',
                    'origin' => '/www/apps/rwriter/vendor/reliv/Rcm',
                    'directory' => '/vendor/reliv/Rcm',
                ),

                'rcmPlugins' => array(
                    'type' => 'git',
                    'origin' => '/www/apps/rwriter/vendor/reliv/Rcm',
                    'directory' => '/vendor/reliv/RcmPlugins',
                ),

                'rcmUser' => array(
                    'type' => 'git',
                    'origin' => '/www/apps/rwriter/vendor/reliv/RcmUser',
                    'directory' => '/vendor/reliv/RcmUser',
                ),

                'elFinder' => array(
                    'type' => 'git',
                    'origin' => '/www/apps/rwriter/vendor/reliv/ElFinder',
                    'directory' => '/vendor/reliv/ElFinder',
                ),
            ),
            'deploy' => array(
                'location' => '/tmp/rwriter'
            )
        )
    )
);