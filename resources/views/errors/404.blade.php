<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Figtree', sans-serif;
            background: #fff;
            color: #111827;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .logo { font-size: 1.5rem; font-weight: 300; letter-spacing: -0.03em; margin-bottom: 3rem; color: #111827; text-decoration: none; }
        .code { font-size: 6rem; font-weight: 300; color: #e5e7eb; line-height: 1; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 400; margin-bottom: 0.75rem; }
        p { color: #6b7280; font-size: 0.9375rem; margin-bottom: 2rem; text-align: center; max-width: 380px; line-height: 1.6; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #111827; color: #fff;
            border-radius: 9999px; text-decoration: none;
            font-size: 0.875rem; font-weight: 500;
            transition: background 0.15s;
        }
        .btn:hover { background: #374151; }
        .links { margin-top: 2rem; display: flex; gap: 1.5rem; }
        .links a { font-size: 0.875rem; color: #9ca3af; text-decoration: none; }
        .links a:hover { color: #111827; }
    </style>
</head>
<body>
<a href="/" class="logo">{{ config('app.name') }}</a>
<div class="code">404</div>
<h1>Page not found</h1>
<p>The page you're looking for doesn't exist or may have been moved.</p>
<a href="/" class="btn">Back to home</a>
<div class="links">
    <a href="/properties">Browse properties</a>
    <a href="/contact">Contact us</a>
</div>
</body>
</html>
