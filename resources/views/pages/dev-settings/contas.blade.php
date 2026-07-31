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

                <!-- Resume Sync Banner -->
                <div id="resume-banner" class="hidden alert alert-info mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="font-bold block text-sm">Sincronização Anterior Interrompida!</span>
                        <span class="text-xs opacity-90" id="resume-banner-text">Detectamos um progresso salvo da ULO...</span>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" onclick="resumeSync()">Continuar de onde parou</button>
                        <button type="button" class="btn btn-sm btn-ghost text-xs" onclick="clearSavedProgress()">Iniciar do Zero</button>
                    </div>
                </div>

                <h3 class="text-lg font-bold mb-4 text-base-content">Integração Omie - Contas Correntes</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Use esta ferramenta para realizar a sincronização inicial de todas as contas correntes registradas nas 5 ULOs configuradas no arquivo de ambiente (.env) de forma fracionada (via AJAX) para identificar as contas Redecard.
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

                    <!-- Error Controls (Hidden by default) -->
                    <div id="error-controls" class="hidden alert alert-error mt-4 flex flex-col items-start gap-3">
                        <div class="flex gap-2 items-center font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Falha na sincronização. Ações de recuperação:</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline text-white hover:bg-white hover:text-red-700 font-bold" onclick="retryCurrentPage()">Tentar Novamente</button>
                            <button type="button" class="btn btn-sm btn-outline text-white hover:bg-white hover:text-red-700 font-bold" onclick="skipCurrentPage()">Pular Página</button>
                            <button type="button" class="btn btn-sm btn-outline text-white hover:bg-white hover:text-red-700 font-bold" onclick="skipCurrentUlo()">Pular ULO</button>
                            <button type="button" class="btn btn-sm btn-ghost text-white font-bold" onclick="cancelSync()">Cancelar Sincronização</button>
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
                                            <th class="text-right">Contas Importadas</th>
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

        let errorAttempts = 0;
        const maxAutoRetries = 3;
        const autoRetryDelayMs = 3000;

        const STORAGE_KEY = 'contas_sync_progress';

        // Check if there is saved progress on load
        window.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    const progress = JSON.parse(saved);
                    if (progress && progress.uloIndex < activeUlos.length) {
                        const uloName = activeUlos[progress.uloIndex].name;
                        document.getElementById('resume-banner-text').textContent = `Detectamos um progresso salvo das contas da ULO: ${uloName} (Página ${progress.page}, total importado: ${progress.totalImported}).`;
                        document.getElementById('resume-banner').classList.remove('hidden');
                    }
                } catch (e) {
                    localStorage.removeItem(STORAGE_KEY);
                }
            }
        });

        function resumeSync() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    const progress = JSON.parse(saved);
                    currentUloIndex = progress.uloIndex;
                    currentPage = progress.page;
                    totalImported = progress.totalImported;
                    
                    document.getElementById('resume-banner').classList.add('hidden');
                    
                    isSyncing = true;
                    errorAttempts = 0;
                    document.getElementById('btn-start-sync').disabled = true;
                    document.getElementById('btn-start-sync').classList.add('loading');
                    document.getElementById('progress-container').classList.remove('hidden');
                    document.getElementById('error-controls').classList.add('hidden');
                    
                    appendLog(`[Retomado] Retomando sincronização a partir da ULO ${activeUlos[currentUloIndex].name} - Página ${currentPage}...`);
                    syncNext();
                } catch (e) {
                    startSync();
                }
            }
        }

        function clearSavedProgress() {
            localStorage.removeItem(STORAGE_KEY);
            document.getElementById('resume-banner').classList.add('hidden');
            appendLog('[Progresso] Registro de progresso anterior limpo.');
        }

        function saveProgress() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                uloIndex: currentUloIndex,
                page: currentPage,
                totalImported: totalImported
            }));
        }

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
            errorAttempts = 0;

            localStorage.removeItem(STORAGE_KEY);
            document.getElementById('resume-banner').classList.add('hidden');
            document.getElementById('btn-start-sync').disabled = true;
            document.getElementById('btn-start-sync').classList.add('loading');
            document.getElementById('progress-container').classList.remove('hidden');
            document.getElementById('error-controls').classList.add('hidden');
            
            appendLog('Iniciando sincronização das contas correntes das ULOs configuradas...');
            syncNext();
        }

        async function syncNext() {
            if (!isSyncing) return;

            if (currentUloIndex >= activeUlos.length) {
                isSyncing = false;
                localStorage.removeItem(STORAGE_KEY);
                document.getElementById('btn-start-sync').disabled = false;
                document.getElementById('btn-start-sync').classList.remove('loading');
                
                document.getElementById('progress-title').textContent = 'Sincronização Concluída!';
                document.getElementById('sync-progress').value = 100;
                document.getElementById('progress-text-left').textContent = 'Pronto';
                document.getElementById('progress-text-right').textContent = '100%';
                document.getElementById('error-controls').classList.add('hidden');
                
                appendLog(`[Concluído] Sincronização das contas correntes de todas as ULOs finalizada com sucesso! Total de ${totalImported} registros importados/atualizados.`);
                
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
                return;
            }

            const currentUlo = activeUlos[currentUloIndex];
            
            document.getElementById('progress-title').textContent = `Sincronizando contas de ${currentUlo.name}...`;
            document.getElementById('progress-text-left').textContent = `Processando página ${currentPage}...`;
            document.getElementById('error-controls').classList.add('hidden');

            try {
                const response = await fetch("{{ route('dev-settings.contas.sync-page') }}", {
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
                    throw new Error(errorData.message || `Erro HTTP ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erro de sincronização.');
                }

                errorAttempts = 0;
                totalImported += result.imported_count;
                
                saveProgress();
                
                const totalPages = result.total_pages || 1;
                const percent = Math.round((currentPage / totalPages) * 100);
                
                document.getElementById('sync-progress').value = percent;
                document.getElementById('progress-text-right').textContent = `${percent}%`;

                appendLog(`[OK] ${currentUlo.name} | Página ${currentPage}/${totalPages} | +${result.imported_count} contas importadas.`);

                if (result.finished) {
                    appendLog(`[Concluído] Contas de ${currentUlo.name} finalizadas com sucesso.`);
                    
                    currentUloIndex++;
                    currentPage = 1;
                    saveProgress();
                } else {
                    currentPage++;
                }

                syncNext();

            } catch (error) {
                let message = error.message || '';
                let delay = autoRetryDelayMs;
                let isRateLimitOrLock = false;

                if (message.includes('Consumo redundante') || message.includes('REDUNDANT')) {
                    const match = message.match(/Aguarde\s+(\d+)\s+segundos/i);
                    const seconds = match ? parseInt(match[1], 10) : 60;
                    delay = (seconds + 3) * 1000;
                    isRateLimitOrLock = true;
                    appendLog(`[Rate Limit] Limite do Omie. Aguardando ${seconds + 3} segundos para tentar novamente de forma automática...`, true);
                } else if (message.includes('Já existe uma requisição') || message.includes('Client-8020')) {
                    delay = 15000;
                    isRateLimitOrLock = true;
                    appendLog(`[Lock] Requisição em andamento no Omie. Aguardando 15 segundos para liberação do lock...`, true);
                }

                if (!isRateLimitOrLock) {
                    errorAttempts++;
                    appendLog(`[Falha] ${currentUlo.name} (Pág. ${currentPage}) | Tentativa ${errorAttempts}/${maxAutoRetries} falhou: ${message}`, true);
                }

                if (isRateLimitOrLock || errorAttempts < maxAutoRetries) {
                    if (!isRateLimitOrLock) {
                        appendLog(`Aguardando ${delay/1000}s para tentar novamente de forma automática...`);
                    }
                    
                    document.getElementById('progress-title').textContent = 'Tentando recuperar...';
                    document.getElementById('progress-text-left').textContent = isRateLimitOrLock
                        ? `Aguardando liberação de taxa/lock (${delay/1000}s)...`
                        : `Auto-retry em andamento (${errorAttempts}/${maxAutoRetries})`;
                    
                    setTimeout(() => {
                        syncNext();
                    }, delay);
                } else {
                    isSyncing = false;
                    document.getElementById('btn-start-sync').disabled = false;
                    document.getElementById('btn-start-sync').classList.remove('loading');
                    
                    document.getElementById('progress-title').textContent = 'Erro na sincronização';
                    document.getElementById('progress-text-left').textContent = 'Aguardando ação do operador.';
                    document.getElementById('error-controls').classList.remove('hidden');
                    appendLog(`[Erro Crítico] Limite de tentativas automáticas atingido. O progresso foi salvo e pode ser retomado.`, true);
                }
            }
        }

        function retryCurrentPage() {
            appendLog(`[Manual] Reiniciando processamento da ULO ${activeUlos[currentUloIndex].name} - Página ${currentPage}...`);
            isSyncing = true;
            errorAttempts = 0;
            document.getElementById('btn-start-sync').disabled = true;
            document.getElementById('btn-start-sync').classList.add('loading');
            syncNext();
        }

        function skipCurrentPage() {
            appendLog(`[Manual] Pulando página ${currentPage} da ULO ${activeUlos[currentUloIndex].name}...`);
            isSyncing = true;
            errorAttempts = 0;
            currentPage++;
            saveProgress();
            document.getElementById('btn-start-sync').disabled = true;
            document.getElementById('btn-start-sync').classList.add('loading');
            syncNext();
        }

        function skipCurrentUlo() {
            appendLog(`[Manual] Pulando o restante da ULO ${activeUlos[currentUloIndex].name}...`);
            isSyncing = true;
            errorAttempts = 0;
            currentUloIndex++;
            currentPage = 1;
            saveProgress();
            document.getElementById('btn-start-sync').disabled = true;
            document.getElementById('btn-start-sync').classList.add('loading');
            syncNext();
        }

        function cancelSync() {
            appendLog(`[Manual] Sincronização cancelada. Progresso salvo localmente.`);
            isSyncing = false;
            document.getElementById('btn-start-sync').disabled = false;
            document.getElementById('btn-start-sync').classList.remove('loading');
            document.getElementById('progress-title').textContent = 'Sincronização cancelada';
            document.getElementById('progress-text-left').textContent = 'Operação abortada.';
            document.getElementById('error-controls').classList.add('hidden');
        }
    </script>
</x-app-layout>
