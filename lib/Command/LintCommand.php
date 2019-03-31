<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class LintCommand extends Command
{
    protected function configure()
    {
        $this->setName('lint')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The directory', '.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('dir');

        $processes = [];
        $processes[] = $this->asyncProc(['find', $dir, '-name', '*.yml', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/yaml-lint', '{}', ';']);
        $processes[] = $this->asyncProc(['find', $dir, '-name', '*.php', '!', '-path', '*/vendor/*', '-exec', 'php', '-l', '{}', '2>&1', ';']);

        foreach ($processes as $process) {
            $process->wait();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            echo $process->getOutput();
        }
    }

    private function asyncProc(array $cmd)
    {
        $process = new Process($cmd);
        $process->start();
        return $process;
    }
}
