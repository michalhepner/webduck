<?php

declare(strict_types = 1);

namespace Webduck\Console;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends SymfonyApplication
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->getDefinition()->addOption(new InputOption('low-level', null, InputOption::VALUE_NONE, 'Show all available low level commands.'));
    }

    public function all($namespace = null)
    {
        $commands = array_filter(parent::all($namespace), function (Command $command) {
            if (in_array('--low-level', $_SERVER['argv'], true)) {
                return true;
            }

            return $this->isCommandAllowed($command);
        });

        return $commands;
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if (!in_array('--low-level', $_SERVER['argv'], true) && !$this->isCommandAllowed($command)) {
            throw new RuntimeException(sprintf(
                'Command %s is supported only when option --low-level is used.',
                $command->getName()
            ));
        }

        return parent::doRunCommand($command, $input, $output);
    }

    private function isCommandAllowed(Command $command)
    {
        return in_array($command->getName(), ['help', 'list'], true) || preg_match('/^audit\:/', $command->getName());
    }
}
