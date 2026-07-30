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
                <div class="tabs tabs-lifted mb-6">
                    <a href="{{ route('dev-settings') }}" class="tab font-semibold">Contas a Receber</a>
                    <a href="{{ route('dev-settings.clientes') }}" class="tab font-semibold">Clientes</a>
                    <a href="{{ route('dev-settings.vinculos') }}" class="tab font-semibold">Vínculos</a>
                    <a href="{{ route('dev-settings.contas') }}" class="tab font-semibold">Contas Correntes</a>
                    <a href="{{ route('dev-settings.reverb') }}" class="tab tab-active font-semibold">Laravel Reverb</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Status & Controls -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                            <h3 class="font-bold text-lg mb-4">Servidor Reverb WebSocket</h3>
                            
                            <!-- Status Indicator -->
                            <div class="flex items-center gap-3 mb-6 p-4 bg-base-100 rounded-lg border border-base-300">
                                <span class="relative flex h-4 w-4">
                                    @if($online)
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-success"></span>
                                    @else
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-error"></span>
                                    @endif
                                </span>
                                <div class="text-sm">
                                    <span class="block font-bold">
                                        {{ $online ? '● ATIVO (Online)' : '● INATIVO (Offline)' }}
                                    </span>
                                    <span class="text-xs opacity-60">Porta local: 8080</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col gap-2">
                                @if(!$online)
                                    <button onclick="controlReverb('start')" class="btn btn-success text-white w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                        </svg>
                                        Iniciar Servidor Reverb
                                    </button>
                                @else
                                    <button onclick="controlReverb('stop')" class="btn btn-error text-white w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" />
                                        </svg>
                                        Parar Servidor Reverb
                                    </button>
                                @endif
                                <button onclick="checkStatus()" class="btn btn-ghost btn-sm mt-2">Atualizar Status</button>
                            </div>
                        </div>

                        <div class="alert alert-warning text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <div>
                                <span class="font-semibold block">Nota de Produção:</span>
                                Em VPS Linux, o controle de processos interage diretamente com o <strong>Supervisor</strong>. Certifique-se de configurar as permissões corretas no <code>/etc/sudoers</code>.
                            </div>
                        </div>
                    </div>

                    <!-- Configuration Instructions -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                            <h3 class="font-bold text-lg mb-4">Guia de Implantação na VPS (Supervisor)</h3>
                            <p class="text-sm text-base-content/85 mb-4">
                                Para manter o Reverb ativo em produção na VPS, crie o arquivo de configuração <code>/etc/supervisor/conf.d/reverb.conf</code>:
                            </p>
                            <pre class="bg-black/90 text-success p-4 rounded-lg text-xs overflow-x-auto select-all mb-4">
[program:reverb]
process_name=%(program_name)s
command=php /var/www/new-ulo-cobranca/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/new-ulo-cobranca/storage/logs/reverb_supervisor.log
stopwaitsecs=3600</pre>
                            
                            <p class="text-sm text-base-content/85 mb-4">
                                Permita que o usuário <code>www-data</code> execute o controle do supervisor sem senha adicionando a linha abaixo via <code>sudo visudo</code>:
                            </p>
                            <pre class="bg-black/90 text-warning p-4 rounded-lg text-xs overflow-x-auto select-all">
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl status reverb, /usr/bin/supervisorctl start reverb, /usr/bin/supervisorctl stop reverb</pre>
                        </div>

                        <div class="card bg-base-200 border border-base-300 p-6 rounded-box">
                            <h3 class="font-bold text-lg mb-4">Configuração Nginx (WSS Proxy)</h3>
                            <p class="text-sm text-base-content/85 mb-4">
                                Adicione este bloco dentro do arquivo de configuração do seu site no Nginx para suportar conexões WebSocket seguras (WSS):
                            </p>
                            <pre class="bg-black/90 text-info p-4 rounded-lg text-xs overflow-x-auto select-all">
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
                    }, 1500);
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

        function checkStatus() {
            window.location.reload();
        }
    </script>
</x-app-layout>
