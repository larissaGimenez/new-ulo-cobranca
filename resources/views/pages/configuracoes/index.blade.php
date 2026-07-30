<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-base-content leading-tight">
            {{ __('Configurações') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 shadow-xl sm:rounded-lg p-8 border border-base-200">
                <h3 class="text-lg font-bold text-base-content mb-4">Preferências do Sistema</h3>
                <p class="text-sm text-base-content/70 mb-6">
                    Ajuste quais notificações você deseja receber na barra superior e se deve reproduzir alertas sonoros.
                </p>

                @if(session('success'))
                    <div class="alert alert-success mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('configuracoes.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="card bg-base-200 p-6 rounded-lg space-y-4">
                        <h4 class="font-semibold text-base text-base-content">Notificações por Webhook</h4>
                        <div class="divider my-1"></div>

                        <!-- Checkbox: Receivable Created -->
                        <div class="form-control">
                            <label class="label cursor-pointer justify-between">
                                <span class="label-text flex flex-col">
                                    <span class="font-medium">Títulos Incluídos</span>
                                    <span class="text-xs text-base-content/60">Notificar quando um novo contas a receber for gerado no Omie.</span>
                                </span>
                                <input type="checkbox" name="notify_receivable_created" class="toggle toggle-primary" {{ ($settings['notify_receivable_created'] ?? true) ? 'checked' : '' }} />
                            </label>
                        </div>

                        <!-- Checkbox: Receivable Updated -->
                        <div class="form-control">
                            <label class="label cursor-pointer justify-between">
                                <span class="label-text flex flex-col">
                                    <span class="font-medium">Títulos Alterados</span>
                                    <span class="text-xs text-base-content/60">Notificar quando o valor, datas ou vencimento de um título forem modificados.</span>
                                </span>
                                <input type="checkbox" name="notify_receivable_updated" class="toggle toggle-primary" {{ ($settings['notify_receivable_updated'] ?? true) ? 'checked' : '' }} />
                            </label>
                        </div>

                        <!-- Checkbox: Receivable Paid -->
                        <div class="form-control">
                            <label class="label cursor-pointer justify-between">
                                <span class="label-text flex flex-col">
                                    <span class="font-medium">Títulos Liquidados / Pagos</span>
                                    <span class="text-xs text-base-content/60">Notificar quando um título for quitado ou receber baixa no Omie.</span>
                                </span>
                                <input type="checkbox" name="notify_receivable_paid" class="toggle toggle-primary" {{ ($settings['notify_receivable_paid'] ?? true) ? 'checked' : '' }} />
                            </label>
                        </div>

                        <!-- Checkbox: Client Created -->
                        <div class="form-control">
                            <label class="label cursor-pointer justify-between">
                                <span class="label-text flex flex-col">
                                    <span class="font-medium">Clientes Cadastrados / Atualizados</span>
                                    <span class="text-xs text-base-content/60">Notificar quando novos clientes ou fornecedores forem incluídos nas ULOs.</span>
                                </span>
                                <input type="checkbox" name="notify_client_created" class="toggle toggle-primary" {{ ($settings['notify_client_created'] ?? true) ? 'checked' : '' }} />
                            </label>
                        </div>
                    </div>

                    <div class="card bg-base-200 p-6 rounded-lg space-y-4">
                        <h4 class="font-semibold text-base text-base-content">Preferências de Áudio</h4>
                        <div class="divider my-1"></div>

                        <!-- Checkbox: Sound Notification -->
                        <div class="form-control">
                            <label class="label cursor-pointer justify-between">
                                <span class="label-text flex flex-col">
                                    <span class="font-medium">Som de Notificação</span>
                                    <span class="text-xs text-base-content/60">Reproduzir um alerta sonoro discreto de sino ao receber um novo aviso.</span>
                                </span>
                                <input type="checkbox" name="sound_enabled" class="toggle toggle-secondary" {{ ($settings['sound_enabled'] ?? true) ? 'checked' : '' }} />
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-8">
                        <button type="submit" class="btn btn-primary px-8">Salvar Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
