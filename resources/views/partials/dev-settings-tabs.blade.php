<div class="tabs tabs-lifted mb-6">
    <a href="{{ route('dev-settings') }}" class="tab {{ request()->routeIs('dev-settings') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Contas a Receber</a>
    <a href="{{ route('dev-settings.clientes') }}" class="tab {{ request()->routeIs('dev-settings.clientes') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Clientes</a>
    <a href="{{ route('dev-settings.vinculos') }}" class="tab {{ request()->routeIs('dev-settings.vinculos') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Vínculos</a>
    <a href="{{ route('dev-settings.contas') }}" class="tab {{ request()->routeIs('dev-settings.contas') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Contas Correntes</a>
    <a href="{{ route('dev-settings.logs-webhooks') }}" class="tab {{ request()->routeIs('dev-settings.logs-webhooks') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Logs de Webhooks</a>
    <a href="{{ route('dev-settings.acompanhamento') }}" class="tab {{ request()->routeIs('dev-settings.acompanhamento') ? 'tab-active font-bold text-primary' : 'font-semibold' }}">Acompanhamento</a>
</div>
