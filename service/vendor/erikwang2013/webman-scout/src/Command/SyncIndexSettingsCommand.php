<?php

namespace Erikwang2013\WebmanScout\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Erikwang2013\WebmanScout\Contracts\UpdatesIndexSettings;
use Erikwang2013\WebmanScout\EngineManager;

class SyncIndexSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected static $defaultName = 'scout:sync-index-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected static $defaultDescription = 'Sync your configured index settings with your search engine (Meilisearch)';

    protected function configure()
    {
        $this->addOption('driver', '--driver', InputOption::VALUE_REQUIRED, 'The number of records to import at a time (Defaults to configuration value: `scout.chunk.searchable`');
    }

    /**
     * Execute the console command.
     *
     * @param  \Erikwang2013\WebmanScout\EngineManager  $manager
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver =  $input->getOption('driver') ?: config('plugin.erikwang2013.webman-scout.app.driver');

        $engine = app(EngineManager::class)->engine($driver);

        if (! $engine instanceof UpdatesIndexSettings) {
            $output->writeln('The "' . $driver . '" engine does not support updating index settings.');
            return Command::FAILURE;
        }

        try {
            $indexes = (array) config('plugin.erikwang2013.webman-scout.app.' . $driver . '.index-settings', []);

            if (count($indexes)) {
                foreach ($indexes as $name => $settings) {
                    if (! is_array($settings)) {
                        $name = $settings;

                        $settings = [];
                    }

                    if (class_exists($name)) {
                        $model = new $name;
                    }

                    if (
                        isset($model) &&
                        config('plugin.erikwang2013.webman-scout.app.soft_delete', false) &&
                        in_array(SoftDeletes::class, class_uses_recursive($model))
                    ) {
                        $settings = $engine->configureSoftDeleteFilter($settings);
                    }

                    $engine->updateIndexSettings($indexName = $this->indexName($name), $settings);

                    $output->writeln('Settings for the [' . $indexName . '] index synced successfully.');
                }
            } else {
                $output->writeln('No index settings found for the "' . $driver . '" engine.');
            }
            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln($exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get the fully-qualified index name for the given index.
     *
     * @param  string  $name
     * @return string
     */
    protected function indexName($name)
    {
        if (class_exists($name)) {
            return (new $name)->indexableAs();
        }

        $prefix = config('plugin.erikwang2013.webman-scout.app.prefix');

        return ! Str::startsWith($name, $prefix) ? $prefix . $name : $name;
    }
}
