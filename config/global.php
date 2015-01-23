<?php

return array(
    'Default' => array(
        'Logger' => array(
            'console' => array(
                'logFormat' => "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n",
                'dateFormat' => "Y-m-d H:i:s",
            )
        ),
        'Git' => array(
            'executable' => trim(`which git`),
        ),
        'Deploy' => array(
            'symlink' => 'current',
            'user' => 'www-data',
            'group' => 'www-data',
            'type' => 'git',
        )
    ),
    'Apps' => array(
        'RWriter' => array(
            'name' => 'R-Writer',
            'Repo' => array(
                'type' => 'git',
                //'origin' => 'https://wshafer:Y3st3erDay@github.com/wshafer/RelivSkeletonApplication.git',
                'origin' => '/www/apps/rwriter',
                'branch' => 'master',
            ),
            'Deploy' => array(
                'directory' => '/www/deployTest/rwiter',
            )
        )
    )
);