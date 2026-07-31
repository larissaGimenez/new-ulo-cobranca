<!-- Active Filters Bar (shows if any filter is active) -->
@php
    $activeFiltersCount = 0;
    if(!empty($search)) $activeFiltersCount++;
    if(!empty($selectedUlos) && count($selectedUlos) < count($availableUlos)) $activeFiltersCount++;
    if($tab !== 'adimplentes' && $faixa !== 'all') $activeFiltersCount++;
@endphp

@if($activeFiltersCount > 0)
    <div class="flex flex-wrap items-center gap-2 mb-2 p-2 bg-base-200 rounded-lg border border-base-300 text-xs shrink-0">
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

<!-- Content Wrapper -->
<div class="bg-base-100 shadow-md rounded-xl border border-base-200 flex-1 flex flex-col min-h-0 overflow-hidden">
    <!-- Navigation Tabs (Hidden in Kanban mode) -->
    @if($viewMode !== 'kanban')
        <div class="tabs tabs-lifted w-full shrink-0">
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
    @endif

    <div class="p-2.5 sm:p-3 relative flex-1 flex flex-col min-h-0 overflow-hidden">
        <!-- Overlay Loading Spinner during AJAX -->
        <div id="table-loading-spinner" class="hidden absolute inset-0 bg-base-100/70 backdrop-blur-xs z-20 flex justify-center items-center rounded-b-xl">
            <div class="flex items-center gap-3 bg-base-100 p-4 rounded-xl shadow-lg border border-base-200">
                <span class="loading loading-spinner loading-md text-primary"></span>
                <span class="font-semibold text-sm">Carregando dados...</span>
            </div>
        </div>

        <!-- Render View according to viewMode (Kanban or Lista) -->
        @if($viewMode === 'kanban')
            @include('pages.clientes.partials.kanban-view')
        @else
            @include('pages.clientes.partials.list-view')
        @endif
    </div>
</div>
