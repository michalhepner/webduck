<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuditListenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('audit:listen');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $_SERVER['argv'][] = '--low-level';

        $options = [];
        foreach ($input->getOptions() as $key => $value) {
            $options['--'.$key] = $value;
        }

        $command = $this->getApplication()->get('enqueue:consume');
        $command->run(new ArrayInput(array_merge(['command' => 'enqueue:consume'], $options)), $output);

        return 0;
    }
}
