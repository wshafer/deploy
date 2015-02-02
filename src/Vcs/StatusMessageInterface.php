<?php

namespace Reliv\Deploy\Vcs;

interface StatusMessageInterface
{
    public function getVcsVersion();
    public function getDeployedVersion();
    public function getDeployedDate();
    public function needsUpdate();
}