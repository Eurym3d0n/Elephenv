<?php
/**
 * Inner template for generic, unspecialised ElephenvException instances.
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var array<string, mixed> $context
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>
