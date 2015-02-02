<?php
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
//                    'pre_deploy' => 'php preDeploy.php',
//                    'post_deploy' => 'php postDeploy.php',
//                    'pre_rollback' => 'php preRollback.php',
//                    'post_rollback' => 'php postRollback.php'
                ),
            ),
        ),
    ),
);
