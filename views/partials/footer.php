<?php
/**
 * Inner partial template for header
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $version
 * @var string $phpVersion
 */
?>
<footer class="footer">
    <span>
        Elephenv v<?= $renderer->e($version); ?>
    </span>

    <span>
        PHP <?= $renderer->e($phpVersion); ?>
    </span>
</footer>
