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

                <h3 class="text-lg font-bold mb-4 text-base-content">Logs de Webhooks Omie</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Acompanhe em tempo real os payloads brutos recebidos da API do Omie para auditoria e depuração de eventos.
                </p>

                @if($logs->isEmpty())
                    <div class="alert alert-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Nenhum log de webhook registrado ainda.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra table-sm w-full bg-base-100 rounded-lg overflow-hidden border border-base-300">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>ULO Source</th>
                                    <th>Entidade</th>
                                    <th>ID Omie</th>
                                    <th>Ação</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td class="font-semibold">{{ $log->ulo_source }}</td>
                                        <td>
                                            <span class="badge badge-sm {{ $log->entity_type === 'receivable' ? 'badge-primary' : 'badge-secondary' }}">
                                                {{ $log->entity_type === 'receivable' ? 'Título' : 'Cliente' }}
                                            </span>
                                        </td>
                                        <td><span class="badge badge-ghost font-mono">{{ $log->entity_id }}</span></td>
                                        <td>
                                            <span class="badge badge-outline badge-sm uppercase">{{ $log->action }}</span>
                                        </td>
                                        <td class="text-right">
                                            <button onclick="showPayload({{ $log->id }}, {{ json_encode($log->details) }})" class="btn btn-xs btn-ghost text-primary">
                                                Ver Payload
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para Exibição do Payload -->
    <dialog id="payload-modal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl bg-neutral text-neutral-content">
            <h3 class="font-bold text-lg mb-2 text-white">Detalhamento do Payload JSON</h3>
            <p class="text-xs opacity-75 mb-4" id="payload-title">Visualizando log #</p>
            
            <pre class="bg-black/80 p-4 rounded-lg text-xs overflow-auto max-h-96 text-success select-all font-mono" id="payload-content"></pre>
            
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-sm btn-ghost text-white">Fechar</button>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        function showPayload(id, json) {
            document.getElementById('payload-title').innerText = `Visualizando Log de Auditoria #${id}`;
            document.getElementById('payload-content').innerText = JSON.stringify(json, null, 4);
            document.getElementById('payload-modal').showModal();
        }
    </script>
</x-app-layout>
