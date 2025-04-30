<?php

namespace Bug8886;

use PDO;
use function PHPStan\Testing\assertType;

function testPDOStatementGetIterator(): void {
    $pdo = new PDO('sqlite::memory:');
    $stmt = $pdo->query('SELECT 1');

    if (PHP_VERSION_ID >= 80000) {
        // PHP 8 and above
        assertType('Iterator', $stmt->getIterator());
    } else {
        // Fallback for PHP 7.4
        assertType('Traversable', $stmt);
    }
}
