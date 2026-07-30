<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-base-content leading-tight">
                {{ __('Clientes') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section Card -->
            <div class="bg-base-100 shadow-xl sm:rounded-lg p-6 mb-6 border border-base-200">
                <form method="GET" action="{{ route('clientes') }}" id="filter-form">
                    <!-- Keep current tab -->
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Search text -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Buscar Cliente</span></label>
                            <div class="join w-full">
                                <input type="text" name="search" value="{{ $search }}" placeholder="Nome, Fantasia ou CNPJ..." class="input input-bordered w-full join-item" />
                                <button type="submit" class="btn btn-primary join-item">Buscar</button>
                            </div>
                        </div>

                        <!-- ULO Multi-select (flags) -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Filtrar ULOs</span></label>
                            <div class="flex flex-wrap gap-2 items-center p-2 bg-base-200 rounded-lg min-h-12 border border-base-300">
                                @foreach($availableUlos as $ulo)
                                    <label class="label cursor-pointer flex gap-2">
                                        <input type="checkbox" name="ulos[]" value="{{ $ulo }}" 
                                            class="checkbox checkbox-primary checkbox-sm" 
                                            {{ in_array($ulo, $selectedUlos) ? 'checked' : '' }}
                                            onchange="document.getElementById('filter-form').submit()" />
                                        <span class="label-text text-xs">{{ $ulo }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Faixa aging filter (only relevant for Inadimplentes) -->
                        @if($tab !== 'adimplentes')
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Faixa de Atraso</span></label>
                                <select name="faixa" class="select select-bordered w-full" onchange="document.getElementById('filter-form').submit()">
                                    <option value="all" {{ $faixa === 'all' ? 'selected' : '' }}>Todas as faixas</option>
                                    <option value="30" {{ $faixa === '30' ? 'selected' : '' }}>Faixa 30 (Até 30 dias)</option>
                                    <option value="90" {{ $faixa === '90' ? 'selected' : '' }}>Faixa 90 (31 a 90 dias)</option>
                                    <option value="120" {{ $faixa === '120' ? 'selected' : '' }}>Faixa 120 (91+ dias)</option>
                                </select>
                            </div>
                        @else
                            <div class="form-control opacity-50">
                                <label class="label"><span class="label-text font-bold">Faixa de Atraso</span></label>
                                <select class="select select-bordered w-full" disabled>
                                    <option>Apenas clientes inadimplentes</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tab Navigation & Table -->
            <div class="bg-base-100 shadow-xl sm:rounded-lg border border-base-200">
                <!-- Navigation Tabs -->
                <div class="tabs tabs-lifted w-full">
                    <a href="{{ route('clientes', array_merge(request()->query(), ['tab' => 'inadimplentes', 'page' => 1])) }}" 
                        class="tab tab-lg flex-1 font-bold {{ $tab === 'inadimplentes' ? 'tab-active text-primary bg-base-100' : '' }}">
                        Inadimplentes
                    </a>
                    <a href="{{ route('clientes', array_merge(request()->query(), ['tab' => 'inadimplentes_redecard', 'page' => 1])) }}" 
                        class="tab tab-lg flex-1 font-bold {{ $tab === 'inadimplentes_redecard' ? 'tab-active text-warning bg-base-100' : '' }}">
                        Inadimplentes Redecard
                    </a>
                    <a href="{{ route('clientes', array_merge(request()->query(), ['tab' => 'adimplentes', 'page' => 1])) }}" 
                        class="tab tab-lg flex-1 font-bold {{ $tab === 'adimplentes' ? 'tab-active text-success bg-base-100' : '' }}">
                        Adimplentes
                    </a>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto w-full">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <!-- Clickable headers for sort -->
                                    <th>
                                        <a href="{{ route('clientes', array_merge(request()->query(), ['sort_by' => 'name', 'sort_dir' => $sortBy === 'name' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 group">
                                            Nome do Cliente
                                            @if($sortBy === 'name')
                                                <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('clientes', array_merge(request()->query(), ['sort_by' => 'cnpj', 'sort_dir' => $sortBy === 'cnpj' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 group">
                                            CNPJ / CPF
                                            @if($sortBy === 'cnpj')
                                                <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-right">
                                        <a href="{{ route('clientes', array_merge(request()->query(), ['sort_by' => 'divida', 'sort_dir' => $sortBy === 'divida' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1 group">
                                            Dívida Total
                                            @if($sortBy === 'divida')
                                                <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    @if($tab !== 'adimplentes')
                                        <th class="text-right">
                                            <a href="{{ route('clientes', array_merge(request()->query(), ['sort_by' => 'atraso', 'sort_dir' => $sortBy === 'atraso' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1 group">
                                                Atraso
                                                @if($sortBy === 'atraso')
                                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="text-center">Faixa</th>
                                    @endif
                                    <th>ULOs</th>
                                    <th>Telefone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paginator as $cliente)
                                    <tr>
                                        <!-- Nome -->
                                        <td class="font-bold text-base-content max-w-xs truncate">{{ $cliente->name }}</td>
                                        
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
                                            <td class="text-right font-mono">{{ $cliente->dias_atraso }} dias</td>
                                            <td class="text-center">
                                                @if($cliente->dias_atraso <= 30)
                                                    <span class="badge badge-sm badge-info font-bold">Faixa 30</span>
                                                @elseif($cliente->dias_atraso <= 90)
                                                    <span class="badge badge-sm badge-warning font-bold">Faixa 90</span>
                                                @else
                                                    <span class="badge badge-sm badge-error font-bold text-white">Faixa 120</span>
                                                @endif
                                            </td>
                                        @endif

                                        <!-- ULO Badges -->
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(explode(',', $cliente->all_ulos) as $ulo)
                                                    <span class="badge badge-outline badge-xs font-semibold">{{ $ulo }}</span>
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
                    <div class="mt-6">
                        {{ $paginator->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
