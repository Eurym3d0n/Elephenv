<?php
/**
 * Inner template for FileNotFoundException.
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var string $path
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>

<section>
    <h2 class="section-title">File path</h2>
    <pre><?= $renderer->e($path); ?></pre>
</section>
