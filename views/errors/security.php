<?php
/**
 * Inner template for SecurityException
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var array<string, mixed> $context
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>

<?php if (!empty($context)): ?>
<section>
    <h2 class="section-title">Security Context</h2>
    <pre><?= $renderer->e(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
</section>
<?php endif; ?>
