<?php // lint < 8.0

declare(strict_types = 1);

namespace Bug11889Php7;

use PDO;
use PDOStatement;
use function PHPStan\Testing\assertType;

function test(PDOStatement $stmt): void
{
	assertType('list<mixed>|false', $stmt->fetchAll(PDO::FETCH_ASSOC));
	assertType('list<mixed>|false', $stmt->fetchAll(PDO::FETCH_COLUMN));

	// Non-list modes
	assertType('array|false', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
}
