<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class LintCommand extends Command
{
    public const ERR_YAML = 1;
    public const ERR_PHP = 2;
    public const ERR_JSON = 4;
    public const ERR_CSS = 8;

    protected function configure()
    {
        $this->setName('rexlint')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The directory', '.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        // the "+" on "find ... -exec" makes the find command fail, when the -exec'ed command fails.
        $dir = $input->getArgument('dir');

        $processes = [];
        $processes[] = [
            self::ERR_YAML,
            'YAML checks',
            $this->asyncProc(['find', $dir, '-type', 'f', '-name', '*.yml', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/yaml-lint', '{}', '+']),
        ];
        $processes[] = [
            self::ERR_PHP,
            'PHP checks',
            $this->asyncProc(['vendor/bin/parallel-lint', '--exclude', 'vendor', $dir]),
        ];
        $processes[] = [
            self::ERR_JSON,
            'JSON checks',
            $this->asyncProc(['find', $dir, '-type', 'f', '-name', '*.json', '!', '-path', '*/vendor/*', '-exec', 'vendor/bin/jsonlint', '{}', '+']),
        ];

        $this->syncProc(['npm', 'install', 'csslint']);

        // we only want to find errors, no style checks
        $csRules = 'order-alphabetical,important,ids,font-sizes,floats';
        $processes[] = [
            self::ERR_CSS,
            'CSS checks',
            $this->asyncProc(['find', $dir, '-name', '*.css', '!', '-path', '*/vendor/*', '-exec', 'node_modules/.bin/csslint', '--ignore='.$csRules, '{}', '+']),
        ];

        $exit = 0;
        foreach ($processes as $struct) {
            [
                $exitCode,
                $label,
                $process
            ] = $struct;

            $process->wait();

            $succeed = $process->isSuccessful();
            if ($exitCode == self::ERR_PHP) {
                if (strpos($process->getOutput(), 'No file found to check') !== false) {
                    $succeed = true;
                }
            }

            if (!$succeed) {
                $style->section($label);
                echo $process->getOutput();
                echo $process->getErrorOutput();
                $style->error("$label failed\n");
                $exit = $exit | $exitCode;
            } else {
                if ($output->isVerbose()) {
                    echo $process->getOutput();
                }
                $style->success("$label successfull\n");
            }
        }

        return $exit;
    }

    private function asyncProc(array $cmd): Process
    {
        $process = new Process($cmd);
        $process->start();
        return $process;
    }

    private function syncProc(array $cmd)
    {
        $syncP = new Process($cmd);
        $syncP->mustRun();
        echo $syncP->getOutput();
    }
}
