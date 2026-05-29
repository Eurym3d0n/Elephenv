<?php
/**
 * Inner template for IntegrityException
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $source
 * @var string $message
 * @var array<string, mixed> $missing
 * @var array<string, mixed> $context
 * @var array<string, mixed> $templateLines
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>

<section>
    <h2 class="section-title">Missing Variables - <em><?= $renderer->e($source); ?></em></h2>
    <ul class="missing-list">
        <?php foreach ($missing as $name): ?>
            <li class="missing-item">
                <span class="missing-name"><?= $renderer->e($name); ?></span>
                <span class="badge">Missing</span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
