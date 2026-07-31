<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;

class SystemMonitorManager
{
    /**
     * Verifica se o Reverb está rodando.
     */
    public function isReverbOnline(): bool
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
     * Verifica se o Queue Worker está rodando.
     */
    public function isQueueWorkerOnline(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // No Windows Dev local, retornamos true se houver algum processo PHP rodando queue listen.
            // Para simplificar, assumimos true em desenvolvimento local.
            return true;
        }

        // Produção (Linux via Supervisor): verifica se há algum processo contendo 'worker' ou 'queue' rodando
        $process = Process::run('sudo supervisorctl status all');
        $output = $process->output();
        
        // Verifica se há programas do supervisor com status RUNNING que correspondem à fila
        return (str_contains($output, 'worker') || str_contains($output, 'queue')) && str_contains($output, 'RUNNING');
    }

    /**
     * Retorna o status de execução do Laravel Scheduler (Cron).
     */
    public function getSchedulerStatus(): array
    {
        $lastRun = Cache::get('last_scheduler_run');
        
        if (!$lastRun) {
            return [
                'online' => false,
                'last_run' => 'Nunca executou',
            ];
        }

        $lastRunTime = \Carbon\Carbon::parse($lastRun);
        $diffInMinutes = $lastRunTime->diffInMinutes(now());

        return [
            'online' => $diffInMinutes <= 2,
            'last_run' => $lastRunTime->format('d/m/Y H:i:s') . " ({$diffInMinutes} minutos atrás)",
        ];
    }

    /**
     * Inicia o servidor Reverb.
     */
    public function startReverb(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B php artisan reverb:start", "r"));
            return;
        }

        Process::run('sudo supervisorctl start reverb');
    }

    /**
     * Para o servidor Reverb.
     */
    public function stopReverb(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /IM php.exe /F");
            return;
        }

        Process::run('sudo supervisorctl stop reverb');
    }
}
