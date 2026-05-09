<?php declare(strict_types = 1);

if (PHP_SAPI !== 'cli') {
    exit("This script can not run outside of shell\n");
}

foreach ($argv as $argument) {
    if (preg_match('/^--server-name=(.+)/', $argument, $matches)) {
        $foo = $matches[1];
        break;
    }
}
