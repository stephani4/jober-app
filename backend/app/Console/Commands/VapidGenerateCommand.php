<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Пишет VAPID-ключи в .env, если их ещё нет.
 */
class VapidGenerateCommand extends Command
{
    protected $signature = 'vapid:generate {--force : Перезаписать существующие ключи}';

    protected $description = 'Generate VAPID keys for Web Push and write them to .env';

    public function handle(): int
    {
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('.env не найден.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($envPath);
        $hasKeys = str_contains($contents, 'VAPID_PUBLIC_KEY=')
            && ! str_contains($contents, "VAPID_PUBLIC_KEY=\n")
            && ! str_contains($contents, "VAPID_PUBLIC_KEY=\r\n")
            && preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $contents) === 1;

        if ($hasKeys && ! $this->option('force')) {
            $this->info('VAPID-ключи уже заданы. Используйте --force, чтобы сгенерировать заново.');

            return self::SUCCESS;
        }

        $keys = VAPID::createVapidKeys();
        $this->upsertEnv($envPath, $contents, [
            'VAPID_SUBJECT' => '${APP_URL}',
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ]);

        $this->info('VAPID-ключи записаны в .env');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function upsertEnv(string $path, string $contents, array $values): void
    {
        foreach ($values as $key => $value) {
            $line = $key.'='.$value;
            if (preg_match('/^'.$key.'=.*$/m', $contents) === 1) {
                $contents = preg_replace('/^'.$key.'=.*$/m', $line, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }

        file_put_contents($path, $contents);
    }
}
