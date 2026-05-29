<?php
/**
 * Inner template for ParseException
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var string $line
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>

<section>
    <h2 class="section-title">Problematic Line</h2>
    <pre><?= $renderer->e($line); ?></pre>
</section>
