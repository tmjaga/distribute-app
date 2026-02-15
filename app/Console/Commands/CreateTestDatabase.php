<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use PDO;

class CreateTestDatabase extends Command
{
    protected $signature = 'db:create-test';

    protected $description = 'Create Test DB from .env.testing';

    public function handle()
    {
        $lines = file(base_path('.env.testing'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            $envData[$key] = $value;
        }

        try {
            $pdo = new PDO("mysql:host={$envData['DB_HOST']};port={$envData['DB_PORT']}", $envData['DB_USERNAME'], $envData['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$envData['DB_DATABASE']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $this->info("Test DB `{$envData['DB_DATABASE']}`created!");
        } catch (Exception $e) {
            $this->error('Error creating Test DB: '.$e->getMessage());
        }
    }
}
