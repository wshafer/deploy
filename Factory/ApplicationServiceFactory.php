<?php

namespace Reliv\Deploy\Factory;

use Reliv\Deploy\Helper\ApplicationServiceConfigHelper;
use Reliv\Deploy\Helper\ApplicationServiceVcsHelper;
use Reliv\Deploy\Helper\FileHelper;
use Reliv\Deploy\Service\Application;
use Reliv\Deploy\Service\LoggerService;
use Zend\Config\Config;

class ApplicationServiceFactory
{
    static public function getApplicationService($name, Config $config, LoggerService $loggerService)
    {
        $appConfigHelper = new ApplicationServiceConfigHelper($config);
        $fileHelper = new FileHelper();
        $vcsHelper = new ApplicationServiceVcsHelper($appConfigHelper, $loggerService);

        $application = new Application(
            $name,
            $appConfigHelper,
            $loggerService,
            $fileHelper,
            $vcsHelper
        );

        return $application;
    }
}
