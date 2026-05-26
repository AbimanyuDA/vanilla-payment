<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Vanilla Royal Payment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Poppins', sans-serif; }</style>
</head>
<body style="background: linear-gradient(135deg, #2c1810 0%, #41281b 50%, #2c1810 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem;">

<div class="w-full max-w-sm">
    <!-- Logo & Brand -->
    <div class="text-center mb-6">
        <div class="inline-block rounded-2xl px-5 py-4 mb-2"
            style="background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div style="overflow:hidden; height:64px;">
                <img src="{{ asset('images/logo/logo.png') }}" alt="Vanilla Royal Logo"
                    style="height:107px; width:auto; display:block; margin:0 auto;">
            </div>
        </div>
        <p class="text-xs font-semibold tracking-[0.2em] mt-2" style="color:#ffdd79;">PAYMENT SYSTEM</p>
    </div>

    <!-- Card -->
    <div class="rounded-2xl p-6 shadow-2xl" style="background:#fff; border-top: 4px solid #f29923;">

        <h2 class="text-xl font-bold mb-1" style="color:#2c1810;">Selamat Datang</h2>
        <p class="text-sm mb-5" style="color:#41281b; opacity:0.7;">Login untuk mengelola invoice</p>

        @if($errors->any())
            <div class="alert alert-error mb-4 text-sm">
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error mb-4 text-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" style="color:#41281b;">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="input input-bordered w-full text-sm @error('email') input-error @enderror"
                    style="border-color: #d1a96a;"
                    placeholder="admin@vanillaroyal.com" required autofocus>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium mb-1" style="color:#41281b;">Password</label>
                <input type="password" name="password"
                    class="input input-bordered w-full text-sm"
                    style="border-color: #d1a96a;"
                    placeholder="••••••••" required>
            </div>
            <div class="flex items-center gap-2 mb-5">
                <input type="checkbox" name="remember" id="remember" class="checkbox checkbox-sm" style="accent-color:#f29923;">
                <label for="remember" class="text-sm cursor-pointer" style="color:#41281b;">Ingat saya</label>
            </div>
            <button type="submit" class="w-full py-3 rounded-lg font-semibold text-sm transition-all"
                style="background: linear-gradient(135deg, #f29923, #e08810); color:#2c1810; border:none; cursor:pointer;"
                onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Login
            </button>
        </form>
    </div>

    <p class="text-center text-xs mt-4" style="color: rgba(255,221,121,0.4);">© {{ date('Y') }} Vanilla Royal</p>
</div>

</body>
</html>
