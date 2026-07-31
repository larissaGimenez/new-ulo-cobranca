<x-app-layout>
    <!-- Include jQuery & SortableJS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <!-- daisyUI Drawer Component -->
    <div class="drawer drawer-end flex-1 flex flex-col min-h-0 overflow-hidden">
        <input id="filter-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Drawer Main Content -->
        <div class="drawer-content py-0 flex-1 flex flex-col min-h-0 overflow-hidden">
            <div class="w-full flex-1 flex flex-col min-h-0 overflow-hidden">

                <!-- Action Bar & Filter Trigger & View Mode Selector -->
                <div class="bg-base-100 shadow-sm rounded-xl p-2.5 mb-2.5 border border-base-200 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 shrink-0">
                    @php
                        $activeFiltersCount = 0;
                        if(!empty($search)) $activeFiltersCount++;
                        if(!empty($selectedUlos) && count($selectedUlos) < count($availableUlos)) $activeFiltersCount++;
                        if(!empty($selectedStages) && count($selectedStages) < count($dbColumns)) $activeFiltersCount++;
                        if($tab !== 'adimplentes' && $faixa !== 'all') $activeFiltersCount++;
                    @endphp

                    <!-- Quick Search Bar & Icon-Only Filtros Button Side-by-Side -->
                    <form id="quick-search-form" method="GET" action="{{ route('clientes') }}" class="flex-1 flex flex-wrap sm:flex-nowrap items-center gap-2">
                        <div class="relative w-full max-w-xl">
                            <input type="text" id="quick-search-input" name="search" value="{{ $search }}" placeholder="Buscar por Nome, Fantasia, CNPJ, E-mail ou Telefone..." class="input input-bordered input-sm w-full pl-9 pr-4" autocomplete="off" />
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-base-content/40">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Drawer Filter Trigger Button (Icon Only) -->
                        <label for="filter-drawer" class="btn btn-outline btn-primary btn-sm btn-square cursor-pointer shrink-0 relative" title="Filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span id="drawer-filter-badge" class="badge badge-xs badge-primary text-white font-bold absolute -top-1 -right-1 {{ $activeFiltersCount > 0 ? '' : 'hidden' }}">{{ $activeFiltersCount }}</span>
                        </label>
                    </form>

                    <!-- View Mode Selector (Kanban / Lista) -->
                    <div class="flex items-center gap-3">
                        <div class="join border border-base-300 rounded-lg p-0.5 bg-base-200">
                            <button type="button" data-action="change-view" data-view="kanban" class="join-item btn btn-sm {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-ghost' }} gap-2 font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Kanban</span>
                            </button>
                            <button type="button" data-action="change-view" data-view="lista" class="join-item btn btn-sm {{ $viewMode === 'lista' ? 'btn-primary' : 'btn-ghost' }} gap-2 font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                <span>Lista</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Container loaded via jQuery / Blade Partial -->
                <div id="table-container" class="flex-1 flex flex-col min-h-0 overflow-hidden">
                    @include('pages.clientes.partials.table-content')
                </div>
            </div>
        </div>

        <!-- Drawer Side (Filters Sidebar) -->
        <div class="drawer-side z-50">
            <label for="filter-drawer" aria-label="fechar filtros" class="drawer-overlay"></label>
            <div class="bg-base-100 h-screen max-h-screen w-80 sm:w-96 p-5 text-base-content flex flex-col justify-between shadow-2xl border-l border-base-200 overflow-hidden">
                <form id="drawer-filter-form" method="GET" action="{{ route('clientes') }}" class="flex flex-col h-full min-h-0 overflow-hidden">
                    <!-- Hidden State Inputs -->
                    <input type="hidden" name="view_mode" id="filter-view-mode" value="{{ $viewMode }}">
                    <input type="hidden" name="tab" id="filter-tab" value="{{ $tab }}">
                    <input type="hidden" name="sort_by" id="filter-sort-by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" id="filter-sort-dir" value="{{ $sortDir }}">
                    <input type="hidden" name="page" id="filter-page" value="1">

                    <!-- Drawer Header (Fixed at top) -->
                    <div class="flex justify-between items-center pb-3 border-b border-base-200 shrink-0 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content leading-tight">Filtros Avançados</h3>
                                <p class="text-[11px] text-base-content/50">Refine a listagem de clientes</p>
                            </div>
                        </div>
                        <label for="filter-drawer" class="btn btn-sm btn-circle btn-ghost text-base-content/60 hover:text-error">✕</label>
                    </div>

                    <!-- Scrollable Form Body -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar pr-1.5 space-y-5 min-h-0">
                        <!-- Search Field -->
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase tracking-wider text-base-content/70">Buscar Cliente</span></label>
                            <div class="relative">
                                <input type="text" id="drawer-search-input" name="search" value="{{ $search }}" placeholder="Nome, Fantasia, CNPJ, E-mail ou Fone..." class="input input-sm input-bordered w-full pl-8" autocomplete="off" />
                                <svg class="h-4 w-4 text-base-content/40 absolute left-2.5 top-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Etapa Multi-select -->
                        <div class="form-control">
                            <div class="flex justify-between items-center mb-1">
                                <label class="label p-0"><span class="label-text font-bold text-xs uppercase tracking-wider text-base-content/70">Etapas do Kanban</span></label>
                                <button type="button" id="toggle-all-stages" class="text-[11px] text-primary font-semibold hover:underline">Selecionar Todos</button>
                            </div>
                            <div class="flex flex-col gap-1.5 p-2 bg-base-200/60 rounded-xl border border-base-300">
                                @foreach($dbColumns as $col)
                                    <label class="label cursor-pointer justify-start gap-2.5 hover:bg-base-100 p-1.5 rounded-lg transition-colors">
                                        <input type="checkbox" name="stages[]" value="{{ $col->slug }}" 
                                            class="checkbox checkbox-primary checkbox-xs filter-stage-checkbox" 
                                            {{ in_array($col->slug, $selectedStages) ? 'checked' : '' }} />
                                        <span class="w-2.5 h-2.5 rounded-full {{ $col->dot_color }} shrink-0"></span>
                                        <span class="label-text font-medium text-xs uppercase truncate">{{ $col->title }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- ULO Multi-select -->
                        <div class="form-control">
                            <div class="flex justify-between items-center mb-1">
                                <label class="label p-0"><span class="label-text font-bold text-xs uppercase tracking-wider text-base-content/70">Unidades ULO</span></label>
                                <button type="button" id="toggle-all-ulos" class="text-[11px] text-primary font-semibold hover:underline">Selecionar Todos</button>
                            </div>
                            <div class="flex flex-col gap-1.5 p-2 bg-base-200/60 rounded-xl border border-base-300">
                                @foreach($availableUlos as $ulo)
                                    <label class="label cursor-pointer justify-start gap-2.5 hover:bg-base-100 p-1.5 rounded-lg transition-colors">
                                        <input type="checkbox" name="ulos[]" value="{{ $ulo }}" 
                                            class="checkbox checkbox-primary checkbox-xs filter-ulo-checkbox" 
                                            {{ in_array($ulo, $selectedUlos) ? 'checked' : '' }} />
                                        <span class="label-text font-medium text-xs">{{ $ulo }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Faixa de Atraso -->
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase tracking-wider text-base-content/70">Faixa de Atraso</span></label>
                            <select name="faixa" id="filter-faixa-select" class="select select-sm select-bordered w-full text-xs font-semibold" {{ $tab === 'adimplentes' ? 'disabled' : '' }}>
                                <option value="all" {{ $faixa === 'all' ? 'selected' : '' }}>Todas as faixas</option>
                                <option value="30" {{ $faixa === '30' ? 'selected' : '' }}>Faixa 30 (Até 30 dias)</option>
                                <option value="90" {{ $faixa === '90' ? 'selected' : '' }}>Faixa 90 (31 a 90 dias)</option>
                                <option value="120" {{ $faixa === '120' ? 'selected' : '' }}>Faixa 120 (91+ dias)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sticky Footer Buttons (Fixed at bottom) -->
                    <div class="pt-3 border-t border-base-200 mt-3 flex flex-col gap-2 shrink-0 bg-base-100">
                        <button type="submit" class="btn btn-primary btn-sm w-full gap-2 font-bold shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Aplicar Filtros
                        </button>
                        <button type="button" id="drawer-clear-btn" class="btn btn-ghost btn-xs w-full text-base-content/60 hover:text-error">
                            Limpar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery Dynamic AJAX Handler & Kanban Drag & Drop & Column Management -->
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

                        // Rebind Kanban drag events and restore collapsed states
                        initKanbanDragAndDrop();
                        restoreCollapsedColumns();
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
                let selectedStages = $('.filter-stage-checkbox:checked').length;
                let totalStages = $('.filter-stage-checkbox').length;
                let faixa = $('#filter-faixa-select').val();
                let currentTab = $('#filter-tab').val();

                let count = 0;
                if (search !== '') count++;
                if (selectedUlos > 0 && selectedUlos < totalUlos) count++;
                if (selectedStages > 0 && selectedStages < totalStages) count++;
                if (currentTab !== 'adimplentes' && faixa !== 'all') count++;

                let $badge = $('#drawer-filter-badge');
                if (count > 0) {
                    $badge.text(count).removeClass('hidden');
                } else {
                    $badge.addClass('hidden');
                }
            }

            // Live Search Debouncing (>= 3 chars or empty, 300ms delay)
            $('#quick-search-input').on('keyup input', function () {
                syncInputs('quick');
                let query = $(this).val().trim();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    if (query.length >= 3 || query.length === 0) {
                        fetchFilteredData({}, true);
                    }
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

            // Drawer Form Submit
            $('#drawer-filter-form').on('submit', function (e) {
                e.preventDefault();
                syncInputs('drawer');
                fetchFilteredData({}, true);
                $('#filter-drawer').prop('checked', false);
            });

            // Toggle All ULOs
            $('#toggle-all-ulos').on('click', function () {
                let $checkboxes = $('.filter-ulo-checkbox');
                let allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                $checkboxes.prop('checked', !allChecked);
            });

            // Toggle All Stages
            $('#toggle-all-stages').on('click', function () {
                let $checkboxes = $('.filter-stage-checkbox');
                let allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                $checkboxes.prop('checked', !allChecked);
            });

            // Clear All Filters
            $(document).on('click', '#drawer-clear-btn, [data-action="clear-all"]', function () {
                $('#quick-search-input').val('');
                $('#drawer-search-input').val('');
                $('.filter-ulo-checkbox').prop('checked', true);
                $('.filter-stage-checkbox').prop('checked', true);
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

                if (newTab === 'adimplentes') {
                    $('#filter-faixa-select').prop('disabled', true);
                } else {
                    $('#filter-faixa-select').prop('disabled', false);
                }

                fetchFilteredData({ tab: newTab }, true);
            });

            // Switch View Mode (Kanban / Lista)
            $(document).on('click', '[data-action="change-view"]', function () {
                let view = $(this).data('view');
                $('#filter-view-mode').val(view);

                $('[data-action="change-view"]').removeClass('btn-primary').addClass('btn-ghost');
                $(this).removeClass('btn-ghost').addClass('btn-primary');

                fetchFilteredData({ view_mode: view }, false);
            });

            // Dynamic Sort Click in List View
            $(document).on('click', '[data-action="sort"]', function () {
                let sortBy = $(this).data('sort-by');
                let sortDir = $(this).data('sort-dir');

                $('#filter-sort-by').val(sortBy);
                $('#filter-sort-dir').val(sortDir);

                fetchFilteredData({ sort_by: sortBy, sort_dir: sortDir }, false);
            });

            // Intercept Pagination Links
            $(document).on('click', '.ajax-pagination a', function (e) {
                e.preventDefault();
                let url = $(this).attr('href');
                if (!url) return;

                let urlParams = new URLSearchParams(url.split('?')[1]);
                let page = urlParams.get('page') || 1;

                $('#filter-page').val(page);
                fetchFilteredData({ page: page }, false);
            });

            // --- Per-Column Search & Sort in Kanban ---
            $(document).on('click', '.col-search-toggle', function (e) {
                e.stopPropagation();
                let $container = $(this).closest('.kanban-column-header').find('.col-search-container');
                $container.toggleClass('hidden');
                if (!$container.hasClass('hidden')) {
                    $container.find('.col-search-input').focus();
                }
            });

            $(document).on('click', '.col-search-clear', function (e) {
                e.stopPropagation();
                let $header = $(this).closest('.kanban-column-header');
                $header.find('.col-search-input').val('').trigger('input');
                $header.find('.col-search-container').addClass('hidden');
            });

            $(document).on('keyup input', '.col-search-input', function () {
                let query = $(this).val().toLowerCase().trim();
                let $column = $(this).closest('.kanban-column-wrapper');

                $column.find('.kanban-card').each(function () {
                    let name = $(this).data('name') || '';
                    if (name.includes(query)) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            });

            // Column Sort Dropdown Handler matching Screenshot
            $(document).on('click', '.col-sort-item', function (e) {
                e.preventDefault();
                let sortVal = $(this).data('sort');
                let sortText = $(this).text();
                let $wrapper = $(this).closest('.kanban-column-wrapper');
                let $header = $wrapper.find('.kanban-column-header');
                let $columnBody = $wrapper.find('.kanban-column-body');

                // Update active item styling
                $header.find('.col-sort-item').removeClass('active font-bold text-primary');
                $(this).addClass('active font-bold text-primary');
                $header.find('.col-sort-label').text(sortText);

                // Close daisyUI dropdown menu
                if (document.activeElement) {
                    document.activeElement.blur();
                }

                let $cards = $columnBody.find('.kanban-card').get();
                if ($cards.length === 0) return;

                $cards.sort(function (a, b) {
                    let nameA = $(a).data('name') || '';
                    let nameB = $(b).data('name') || '';
                    let amountA = parseFloat($(a).data('amount')) || 0;
                    let amountB = parseFloat($(b).data('amount')) || 0;
                    let atrasoA = parseInt($(a).data('atraso')) || 0;
                    let atrasoB = parseInt($(b).data('atraso')) || 0;

                    if (sortVal === 'name_asc') return nameA.localeCompare(nameB);
                    if (sortVal === 'name_desc') return nameB.localeCompare(nameA);
                    if (sortVal === 'divida_desc') return amountB - amountA;
                    if (sortVal === 'divida_asc') return amountA - amountB;
                    if (sortVal === 'atraso_desc') return atrasoB - atrasoA;
                    if (sortVal === 'atraso_asc') return atrasoA - atrasoB;
                    return 0;
                });

                $.each($cards, function (idx, item) {
                    $columnBody.append(item);
                });
            });

            // --- Column Collapse / Expand Handling ---
            $(document).on('click', '.col-toggle-collapse', function () {
                let $col = $(this).closest('.kanban-column-wrapper');
                let colId = $col.data('col-id');

                $col.toggleClass('is-collapsed w-80 w-14 min-w-[3.5rem]');
                if ($col.hasClass('is-collapsed')) {
                    $col.find('.kanban-column-header, .kanban-column-body').addClass('hidden');
                    $col.find('.collapsed-view-container').removeClass('hidden').addClass('flex');
                    saveCollapsedState(colId, true);
                } else {
                    $col.find('.kanban-column-header, .kanban-column-body').removeClass('hidden');
                    $col.find('.collapsed-view-container').addClass('hidden').removeClass('flex');
                    saveCollapsedState(colId, false);
                }
            });

            function saveCollapsedState(colId, isCollapsed) {
                let collapsed = JSON.parse(localStorage.getItem('kanban_collapsed_cols') || '[]');
                if (isCollapsed) {
                    if (!collapsed.includes(colId)) collapsed.push(colId);
                } else {
                    collapsed = collapsed.filter(id => id !== colId);
                }
                localStorage.setItem('kanban_collapsed_cols', JSON.stringify(collapsed));
            }

            function restoreCollapsedColumns() {
                let collapsed = JSON.parse(localStorage.getItem('kanban_collapsed_cols') || '[]');
                collapsed.forEach(function (colId) {
                    let $col = $('.kanban-column-wrapper[data-col-id="' + colId + '"]');
                    if ($col.length) {
                        $col.addClass('is-collapsed w-14 min-w-[3.5rem]').removeClass('w-80');
                        $col.find('.kanban-column-header, .kanban-column-body').addClass('hidden');
                        $col.find('.collapsed-view-container').removeClass('hidden').addClass('flex');
                    }
                });
            }

            // --- Add New Kanban Column ---
            $(document).on('click', '#btn-add-column-card, #btn-add-column', function () {
                let name = prompt("Digite o nome da nova etapa para o Kanban:");
                if (!name || name.trim() === '') return;

                $.ajax({
                    url: "{{ route('clientes.kanban.column.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        title: name.trim()
                    },
                    success: function () {
                        fetchFilteredData({}, false);
                    },
                    error: function (err) {
                        alert('Erro ao criar coluna: ' + (err.responseJSON?.error || 'Erro desconhecido'));
                    }
                });
            });

            // --- Delete Custom Kanban Column ---
            $(document).on('click', '.col-delete-btn', function (e) {
                e.stopPropagation();
                let slug = $(this).data('slug');
                if (!confirm("Deseja realmente excluir esta coluna?")) return;

                $.ajax({
                    url: "{{ route('clientes.kanban.column.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        slug: slug
                    },
                    success: function () {
                        fetchFilteredData({}, false);
                    },
                    error: function (err) {
                        alert(err.responseJSON?.error || 'Erro ao excluir coluna');
                    }
                });
            });

            // --- Move Card via 3 Dots Menu (Optimistic UI & Race Condition Protection) ---
            $(document).on('click', '[data-action="move-card"]', function (e) {
                e.preventDefault();
                e.stopPropagation();

                let cnpjCpf = String($(this).data('cnpj'));
                let targetStage = $(this).data('target-stage');

                if (document.activeElement) {
                    document.activeElement.blur();
                }

                let $card = $(this).closest('.kanban-card');
                if (!$card.length) {
                    $card = $('.kanban-card[data-cnpj="' + cnpjCpf + '"]');
                }
                if (!$card.length) return;

                let $sourceColumn = $card.closest('.kanban-column-body');
                let $targetColumn = $('.kanban-column-body[data-stage="' + targetStage + '"]');

                if (!$targetColumn.length) return;

                let sourceStage = $sourceColumn.data('stage');
                if (sourceStage === targetStage) return;

                // Optimistic UI: Instant visual move in DOM
                $targetColumn.find('.empty-placeholder').remove();
                $targetColumn.prepend($card);

                if ($sourceColumn.find('.kanban-card').length === 0) {
                    $sourceColumn.append('<div class="border-2 border-dashed border-base-300/80 rounded-xl p-6 text-center text-xs text-base-content/40 font-medium empty-placeholder my-auto">Sem cards nesta etapa</div>');
                }

                updateColumnSummaries();

                // AJAX Sync with Database Transaction & Lock Protection
                $.ajax({
                    url: "{{ route('clientes.update-stage') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        cnpj_cpf: cnpjCpf,
                        stage: targetStage
                    },
                    error: function (err) {
                        console.error('Erro ao mover card de etapa:', err);
                        // Rollback visual state if transaction or network fails
                        $sourceColumn.find('.empty-placeholder').remove();
                        $sourceColumn.prepend($card);
                        updateColumnSummaries();
                        alert('Não foi possível mover o cliente: ' + (err.responseJSON?.error || 'Erro de concorrência. Tente novamente.'));
                    }
                });
            });

            function initKanbanDragAndDrop() {
                // Drag & Drop disabled as requested. All stage transitions managed via 3-dots menu.
            }

            // Recalculate summary stats for columns and toggle delete button visibility
            function updateColumnSummaries() {
                $('.kanban-column-body').each(function () {
                    let colId = $(this).data('stage');
                    let count = $(this).find('.kanban-card:not(.hidden)').length;
                    let total = 0;

                    $(this).find('.kanban-card:not(.hidden)').each(function () {
                        total += parseFloat($(this).data('amount')) || 0;
                    });

                    let $wrapper = $('.kanban-column-wrapper[data-col-id="' + colId + '"]');
                    let $header = $wrapper.find('.kanban-column-header');

                    $wrapper.find('.col-count-badge').text(count);

                    let $summary = $('.column-summary-text[data-col="' + colId + '"]');
                    $summary.find('.col-total').text(total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    // Toggle Delete Column Button visibility dynamically
                    let $deleteBtn = $header.find('.col-delete-btn');
                    if (count > 0) {
                        $deleteBtn.addClass('hidden');
                    } else {
                        $deleteBtn.removeClass('hidden');
                    }
                });
            }

            // Initialize drag & drop and restore state on page load
            initKanbanDragAndDrop();
            restoreCollapsedColumns();
        });
    </script>

    <style>
        .sortable-column-ghost {
            opacity: 0.35 !important;
            border: 2px dashed #6366f1 !important;
            background-color: rgba(99, 102, 241, 0.1) !important;
            border-radius: 0.75rem !important;
        }
        .sortable-card-ghost {
            opacity: 0.45 !important;
            border: 2px dashed #6366f1 !important;
            background-color: rgba(99, 102, 241, 0.1) !important;
            border-radius: 0.75rem !important;
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.4) transparent;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.4);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.7);
        }
    </style>
</x-app-layout>
