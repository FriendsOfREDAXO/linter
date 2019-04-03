<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class LintCommand extends Command
{
    protected function configure()
    {
        $this->setName('rexlint')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The directory', '.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('dir');

        $processes = [];
        //$processes[] = $this->asyncProc(['find', $dir, '-name', '*.yml', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/yaml-lint', '{}', ';']);
        //$processes[] = $this->asyncProc(['vendor/bin/parallel-lint', '--exclude',  'vendor', $dir]);
        //$processes[] = $this->asyncProc(['find', $dir, '-name', '*.json', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/jsonlint', '{}', ';']);

        $syncP = new Process(['npm', 'install', 'csslint']);
        $syncP->run();
        if (!$syncP->isSuccessful()) {
            throw new ProcessFailedException($syncP);
        }
        echo $syncP->getOutput();

        $syncP = new Process(['find', $dir, '-name', '*.css']);
        $syncP->run();
        if (!$syncP->isSuccessful()) {
            throw new ProcessFailedException($syncP);
        }
        echo $syncP->getOutput();

        $processes[] = $this->asyncProc(['find', $dir, '-name', '*.css', '!', '-path', '*/vendor/*', '-exec', 'node_modules/.bin/csslint', '--ignore', 'order-alphabetical,important,ids,font-sizes,floats', '{}', ';']);
        
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
