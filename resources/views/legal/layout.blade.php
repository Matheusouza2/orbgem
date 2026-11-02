<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · OrbGem</title>
    <meta name="description" content="@yield('description')">
    <meta name="robots" content="index,follow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <style>
        :root { color-scheme: light dark; --bg:#f6f5fb; --fg:#1a1530; --muted:#6b6785; --brand:#3b2a8f; --deep:#170f3d; --card:#fff; --line:#e5e2f0; }
        @media (prefers-color-scheme: dark) { :root { --bg:#0B1220; --fg:#efecff; --muted:#9AA7BD; --brand:#8FB2FF; --deep:#efecff; --card:#111A2E; --line:#22304B; } }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
        main { max-width:760px; margin:0 auto; padding:32px 20px 64px; }
        header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:28px; }
        .brand { font-weight:700; letter-spacing:.2em; color:var(--deep); text-decoration:none; }
        nav a { color:var(--brand); margin-left:16px; font-size:14px; }
        article { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:28px 24px; }
        h1 { color:var(--deep); font-size:28px; line-height:1.2; margin:0 0 4px; }
        h2 { color:var(--deep); font-size:19px; margin:28px 0 6px; }
        p, li { color:var(--fg); } .muted { color:var(--muted); font-size:14px; }
        a { color:var(--brand); } ul { padding-left:22px; }
        footer { margin-top:24px; text-align:center; }
    </style>
</head>
<body>
<main>
    <header>
        <a class="brand" href="{{ url('/') }}">ORBGEM</a>
        <nav aria-label="Documentos legais">
            <a href="{{ url('/privacidade') }}">Privacidade</a>
            <a href="{{ url('/termos') }}">Termos</a>
            <a href="{{ url('/excluir-conta') }}">Excluir conta</a>
        </nav>
    </header>
    <article>@yield('content')</article>
    <footer class="muted">Dúvidas: <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a></footer>
</main>
</body>
</html>
