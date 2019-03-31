<?php


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class LintCommand extends Command {
    protected function configure()
    {
        $this->setName('lint')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $process = new Process(['find', '.', '-name', '*.yml', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/yaml-lint', '{}', ';']);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();
    }
}
