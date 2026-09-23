<?php

spl_autoload_register(function (string $class): void {
    if ($class === 'Demo\\Zeta') {
        require __DIR__ . '/lib/Zeta.php';
    }
});
