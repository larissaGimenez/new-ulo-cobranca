<!-- Active Filters Bar (shows if any filter is active) -->
@php
    $activeFiltersCount = 0;
    if(!empty($search)) $activeFiltersCount++;
    if(!empty($selectedUlos) && count($selectedUlos) < count($availableUlos)) $activeFiltersCount++;
    if($tab !== 'adimplentes' && $faixa !== 'all') $activeFiltersCount++;
@endphp

@if($activeFiltersCount > 0)
    <div class="flex flex-wrap items-center gap-2 mb-6 p-3 bg-base-200 rounded-lg border border-base-300 text-xs">
        <span class="font-bold text-base-content/70">Filtros Ativos:</span>
        
        @if(!empty($search))
            <span class="badge badge-neutral gap-1 p-2">
                Busca: "{{ $search }}"
                <button type="button" data-action="remove-filter" data-filter="search" class="hover:text-error font-bold ml-1">✕</button>
            </span>
        @endif

        @if(!empty($selectedUlos) && count($selectedUlos) < count($availableUlos))
            <span class="badge badge-info gap-1 p-2">
                ULOs: {{ implode(', ', $selectedUlos) }}
            </span>
        @endif

        @if($tab !== 'adimplentes' && $faixa !== 'all')
            <span class="badge badge-warning gap-1 p-2">
                Faixa: {{ $faixa }} dias
                <button type="button" data-action="remove-filter" data-filter="faixa" class="hover:text-error font-bold ml-1">✕</button>
            </span>
        @endif

        <button type="button" data-action="clear-all" class="btn btn-xs btn-ghost text-error ml-auto">Limpar Todos</button>
    </div>
@endif

<!-- Tab Navigation & Table -->
<div class="bg-base-100 shadow-xl sm:rounded-lg border border-base-200">
    <!-- Navigation Tabs -->
    <div class="tabs tabs-lifted w-full">
        <button type="button" data-action="change-tab" data-tab="inadimplentes" 
            class="tab tab-lg flex-1 font-bold {{ $tab === 'inadimplentes' ? 'tab-active text-primary bg-base-100' : '' }}">
            Inadimplentes
        </button>
        <button type="button" data-action="change-tab" data-tab="inadimplentes_redecard" 
            class="tab tab-lg flex-1 font-bold {{ $tab === 'inadimplentes_redecard' ? 'tab-active text-warning bg-base-100' : '' }}">
            Inadimplentes Redecard
        </button>
        <button type="button" data-action="change-tab" data-tab="adimplentes" 
            class="tab tab-lg flex-1 font-bold {{ $tab === 'adimplentes' ? 'tab-active text-success bg-base-100' : '' }}">
            Adimplentes
        </button>
    </div>

    <div class="p-6 relative">
        <!-- Overlay Loading Spinner during AJAX -->
        <div id="table-loading-spinner" class="hidden absolute inset-0 bg-base-100/70 backdrop-blur-xs z-20 flex justify-center items-center rounded-b-lg">
            <div class="flex items-center gap-3 bg-base-100 p-4 rounded-xl shadow-lg border border-base-200">
                <span class="loading loading-spinner loading-md text-primary"></span>
                <span class="font-semibold text-sm">Carregando dados...</span>
            </div>
        </div>

        <!-- Stats Panel -->
        @if($tab !== 'adimplentes')
            <div class="stats shadow bg-base-200 border border-base-300 w-full mb-6">
                <div class="stat">
                    <div class="stat-title text-base-content/70">Títulos em Atraso Considerados</div>
                    <div class="stat-value text-primary">{{ number_format($totalOverdueTitlesCount, 0, ',', '.') }}</div>
                    <div class="stat-desc text-xs text-base-content/50">Soma de todos os recebíveis vencidos e não baixados</div>
                </div>
                
                <div class="stat">
                    <div class="stat-title text-base-content/70">Dívida Total Acumulada</div>
                    <div class="stat-value text-error">R$ {{ number_format($totalOverdueAmount, 2, ',', '.') }}</div>
                    <div class="stat-desc text-xs text-base-content/50">Valor total a receber (apenas títulos atrasados)</div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto w-full">
            <table class="table w-full">
                <thead>
                    <tr>
                        <!-- Clickable headers for sort -->
                        <th>
                            <button type="button" data-action="sort" data-sort-by="name" data-sort-dir="{{ $sortBy === 'name' && $sortDir === 'asc' ? 'desc' : 'asc' }}" class="flex items-center gap-1 group font-bold hover:text-primary">
                                Nome do Cliente
                                @if($sortBy === 'name')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" data-action="sort" data-sort-by="cnpj" data-sort-dir="{{ $sortBy === 'cnpj' && $sortDir === 'asc' ? 'desc' : 'asc' }}" class="flex items-center gap-1 group font-bold hover:text-primary">
                                CNPJ / CPF
                                @if($sortBy === 'cnpj')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="text-right">
                            <button type="button" data-action="sort" data-sort-by="divida" data-sort-dir="{{ $sortBy === 'divida' && $sortDir === 'asc' ? 'desc' : 'asc' }}" class="flex items-center justify-end gap-1 group font-bold w-full hover:text-primary">
                                Dívida Total
                                @if($sortBy === 'divida')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        @if($tab !== 'adimplentes')
                            <th class="text-right">
                                <button type="button" data-action="sort" data-sort-by="atraso" data-sort-dir="{{ $sortBy === 'atraso' && $sortDir === 'asc' ? 'desc' : 'asc' }}" class="flex items-center justify-end gap-1 group font-bold w-full hover:text-primary">
                                    Atraso
                                    @if($sortBy === 'atraso')
                                        <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center font-bold">Faixa</th>
                        @endif
                        <th class="font-bold">ULOs</th>
                        <th class="font-bold">Telefone</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginator as $cliente)
                        <tr class="hover">
                            <!-- Nome -->
                            <td class="font-bold text-base-content">{{ $cliente->name }}</td>
                            
                            <!-- CNPJ -->
                            <td class="font-mono text-sm">{{ $cliente->cnpj_cpf }}</td>
                            
                            <!-- Dívida -->
                            <td class="text-right font-semibold">
                                @if($tab === 'inadimplentes')
                                    <span class="text-error">R$ {{ number_format($cliente->divida_comum, 2, ',', '.') }}</span>
                                @elseif($tab === 'inadimplentes_redecard')
                                    <span class="text-warning">R$ {{ number_format($cliente->divida_redecard, 2, ',', '.') }}</span>
                                @else
                                    <span class="text-success">R$ 0,00</span>
                                @endif
                            </td>
                            
                            <!-- Atraso e Faixa -->
                            @if($tab !== 'adimplentes')
                                <td class="text-right font-mono whitespace-nowrap">{{ $cliente->dias_atraso }} dias</td>
                                <td class="text-center whitespace-nowrap">
                                    @if($cliente->dias_atraso <= 30)
                                        <span class="badge badge-sm badge-info font-bold whitespace-nowrap">30</span>
                                    @elseif($cliente->dias_atraso <= 90)
                                        <span class="badge badge-sm badge-warning font-bold whitespace-nowrap">90</span>
                                    @else
                                        <span class="badge badge-sm badge-error font-bold text-white whitespace-nowrap">120</span>
                                    @endif
                                </td>
                            @endif

                            <!-- ULO Badges -->
                            <td class="whitespace-nowrap">
                                <div class="flex flex-wrap gap-1 items-center">
                                    @foreach(explode(',', $cliente->all_ulos) as $ulo)
                                        <span class="badge badge-outline badge-sm font-semibold whitespace-nowrap">{{ trim($ulo) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            
                            <!-- Telefone -->
                            <td>
                                @if(!empty($cliente->phone))
                                    <span class="text-xs">
                                        @if(!empty($cliente->phone_ddd))
                                            ({{ $cliente->phone_ddd }})
                                        @endif
                                        {{ $cliente->phone }}
                                    </span>
                                @else
                                    <span class="text-base-content/40 text-xs">Sem telefone</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab !== 'adimplentes' ? 7 : 5 }}" class="text-center text-base-content/50 py-10">
                                Nenhum cliente encontrado com os filtros selecionados nesta aba.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="mt-6 ajax-pagination">
            {{ $paginator->links() }}
        </div>
    </div>
</div>
