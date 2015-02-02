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
            ),
        ),
//        'RWriter' => array(
//            'name' => 'R-Writer',
//            'repositories' => array(
//                'main' => array(
//                    'type' => 'git',
//                    'origin' => '/www/apps/rwriter',
//                    'directory' => '/',
//                ),
//
//                'rcm' => array(
//                    'type' => 'git',
//                    'origin' => '/www/apps/rwriter/vendor/reliv/Rcm',
//                    'directory' => '/vendor/reliv/Rcm',
//                ),
//
//                'rcmPlugins' => array(
//                    'type' => 'git',
//                    'origin' => '/www/apps/rwriter/vendor/reliv/Rcm',
//                    'directory' => '/vendor/reliv/RcmPlugins',
//                ),
//
//                'rcmUser' => array(
//                    'type' => 'git',
//                    'origin' => '/www/apps/rwriter/vendor/reliv/RcmUser',
//                    'directory' => '/vendor/reliv/RcmUser',
//                ),
//
//                'elFinder' => array(
//                    'type' => 'git',
//                    'origin' => '/www/apps/rwriter/vendor/reliv/ElFinder',
//                    'directory' => '/vendor/reliv/ElFinder',
//                ),
//            ),
//            'deploy' => array(
//                'location' => '/tmp/rwriter'
//            )
//        )
    )
);
