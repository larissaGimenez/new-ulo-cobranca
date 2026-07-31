<!-- Table List View -->
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
                <th class="font-bold">Etapa (Kanban)</th>
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
                    
                    <!-- Etapa (Kanban Stage Flag) -->
                    <td class="whitespace-nowrap">
                        <span class="badge badge-sm font-bold text-white px-2.5 py-1 shadow-xs {{ $cliente->stage_dot_color ?? 'bg-primary' }}">
                            {{ $cliente->stage_title ?? 'INADIMPLÊNCIA' }}
                        </span>
                    </td>

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
