@php
    $divida = ($tab === 'inadimplentes_redecard') ? $cliente->divida_redecard : $cliente->divida_comum;
    $atrasoColor = $cliente->dias_atraso > 90 ? 'text-error' : ($cliente->dias_atraso > 30 ? 'text-warning' : ($cliente->dias_atraso > 0 ? 'text-info' : 'text-success'));
    $faixaBadgeClass = $cliente->dias_atraso > 90 ? 'badge-error text-white' : ($cliente->dias_atraso > 30 ? 'badge-warning text-base-content' : ($cliente->dias_atraso > 0 ? 'badge-info text-white' : 'badge-success text-white'));
    $faixaText = $cliente->dias_atraso > 90 ? '120' : ($cliente->dias_atraso > 30 ? '90' : ($cliente->dias_atraso > 0 ? '30' : '0'));
@endphp

<!-- Kanban Card Item -->
<div class="kanban-card bg-base-100 p-3.5 rounded-xl shadow-xs border border-base-200 hover:shadow-md transition flex flex-col justify-between gap-2.5 group relative"
    data-cnpj="{{ $cliente->cnpj_cpf }}"
    data-name="{{ strtolower($cliente->name) }}"
    data-amount="{{ $divida }}"
    data-atraso="{{ $cliente->dias_atraso }}">
    
    <!-- Card Header with Title, Faixa Badge & 3-dots Menu -->
    <div class="flex justify-between items-start gap-2">
        <h5 class="font-bold text-sm text-base-content leading-snug truncate" title="{{ $cliente->name }}">
            {{ $cliente->name }}
        </h5>
        
        <div class="flex items-center gap-1 shrink-0">
            <!-- Faixa de Atraso Badge -->
            @if($cliente->dias_atraso > 0)
                <span class="badge badge-xs font-bold px-1.5 py-1 {{ $faixaBadgeClass }}" title="Faixa de Atraso: {{ $cliente->dias_atraso }} dias">
                    Faixa {{ $faixaText }}
                </span>
            @else
                <span class="badge badge-xs badge-success text-white font-bold px-1.5 py-1" title="Cliente sem débitos atrasados">
                    Em Dia
                </span>
            @endif

            <!-- Card Options Dropdown (3 Dots Menu to Move Stage) -->
            <div class="dropdown dropdown-end card-menu-dropdown">
                <button type="button" tabindex="0" class="btn btn-ghost btn-circle btn-xs text-base-content/40 hover:text-base-content" title="Mover para outra etapa" onclick="event.stopPropagation()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <ul tabindex="0" class="dropdown-content z-30 menu p-1.5 shadow-xl bg-base-100 rounded-xl w-56 text-xs border border-base-200 space-y-0.5">
                    <li class="menu-title text-[10px] uppercase font-bold text-base-content/50 px-2 py-1">Mover para etapa:</li>
                    @foreach($kanbanColumns as $targetColId => $targetCol)
                        @if($targetColId !== $colId)
                            <li>
                                <a href="javascript:void(0)" data-action="move-card" data-cnpj="{{ $cliente->cnpj_cpf }}" data-target-stage="{{ $targetColId }}" class="flex items-center gap-2 py-1.5 hover:bg-base-200 rounded-lg">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $targetCol['dot_color'] }} shrink-0 pointer-events-none"></span>
                                    <span class="font-medium text-xs truncate pointer-events-none">{{ $targetCol['title'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Card Footer -->
    <div class="pt-2 border-t border-base-200/60 flex justify-between items-end mt-0.5">
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
