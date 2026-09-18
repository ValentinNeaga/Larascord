<?php

namespace Jakyeru\Larascord\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Validator;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larascord:install
                            {--client-id= : The Discord application\'s client id}
                            {--client-secret= : The Discord application\'s client secret}
                            {--prefix= : The route prefix Larascord should use}
                            {--force : Overwrite the existing configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to install Larascord.';

    /*
     * The Discord application's client id.
     *
     * @var string|null
     */
    private ?string $clientId;

    /*
     * The Discord application's client secret.
     *
     * @var string|null
     */
    private ?string $clientSecret;

    /*
     * The route prefix.
     *
     * @var string|null
     */
    private ?string $prefix;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->clientId = $this->option('client-id') ?: $this->ask('What is your Discord application\'s client id?');
        $this->clientSecret = $this->option('client-secret') ?: $this->ask('What is your Discord application\'s client secret?');
        $this->prefix = $this->option('prefix') ?: $this->ask('What route prefix should Larascord use?', 'larascord');

        try {
            $this->validateInput();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Writing the credentials to the .env file...');
        $this->writeToEnvFile();

        $this->info('Publishing the configuration file...');
        $this->call('larascord:publish', array_filter([
            '--force' => $this->option('force'),
        ]));

        $this->info('Publishing the migrations...');
        $this->call('vendor:publish', [
            '--provider' => 'Jakyeru\Larascord\LarascordServiceProvider',
            '--tag' => 'larascord-migrations',
        ]);

        if ($this->confirm('Do you want to run the migrations?', true)) {
            $this->call('migrate');
        } else {
            $this->comment('You can run the migrations later by running the command:');
            $this->comment('php artisan migrate');
        }

        $this->newLine();
        $this->alert('Please make sure you add "' . $this->redirectUri() . '" to your Discord application\'s redirect urls in the OAuth2 tab.');
        $this->warn('If the domain doesn\'t match your current environment\'s domain you need to set it manually in the .env file. (APP_URL)');
        $this->newLine();
        $this->comment('Point your users to the "larascord.redirect" route to start the login flow:');
        $this->comment('<a href="{{ route(\'larascord.redirect\') }}">Log in with Discord</a>');
        $this->newLine();
        $this->info('Larascord has been successfully installed!');

        return self::SUCCESS;
    }

    /**
     * Validate the user's input.
     *
     * @throws \Exception
     */
    protected function validateInput(): void
    {
        $rules = [
            'clientId' => ['required', 'numeric'],
            'clientSecret' => ['required', 'string'],
            'prefix' => ['required', 'string'],
        ];

        $validator = Validator::make([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'prefix' => $this->prefix,
        ], $rules);

        $validator->validate();
    }

    /**
     * Write the credentials to the .env file without touching the existing values.
     */
    protected function writeToEnvFile(): void
    {
        $values = [
            'LARASCORD_CLIENT_ID' => $this->clientId,
            'LARASCORD_CLIENT_SECRET' => $this->clientSecret,
            'LARASCORD_GRANT_TYPE' => 'authorization_code',
            'LARASCORD_PREFIX' => $this->prefix,
            'LARASCORD_SCOPES' => 'identify,email',
        ];

        $filesystem = new Filesystem();
        $path = base_path('.env');

        if (!$filesystem->exists($path)) {
            $this->warn('No .env file was found. Please add the following values yourself:');

            foreach ($values as $key => $value) {
                $this->line($key . '=' . $value);
            }

            return;
        }

        $contents = $filesystem->get($path);

        foreach ($values as $key => $value) {
            if (preg_match('/^' . $key . '=.*$/m', $contents)) {
                $contents = preg_replace('/^' . $key . '=.*$/m', $key . '=' . $value, $contents);

                continue;
            }

            $contents = rtrim($contents, PHP_EOL) . PHP_EOL . $key . '=' . $value;
        }

        $filesystem->put($path, rtrim($contents, PHP_EOL) . PHP_EOL);
    }

    /**
     * Get the redirect uri the user has to register with Discord.
     */
    protected function redirectUri(): string
    {
        return rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/' . $this->prefix . '/callback';
    }
}
