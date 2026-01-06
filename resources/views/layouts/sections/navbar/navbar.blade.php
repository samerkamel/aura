@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Settings\Models\CompanySetting;
$containerNav = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
$companySettings = CompanySetting::getSettings();
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
        <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
          <a href="{{url('/')}}" class="app-brand-link">
            <span class="app-brand-logo demo">
              @if($companySettings->dashboard_logo_path)
                <img src="{{ $companySettings->dashboard_logo_url }}" alt="{{ $companySettings->company_name }}" style="height: 28px; width: auto; max-width: 150px; object-fit: contain;">
              @else
                @include('_partials.macros',["height"=>20])
              @endif
            </span>
            <span class="app-brand-text demo menu-text fw-bold">{{ $companySettings->company_name ?? config('variables.templateName') }}</span>
          </a>
          @if(isset($menuHorizontal))
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
              <i class="ti ti-x ti-md align-middle"></i>
            </a>
          @endif
        </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
          <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-md"></i>
          </a>
        </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        @if(!isset($menuHorizontal))
        <!-- Search -->
        <div class="navbar-nav align-items-center">
          <div class="nav-item navbar-search-wrapper mb-0">
            <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
              <i class="ti ti-search ti-md me-2 me-lg-4 ti-lg"></i>
              <span class="d-none d-md-inline-block text-muted fw-normal">Search (Ctrl+/)</span>
            </a>
          </div>
        </div>
        <!-- /Search -->
        @endif

       <ul class="navbar-nav flex-row align-items-center ms-auto">
          @if(isset($menuHorizontal))
            <!-- Search -->
            <li class="nav-item navbar-search-wrapper">
              <a class="nav-link btn btn-text-secondary btn-icon rounded-pill search-toggler" href="javascript:void(0);">
                <i class="ti ti-search ti-md"></i>
              </a>
            </li>
            <!-- /Search -->
          @endif

          <!-- Language -->
          <li class="nav-item dropdown-language dropdown">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class='ti ti-language rounded-circle ti-md'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{url('lang/en')}}" data-language="en" data-text-direction="ltr">
                  <span>English</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{url('lang/fr')}}" data-language="fr" data-text-direction="ltr">
                  <span>French</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" href="{{url('lang/ar')}}" data-language="ar" data-text-direction="rtl">
                  <span>Arabic</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item {{ app()->getLocale() === 'de' ? 'active' : '' }}" href="{{url('lang/de')}}" data-language="de" data-text-direction="ltr">
                  <span>German</span>
                </a>
              </li>
            </ul>
          </li>
          <!--/ Language -->


          @if($configData['hasCustomizer'] == true)
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown">
              <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class='ti ti-md'></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                    <span class="align-middle"><i class='ti ti-sun ti-md me-3'></i>Light</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                    <span class="align-middle"><i class="ti ti-moon-stars ti-md me-3"></i>Dark</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                    <span class="align-middle"><i class="ti ti-device-desktop-analytics ti-md me-3"></i>System</span>
                  </a>
                </li>
              </ul>
            </li>
            <!-- / Style Switcher -->
          @endif

          <!-- Quick links  -->
          <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill btn-icon dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <i class='ti ti-layout-grid-add ti-md'></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 22rem;">
              <div class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                  <h6 class="mb-0 me-auto">Shortcuts</h6>
                  <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon" id="shortcut-edit-toggle" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit shortcuts">
                    <i class="ti ti-pencil text-heading"></i>
                  </a>
                  <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon" id="shortcut-add-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Add shortcut">
                    <i class="ti ti-plus text-heading"></i>
                  </a>
                </div>
              </div>
              <div class="dropdown-shortcuts-list scrollable-container" id="shortcuts-container" style="max-height: 400px; overflow-y: auto;">
                <!-- Shortcuts will be loaded dynamically -->
                <div class="text-center py-4" id="shortcuts-loading">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
                <div class="text-center py-4 d-none" id="shortcuts-empty">
                  <i class="ti ti-layout-grid-add ti-xl text-muted mb-2"></i>
                  <p class="text-muted mb-0">No shortcuts yet</p>
                  <small class="text-muted">Click + to add shortcuts</small>
                </div>
              </div>
            </div>
          </li>
          <!-- Quick links -->

          <!-- PM Notifications -->
          <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2" id="pm-notifications-dropdown">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <span class="position-relative">
                <i class="ti ti-bell ti-md"></i>
                <span class="badge rounded-pill bg-danger badge-dot badge-notifications border pm-notification-badge d-none"></span>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 380px;">
              <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                  <h6 class="mb-0 me-auto">Notifications</h6>
                  <div class="d-flex align-items-center h6 mb-0">
                    <span class="badge bg-label-primary me-2 pm-notification-count">0 New</span>
                    <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon pm-mark-all-read" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read">
                      <i class="ti ti-mail-opened text-heading"></i>
                    </a>
                  </div>
                </div>
              </li>
              <li class="dropdown-notifications-list scrollable-container" style="max-height: 400px;">
                <ul class="list-group list-group-flush pm-notifications-list">
                  <li class="list-group-item text-center py-4 pm-notifications-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </li>
                  <li class="list-group-item text-center py-4 pm-notifications-empty d-none">
                    <i class="ti ti-bell-off text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">No notifications</p>
                  </li>
                </ul>
              </li>
              <li class="border-top">
                <div class="d-grid p-3">
                  <a class="btn btn-primary btn-sm d-flex justify-content-center align-items-center" href="{{ route('projects.pm-dashboard.notifications') }}">
                    <small class="align-middle">View all notifications</small>
                  </a>
                </div>
              </li>
            </ul>
          </li>
          <!--/ PM Notifications -->

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}" alt class="rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item mt-0" href="{{ route('profile.index') }}">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                      <div class="avatar avatar-online">
                        <div class="avatar-initial rounded-circle bg-primary text-white">
                          {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'JD' }}
                        </div>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0">
                        @if (Auth::check())
                          {{ Auth::user()->name }}
                        @else
                          John Doe
                        @endif
                      </h6>
                      <small class="text-muted">
                        @if (Auth::check() && method_exists(Auth::user(), 'getRoleNames'))
                          {{ Auth::user()->getRoleNames()->first() ? ucfirst(str_replace('-', ' ', Auth::user()->getRoleNames()->first())) : 'User' }}
                        @else
                          User
                        @endif
                      </small>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1 mx-n2"></div>
              </li>
              <li>
                <a class="dropdown-item" href="{{ route('profile.index') }}">
                  <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">My Profile</span>
                </a>
              </li>

              @if (Auth::User() && class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasTeamFeatures())
                <li>
                  <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                  <h6 class="dropdown-header">Manage Team</h6>
                </li>
                <li>
                  <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                  <a class="dropdown-item" href="{{ Auth::user() ? route('teams.show', Auth::user()->currentTeam->id) : 'javascript:void(0)' }}">
                    <i class="ti ti-settings ti-md me-3"></i><span class="align-middle">Team Settings</span>
                  </a>
                </li>
                @if(class_exists('\Laravel\Jetstream\Jetstream'))
                  @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                    <li>
                      <a class="dropdown-item" href="{{ route('teams.create') }}">
                        <i class="ti ti-user ti-md me-3"></i><span class="align-middle">Create New Team</span>
                      </a>
                    </li>
                  @endcan
                @endif

                @if (Auth::user()->allTeams()->count() > 1)
                  <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                  </li>
                  <li>
                    <h6 class="dropdown-header">Switch Teams</h6>
                  </li>
                  <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                  </li>
                @endif

                @if (Auth::user())
                  @foreach (Auth::user()->allTeams() as $team)
                  {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}

                  {{-- <x-switchable-team :team="$team" /> --}}
                  @endforeach
                @endif
              @endif
              <li>
                <div class="dropdown-divider my-1 mx-n2"></div>
              </li>
              @if (Auth::check())
                <li>
                  <div class="d-grid px-2 pt-2 pb-1">
                    <a class="btn btn-sm btn-danger d-flex" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                      <small class="align-middle">Logout</small>
                      <i class="ti ti-logout ms-2 ti-14px"></i>
                    </a>
                  </div>
                </li>
                <form method="POST" id="logout-form" action="{{ route('logout') }}">
                  @csrf
                </form>
              @else
                <li>
                  <div class="d-grid px-2 pt-2 pb-1">
                    <a class="btn btn-sm btn-danger d-flex" href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                      <small class="align-middle">Login</small>
                      <i class="ti ti-login ms-2 ti-14px"></i>
                    </a>
                  </div>
                </li>
              @endif
            </ul>
          </li>
          <!--/ User -->
        </ul>
      </div>

      <!-- Search Small Screens -->
      <div class="navbar-search-wrapper search-input-wrapper {{ isset($menuHorizontal) ? $containerNav : '' }} d-none">
        <input type="text" class="form-control search-input {{ isset($menuHorizontal) ? '' : $containerNav }} border-0" placeholder="Search..." aria-label="Search...">
        <i class="ti ti-x search-toggler cursor-pointer"></i>
      </div>
      <!--/ Search Small Screens -->
      @if(isset($navbarDetached) && $navbarDetached == '')
    </div>
    @endif
  </nav>
  <!-- / Navbar -->

  <!-- Add Shortcut Modal -->
  <div class="modal fade" id="addShortcutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Shortcut</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <input type="text" class="form-control" id="shortcut-search" placeholder="Search menu items...">
          </div>
          <div id="available-shortcuts-list" style="max-height: 400px; overflow-y: auto;">
            <div class="text-center py-4" id="available-shortcuts-loading">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /Add Shortcut Modal -->

  @once
  @push('pricing-script')
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const shortcutsContainer = document.getElementById('shortcuts-container');
    const shortcutsLoading = document.getElementById('shortcuts-loading');
    const shortcutsEmpty = document.getElementById('shortcuts-empty');
    const addShortcutBtn = document.getElementById('shortcut-add-btn');
    const editToggleBtn = document.getElementById('shortcut-edit-toggle');
    const addShortcutModal = new bootstrap.Modal(document.getElementById('addShortcutModal'));
    const availableShortcutsList = document.getElementById('available-shortcuts-list');
    const availableShortcutsLoading = document.getElementById('available-shortcuts-loading');
    const shortcutSearch = document.getElementById('shortcut-search');

    let shortcuts = [];
    let availableItems = [];
    let isEditMode = false;

    // Load shortcuts on page load
    loadShortcuts();

    // Toggle edit mode
    editToggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      isEditMode = !isEditMode;
      this.querySelector('i').className = isEditMode ? 'ti ti-check text-success' : 'ti ti-pencil text-heading';
      renderShortcuts();
    });

    // Open add shortcut modal
    addShortcutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      loadAvailableItems();
      addShortcutModal.show();
    });

    // Search filter
    shortcutSearch.addEventListener('input', function() {
      renderAvailableItems(this.value.toLowerCase());
    });

    function loadShortcuts() {
      shortcutsLoading.classList.remove('d-none');
      shortcutsEmpty.classList.add('d-none');

      fetch('/shortcuts', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        shortcuts = data.shortcuts || [];
        renderShortcuts();
      })
      .catch(error => {
        console.error('Error loading shortcuts:', error);
        shortcutsLoading.classList.add('d-none');
        shortcutsEmpty.classList.remove('d-none');
      });
    }

    function renderShortcuts() {
      shortcutsLoading.classList.add('d-none');

      if (shortcuts.length === 0) {
        shortcutsEmpty.classList.remove('d-none');
        // Remove any existing shortcut rows
        const existingRows = shortcutsContainer.querySelectorAll('.shortcut-row');
        existingRows.forEach(row => row.remove());
        return;
      }

      shortcutsEmpty.classList.add('d-none');

      // Remove existing shortcut rows
      const existingRows = shortcutsContainer.querySelectorAll('.shortcut-row');
      existingRows.forEach(row => row.remove());

      // Create rows with 2 items each
      for (let i = 0; i < shortcuts.length; i += 2) {
        const row = document.createElement('div');
        row.className = 'row row-bordered overflow-visible g-0 shortcut-row';

        // First item
        row.appendChild(createShortcutItem(shortcuts[i]));

        // Second item (if exists)
        if (shortcuts[i + 1]) {
          row.appendChild(createShortcutItem(shortcuts[i + 1]));
        } else {
          // Empty placeholder
          const emptyCol = document.createElement('div');
          emptyCol.className = 'dropdown-shortcuts-item col';
          row.appendChild(emptyCol);
        }

        shortcutsContainer.appendChild(row);
      }
    }

    function createShortcutItem(shortcut) {
      const item = document.createElement('div');
      item.className = 'dropdown-shortcuts-item col position-relative';

      const iconClass = shortcut.icon || 'ti ti-link';

      item.innerHTML = `
        <span class="dropdown-shortcuts-icon rounded-circle mb-3">
          <i class="${iconClass} ti-26px text-heading"></i>
        </span>
        <a href="${shortcut.url}" class="stretched-link">${shortcut.name}</a>
        <small>${shortcut.subtitle || ''}</small>
        ${isEditMode ? `<button class="btn btn-xs btn-danger position-absolute top-0 end-0 m-2 remove-shortcut" data-id="${shortcut.id}" style="z-index: 10;">
          <i class="ti ti-x ti-xs"></i>
        </button>` : ''}
      `;

      if (isEditMode) {
        const removeBtn = item.querySelector('.remove-shortcut');
        removeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          removeShortcut(shortcut.id);
        });
      }

      return item;
    }

    function removeShortcut(id) {
      fetch(`/shortcuts/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          shortcuts = shortcuts.filter(s => s.id !== id);
          renderShortcuts();
        }
      })
      .catch(error => console.error('Error removing shortcut:', error));
    }

    function loadAvailableItems() {
      availableShortcutsLoading.classList.remove('d-none');
      shortcutSearch.value = '';

      fetch('/shortcuts/available', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        availableItems = data.items || [];
        renderAvailableItems();
      })
      .catch(error => {
        console.error('Error loading available items:', error);
        availableShortcutsLoading.classList.add('d-none');
      });
    }

    function renderAvailableItems(searchTerm = '') {
      availableShortcutsLoading.classList.add('d-none');

      // Clear existing items except loading
      const existingItems = availableShortcutsList.querySelectorAll('.available-shortcut-item');
      existingItems.forEach(item => item.remove());

      const filteredItems = searchTerm
        ? availableItems.filter(item =>
            item.name.toLowerCase().includes(searchTerm) ||
            (item.subtitle && item.subtitle.toLowerCase().includes(searchTerm))
          )
        : availableItems;

      if (filteredItems.length === 0) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'available-shortcut-item text-center text-muted py-4';
        emptyMsg.textContent = searchTerm ? 'No matching items found' : 'All available items have been added';
        availableShortcutsList.appendChild(emptyMsg);
        return;
      }

      filteredItems.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = 'available-shortcut-item d-flex align-items-center p-3 border-bottom cursor-pointer hover-bg-light';
        itemEl.style.cursor = 'pointer';

        const iconClass = item.icon || 'ti ti-link';

        itemEl.innerHTML = `
          <span class="avatar avatar-sm bg-label-primary me-3 d-flex align-items-center justify-content-center">
            <i class="${iconClass}"></i>
          </span>
          <div class="flex-grow-1">
            <h6 class="mb-0">${item.name}</h6>
            <small class="text-muted">${item.subtitle || item.url}</small>
          </div>
          <i class="ti ti-plus text-primary"></i>
        `;

        itemEl.addEventListener('click', function() {
          addShortcut(item);
        });

        itemEl.addEventListener('mouseenter', function() {
          this.classList.add('bg-lighter');
        });

        itemEl.addEventListener('mouseleave', function() {
          this.classList.remove('bg-lighter');
        });

        availableShortcutsList.appendChild(itemEl);
      });
    }

    function addShortcut(item) {
      fetch('/shortcuts', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          name: item.name,
          url: item.url,
          icon: item.icon,
          subtitle: item.subtitle,
          slug: item.slug,
          required_roles: item.required_roles
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          shortcuts.push(data.shortcut);
          renderShortcuts();

          // Remove from available items
          availableItems = availableItems.filter(i => i.url !== item.url);
          renderAvailableItems(shortcutSearch.value.toLowerCase());

          // Close modal if no more items
          if (availableItems.length === 0) {
            addShortcutModal.hide();
          }
        } else {
          alert(data.message || 'Failed to add shortcut');
        }
      })
      .catch(error => {
        console.error('Error adding shortcut:', error);
        alert('Failed to add shortcut');
      });
    }
  });
  </script>
  @endpush
  @endonce

  @once
  @push('pricing-script')
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // PM Notifications
    const pmNotificationBadge = document.querySelector('.pm-notification-badge');
    const pmNotificationCount = document.querySelector('.pm-notification-count');
    const pmNotificationsList = document.querySelector('.pm-notifications-list');
    const pmNotificationsLoading = document.querySelector('.pm-notifications-loading');
    const pmNotificationsEmpty = document.querySelector('.pm-notifications-empty');
    const pmMarkAllReadBtn = document.querySelector('.pm-mark-all-read');
    const pmNotificationsDropdown = document.getElementById('pm-notifications-dropdown');

    let pmNotifications = [];
    let notificationsFetched = false;

    // Fetch notifications when dropdown is opened
    if (pmNotificationsDropdown) {
      pmNotificationsDropdown.addEventListener('show.bs.dropdown', function() {
        loadPMNotifications();
      });
    }

    // Mark all as read
    if (pmMarkAllReadBtn) {
      pmMarkAllReadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        markAllNotificationsRead();
      });
    }

    // Load notification count on page load
    loadNotificationCount();

    // Refresh notification count every 2 minutes
    setInterval(loadNotificationCount, 120000);

    function loadNotificationCount() {
      fetch('{{ route("projects.pm-dashboard.api.notifications") }}?count_only=1', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        const count = data.unread_count || 0;
        updateBadge(count);
      })
      .catch(error => {
        console.error('Error loading notification count:', error);
      });
    }

    function loadPMNotifications() {
      if (pmNotificationsLoading) pmNotificationsLoading.classList.remove('d-none');
      if (pmNotificationsEmpty) pmNotificationsEmpty.classList.add('d-none');

      // Clear existing notifications (except loading and empty states)
      const existingItems = pmNotificationsList.querySelectorAll('.pm-notification-item');
      existingItems.forEach(item => item.remove());

      fetch('{{ route("projects.pm-dashboard.api.notifications") }}', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        pmNotifications = data.notifications || [];
        notificationsFetched = true;
        renderPMNotifications();
        updateBadge(data.unread_count || 0);
      })
      .catch(error => {
        console.error('Error loading PM notifications:', error);
        if (pmNotificationsLoading) pmNotificationsLoading.classList.add('d-none');
        if (pmNotificationsEmpty) pmNotificationsEmpty.classList.remove('d-none');
      });
    }

    function renderPMNotifications() {
      if (pmNotificationsLoading) pmNotificationsLoading.classList.add('d-none');

      if (pmNotifications.length === 0) {
        if (pmNotificationsEmpty) pmNotificationsEmpty.classList.remove('d-none');
        return;
      }

      if (pmNotificationsEmpty) pmNotificationsEmpty.classList.add('d-none');

      pmNotifications.slice(0, 10).forEach(notification => {
        const item = createNotificationItem(notification);
        pmNotificationsList.appendChild(item);
      });
    }

    function createNotificationItem(notification) {
      const item = document.createElement('li');
      item.className = 'list-group-item list-group-item-action dropdown-notifications-item pm-notification-item' + (notification.read_at ? ' marked-as-read' : '');
      item.dataset.id = notification.id;

      const priorityColors = {
        'urgent': 'danger',
        'high': 'warning',
        'normal': 'primary',
        'low': 'secondary'
      };

      const typeIcons = {
        'followup_overdue': 'ti-alert-circle',
        'followup_upcoming': 'ti-calendar-event',
        'milestone_overdue': 'ti-flag-2',
        'milestone_upcoming': 'ti-flag',
        'payment_overdue': 'ti-currency-dollar-off',
        'payment_upcoming': 'ti-currency-dollar',
        'health_alert': 'ti-heart-broken',
        'stale_project': 'ti-clock-pause'
      };

      const priorityColor = notification.priority_color || priorityColors[notification.priority] || 'primary';
      const typeIcon = notification.type_icon || typeIcons[notification.type] || 'ti-bell';
      const timeAgo = notification.created_at; // Already formatted by server

      item.innerHTML = `
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-${priorityColor}">
                <i class="ti ${typeIcon}"></i>
              </span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h6 class="small mb-1">${escapeHtml(notification.title)}</h6>
            <small class="mb-1 d-block text-body">${escapeHtml(notification.message).substring(0, 80)}${notification.message.length > 80 ? '...' : ''}</small>
            <small class="text-muted">${timeAgo}</small>
          </div>
          <div class="flex-shrink-0 dropdown-notifications-actions">
            <a href="javascript:void(0);" class="dropdown-notifications-read" data-id="${notification.id}">
              <span class="badge badge-dot"></span>
            </a>
            <a href="javascript:void(0);" class="dropdown-notifications-archive" data-id="${notification.id}">
              <span class="ti ti-x"></span>
            </a>
          </div>
        </div>
      `;

      // Add click handler for the notification
      item.addEventListener('click', function(e) {
        if (e.target.closest('.dropdown-notifications-read') || e.target.closest('.dropdown-notifications-archive')) {
          return;
        }
        if (notification.action_url) {
          markNotificationRead(notification.id, function() {
            window.location.href = notification.action_url;
          });
        }
      });

      // Mark as read handler
      const readBtn = item.querySelector('.dropdown-notifications-read');
      if (readBtn) {
        readBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          markNotificationRead(notification.id);
        });
      }

      // Dismiss handler
      const archiveBtn = item.querySelector('.dropdown-notifications-archive');
      if (archiveBtn) {
        archiveBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          dismissNotification(notification.id);
        });
      }

      return item;
    }

    function markNotificationRead(id, callback) {
      fetch('{{ route("projects.pm-dashboard.notifications.mark-read") }}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ notification_id: id })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update local state
          const notification = pmNotifications.find(n => n.id === id);
          if (notification) {
            notification.read_at = new Date().toISOString();
          }

          // Update UI
          const item = pmNotificationsList.querySelector(`.pm-notification-item[data-id="${id}"]`);
          if (item) {
            item.classList.add('marked-as-read');
          }

          // Update badge
          loadNotificationCount();

          if (callback) callback();
        }
      })
      .catch(error => console.error('Error marking notification as read:', error));
    }

    function dismissNotification(id) {
      fetch('{{ route("projects.pm-dashboard.notifications.dismiss") }}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ notification_id: id })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove from local state
          pmNotifications = pmNotifications.filter(n => n.id !== id);

          // Remove from UI
          const item = pmNotificationsList.querySelector(`.pm-notification-item[data-id="${id}"]`);
          if (item) {
            item.remove();
          }

          // Update badge
          loadNotificationCount();

          // Show empty state if no more notifications
          if (pmNotifications.length === 0) {
            if (pmNotificationsEmpty) pmNotificationsEmpty.classList.remove('d-none');
          }
        }
      })
      .catch(error => console.error('Error dismissing notification:', error));
    }

    function markAllNotificationsRead() {
      fetch('{{ route("projects.pm-dashboard.notifications.mark-all-read") }}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update all local notifications
          pmNotifications.forEach(n => n.read_at = new Date().toISOString());

          // Update UI
          const items = pmNotificationsList.querySelectorAll('.pm-notification-item');
          items.forEach(item => item.classList.add('marked-as-read'));

          // Update badge
          updateBadge(0);
        }
      })
      .catch(error => console.error('Error marking all notifications as read:', error));
    }

    function updateBadge(count) {
      if (pmNotificationBadge) {
        if (count > 0) {
          pmNotificationBadge.classList.remove('d-none');
        } else {
          pmNotificationBadge.classList.add('d-none');
        }
      }
      if (pmNotificationCount) {
        pmNotificationCount.textContent = count + ' New';
      }
    }

    function formatTimeAgo(dateString) {
      const date = new Date(dateString);
      const now = new Date();
      const seconds = Math.floor((now - date) / 1000);

      if (seconds < 60) return 'Just now';
      if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
      if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
      if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
      return date.toLocaleDateString();
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  });
  </script>
  @endpush
  @endonce
