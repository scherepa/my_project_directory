<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TailLogCommand extends Command
{
    protected static $defaultName = 'log:tail';

    protected function configure(): void
    {
        $this
            ->setDescription('Tail a log file by channel on windows,  laravel like')
            ->addArgument('channel', InputArgument::OPTIONAL, 'Log channel (default, access_log, websocket)', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = (new \DateTime())->format('Y-m-d');
        $channel = $input->getArgument('channel');
        switch ($channel) {
            case 'access_log':
                $logFile = "var/log/access-$today.log";
                break;
            case 'websocket':
                $logFile = "var/log/websocket-$today.log";
                break;
            default:
                $logFile = 'var/log/dev.log';
        }

        if (!file_exists($logFile)) {
            $output->writeln("<error>Log file not found: $logFile</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Tailing: $logFile</info> Press CTRL+C to stop.");

        $fp = fopen($logFile, 'r');
        fseek($fp, 0, SEEK_END);

        while (true) {
            $line = fgets($fp);
            if ($line) {
                $output->writeln(rtrim($line));
            } else {
                usleep(200000); // 0.2 seconds
            }
        }

        return Command::SUCCESS;
    }
}

