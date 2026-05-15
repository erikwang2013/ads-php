<?php

namespace Erikwang2013\WebmanScout\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Erikwang2013\WebmanScout\EngineManager;

class DeleteAllIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected static $defaultName = 'scout:delete-all-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected static $defaultDescription = 'Delete all indexes';

    /**
     * Execute the console command.
     *
     * @param  \Erikwang2013\WebmanScout\EngineManager  $manager
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output):int
    {
        $engine = app(EngineManager::class)->engine();

        $driver = config('plugin.erikwang2013.webman-scout.app.driver');

        if (! method_exists($engine, 'deleteAllIndexes')) {
            return $output->writeln('The ['.$driver.'] engine does not support deleting all indexes.');
        }

        try {
            $engine->deleteAllIndexes();

            $output->writeln('All indexes deleted successfully.');
            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln($exception->getMessage());
            return Command::FAILURE;
        }
    }
}
