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

                <h3 class="text-lg font-bold mb-4 text-base-content">Diagnóstico de Vínculos (Relacionamentos)</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Esta ferramenta realiza uma análise rápida no banco de dados local para verificar o relacionamento entre as tabelas de Clientes e Títulos (Contas a Receber) com base nas colunas <code>codigo_cliente_fornecedor</code> e <code>codigo_cliente_omie</code>.
                </p>

                <!-- Cards com Estatísticas Gerais -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="stats shadow bg-base-200">
                        <div class="stat">
                            <div class="stat-title text-base-content/70">Total de Títulos</div>
                            <div class="stat-value text-primary">{{ number_format($totalTitles, 0, ',', '.') }}</div>
                            <div class="stat-desc text-base-content/50">Lançados no banco local</div>
                        </div>
                    </div>

                    <div class="stats shadow bg-base-200">
                        <div class="stat">
                            <div class="stat-title text-base-content/70">Títulos Vinculados</div>
                            <div class="stat-value text-success">{{ number_format($linkedTitles, 0, ',', '.') }}</div>
                            <div class="stat-desc text-success font-medium">
                                @if($totalTitles > 0)
                                    {{ round(($linkedTitles / $totalTitles) * 100, 1) }}% de aproveitamento
                                @else
                                    0% de aproveitamento
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="stats shadow bg-base-200">
                        <div class="stat">
                            <div class="stat-title text-base-content/70">Títulos Órfãos</div>
                            <div class="stat-value text-warning">{{ number_format($orphanTitlesCount, 0, ',', '.') }}</div>
                            <div class="stat-desc text-base-content/50">Cliente correspondente não importado</div>
                        </div>
                    </div>
                </div>

                <!-- Detalhamento por ULO -->
                <div class="card bg-base-200 shadow-md">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="card-title text-base font-bold text-base-content">Status por ULO</h4>
                            <a href="{{ route('dev-settings.vinculos') }}" class="btn btn-sm btn-outline gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Atualizar Diagnóstico
                            </a>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>Nome da ULO</th>
                                        <th class="text-right">Total Títulos</th>
                                        <th class="text-right">Vinculados</th>
                                        <th class="text-right">Órfãos</th>
                                        <th class="text-right">Aproveitamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($statsByUlo as $stat)
                                        <tr>
                                            <td class="font-medium text-base-content">{{ $stat['name'] }}</td>
                                            <td class="text-right">{{ number_format($stat['total'], 0, ',', '.') }}</td>
                                            <td class="text-right text-success font-medium">{{ number_format($stat['linked'], 0, ',', '.') }}</td>
                                            <td class="text-right text-warning font-medium">{{ number_format($stat['orphan'], 0, ',', '.') }}</td>
                                            <td class="text-right">
                                                @if($stat['total'] > 0)
                                                    <span class="badge badge-success font-bold">
                                                        {{ round(($stat['linked'] / $stat['total']) * 100, 1) }}%
                                                    </span>
                                                @else
                                                    <span class="badge badge-ghost">0%</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-base-content/50 py-4">Nenhum dado encontrado no banco de dados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Orphan Titles Table -->
                <div class="card bg-base-200 border border-base-300 p-6 rounded-box mt-8">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-base-content">Títulos Órfãos (Sem Vínculo com Clientes)</h3>
                        <span class="badge badge-error font-mono">{{ $orphanTitlesCount }} inconsistentes</span>
                    </div>

                    @if($orphans->isEmpty())
                        <div class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Nenhum título órfão detectado! Todos os títulos estão vinculados corretamente aos seus clientes.</span>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-zebra table-sm w-full bg-base-100 rounded-lg overflow-hidden border border-base-300">
                                <thead>
                                    <tr>
                                        <th>ULO Source</th>
                                        <th>Cód. Omie</th>
                                        <th>Cód. Cliente (Omie)</th>
                                        <th>Valor Documento</th>
                                        <th>Status Título</th>
                                        <th>Data Vencimento</th>
                                        <th>Data Previsão</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orphans as $orphan)
                                        <tr>
                                            <td class="font-semibold">{{ $orphan->ulo_source }}</td>
                                            <td><span class="badge badge-ghost font-mono">{{ $orphan->codigo_lancamento_omie }}</span></td>
                                            <td><span class="text-error font-semibold font-mono">{{ $orphan->codigo_cliente_fornecedor }}</span></td>
                                            <td>R$ {{ number_format($orphan->valor_documento, 2, ',', '.') }}</td>
                                            <td>
                                                <span class="badge badge-xs
                                                    @if($orphan->status_titulo === 'RECEBIDO') badge-success
                                                    @elseif($orphan->status_titulo === 'ATRASADO') badge-error
                                                    @elseif($orphan->status_titulo === 'VENCE HOJE') badge-warning
                                                    @else badge-info
                                                    @endif">
                                                    {{ $orphan->status_titulo }}
                                                </span>
                                            </td>
                                            <td>{{ $orphan->data_vencimento ? \Carbon\Carbon::parse($orphan->data_vencimento)->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $orphan->data_previsao ? \Carbon\Carbon::parse($orphan->data_previsao)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="importClient('{{ $orphan->ulo_source }}', {{ $orphan->codigo_cliente_fornecedor }}, this)" class="btn btn-xs btn-primary text-white" title="Importar da API Omie">
                                                        Importar
                                                    </button>
                                                    <button onclick="autoDetectReassociate({{ $orphan->id }}, this)" class="btn btn-xs btn-accent text-white" title="Detectar automaticamente cliente da OS">
                                                        💡 Auto-Detectar
                                                    </button>
                                                    <button onclick="openReassociateModal({{ $orphan->id }}, '{{ $orphan->ulo_source }}', {{ $orphan->codigo_cliente_fornecedor }})" class="btn btn-xs btn-outline btn-secondary" title="Vincular a outro cliente cadastrado">
                                                        Vincular Outro
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Links -->
                        <div class="mt-4">
                            {{ $orphans->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de Reassociação de Cliente -->
    <dialog id="reassociate-modal" class="modal">
        <div class="modal-box bg-base-100 border border-base-200 w-11/12 max-w-lg">
            <h3 class="font-bold text-lg mb-2">Vincular Título a Outro Cliente</h3>
            <p class="text-xs opacity-70 mb-4">
                Se o cliente original (Código <span id="reassociate-old-code" class="font-mono font-bold"></span>) foi excluído ou recriado no Omie com outro ID, pesquise abaixo o cadastro correto na ULO <span id="reassociate-ulo" class="font-bold"></span> para reassociá-lo no banco.
            </p>

            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Buscar Cliente (Razão Social, Fantasia ou CPF/CNPJ)</span>
                </label>
                <input type="text" id="client-search-input" placeholder="Digite pelo menos 2 caracteres..." class="input input-bordered w-full" onkeyup="searchClientsDebounced()" />
            </div>

            <!-- Lista de Resultados -->
            <div id="search-results" class="space-y-2 max-h-60 overflow-y-auto">
                <p class="text-xs opacity-50 text-center py-4">Aguardando digitação...</p>
            </div>

            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-sm btn-ghost">Cancelar</button>
                </form>
            </div>
        </div>
    </dialog>
</x-app-layout>

<script>
    let currentTitleId = null;
    let currentUloSource = null;
    let searchTimeout = null;

    function importClient(uloSource, clientCode, btn) {
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span>';

        fetch('/dev-settings/vinculos/import-client', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ulo_source: uloSource,
                codigo_cliente_omie: clientCode
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Falha ao importar.'));
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            alert('Erro ao conectar com a API local.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }

    function openReassociateModal(titleId, uloSource, oldClientCode) {
        currentTitleId = titleId;
        currentUloSource = uloSource;
        document.getElementById('reassociate-old-code').innerText = oldClientCode;
        document.getElementById('reassociate-ulo').innerText = uloSource;
        document.getElementById('client-search-input').value = '';
        document.getElementById('search-results').innerHTML = '<p class="text-xs opacity-50 text-center py-4">Aguardando digitação...</p>';
        document.getElementById('reassociate-modal').showModal();
    }

    function searchClientsDebounced() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = document.getElementById('client-search-input').value.trim();
            const resultsDiv = document.getElementById('search-results');

            if (query.length < 2) {
                resultsDiv.innerHTML = '<p class="text-xs opacity-50 text-center py-4">Digite pelo menos 2 caracteres...</p>';
                return;
            }

            resultsDiv.innerHTML = '<div class="flex justify-center py-4"><span class="loading loading-spinner loading-sm"></span></div>';

            fetch(`/dev-settings/vinculos/search-clients?ulo_source=${encodeURIComponent(currentUloSource)}&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<p class="text-xs opacity-50 text-center py-4">Nenhum cliente cadastrado no banco local com esses dados.</p>';
                        return;
                    }

                    let html = '';
                    data.forEach(client => {
                        html += `
                            <div class="flex justify-between items-center p-2 rounded bg-base-200 hover:bg-base-300 transition text-xs">
                                <div>
                                    <div class="font-bold text-base-content">${client.razao_social}</div>
                                    <div class="text-[10px] opacity-70">Fantasia: ${client.nome_fantasia || '-'} | CPF/CNPJ: ${client.cnpj_cpf}</div>
                                    <div class="text-[10px] opacity-50">Código Omie: ${client.codigo_cliente_omie}</div>
                                </div>
                                <button onclick="reassociate(${client.codigo_cliente_omie}, this)" class="btn btn-xs btn-success text-white">
                                    Vincular
                                </button>
                            </div>
                        `;
                    });
                    resultsDiv.innerHTML = html;
                })
                .catch(err => {
                    resultsDiv.innerHTML = '<p class="text-xs text-error text-center py-4">Erro ao buscar clientes.</p>';
                });
        }, 300);
    }

    function reassociate(newClientCode, btn) {
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span>';

        fetch('/dev-settings/vinculos/reassociate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title_id: currentTitleId,
                new_client_code: newClientCode
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Falha ao reassociar.'));
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            alert('Erro de conexão ao reassociar.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }

    function autoDetectReassociate(titleId, btn) {
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span>';

        fetch(`/dev-settings/vinculos/${titleId}/suggest`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.found) {
                        openAiDiagnosisModal(titleId, data.ai_diagnosis, data.old_code, data.client_code, data.client_name);
                    } else {
                        alert(data.message || 'Nenhum cliente correspondente encontrado.');
                    }
                } else {
                    alert('Erro na busca: ' + (data.message || 'Falha ao sugerir reautovinculo.'));
                }
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            })
            .catch(err => {
                alert('Erro de conexão ao executar detecção automática.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
    }

    function openAiDiagnosisModal(titleId, diagnosisText, oldClientCode, newClientCode, newClientName) {
        document.getElementById('ai-diagnosis-text').innerText = diagnosisText;
        document.getElementById('flow-old-id').innerText = oldClientCode;
        document.getElementById('flow-new-id').innerText = newClientCode;
        
        const confirmBtn = document.getElementById('btn-confirm-ai-link');
        confirmBtn.onclick = function() {
            const originalBtnHTML = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Vinculando...';
            
            reassociateTitleDirectly(titleId, newClientCode, confirmBtn, originalBtnHTML);
        };
        
        document.getElementById('ai-diagnosis-modal').showModal();
    }

    function reassociateTitleDirectly(titleId, clientCode, btn, originalHTML) {
        fetch('/dev-settings/vinculos/reassociate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title_id: titleId,
                new_client_code: clientCode
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro ao reassociar: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            alert('Erro de conexão ao salvar reassociação.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }
</script>

<!-- Modal de Diagnóstico Inteligente de IA -->
<dialog id="ai-diagnosis-modal" class="modal">
    <div class="modal-box bg-base-100 border border-base-200 w-11/12 max-w-lg p-6 rounded-2xl shadow-2xl overflow-x-hidden">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-5">
            <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-xl">
                🤖
            </div>
            <div>
                <h3 class="font-bold text-base text-base-content">Resolução de Vínculo de Cliente</h3>
                <p class="text-[10px] opacity-60">Análise de Rastreabilidade inteligente da ULO</p>
            </div>
        </div>
        
        <!-- AI Diagnosis text -->
        <div class="bg-base-200/50 border border-base-300 rounded-xl p-4 mb-6">
            <span class="text-[10px] font-bold uppercase tracking-wider text-primary block mb-2">Análise do Sistema</span>
            <p id="ai-diagnosis-text" class="text-xs text-base-content/80 leading-relaxed"></p>
        </div>

        <!-- Visual Flow Comparison -->
        <div class="grid grid-cols-5 gap-2 items-center bg-base-200/30 rounded-xl p-3 mb-6 border border-base-200 text-center">
            <div class="col-span-2">
                <span class="text-[9px] opacity-60 uppercase block mb-1 font-semibold">ID Excluído (Título)</span>
                <span id="flow-old-id" class="badge badge-error badge-outline font-mono text-xs px-2 py-1 h-auto font-bold">-</span>
            </div>
            <div class="flex justify-center text-base-content/40 text-lg">
                ➡️
            </div>
            <div class="col-span-2">
                <span class="text-[9px] opacity-60 uppercase block mb-1 font-semibold">ID Ativo (Omie)</span>
                <span id="flow-new-id" class="badge badge-success badge-outline font-mono text-xs px-2 py-1 h-auto font-bold">-</span>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex justify-between items-center gap-4">
            <span class="text-[10px] opacity-50 max-w-[200px] leading-tight">O vínculo será atualizado na tabela de cobranças local.</span>
            <div class="flex gap-2 shrink-0">
                <form method="dialog">
                    <button class="btn btn-sm btn-ghost text-xs">Cancelar</button>
                </form>
                <button id="btn-confirm-ai-link" class="btn btn-sm btn-success text-white text-xs font-semibold">
                    Vincular Cliente
                </button>
            </div>
        </div>
    </div>
</dialog>
