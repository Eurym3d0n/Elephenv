<?php
/**
 * Inner template for ValidationException
 *
 * @var \Elephenv\View\ViewRenderer $renderer
 * @var string $message
 * @var array<string, mixed> $violations
 */
?>
<section>
    <p class="message"><?= $renderer->e($message); ?></p>
</section>

<section>
    <h2 class="section-title">Rule Violations</h2>
    <table>
        <thead>
            <tr>
                <th>Variable</th>
                <th>Rule</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($violations as $v): ?>
            <?php $rule = is_object($v['rule']) ? ($v['rule']->value ?? (string) $v['rule']) : (string) $v['rule']; ?>
            <tr>
                <td class="var"><?= $renderer->e((string) $v['variable']); ?></td>
                <td class="rule"><?= $renderer->e($rule); ?></td>
                <td><?= $renderer->e((string) $v['message']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
