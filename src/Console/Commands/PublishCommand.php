<?php

namespace Jakyeru\Larascord\Console\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larascord:publish
                            {--migrations : Publish the migrations instead of the configuration file}
                            {--force : Overwrite any existing files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to publish Larascord\'s settings.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('migrations')) {
            $this->call('vendor:publish', [
                '--provider' => 'Jakyeru\Larascord\LarascordServiceProvider',
                '--tag' => 'larascord-migrations',
                '--force' => $this->option('force'),
            ]);

            $this->info('Larascord\'s migrations have been published successfully.');

            return self::SUCCESS;
        }

        if (file_exists(config_path('larascord.php')) && !$this->option('force')) {
            $this->error('The configuration file has already been published.');
            $this->error('If you want to overwrite the existing file, use the --force option.');

            return self::FAILURE;
        }

        if ($this->option('force')) {
            $this->warn('This command is running in force mode. Any existing configuration file will be overwritten.');
        }

        $this->call('vendor:publish', [
            '--provider' => 'Jakyeru\Larascord\LarascordServiceProvider',
            '--tag' => 'larascord-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('Larascord\'s settings have been published successfully.');

        return self::SUCCESS;
    }
}
