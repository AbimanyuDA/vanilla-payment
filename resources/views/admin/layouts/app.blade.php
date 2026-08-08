<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Vanilla Royal Payment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --vr-primary: #f29923;
            --vr-secondary: #41281b;
            --vr-dark: #2c1810;
            --vr-gold: #ffdd79;
        }
        * { font-family: 'Poppins', sans-serif; }
        [data-theme="vanillaroyal"] { --p: 33 95% 54%; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'vr-primary': '#f29923',
                        'vr-secondary': '#41281b',
                        'vr-dark': '#2c1810',
                        'vr-gold': '#ffdd79',
                        'vr-cream': '#fef9f0',
                    }
                }
            }
        }
    </script>
    @stack('head')
</head>
<body style="background:#fef9f0; font-family:'Poppins',sans-serif;" class="min-h-screen">

<div class="drawer lg:drawer-open">
    <input id="drawer" type="checkbox" class="drawer-toggle">
    <div class="drawer-content flex flex-col min-h-screen">

        <!-- Navbar -->
        <div class="navbar shadow-sm sticky top-0 z-10" style="background:#fff; border-bottom:2px solid #f29923;">
            <div class="flex-none lg:hidden">
                <label for="drawer" class="btn btn-ghost btn-square">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </label>
            </div>
            <div class="flex-1">
                <span class="text-lg font-semibold" style="color:#41281b;">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="flex-none gap-2">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar placeholder">
                        <div class="rounded-full w-9 flex items-center justify-center text-white text-sm font-bold" style="background:#f29923;">
                            {{ substr(auth('admin')->user()->name, 0, 1) }}
                        </div>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-white rounded-box w-48 border border-gray-100">
                        <li class="menu-title"><span style="color:#41281b;">{{ auth('admin')->user()->name }}</span></li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left" style="color:#41281b;">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-1 p-4 lg:p-6">
            @if(session('success'))
                <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4"><span>{{ session('error') }}</span></div>
            @endif
            @if(session('info'))
                <div class="alert alert-info mb-4"><span>{{ session('info') }}</span></div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Sidebar -->
    <div class="drawer-side z-20">
        <label for="drawer" class="drawer-overlay"></label>
        <aside class="w-64 min-h-full flex flex-col" style="background: linear-gradient(180deg, #2c1810 0%, #41281b 60%, #2c1810 100%);">

            <!-- Sidebar Header / Logo -->
            <div class="p-5 border-b" style="border-color: rgba(242,153,35,0.3);">
                <div class="flex flex-col items-center gap-2">
                    <div class="rounded-xl px-4 py-3" style="background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.3);">
                        <img src="{{ asset('images/logo/logo.webp') }}" alt="Vanilla Royal Logo"
                            class="h-12 w-auto object-contain">
                    </div>
                    <div class="font-semibold text-xs tracking-widest" style="color:#ffdd79; letter-spacing:0.15em;">PAYMENT SYSTEM</div>
                </div>
            </div>

            <!-- Menu -->
            <ul class="menu p-3 flex-1 gap-1">
                <li>
                    <a href="{{ route('admin.dashboard') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all
                            {{ request()->routeIs('admin.dashboard') ? 'text-white' : '' }}"
                        style="{{ request()->routeIs('admin.dashboard') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.invoices.index') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.invoices*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Invoice
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.quotations.index') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.quotations*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Quotations
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.buyers.index') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.buyers*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a4 4 0 10-4-4"/></svg>
                        Buyers
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.products.index') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.products*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Products
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.index') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.reports*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Laporan
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.settings.company') }}"
                        class="rounded-lg font-medium text-sm flex items-center gap-3 px-3 py-2.5 transition-all"
                        style="{{ request()->routeIs('admin.settings*') ? 'background:#f29923; color:#2c1810;' : 'color:#fef9f0;' }}"
                        onmouseover="if(!this.style.background.includes('f29923')) { this.style.background='rgba(242,153,35,0.15)'; }"
                        onmouseout="if(!this.style.background.includes('f29923')) { this.style.background=''; }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Company Settings
                    </a>
                </li>
            </ul>

            <div class="p-4 text-center sidebar-footer">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <div class="flex-1 h-px" style="background: rgba(242,153,35,0.15);"></div>
                    <img src="{{ asset('images/logo/logo.webp') }}" alt="" class="h-4 w-auto" style="opacity: 0.35;">
                    <div class="flex-1 h-px" style="background: rgba(242,153,35,0.15);"></div>
                </div>
                <span class="text-xs font-medium" style="color: rgba(255,221,121,0.45);">© {{ date('Y') }} Vanilla Royal</span>
            </div>
        </aside>
    </div>
</div>

<style>
    /* Override DaisyUI primary → Vanilla Royal amber */
    .btn-primary, .btn.btn-primary {
        background: linear-gradient(135deg, #f29923, #e08810) !important;
        color: #2c1810 !important;
        border: none !important;
        font-weight: 600 !important;
    }
    .btn-primary:hover { opacity: 0.9 !important; }

    /* Override badge-info (teal) → amber untuk status Terkirim */
    .badge-info { background: #fef3c7 !important; color: #92400e !important; border: none !important; }
    .badge-success { background: #d1fae5 !important; color: #065f46 !important; border: none !important; }
    .badge-warning { background: #fed7aa !important; color: #c2410c !important; border: none !important; }
    .badge-error { background: #fee2e2 !important; color: #991b1b !important; border: none !important; }
    .badge-ghost { background: #f3f4f6 !important; color: #6b7280 !important; border: none !important; }
    .badge-neutral { background: #e5e7eb !important; color: #374151 !important; border: none !important; }

    /* Stat value colors override */
    .stat-value.text-info { color: #f29923 !important; }
    .stat-value.text-warning { color: #ef8c00 !important; }
    .stat-value.text-success { color: #16a34a !important; }
    .stat-value.text-primary { color: #f29923 !important; }

    /* Custom VR buttons */
    .btn-vr { background: linear-gradient(135deg, #f29923, #e08810) !important; color: #2c1810 !important; border: none; font-weight: 600; }
    .btn-vr:hover { opacity: 0.9 !important; }
    .btn-vr-outline { background: transparent !important; color: #f29923 !important; border: 1.5px solid #f29923 !important; font-weight: 600; }
    .btn-vr-outline:hover { background: #f29923 !important; color: #2c1810 !important; }

    /* Table & form */
    .card { border-radius: 12px !important; }
    .table th { background: #41281b !important; color: #ffdd79 !important; }
    .table tr:hover td { background: rgba(242,153,35,0.05) !important; }
    .input:focus, .select:focus, .textarea:focus { border-color: #f29923 !important; outline-color: #f29923 !important; box-shadow: 0 0 0 2px rgba(242,153,35,0.15) !important; }
    input[type=checkbox]:checked, input[type=radio]:checked { accent-color: #f29923; }

    /* Sidebar footer */
    .sidebar-footer { border-top: 1px solid rgba(242,153,35,0.2); }
</style>

@stack('scripts')
</body>
</html>
