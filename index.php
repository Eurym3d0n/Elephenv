<?php
declare(strict_types=1);

use Elephenv\Elephenv;

require_once 'vendor/autoload.php';


Elephenv::load(__DIR__ . DIRECTORY_SEPARATOR . '.env');

Elephenv::checkIntegrity(
    __DIR__ . DIRECTORY_SEPARATOR . '.env.example',
);

print('<pre>');
print_r(Elephenv::all());
print('</pre>');
