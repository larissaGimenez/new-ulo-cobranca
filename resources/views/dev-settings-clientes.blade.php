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
                    <a href="{{ route('dev-settings') }}" class="tab font-semibold">Contas a Pagar</a>
                    <a href="{{ route('dev-settings.clientes') }}" class="tab tab-active font-semibold">Clientes</a>
                    <a href="{{ route('dev-settings.vinculos') }}" class="tab font-semibold">Vínculos</a>
                </div>

                <h3 class="text-lg font-bold mb-4 text-base-content">Integração Omie - Clientes</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Use esta ferramenta para realizar a sincronização inicial de todos os clientes registrados nas 5 ULOs configuradas no arquivo de ambiente (.env) de forma fracionada (via AJAX) para evitar timeouts.
                </p>

                <!-- Dynamic Progress Section (Hidden by default) -->
                <div id="progress-container" class="hidden card bg-base-200 shadow-md p-6 mb-6">
                    <h4 class="font-bold text-base-content mb-2" id="progress-title">Preparando sincronização...</h4>
                    
                    <div class="flex flex-col gap-2">
                        <progress id="sync-progress" class="progress progress-primary w-full" value="0" max="100"></progress>
                        <div class="flex justify-between text-xs text-base-content/70">
                            <span id="progress-text-left">Aguardando...</span>
                            <span id="progress-text-right">0%</span>
                        </div>
                    </div>

                    <!-- Logs Console -->
                    <div class="mt-4">
                        <span class="text-xs font-bold text-base-content/70">Logs em Tempo Real:</span>
                        <div id="logs-console" class="bg-neutral text-neutral-content p-4 rounded-lg font-mono text-xs h-48 overflow-y-auto mt-1 flex flex-col gap-1">
                            <div>[Sistema] Pronto para iniciar a sincronização.</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Configured ULOs Card -->
                    <div class="card bg-base-200 shadow-md">
                        <div class="card-body">
                            <h4 class="card-title text-base font-bold text-base-content">ULOs Configuradas (.env)</h4>
                            <div class="overflow-x-auto mt-2">
                                <table class="table w-full">
                                    <thead>
                                        <tr>
                                            <th>Nome da ULO</th>
                                            <th>App Key</th>
                                            <th class="text-right">Clientes Importados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($ulos as $ulo)
                                            <tr>
                                                <td class="font-medium text-base-content">{{ $ulo['name'] }}</td>
                                                <td><code class="text-xs font-mono opacity-80">{{ substr($ulo['key'], 0, 4) }}...</code></td>
                                                <td class="text-right">
                                                    <span class="badge badge-primary badge-md font-bold" id="total-badge-{{ $ulo['id'] }}">
                                                        {{ number_format($ulo['total_records'], 0, ',', '.') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-base-content/50 py-4">Nenhuma ULO configurada ou encontrada no .env</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Actions Card -->
                    <div class="card bg-base-200 shadow-md flex justify-between">
                        <div class="card-body flex flex-col justify-between">
                            <div>
                                <h4 class="card-title text-base font-bold text-base-content">Sincronização Fracionada</h4>
                                <p class="text-xs text-base-content/60 mt-2">
                                    Esta ferramenta divide a carga solicitando uma página da API do Omie por vez. Isso evita o limite de tempo de resposta (Timeout) do servidor web e permite visualizar o progresso em tempo real.
                                </p>
                                <div class="alert alert-info mt-4 p-3 text-xs flex gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Os dados já existentes serão atualizados automaticamente sem duplicados no banco.</span>
                                </div>
                            </div>
                            <div class="card-actions justify-end mt-6">
                                <button type="button" id="btn-start-sync" class="btn btn-primary gap-2" onclick="startSync()">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    Iniciar Sincronização
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Sincronização AJAX -->
    <script>
        const activeUlos = @json($ulos);
        let currentUloIndex = 0;
        let currentPage = 1;
        let totalImported = 0;
        let isSyncing = false;

        function appendLog(message, isError = false) {
            const logsConsole = document.getElementById('logs-console');
            const logEntry = document.createElement('div');
            
            const now = new Date().toLocaleTimeString();
            logEntry.textContent = `[${now}] ${message}`;
            
            if (isError) {
                logEntry.classList.add('text-error');
            } else if (message.includes('[Concluído]')) {
                logEntry.classList.add('text-success');
            }
            
            logsConsole.appendChild(logEntry);
            logsConsole.scrollTop = logsConsole.scrollHeight;
        }

        function startSync() {
            if (isSyncing) return;
            if (activeUlos.length === 0) {
                alert('Nenhuma ULO configurada para sincronizar.');
                return;
            }

            isSyncing = true;
            currentUloIndex = 0;
            currentPage = 1;
            totalImported = 0;

            document.getElementById('btn-start-sync').disabled = true;
            document.getElementById('btn-start-sync').classList.add('loading');
            document.getElementById('progress-container').classList.remove('hidden');
            
            appendLog('Iniciando sincronização dos clientes das ULOs configuradas...');
            syncNext();
        }

        async function syncNext() {
            if (currentUloIndex >= activeUlos.length) {
                // Sincronização completa de todas as ULOs!
                isSyncing = false;
                document.getElementById('btn-start-sync').disabled = false;
                document.getElementById('btn-start-sync').classList.remove('loading');
                
                document.getElementById('progress-title').textContent = 'Sincronização Concluída!';
                document.getElementById('sync-progress').value = 100;
                document.getElementById('progress-text-left').textContent = 'Pronto';
                document.getElementById('progress-text-right').textContent = '100%';
                
                appendLog(`[Concluído] Sincronização dos clientes de todas as ULOs finalizada com sucesso! Total de ${totalImported} registros importados/atualizados.`);
                
                // Recarrega estatísticas em 3 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
                return;
            }

            const currentUlo = activeUlos[currentUloIndex];
            
            document.getElementById('progress-title').textContent = `Sincronizando clientes de ${currentUlo.name}...`;
            document.getElementById('progress-text-left').textContent = `Processando página ${currentPage}...`;

            try {
                const response = await fetch("{{ route('dev-settings.clientes.sync-page') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ulo_id: parseInt(currentUlo.id),
                        page: currentPage
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Erro de comunicação com o servidor.');
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erro desconhecido retornado pelo servidor.');
                }

                // Sucesso na página
                totalImported += result.imported_count;
                
                // Calcula percentual para a ULO atual
                const totalPages = result.total_pages || 1;
                const percent = Math.round((currentPage / totalPages) * 100);
                
                document.getElementById('sync-progress').value = percent;
                document.getElementById('progress-text-right').textContent = `${percent}%`;

                appendLog(`[OK] ${currentUlo.name} | Página ${currentPage}/${totalPages} | +${result.imported_count} clientes importados.`);

                if (result.finished) {
                    appendLog(`[Concluído] Clientes de ${currentUlo.name} finalizados com sucesso.`);
                    
                    // Incrementa ULO index, reseta página
                    currentUloIndex++;
                    currentPage = 1;
                } else {
                    // Próxima página da ULO atual
                    currentPage++;
                }

                // Chama recursivamente a próxima etapa
                syncNext();

            } catch (error) {
                appendLog(`[Erro] ${currentUlo.name} (Página ${currentPage}): ${error.message}`, true);
                
                // Para a sincronização para permitir intervenção
                isSyncing = false;
                document.getElementById('btn-start-sync').disabled = false;
                document.getElementById('btn-start-sync').classList.remove('loading');
                
                document.getElementById('progress-title').textContent = 'Erro na sincronização';
                document.getElementById('progress-text-left').textContent = 'Operação interrompida por erro.';
            }
        }
    </script>
</x-app-layout>
