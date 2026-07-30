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
                    <a href="{{ route('dev-settings.clientes') }}" class="tab font-semibold">Clientes</a>
                    <a href="{{ route('dev-settings.vinculos') }}" class="tab tab-active font-semibold">Vínculos</a>
                </div>

                <h3 class="text-lg font-bold mb-4 text-base-content">Diagnóstico de Vínculos (Relacionamentos)</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Esta ferramenta realiza uma análise rápida no banco de dados local para verificar o relacionamento entre as tabelas de Clientes e Títulos (Contas a Pagar) com base nas colunas <code>codigo_cliente_fornecedor</code> e <code>codigo_cliente_omie</code>.
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
                            <div class="stat-value text-warning">{{ number_format($orphanTitles, 0, ',', '.') }}</div>
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

            </div>
        </div>
    </div>
</x-app-layout>
