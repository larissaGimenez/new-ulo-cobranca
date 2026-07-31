<!-- Kanban Board View -->
<div id="kanban-board-container" class="flex gap-4 overflow-x-auto pb-4 items-start w-full min-h-[600px]">
    @foreach($kanbanColumns as $colId => $col)
        <div class="kanban-column-wrapper flex flex-col bg-base-200/50 rounded-xl border border-base-300 shadow-xs transition-all duration-200 shrink-0 w-80 max-h-[calc(100vh-200px)]" 
            data-col-id="{{ $colId }}"
            draggable="true">
            
            <!-- Column Header -->
            <div class="kanban-column-header bg-base-200/90 p-3.5 flex flex-col gap-2 border-b border-base-300 cursor-grab active:cursor-grabbing rounded-t-xl shrink-0 {{ $col['border_color'] }}">
                <!-- Header Line 1: Dot, Title, Badge & Action Icons (3-dots removed) -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 truncate pr-1">
                        <span class="w-2.5 h-2.5 rounded-full {{ $col['dot_color'] }} shrink-0"></span>
                        <h4 class="font-bold text-xs text-base-content uppercase tracking-wider truncate col-title-text" title="{{ $col['title'] }}">{{ $col['title'] }}</h4>
                        <span class="badge badge-sm badge-ghost border border-base-300 font-semibold text-[11px] px-2 py-0.5 col-count-badge">{{ $col['count'] }}</span>
                    </div>
                    
                    <div class="flex items-center gap-0.5">
                        <!-- Toggle Search Button -->
                        <button type="button" class="btn btn-ghost btn-xs text-base-content/60 hover:text-base-content col-search-toggle" title="Buscar nesta coluna">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <!-- Collapse Button -->
                        <button type="button" class="btn btn-ghost btn-xs text-base-content/60 hover:text-base-content col-toggle-collapse" title="Minimizar Coluna">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Header Line 2: Total Sum & DaisyUI Sort Dropdown -->
                <div class="flex justify-between items-center text-xs font-medium border-t border-base-300/40 pt-2 column-summary-text" data-col="{{ $colId }}">
                    <div class="text-base-content/70">
                        Total: <span class="font-bold text-error">R$ <span class="col-total">{{ number_format($col['total'], 2, ',', '.') }}</span></span>
                    </div>

                    <!-- Visual Sort Dropdown -->
                    <div class="dropdown dropdown-end">
                        <button type="button" tabindex="0" class="btn btn-ghost btn-xs text-xs font-semibold text-base-content/70 hover:text-base-content flex items-center gap-1 px-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                            <span class="col-sort-label">Padrão</span>
                        </button>
                        <ul tabindex="0" class="dropdown-content z-30 menu p-1.5 shadow-xl bg-base-100 rounded-xl w-52 text-xs border border-base-200 col-sort-menu space-y-0.5">
                            <li><a data-sort="default" class="col-sort-item active font-bold text-primary">Padrão</a></li>
                            <div class="divider my-0 opacity-40"></div>
                            <li><a data-sort="divida_desc" class="col-sort-item">Dívida (Maior)</a></li>
                            <li><a data-sort="divida_asc" class="col-sort-item">Dívida (Menor)</a></li>
                            <div class="divider my-0 opacity-40"></div>
                            <li><a data-sort="name_asc" class="col-sort-item">Nome (A-Z)</a></li>
                            <li><a data-sort="name_desc" class="col-sort-item">Nome (Z-A)</a></li>
                            <div class="divider my-0 opacity-40"></div>
                            <li><a data-sort="atraso_desc" class="col-sort-item">Inadimplência (Maior)</a></li>
                            <li><a data-sort="atraso_asc" class="col-sort-item">Inadimplência (Menor)</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Collapsible Search Container -->
                <div class="col-search-container hidden pt-1">
                    <div class="relative">
                        <input type="text" class="input input-xs input-bordered w-full pr-6 col-search-input" placeholder="Buscar nesta coluna..." autocomplete="off" />
                        <button type="button" class="btn btn-ghost btn-xs text-xs absolute right-0 top-0 text-base-content/40 hover:text-error col-search-clear">✕</button>
                    </div>
                </div>
            </div>

            <!-- Collapsed Vertical View Container (Includes Dot Color Badge) -->
            <div class="collapsed-view-container hidden flex-col items-center py-5 px-2 gap-4 cursor-pointer min-h-[480px]">
                <button type="button" class="btn btn-circle btn-xs btn-ghost col-toggle-collapse" title="Expandir Coluna">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <span class="w-3 h-3 rounded-full {{ $col['dot_color'] }} shrink-0 shadow-xs" title="{{ $col['title'] }}"></span>
                <div class="writing-mode-vertical text-xs font-bold uppercase tracking-wider text-base-content/70 select-none">
                    {{ $col['title'] }}
                </div>
                <span class="badge badge-sm badge-neutral font-bold col-count-badge">{{ $col['count'] }}</span>
            </div>

            <!-- Column Body / Scrollable Drop Container (max-h to fit screen) -->
            <div class="kanban-column-body flex flex-col gap-3 p-3 max-h-[calc(100vh-270px)] overflow-y-auto custom-scrollbar transition-colors duration-150" data-stage="{{ $colId }}">
                @if(count($col['items']) === 0)
                    <div class="border-2 border-dashed border-base-300/80 rounded-xl p-8 text-center text-xs text-base-content/40 font-medium empty-placeholder my-auto">
                        Sem cards nesta etapa
                    </div>
                @endif

                @foreach($col['items'] as $cliente)
                    @php
                        $divida = ($tab === 'inadimplentes_redecard') ? $cliente->divida_redecard : $cliente->divida_comum;
                        $dotColor = $cliente->dias_atraso > 90 ? 'bg-error' : ($cliente->dias_atraso > 30 ? 'bg-warning' : ($cliente->dias_atraso > 0 ? 'bg-info' : 'bg-success'));
                        $atrasoColor = $cliente->dias_atraso > 90 ? 'text-error' : ($cliente->dias_atraso > 30 ? 'text-warning' : ($cliente->dias_atraso > 0 ? 'text-info' : 'text-success'));
                    @endphp

                    <!-- Kanban Card Item -->
                    <div class="kanban-card bg-base-100 p-4 rounded-xl shadow-xs border border-base-200 hover:shadow-md transition cursor-grab active:cursor-grabbing flex flex-col justify-between gap-3 group relative"
                        draggable="true" 
                        data-cnpj="{{ $cliente->cnpj_cpf }}"
                        data-name="{{ strtolower($cliente->name) }}"
                        data-amount="{{ $divida }}"
                        data-atraso="{{ $cliente->dias_atraso }}">
                        
                        <!-- Top Header -->
                        <div class="flex justify-between items-start pr-4">
                            <h5 class="font-bold text-sm text-base-content leading-snug truncate" title="{{ $cliente->name }}">
                                {{ $cliente->name }}
                            </h5>
                            <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }} absolute top-4 right-4" title="Status de Atraso"></span>
                        </div>

                        <!-- Card Footer -->
                        <div class="pt-2 border-t border-base-200/60 flex justify-between items-end mt-1">
                            <div>
                                <span class="text-[10px] font-bold text-base-content/40 uppercase tracking-wider block">DÍVIDA</span>
                                <span class="font-bold text-sm text-base-content">R$ {{ number_format($divida, 2, ',', '.') }}</span>
                            </div>
                            <div class="text-right">
                                @if($cliente->dias_atraso > 0)
                                    <span class="text-xs font-bold {{ $atrasoColor }}">{{ $cliente->dias_atraso }}d atraso</span>
                                @else
                                    <span class="text-xs font-bold text-success">em dia</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- Adicionar Etapa Column Card -->
    <div id="btn-add-column-card" class="kanban-add-column-card bg-base-200/30 hover:bg-base-200/60 border-2 border-dashed border-base-300 hover:border-primary/50 rounded-xl flex flex-col justify-center items-center cursor-pointer transition p-8 text-base-content/40 hover:text-primary w-80 shrink-0 h-[calc(100vh-270px)] group">
        <div class="text-6xl font-light leading-none group-hover:scale-110 transition-transform select-none">
            +
        </div>
        <span class="font-bold text-xs tracking-widest mt-4 uppercase text-base-content/60 group-hover:text-primary">ADICIONAR ETAPA</span>
    </div>
</div>

<style>
    .writing-mode-vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.4);
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.7);
    }
</style>
