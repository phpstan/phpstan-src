<?php

namespace Bug8886;

use PDO;
use function PHPStan\Testing\assertType;

function testPDOStatementGetIterator(): void {
	if (PHP_VERSION_ID < 80000) {
        echo "Test skipped: PDOStatement::getIterator is only available in PHP 8 and above.";
        return;
    }
    
	$pdo = new PDO('sqlite::memory:');
    $stmt = $pdo->query('SELECT 1');

	assertType('Iterator', $stmt->getIterator());
}
