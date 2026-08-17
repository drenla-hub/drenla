<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-black text-white">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-6">
        <div class="w-full border border-white/10 bg-black/90 p-8 backdrop-blur">
            <p class="text-eyebrow">Drenla Admin</p>
            <h1 class="mt-5 text-4xl font-light tracking-[-0.03em]">Sign in</h1>
            <p class="mt-3 text-sm leading-7 text-white/55">Use a staff account with an admin role.</p>

            @if ($errors->any())
                <div class="mt-6 border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white/80">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition placeholder:text-white/25 focus:border-white/40" required>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35" for="password">Password</label>
                    <input id="password" name="password" type="password" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition placeholder:text-white/25 focus:border-white/40" required>
                </div>
                <label class="flex items-center gap-3 text-sm text-white/45">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 border-white/15 bg-black text-white focus:ring-0">
                    Remember me
                </label>
                <button class="w-full border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">Sign in</button>
            </form>
        </div>
    </main>
</body>
</html>
