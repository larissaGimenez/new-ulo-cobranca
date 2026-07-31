<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-base-content leading-tight">
            {{ __('Dev Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 overflow-hidden shadow-xl sm:rounded-lg p-6 border border-base-200">
                <!-- Navigation Tabs -->
                @include('partials.dev-settings-tabs')

                <h3 class="text-lg font-bold mb-1 text-base-content">Acompanhamento de Infraestrutura</h3>
                <p class="text-xs text-base-content/60 mb-6">Monitore e gerencie a saúde das conexões de WebSockets, das filas do Laravel e da atividade do Cron Scheduler.</p>

                <!-- Cards de Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Reverb WebSocket Status Card -->
                    <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs opacity-60 uppercase font-semibold">WebSockets Reverb</span>
                                <h4 class="font-bold text-lg text-base-content mt-1">Servidor Reverb</h4>
                            </div>
                            <span class="relative flex h-3 w-3">
                                @if($reverbOnline)
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                                @else
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-error"></span>
                                @endif
                            </span>
                        </div>
                        <div class="divider my-2"></div>
                        <div class="text-xs space-y-1 mb-4">
                            <div class="flex justify-between">
                                <span class="opacity-70">Status:</span>
                                <span class="font-bold {{ $reverbOnline ? 'text-success' : 'text-error' }}">
                                    {{ $reverbOnline ? 'Online (Ativo)' : 'Offline (Inativo)' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Porta Interna:</span>
                                <span class="font-mono">8080</span>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-auto">
                            @if(!$reverbOnline)
                                <button onclick="controlReverb('start')" class="btn btn-success btn-sm text-white flex-1">
                                    Iniciar
                                </button>
                            @else
                                <button onclick="controlReverb('stop')" class="btn btn-error btn-sm text-white flex-1">
                                    Parar
                                </button>
                            @endif
                            <button onclick="window.location.reload()" class="btn btn-ghost btn-sm" title="Recarregar">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Queue workers (Fila) Status Card -->
                    <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs opacity-60 uppercase font-semibold">Laravel Queue</span>
                                <h4 class="font-bold text-lg text-base-content mt-1">Fila (Queue Workers)</h4>
                            </div>
                            <span class="relative flex h-3 w-3">
                                @if($queueOnline)
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                                @else
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-error"></span>
                                @endif
                            </span>
                        </div>
                        <div class="divider my-2"></div>
                        <div class="text-xs space-y-1 mt-1">
                            <div class="flex justify-between">
                                <span class="opacity-70">Status:</span>
                                <span class="font-bold {{ $queueOnline ? 'text-success' : 'text-error' }}">
                                    {{ $queueOnline ? 'Ativo (Escutando)' : 'Inativo (Parado)' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Driver Connection:</span>
                                <span class="font-mono">{{ env('QUEUE_CONNECTION', 'redis') }}</span>
                            </div>
                        </div>
                        <div class="alert alert-info text-[10px] p-2 mt-4 leading-normal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Monitorado via Supervisor (Linux) / Simulado local em desenvolvimento.</span>
                        </div>
                    </div>

                    <!-- Scheduler Status Card -->
                    <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs opacity-60 uppercase font-semibold">Linux Cron Job</span>
                                <h4 class="font-bold text-lg text-base-content mt-1">Scheduler (Agendador)</h4>
                            </div>
                            <span class="relative flex h-3 w-3">
                                @if($scheduler['online'])
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                                @else
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-error"></span>
                                @endif
                            </span>
                        </div>
                        <div class="divider my-2"></div>
                        <div class="text-xs space-y-1 mt-1">
                            <div class="flex justify-between">
                                <span class="opacity-70">Status:</span>
                                <span class="font-bold {{ $scheduler['online'] ? 'text-success' : 'text-error' }}">
                                    {{ $scheduler['online'] ? 'Ativo (Cron Ok)' : 'Inativo (Aviso)' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Última Execução:</span>
                                <span class="font-semibold text-base-content/80">{{ $scheduler['last_run'] }}</span>
                            </div>
                        </div>
                        <div class="alert alert-info text-[10px] p-2 mt-4 leading-normal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>O agendador precisa que a linha cron <code>* * * * * php artisan schedule:run</code> esteja ativa na VPS.</span>
                        </div>
                    </div>
                </div>

                <!-- Guias e Códigos de Configuração -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Supervisor Guides -->
                    <div class="space-y-6">
                        <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                            <h3 class="font-bold text-base mb-3">Configuração do Supervisor (Reverb & Fila)</h3>
                            <p class="text-xs text-base-content/75 mb-3">
                                Crie as configurações dos daemons no Supervisor para que os serviços não caiam em produção.
                            </p>
                            
                            <span class="text-xs font-semibold block mb-1">1. Reverb Config (<code>/etc/supervisor/conf.d/reverb.conf</code>)</span>
                            <pre class="bg-black/90 text-success p-3 rounded-lg text-[10px] overflow-x-auto select-all mb-4">
[program:reverb]
process_name=%(program_name)s
command=php /var/www/new-ulo-cobranca/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/new-ulo-cobranca/storage/logs/reverb_supervisor.log
stopwaitsecs=3600</pre>

                            <span class="text-xs font-semibold block mb-1">2. Queue Workers Config (<code>/etc/supervisor/conf.d/queue-worker.conf</code>)</span>
                            <pre class="bg-black/90 text-success p-3 rounded-lg text-[10px] overflow-x-auto select-all mb-3">
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/new-ulo-cobranca/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/new-ulo-cobranca/storage/logs/worker_supervisor.log
stopwaitsecs=3600</pre>
                        </div>
                    </div>

                    <!-- Nginx & Visudo Guides -->
                    <div class="space-y-6">
                        <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                            <h3 class="font-bold text-base mb-3">Proxy Reverso Nginx (WebSockets)</h3>
                            <p class="text-xs text-base-content/75 mb-3">
                                Adicione este bloco dentro do arquivo do site no Nginx para suportar conexões seguras WSS (Mixed Content prevention):
                            </p>
                            <pre class="bg-black/90 text-info p-3 rounded-lg text-[10px] overflow-x-auto select-all mb-4">
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}</pre>

                            <h3 class="font-bold text-base mb-1">Sudoers Seguros (Visudo)</h3>
                            <p class="text-xs text-base-content/75 mb-3">
                                Permita que o PHP acione o Supervisor de forma restrita rodando <code>sudo visudo</code> e adicionando:
                            </p>
                            <pre class="bg-black/90 text-warning p-3 rounded-lg text-[10px] overflow-x-auto select-all">
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl status reverb, /usr/bin/supervisorctl start reverb, /usr/bin/supervisorctl stop reverb</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Ações -->
    <script>
        function controlReverb(action) {
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Processando...';

            fetch(`/dev-settings/reverb/${action}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    alert('Erro ao controlar o servidor Reverb.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                alert('Erro de conexão com o servidor.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }
    </script>
</x-app-layout>
