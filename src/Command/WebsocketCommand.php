<?php

namespace App\Command;

use App\Service\InternalWebSocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WebsocketCommand extends Command
{
    protected static $defaultName = 'app:websocket';
    protected static $defaultDescription = 'Starting websockets ws for internal';
    private $internalWebSocketServer;

    public function __construct(InternalWebSocketServer $internal)
    {
        parent::__construct();
        $this->internalWebSocketServer = $internal;
    }



    protected function configure(): void
    {
        $this
            ->addOption('ws', null, InputOption::VALUE_NONE, 'Start Internal websocket', 'ws')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('ws')) {
            $this->internalWebSocketServer->startServer();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
