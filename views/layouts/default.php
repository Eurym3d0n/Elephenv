<?php
/**
 * Default layout template for Elephenv HTML error pages.
 *
 * Available variables (all provided by ErrorViewModelFactory):
 * * $renderer \Elephenv\View\ViewRenderer - use $renderer->e() to escape output
 * * $pageTitle string
 * * $exceptionClass string
 * * $content string - rendered inner template (already escaped)
 * * $version string
 * * $phpVersion string
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $pageTitle
 * @var string $exceptionClass
 * @var string $content
 * @var string $version
 * @var string $phpVersion
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $renderer->e($title ?? 'Elephenv'); ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --font-body: "Inter", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --font-mono: "JetBrains Mono", ui-monospace, "Cascadia Code", Menlo, Consolas, monospace;
            --space-1: 0.25rem;
            --space-2: 0.50rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.50rem;
            --space-7: 1.75rem;
            --space-8: 2rem;
            --space-9: 2.25rem;
            --space-10: 2.50rem;
            --radius-sm: 0.50rem;
            --radius-md: 0.875rem;
            --radius-lg: 1.25rem;
            --radius-full: 999px;
            --shadow-lg: 0 20px 60px rgba(15, 23, 42, .12);
            --transition: 180ms cubic-bezier(.16, 1, .3, 1);
            --bg: #f5f7fb;
            --surface: rgba(255, 255, 255, .84);
            --surface-2: #fff;
            --border: rgba(15, 23, 42, .09);
            --border-strong: rgba(15, 23, 42, .14);
            --text: #0f172a;
            --muted: #64748b;
            --accent: #dc2626;
            --accent-soft: rgba(220, 38, 38, .08);
            --warning-bg: #fff7ed;
            --warning-border: #fb923c;
            --warning-text: #9a3412;
            --badge-bg: rgba(220, 38, 38, .1);
            --badge-text: #b91c1c;
            --table-head: #f8fafc;
            --code-bg: #f8fafc;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #020617;
                --surface: rgba(15, 23, 42 .88);
                --surface-2: #0f172a;
                --surface-3: #111827;
                --border: rgba(148, 163, 184, .18);
                --border-strong: rgba(148, 163, 184, .24);
                --text: #e5edf7;
                --muted: #94a3b8;
                --accent: #f87171;
                --accent-soft: rgba(248, 113, 113, .12);
                --warning-bg: rgba(124, 45, 18, .22);
                --warning-border: #ea580c;
                --warning-text: #fdba74;
                --badge-bg: rgba(248, 113, 113, .14);
                --badge-text: #fecaca;
                --table-head: #111827;
                --code-bg: #020617;
                --shadow-lg: 0 24px 64px rgba(0, 0, 0, .45);
            }
        }

        [data-theme="dark"] {
            --bg: #020617;
            --surface: rgba(15, 23, 42 .88);
            --surface-2: #0f172a;
            --surface-3: #111827;
            --border: rgba(148, 163, 184, .18);
            --border-strong: rgba(148, 163, 184, .24);
            --text: #e5edf7;
            --muted: #94a3b8;
            --accent: #f87171;
            --accent-soft: rgba(248, 113, 113, .12);
            --warning-bg: rgba(124, 45, 18, .22);
            --warning-border: #ea580c;
            --warning-text: #fdba74;
            --badge-bg: rgba(248, 113, 113, .14);
            --badge-text: #fecaca;
            --table-head: #111827;
            --code-bg: #020617;
            --shadow-lg: 0 24px 64px rgba(0, 0, 0, .45);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            color-scheme: light dark;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            font-family: var(--font-body);
            color:var(--text);
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.08), transparent 28%),
                radial-gradient(circle at top right, rgba(239, 68, 68, 0.08), transparent 28%),
                var(--bg);
            line-height: 1.6;
        }

        .shell {
            width: min(100%, 72rem);
            margin-inline: auto;
            padding: clamp(1rem, 2vw, 1.5rem);
            min-height: 100dvh;
            display: grid;
            align-items: center;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(16px);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: clamp(1rem, 2vw, 1.5rem);
            background: linear-gradient(180deg, var(--accent-soft), transparent);
            border-bottom: 1px solid var(--border);
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            min-width: 0;
        }

        .icon-wrap {
            display: grid;
            place-items: center;
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 65%, white));
            color: white;
            flex: 0 0 auto;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .2rem;
            color: var(--muted);
            font-size: .8rem;
            letter-spacing: .08rem;
            text-transform: uppercase;
        }

        .title {
            margin: 0;
            font-size: clamp(1.125rem, 1rem + .75vw, 1.6rem);
            line-height: 1.2;
        }

        .subtitle {
            margin-top: .25rem;
            color: var(--muted);
            font-family: var(--font-mono);
            font-size: .9rem;
            word-break: break-word;
        }

        .theme-toggle {
            appearance: none;
            border: 1px solid var(--border-strong);
            background: var(--surface-2);
            color: var(--text);
            border-radius: var(--radius-full);
            min-width: 44px;
            min-height: 44px;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            transition: transform var(--transition), border-color var(--transition), background var(--transition);
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
        }

        .theme-toggle:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }

        .content {
            padding: clamp(1rem, 2vw, 2rem);
        }

        .message {
            margin: 0 0 var(--space-8);
            padding: 1rem 1.125rem;
            border: 1px solid color-mix(in srgb, var(--accent) 24%, var(--border));
            border-left: 4px solid var(--accent);
            border-radius: var(--radius-md);
            background: var(--accent-soft);
            font-family: var(--font-mono);
            overflow-wrap: anywhere;
        }

        .section-title {
            margin: 0 0 .85rem;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        th,
        td {
            padding: .85rem 1rem;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--table-head);
            color: var(--muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .var {
            color: var(--accent);
            font-family: var(--font-mono);
            font-weight: 700;
        }

        .rule {
            color: var(--muted);
            font-family: var(--font-mono);
        }

        .missing-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: .75rem;
        }

        .missing-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .9rem 1rem;
            border: 1px solid color-mix(in srgb, var(--warning-border) 45%, var(--border));
            border-radius: var(--radius-md);
            background: var(--warning-bg);
        }

        .missing-name {
            font-family: var(--font-mono);
            color: var(--warning-text);
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .28rem .6rem;
            border-radius: var(--radius-full);
            background: var(--badge-bg);
            color: var(--badge-text);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        pre {
            margin: 0;
            padding: 1rem 1.125rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--code-bg);
            color: var(--text);
            font-family: var(--font-mono);
            font-size: .92rem;
            overflow-x: auto;
        }

        footer {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem clamp(1rem, 2vw, 2rem) 1.25rem;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: .82rem;
        }

        @media (max-width: 720px) {
            .header,
            .footer,
            .missing-item {
                align-items: flex-start;
            }

            .header,
            .footer {
                flex-direction: column;
            }

            th:nth-child(2),
            td:nth-child(2) {
                display: none;
            }

            .theme-toggle {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="card" role="alert" aria-live="assertive">
            <?= $renderer->partial('partials/header.php', [
                'pageTitle' => $pageTitle ?? 'Elephenv - Environment Error',
                'exceptionClass' => $exceptionClass ?? 'Error',
            ]); ?>

            <main class="content">
                <?= $content; ?>
            </main>

            <?= $renderer->partial('partials/footer.php', [
                'version' => $version ?? 'dev',
                'phpVersion' => $phpVersion ?? PHP_VERSION,
            ]); ?>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
    <script>
        $(() => {
            const $root = $('html');
            const $button = $('[data-theme-toggle]');

            let mode = window.matchMedia('[prefers-color-scheme: dark]').matches ? 'dark' : 'light';

            $root.attr('data-theme', mode);

            const update = () => {
                $button.attr('aria-label', mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
                $button.html(mode === 'dark' ? '☀️' : '🌑');
            };

            update();

            $button.on('click', () => {
                mode = mode === 'dark' ? 'light' : 'dark';
                $root.attr('data-theme', mode);
                update();
            });
        });
    </script>
</body>
</html>
