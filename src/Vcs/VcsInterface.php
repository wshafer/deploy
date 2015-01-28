<?php

namespace Reliv\Deploy\Vcs;

use Psr\Log\LoggerInterface;
use Zend\Config\Config;

interface VcsInterface
{
    public function setConfig($config);
    public function setCurrentReleaseAppDir($dir);
    public function setNextReleaseAppDir($dir);
    public function setLogger(LoggerInterface $logger);
    public function needsUpdate();
    public function update();
}