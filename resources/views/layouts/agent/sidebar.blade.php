@props(['collapsed' => false])

<aside id="default-sidebar"
       class="fixed top-0 left-0 z-40 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-all duration-300 shadow-xl"
       :style="{ width: sidebarCollapsed ? '80px' : '280px' }"
       x-data="agentSidebarComponent()"
       x-init="init()"
       x-show="true"
       x-cloak>
    
    <nav class="h-full flex flex-col bg-white dark:bg-gray-900">
        <!-- Header / Logo Area - Compact -->
        <div class="py-4 px-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between" :class="{ 'flex-col gap-3': sidebarCollapsed, 'flex-row': !sidebarCollapsed }">
                <!-- Logo and Title -->
                <div class="flex items-center gap-2" :class="{ 'flex-col text-center': sidebarCollapsed, 'flex-row': !sidebarCollapsed }">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                            <i class="bi bi-headset text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="transition-all duration-300" 
                         :class="{ 'hidden opacity-0': sidebarCollapsed, 'block opacity-100': !sidebarCollapsed }">
                        <span class="text-sm font-bold text-gray-800 dark:text-white whitespace-nowrap">Agent Portal</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Support Team</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1" :class="{ 'flex-row justify-center w-full': sidebarCollapsed, 'flex-row': !sidebarCollapsed }">
                    <!-- Dark/Light Mode Toggle -->
                    <button @click="toggleTheme()"
                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                            @mouseenter="sidebarCollapsed ? showTooltip($event, isDarkMode ? 'Light Mode' : 'Dark Mode') : null"
                            @mouseleave="hideTooltip()">
                        <i class="bi text-lg transition-all duration-300"
                           :class="{ 
                               'bi-moon-stars text-gray-600 dark:text-gray-400': !isDarkMode,
                               'bi-sun text-yellow-500': isDarkMode
                           }"></i>
                    </button>

                    <!-- Collapse Toggle -->
                    <button id="toggle-sidebar"
                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                            @click="toggleSidebar()"
                            @mouseenter="sidebarCollapsed ? showTooltip($event, 'Expand') : showTooltip($event, 'Collapse')"
                            @mouseleave="hideTooltip()">
                        <i class="bi text-lg text-gray-600 dark:text-gray-400 transition-transform duration-300" 
                           :class="{ 'bi-chevron-right': sidebarCollapsed, 'bi-chevron-left': !sidebarCollapsed }"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Items - AGENT SPECIFIC MODULES -->
        <div class="flex-1 px-3 py-4 overflow-y-auto">
            @php
                $openCount = \App\Models\Ticket::where('assigned_to', Auth::id())->where('status', 'open')->count();
                $inProgressCount = \App\Models\Ticket::where('assigned_to', Auth::id())->where('status', 'in_progress')->count();
                $resolvedCount = \App\Models\Ticket::where('assigned_to', Auth::id())->where('status', 'resolved')->count();
                $closedCount = \App\Models\Ticket::where('assigned_to', Auth::id())->where('status', 'closed')->count();
                $allTicketsCount = \App\Models\Ticket::where('assigned_to', Auth::id())->count();
                $notificationCount = auth()->user()->unreadNotifications()->count();
            @endphp
            <ul class="space-y-1">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('agent.dashboard') }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'dashboard',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'dashboard'
                        }"
                        @click="setActiveMenu('dashboard')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Dashboard') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-speedometer2 text-xl" :class="{ 'text-white': activeMenu === 'dashboard' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Dashboard</span>
                    </a>
                </li>

                <!-- Notifications -->
<li>
    <a href="{{ route('agent.notifications.index') }}"
        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
        :class="{
            'justify-center': sidebarCollapsed,
            'justify-start': !sidebarCollapsed,
            'bg-blue-600 text-white shadow-md': activeMenu === 'notifications',
            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'notifications'
        }"
        @click="setActiveMenu('notifications')"
        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Notifications') : null"
        @mouseleave="hideTooltip()">
        <i class="bi bi-bell text-xl" :class="{ 'text-white': activeMenu === 'notifications' }"></i>
        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Notifications</span>
        @php
            $notificationCount = auth()->user()->unreadNotifications()->count();
        @endphp
        @if($notificationCount > 0)
        <span class="ml-auto inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-[11px] font-semibold text-red-700 dark:bg-red-900/20 dark:text-red-200" :class="{ 'hidden': sidebarCollapsed }">{{ $notificationCount }}</span>
        @endif
    </a>
</li>

                <li class="pt-2"><div class="border-t border-gray-200 dark:border-gray-800"></div></li>

                <!-- TICKETS SECTION LABEL -->
                <li>
                    <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 py-2" :class="{ 'hidden': sidebarCollapsed, 'block': !sidebarCollapsed }">
                        Tickets Management
                    </div>
                </li>

                <!-- All Tickets -->
                <li>
                    <a href="{{ route('agent.tickets.index') }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'all-tickets',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'all-tickets'
                        }"
                        @click="setActiveMenu('all-tickets')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'All Tickets') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-ticket-detailed text-xl" :class="{ 'text-white': activeMenu === 'all-tickets' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">All Tickets</span>
                        @if($allTicketsCount > 0)
                        <span class="ml-auto bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-full px-2 py-0.5" :class="{ 'hidden': sidebarCollapsed }">{{ $allTicketsCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- Open Tickets -->
                <li>
                    <a href="{{ route('agent.tickets.index', ['status' => 'open']) }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'open',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'open'
                        }"
                        @click="setActiveMenu('open')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Open Tickets') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-envelope-open text-xl" :class="{ 'text-white': activeMenu === 'open' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Open Tickets</span>
                        @if($openCount > 0)
                        <span class="ml-auto bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-medium rounded-full px-2 py-0.5" :class="{ 'hidden': sidebarCollapsed }">{{ $openCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- In Progress -->
                <li>
                    <a href="{{ route('agent.tickets.in-progress') }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'in-progress',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'in-progress'
                        }"
                        @click="setActiveMenu('in-progress')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'In Progress') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-arrow-repeat text-xl" :class="{ 'text-white': activeMenu === 'in-progress' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">In Progress</span>
                        @if($inProgressCount > 0)
                        <span class="ml-auto bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 text-xs font-medium rounded-full px-2 py-0.5" :class="{ 'hidden': sidebarCollapsed }">{{ $inProgressCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- Resolved -->
                <li>
                    <a href="{{ route('agent.tickets.resolved') }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'resolved',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'resolved'
                        }"
                        @click="setActiveMenu('resolved')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Resolved Tickets') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-check-circle text-xl" :class="{ 'text-white': activeMenu === 'resolved' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Resolved</span>
                        @if($resolvedCount > 0)
                        <span class="ml-auto bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium rounded-full px-2 py-0.5" :class="{ 'hidden': sidebarCollapsed }">{{ $resolvedCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- Closed -->
                <li>
                    <a href="{{ route('agent.tickets.index', ['status' => 'closed']) }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-blue-600 text-white shadow-md': activeMenu === 'closed',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'closed'
                        }"
                        @click="setActiveMenu('closed')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Closed Tickets') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-archive text-xl" :class="{ 'text-white': activeMenu === 'closed' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Closed</span>
                        @if($closedCount > 0)
                        <span class="ml-auto bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full px-2 py-0.5" :class="{ 'hidden': sidebarCollapsed }">{{ $closedCount }}</span>
                        @endif
                    </a>
                </li>

                <li class="pt-2"><div class="border-t border-gray-200 dark:border-gray-800"></div></li>

                <!-- Create New Ticket -->
                <li>
                    <a href="{{ route('agent.tickets.create') }}"
                        class="flex items-center gap-3 p-2.5 rounded-lg transition-all duration-200"
                        :class="{
                            'justify-center': sidebarCollapsed,
                            'justify-start': !sidebarCollapsed,
                            'bg-green-600 text-white shadow-md': activeMenu === 'create-ticket',
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': activeMenu !== 'create-ticket'
                        }"
                        @click="setActiveMenu('create-ticket')"
                        @mouseenter="sidebarCollapsed ? showTooltip($event, 'Create Ticket') : null"
                        @mouseleave="hideTooltip()">
                        <i class="bi bi-plus-circle text-xl" :class="{ 'text-white': activeMenu === 'create-ticket' }"></i>
                        <span class="text-sm font-medium" :class="{ 'hidden': sidebarCollapsed }">Create Ticket</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- User Profile Section with Avatar Dropdown -->
        <div class="border-t border-gray-200 dark:border-gray-800 p-2 relative" x-data="{ profileOpen: false }">
            <!-- Profile Button -->
            <button @click="profileOpen = !profileOpen"
                    class="w-full flex items-center gap-3 p-2 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                    :class="{ 'justify-center': sidebarCollapsed, 'justify-start': !sidebarCollapsed }"
                    @mouseenter="sidebarCollapsed ? showTooltip($event, 'My Account') : null"
                    @mouseleave="hideTooltip()">
                
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?background=2563EB&color=fff&name=' . urlencode(Auth::user()->name) }}"
                         alt="{{ Auth::user()->name }}"
                         class="w-9 h-9 rounded-full object-cover shadow-md">
                </div>

                <!-- User Info (Hidden when collapsed) -->
                <div class="flex-1 text-left" :class="{ 'hidden': sidebarCollapsed }">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-0.5">Support Agent</p>
                </div>
                <i class="bi bi-chevron-down text-xs text-gray-500 transition-transform duration-200" 
                   :class="{ 'hidden': sidebarCollapsed, 'rotate-180': profileOpen }"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div x-show="profileOpen" 
                 x-cloak 
                 @click.away="profileOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="absolute bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-50"
                 :class="{ 'left-20 bottom-2 min-w-[200px]': sidebarCollapsed, 'left-3 right-3 bottom-14': !sidebarCollapsed }">
                
                <!-- My Profile option -->
                <a href="{{ route('agent.profile') }}" @click="profileOpen = false; setActiveMenu('profile')" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i class="bi bi-person-circle text-blue-600 text-base"></i>
                    <span>My Profile</span>
                </a>
                
                <!-- Change Password option -->
                <a href="{{ route('agent.profile.password') }}" @click="profileOpen = false" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i class="bi bi-key text-blue-600 text-base"></i>
                    <span>Change Password</span>
                </a>
                
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                
                <!-- LOGOUT Form -->
                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="bi bi-box-arrow-right text-red-600 text-base"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>

<script>
function agentSidebarComponent() {
    return {
        activeMenu: 'dashboard',
        tooltipTimeout: null,
        tooltipElement: null,
        isDarkMode: localStorage.getItem('darkMode') === 'true',
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        
        init() {
            // Create tooltip element
            this.tooltipElement = document.createElement('div');
            this.tooltipElement.className = 'fixed px-3 py-1.5 bg-gray-900 dark:bg-gray-700 text-white text-sm font-medium rounded-lg whitespace-nowrap shadow-xl z-[9999]';
            this.tooltipElement.style.display = 'none';
            this.tooltipElement.style.pointerEvents = 'none';
            document.body.appendChild(this.tooltipElement);
            
            // Listen for theme changes
            window.addEventListener('theme-toggle', (event) => {
                if (event.detail.darkMode !== undefined) {
                    this.isDarkMode = event.detail.darkMode;
                }
            });
            
            // Listen for sidebar toggle events
            window.addEventListener('sidebar-toggle', (event) => {
                if (event.detail.collapsed !== undefined) {
                    this.sidebarCollapsed = event.detail.collapsed;
                    this.hideTooltip();
                }
            });
            
            // Set active menu based on current route
            this.updateActiveMenuFromRoute();
        },
        
        updateActiveMenuFromRoute() {
            const currentPath = window.location.pathname;
            const searchParams = new URLSearchParams(window.location.search);
            const status = searchParams.get('status');
            
            if (currentPath.includes('/agent/dashboard') || currentPath === '/agent') {
                this.activeMenu = 'dashboard';
            } else if (currentPath.includes('/agent/tickets/create')) {
                this.activeMenu = 'create-ticket';
            } else if (currentPath.includes('/agent/tickets/in-progress')) {
                this.activeMenu = 'in-progress';
            } else if (currentPath.includes('/agent/tickets/resolved')) {
                this.activeMenu = 'resolved';
            } else if (status === 'closed') {
                this.activeMenu = 'closed';
            } else if (status === 'open') {
                this.activeMenu = 'open';
            } else if (currentPath.includes('/agent/tickets')) {
                this.activeMenu = 'all-tickets';
            } else if (currentPath.includes('/notifications')) {
                this.activeMenu = 'notifications';
            } else if (currentPath.includes('/profile')) {
                this.activeMenu = 'profile';
            }
        },
        
        setActiveMenu(menu) {
            this.activeMenu = menu;
        },
        
        toggleSidebar() {
            const newState = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', newState);
            window.dispatchEvent(new CustomEvent('sidebar-toggle', { 
                detail: { collapsed: newState } 
            }));
            this.hideTooltip();
        },
        
        toggleTheme() {
            this.isDarkMode = !this.isDarkMode;
            localStorage.setItem('darkMode', this.isDarkMode);
            if (this.isDarkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            const event = new CustomEvent('theme-toggle', { 
                detail: { darkMode: this.isDarkMode } 
            });
            window.dispatchEvent(event);
        },
        
        showTooltip(event, text) {
            if (!this.sidebarCollapsed) return;
            if (this.tooltipTimeout) clearTimeout(this.tooltipTimeout);
            
            this.tooltipTimeout = setTimeout(() => {
                const rect = event.currentTarget.getBoundingClientRect();
                this.tooltipElement.textContent = text;
                this.tooltipElement.style.display = 'block';
                this.tooltipElement.style.left = (rect.right + 8) + 'px';
                this.tooltipElement.style.top = (rect.top + rect.height / 2 - 20) + 'px';
            }, 300);
        },
        
        hideTooltip() {
            if (this.tooltipTimeout) clearTimeout(this.tooltipTimeout);
            if (this.tooltipElement) this.tooltipElement.style.display = 'none';
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 5px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: #1f2937;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>