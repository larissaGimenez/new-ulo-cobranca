<!-- Top Kanban Actions: Add New Column -->
<div class="flex justify-between items-center mb-4">
    <div class="text-xs text-base-content/60 font-medium">
        Arraste os cards entre as colunas ou reordene as próprias colunas arrastando pelo cabeçalho.
    </div>
    <button type="button" id="btn-add-column" class="btn btn-sm btn-outline btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Nova Coluna</span>
    </button>
</div>

<!-- Kanban Board View -->
<div id="kanban-board-container" class="flex gap-4 overflow-x-auto pb-6 items-start w-full min-h-[650px]">
    @foreach($kanbanColumns as $colId => $col)
        <div class="kanban-column-wrapper flex flex-col bg-base-200/50 rounded-xl border border-base-300 shadow-xs transition-all duration-200 shrink-0 w-80" 
            data-col-id="{{ $colId }}"
            draggable="true">
            
            <!-- Column Header -->
            <div class="kanban-column-header bg-base-200 p-3 flex flex-col gap-2 border-b border-base-300 cursor-grab active:cursor-grabbing {{ $col['border_color'] }}">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 truncate pr-1">
                        <span class="w-2.5 h-2.5 rounded-full {{ $col['dot_color'] }} shrink-0"></span>
                        <h4 class="font-bold text-xs text-base-content uppercase tracking-wider truncate col-title-text" title="{{ $col['title'] }}">{{ $col['title'] }}</h4>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <!-- Minimize / Collapse Button -->
                        <button type="button" class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content col-toggle-collapse" title="Minimizar / Expandir">
                            <span class="collapse-icon">─</span>
                        </button>
                        
                        <!-- Delete Custom Column if not default -->
                        @if(!in_array($colId, ['inadimplencia', 'contato_inicial', 'em_negociacao', 'acordo_ativo', 'pagamento_concluido']))
                            <button type="button" class="btn btn-ghost btn-xs text-error/70 hover:text-error col-delete-btn" data-slug="{{ $colId }}" title="Excluir Coluna">✕</button>
                        @endif
                    </div>
                </div>

                <!-- Column Summary Count & Total -->
                <div class="text-[11px] text-base-content/60 font-semibold column-summary-text" data-col="{{ $colId }}">
                    <span class="col-count">{{ $col['count'] }}</span> cards - R$ <span class="col-total">{{ number_format($col['total'], 2, ',', '.') }}</span>
                </div>

                <!-- Column Search & Sort Controls (Visible when expanded) -->
                <div class="col-controls flex flex-col gap-1.5 mt-1 pt-2 border-t border-base-300/60">
                    <input type="text" class="input input-xs input-bordered w-full col-search-input" placeholder="Buscar nesta coluna..." autocomplete="off" />
                    <select class="select select-xs select-bordered w-full col-sort-select">
                        <option value="default">Ordenação padrão</option>
                        <option value="name_asc">Nome (A - Z)</option>
                        <option value="name_desc">Nome (Z - A)</option>
                        <option value="divida_desc">Maior Dívida</option>
                        <option value="divida_asc">Menor Dívida</option>
                        <option value="atraso_desc">Maior Atraso</option>
                        <option value="atraso_asc">Menor Atraso</option>
                    </select>
                </div>
            </div>

            <!-- Collapsed Vertical View Container (Hidden by default) -->
            <div class="collapsed-view-container hidden flex-col items-center py-6 px-2 gap-4 cursor-pointer min-h-[500px]">
                <button type="button" class="btn btn-circle btn-xs btn-ghost col-toggle-collapse" title="Expandir Coluna">▶</button>
                <div class="writing-mode-vertical text-xs font-bold uppercase tracking-wider text-base-content/70 select-none">
                    {{ $col['title'] }}
                </div>
                <span class="badge badge-sm badge-neutral font-bold col-count-badge">{{ $col['count'] }}</span>
            </div>

            <!-- Column Body / Drop Container -->
            <div class="kanban-column-body flex flex-col gap-3 p-3 min-h-[480px] transition-colors duration-150" data-stage="{{ $colId }}">
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

                    <!-- Kanban Card Item (Without Unidades/ULOs text) -->
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
</div>

<style>
    .writing-mode-vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
    }
</style>
