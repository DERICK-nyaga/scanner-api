<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PickUp Hub | Warehouse & Station Management')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        hub: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            800: '#0c4a6e',
                            900: '#082f49',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Global Authenticated User Context for Vue / PWA (Auth Directives Removed) -->
    <script>
        window.LaravelUser = {
            id: "{{ Auth::id() ?? 1 }}",
            name: "{{ Auth::user()?->name ?? 'System Administrator' }}",
            role: "{{ Auth::user()?->role ?? 'superadmin' }}",
            station_id: "{{ Auth::user()?->station_id ?? 1 }}"
        };
    </script>

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- APP CONTAINER -->
    <div id="app" class="flex-1 flex flex-col">

        <!-- TOP NAVIGATION BAR (ALWAYS VISIBLE) -->
        <header class="bg-slate-900 text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <!-- Logo & Station Info -->
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-hub-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.dashboard') }}" class="font-bold tracking-tight text-white text-base hover:text-hub-300 transition">pickuphub.xyz</a>
                            <span class="hidden sm:inline-block bg-slate-800 text-slate-300 text-[11px] px-2 py-0.5 rounded border border-slate-700 font-medium">Nakuru Hub #04</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Dispatch & Master Manifest Engine</p>
                    </div>
                </div>

                <!-- Navigation Links & User Profile Actions -->
                <div class="flex items-center space-x-6">
                    <nav class="hidden md:flex items-center space-x-4 text-xs font-medium text-slate-300">
                        <a href="{{ route('admin.stations.index') }}" class="hover:text-white transition">View Stations</a>
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition">Admin Dashboard</a>
                        <a href="/app/" class="hover:text-white transition">Scanner PWA</a>
                    </nav>

                    <div class="flex items-center space-x-3 border-l border-slate-700 pl-4">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-medium text-slate-200">
                                {{ auth()->user()?->email ?? 'admin@pickuphub.xyz' }}
                            </div>
                            <div class="flex items-center justify-end gap-1.5 mt-0.5">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-hub-500/20 text-hub-300 border border-hub-500/30 uppercase tracking-wider">
                                    {{ auth()->user()?->role ?? 'superadmin' }}
                                </span>
                            </div>
                        </div>

                        @if(Route::has('logout'))
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg bg-slate-800 hover:bg-red-900/40 text-slate-300 hover:text-red-400 border border-slate-700 transition" title="Sign Out">
                                <i class="fa-solid fa-power-off text-sm"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

            </div>
        </header>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="flex-1 flex flex-col w-full">
            
            <!-- Session Alerts -->
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex justify-between items-center shadow-sm">
                    <span>{{ session('success') }}</span>
                    <button type="button" class="text-emerald-600 hover:text-emerald-800 font-bold" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs flex justify-between items-center shadow-sm">
                    <span>{{ session('error') }}</span>
                    <button type="button" class="text-red-600 hover:text-red-800 font-bold" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            <!-- Yield Section Content -->
            @yield('content')

        </main>
    </div>

</body>
</html>