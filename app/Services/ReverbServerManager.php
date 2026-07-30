<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class ReverbServerManager
{
    /**
     * Verifica se o Reverb está rodando
     */
    public function isOnline(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $connection = @fsockopen('127.0.0.1', 8080);
            if (is_resource($connection)) {
                fclose($connection);
                return true;
            }
            return false;
        }

        // Produção (Linux via Supervisor)
        $process = Process::run('sudo supervisorctl status reverb');
        return str_contains($process->output(), 'RUNNING');
    }

    /**
     * Inicia o servidor Reverb
     */
    public function start(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B php artisan reverb:start", "r"));
            return;
        }

        Process::run('sudo supervisorctl start reverb');
    }

    /**
     * Para o servidor Reverb
     */
    public function stop(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // No Windows Dev local
            exec("taskkill /IM php.exe /F");
            return;
        }

        Process::run('sudo supervisorctl stop reverb');
    }
}
