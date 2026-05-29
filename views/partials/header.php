<?php
/**
 * Inner partial template for header
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var string $pageTitle
 * @var string $exceptionClass
 */
?>
<header class="header">
    <div class="header-main">
        <div class="icon-wrap" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="7" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>

        <div class="">
            <div class="eyebrow">
                <span>Environment Error</span>
            </div>

            <h1 class="title"><?= $renderer->e($pageTitle); ?></h1>

            <div class="subtitle"><?= $renderer->e($exceptionClass); ?></div>
        </div>
    </div>

    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme"></button>
</header>
