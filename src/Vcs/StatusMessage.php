<?php

namespace Reliv\Deploy\Vcs;

class StatusMessage implements StatusMessageInterface
{
    protected $vcsVersion;
    protected $deployedVersion;
    protected $deployedDate;
    protected $needsUpdate;

    public function __construct(
        $vcsVersion,
        $deployedVersion,
        $deployedDate,
        $needsUpdate
    ) {
        $this->vcsVersion = $vcsVersion;
        $this->deployedVersion = $deployedVersion;
        $this->deployedDate = $deployedDate;
        $this->needsUpdate = $needsUpdate;
    }

    public function getVcsVersion()
    {
        return $this->vcsVersion;
    }

    public function getDeployedVersion()
    {
        return $this->deployedVersion;
    }

    public function getDeployedDate()
    {
        return $this->deployedDate;
    }

    public function needsUpdate()
    {
        return $this->needsUpdate();
    }
}