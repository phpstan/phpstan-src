<?php // lint >= 8.0

namespace Bug8886;

use PDO;
use function PHPStan\Testing\assertType;

function testPDOStatementGetIterator(): void {
	$pdo = new PDO('sqlite::memory:');
    $stmt = $pdo->query('SELECT 1');

	assertType('Iterator', $stmt->getIterator());
}
