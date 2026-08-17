<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drenla Backend</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-black text-white">
    <main class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-16">
        <p class="text-eyebrow">Drenla Backend</p>
        <h1 class="mt-6 max-w-4xl text-5xl font-light leading-none tracking-[-0.04em] md:text-7xl">Content, delivery, proposals, and finance now belong in Laravel.</h1>
        <p class="mt-8 max-w-2xl text-body-large">This application is the long-term system of record for Drenla operations. The public marketing experience remains separate for now.</p>
        <div class="mt-12 flex flex-wrap gap-4">
            <a href="{{ route('admin.login') }}" class="inline-flex items-center border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">Enter Admin</a>
            <a href="/api/site/settings" class="inline-flex items-center border border-white/15 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white transition hover:border-white/30 hover:bg-white/[0.03]">View Public API</a>
        </div>
    </main>
</body>
</html>
