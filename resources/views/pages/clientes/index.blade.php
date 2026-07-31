<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-base-content leading-tight">
                {{ __('Clientes') }}
            </h2>
        </div>
    </x-slot>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- daisyUI Drawer Component -->
    <div class="drawer drawer-end min-h-screen">
        <input id="filter-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Drawer Main Content -->
        <div class="drawer-content py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">

                <!-- Action Bar & Filter Trigger -->
                <div class="bg-base-100 shadow-xl sm:rounded-lg p-4 mb-6 border border-base-200 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                    <!-- Quick Search Bar -->
                    <form id="quick-search-form" method="GET" action="{{ route('clientes') }}" class="flex-1 flex gap-2">
                        <div class="join w-full max-w-xl">
                            <input type="text" id="quick-search-input" name="search" value="{{ $search }}" placeholder="Buscar por Nome, Fantasia ou CNPJ..." class="input input-bordered input-md w-full join-item" autocomplete="off" />
                            <button type="submit" class="btn btn-primary join-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span class="hidden md:inline">Buscar</span>
                            </button>
                        </div>
                    </form>

                    <!-- Drawer Toggle Button -->
                    @php
                        $activeFiltersCount = 0;
                        if(!empty($search)) $activeFiltersCount++;
                        if(!empty($selectedUlos) && count($selectedUlos) < count($availableUlos)) $activeFiltersCount++;
                        if($tab !== 'adimplentes' && $faixa !== 'all') $activeFiltersCount++;
                    @endphp

                    <div class="flex items-center gap-2">
                        <label for="filter-drawer" class="btn btn-outline btn-primary gap-2 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Filtros</span>
                            <span id="drawer-filter-badge" class="badge badge-sm badge-primary text-white font-bold {{ $activeFiltersCount > 0 ? '' : 'hidden' }}">{{ $activeFiltersCount }}</span>
                        </label>
                    </div>
                </div>

                <!-- Dynamic Container loaded via jQuery / Blade Partial -->
                <div id="table-container">
                    @include('pages.clientes.partials.table-content')
                </div>
            </div>
        </div>

        <!-- Drawer Side (Filters Sidebar) -->
        <div class="drawer-side z-50">
            <label for="filter-drawer" aria-label="fechar filtros" class="drawer-overlay"></label>
            <div class="bg-base-100 min-h-full w-80 sm:w-96 p-6 text-base-content flex flex-col justify-between shadow-2xl border-l border-base-200">
                <form id="drawer-filter-form" method="GET" action="{{ route('clientes') }}" class="flex flex-col justify-between h-full">
                    <!-- Hidden State Inputs -->
                    <input type="hidden" name="tab" id="filter-tab" value="{{ $tab }}">
                    <input type="hidden" name="sort_by" id="filter-sort-by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" id="filter-sort-dir" value="{{ $sortDir }}">
                    <input type="hidden" name="page" id="filter-page" value="1">

                    <div>
                        <!-- Header -->
                        <div class="flex justify-between items-center pb-4 border-b border-base-200 mb-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <h3 class="font-bold text-lg text-base-content">Filtros da Lista</h3>
                            </div>
                            <label for="filter-drawer" class="btn btn-sm btn-circle btn-ghost">✕</label>
                        </div>

                        <div class="space-y-6">
                            <!-- Search Field -->
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Buscar Cliente</span></label>
                                <input type="text" id="drawer-search-input" name="search" value="{{ $search }}" placeholder="Nome, Fantasia ou CNPJ..." class="input input-bordered w-full" autocomplete="off" />
                            </div>

                            <!-- ULO Multi-select -->
                            <div class="form-control">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="label p-0"><span class="label-text font-bold">Filtrar por ULOs</span></label>
                                    <button type="button" id="toggle-all-ulos" class="text-xs text-primary font-semibold hover:underline">Marcar / Desmarcar Todos</button>
                                </div>
                                <div class="flex flex-col gap-2 p-3 bg-base-200 rounded-lg border border-base-300 max-h-60 overflow-y-auto">
                                    @foreach($availableUlos as $ulo)
                                        <label class="label cursor-pointer justify-start gap-3 hover:bg-base-300/50 p-1.5 rounded">
                                            <input type="checkbox" name="ulos[]" value="{{ $ulo }}" 
                                                class="checkbox checkbox-primary checkbox-sm filter-ulo-checkbox" 
                                                {{ in_array($ulo, $selectedUlos) ? 'checked' : '' }} />
                                            <span class="label-text font-medium">{{ $ulo }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Faixa de Atraso -->
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Faixa de Atraso</span></label>
                                <select name="faixa" id="filter-faixa-select" class="select select-bordered w-full" {{ $tab === 'adimplentes' ? 'disabled' : '' }}>
                                    <option value="all" {{ $faixa === 'all' ? 'selected' : '' }}>Todas as faixas</option>
                                    <option value="30" {{ $faixa === '30' ? 'selected' : '' }}>Faixa 30 (Até 30 dias)</option>
                                    <option value="90" {{ $faixa === '90' ? 'selected' : '' }}>Faixa 90 (31 a 90 dias)</option>
                                    <option value="120" {{ $faixa === '120' ? 'selected' : '' }}>Faixa 120 (91+ dias)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="pt-6 border-t border-base-200 mt-6 flex flex-col gap-2">
                        <button type="submit" class="btn btn-primary w-full gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Aplicar Filtros
                        </button>
                        <button type="button" id="drawer-clear-btn" class="btn btn-outline btn-ghost w-full">
                            Limpar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery Dynamic AJAX Handler -->
    <script>
        $(document).ready(function () {
            let searchTimer = null;

            // Helper to sync inputs between top bar and drawer
            function syncInputs(source) {
                if (source === 'quick') {
                    $('#drawer-search-input').val($('#quick-search-input').val());
                } else if (source === 'drawer') {
                    $('#quick-search-input').val($('#drawer-search-input').val());
                }
            }

            // Core AJAX Fetch Function
            function fetchFilteredData(customParams = {}, resetPage = false) {
                if (resetPage) {
                    $('#filter-page').val(1);
                }

                // Show loading spinner
                $('#table-loading-spinner').removeClass('hidden');

                // Serialize form data
                let formData = $('#drawer-filter-form').serializeArray();

                // Merge custom parameters
                $.each(customParams, function(key, val) {
                    // Remove existing parameter if present
                    formData = formData.filter(item => item.name !== key && item.name !== key + '[]');
                    if (Array.isArray(val)) {
                        val.forEach(v => formData.push({ name: key + '[]', value: v }));
                    } else if (val !== null && val !== undefined) {
                        formData.push({ name: key, value: val });
                    }
                });

                $.ajax({
                    url: "{{ route('clientes') }}",
                    type: "GET",
                    data: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (htmlResponse) {
                        $('#table-container').html(htmlResponse);

                        // Update browser URL seamlessly without page reload
                        let queryString = $.param(formData);
                        history.pushState(null, '', "{{ route('clientes') }}" + '?' + queryString);

                        // Update drawer filter counter badge
                        updateBadgeCounter();
                    },
                    error: function (xhr) {
                        console.error('Erro ao buscar dados:', xhr);
                    },
                    complete: function () {
                        $('#table-loading-spinner').addClass('hidden');
                    }
                });
            }

            // Update badge counter on drawer button
            function updateBadgeCounter() {
                let search = $('#quick-search-input').val().trim();
                let selectedUlos = $('.filter-ulo-checkbox:checked').length;
                let totalUlos = $('.filter-ulo-checkbox').length;
                let faixa = $('#filter-faixa-select').val();
                let currentTab = $('#filter-tab').val();

                let count = 0;
                if (search !== '') count++;
                if (selectedUlos > 0 && selectedUlos < totalUlos) count++;
                if (currentTab !== 'adimplentes' && faixa !== 'all') count++;

                let $badge = $('#drawer-filter-badge');
                if (count > 0) {
                    $badge.text(count).removeClass('hidden');
                } else {
                    $badge.addClass('hidden');
                }
            }

            // Live Search Debouncing (Quick Search & Drawer Search)
            $('#quick-search-input').on('keyup input', function () {
                syncInputs('quick');
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchFilteredData({}, true);
                }, 300);
            });

            $('#drawer-search-input').on('keyup input', function () {
                syncInputs('drawer');
            });

            // Prevent traditional form submission for quick search
            $('#quick-search-form').on('submit', function (e) {
                e.preventDefault();
                syncInputs('quick');
                fetchFilteredData({}, true);
            });

            // Drawer Form Submit (Applies all selected filters at once)
            $('#drawer-filter-form').on('submit', function (e) {
                e.preventDefault();
                syncInputs('drawer');
                fetchFilteredData({}, true);
                // Close drawer automatically after applying filters
                $('#filter-drawer').prop('checked', false);
            });

            // Toggle All ULOs inside drawer without immediate fetch
            $('#toggle-all-ulos').on('click', function () {
                let $checkboxes = $('.filter-ulo-checkbox');
                let allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                $checkboxes.prop('checked', !allChecked);
            });

            // Clear All Filters
            $(document).on('click', '#drawer-clear-btn, [data-action="clear-all"]', function () {
                $('#quick-search-input').val('');
                $('#drawer-search-input').val('');
                $('.filter-ulo-checkbox').prop('checked', true);
                $('#filter-faixa-select').val('all');

                fetchFilteredData({ clear: 1 }, true);
            });

            // Remove Individual Filter Tag
            $(document).on('click', '[data-action="remove-filter"]', function () {
                let filterType = $(this).data('filter');
                if (filterType === 'search') {
                    $('#quick-search-input').val('');
                    $('#drawer-search-input').val('');
                    fetchFilteredData({ search: '' }, true);
                } else if (filterType === 'faixa') {
                    $('#filter-faixa-select').val('all');
                    fetchFilteredData({ faixa: 'all' }, true);
                }
            });

            // Dynamic Tab Switch
            $(document).on('click', '[data-action="change-tab"]', function () {
                let newTab = $(this).data('tab');
                $('#filter-tab').val(newTab);

                // Disable or enable faixa select depending on tab
                if (newTab === 'adimplentes') {
                    $('#filter-faixa-select').prop('disabled', true);
                } else {
                    $('#filter-faixa-select').prop('disabled', false);
                }

                fetchFilteredData({ tab: newTab }, true);
            });

            // Dynamic Sort Click
            $(document).on('click', '[data-action="sort"]', function () {
                let sortBy = $(this).data('sort-by');
                let sortDir = $(this).data('sort-dir');

                $('#filter-sort-by').val(sortBy);
                $('#filter-sort-dir').val(sortDir);

                fetchFilteredData({ sort_by: sortBy, sort_dir: sortDir }, false);
            });

            // Intercept Pagination Links for AJAX Pagination
            $(document).on('click', '.ajax-pagination a', function (e) {
                e.preventDefault();
                let url = $(this).attr('href');
                if (!url) return;

                let urlParams = new URLSearchParams(url.split('?')[1]);
                let page = urlParams.get('page') || 1;

                $('#filter-page').val(page);
                fetchFilteredData({ page: page }, false);
            });

            // Handle browser Back/Forward navigation
            window.onpopstate = function () {
                let urlParams = new URLSearchParams(window.location.search);
                let page = urlParams.get('page') || 1;
                let search = urlParams.get('search') || '';

                $('#quick-search-input').val(search);
                $('#drawer-search-input').val(search);
                $('#filter-page').val(page);

                fetchFilteredData({}, false);
            };
        });
    </script>
</x-app-layout>
