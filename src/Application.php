<?php

namespace Reliv\Deploy;

use \Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends ConsoleApplication
{
    protected $input;
    protected $output;

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = $this->getInput();
        }

        if (null === $output) {
            $output = $this->getOutput();
        }

        parent::run($input, $output);
    }

    public function getOutput()
    {
        $this->checkInputOutput();
        return $this->output;
    }

    public function getInput()
    {
        $this->checkInputOutput();
        return $this->input;
    }

    protected function checkInputOutput()
    {
        $configure = false;

        if (!$this->output) {
            $this->output = new ConsoleOutput();
            $configure = true;
        }

        if (!$this->input) {
            $configure = true;
            $this->input = new ArgvInput();
        }

        if ($configure) {
            $this->configureIO($this->input, $this->output);
        }
    }

}