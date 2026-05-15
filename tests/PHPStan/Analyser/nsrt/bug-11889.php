<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11889;

use PDO;
use PDOStatement;
use function PHPStan\Testing\assertType;

function test(PDOStatement $stmt): void
{
	// No mode argument - unknown default, stays as array
	assertType('array', $stmt->fetchAll());

	// Single-argument modes that return lists
	assertType('list', $stmt->fetchAll(PDO::FETCH_ASSOC));
	assertType('list', $stmt->fetchAll(PDO::FETCH_NUM));
	assertType('list', $stmt->fetchAll(PDO::FETCH_BOTH));
	assertType('list', $stmt->fetchAll(PDO::FETCH_OBJ));
	assertType('list', $stmt->fetchAll(PDO::FETCH_COLUMN));
	assertType('list', $stmt->fetchAll(PDO::FETCH_CLASS));
	assertType('list', $stmt->fetchAll(PDO::FETCH_NAMED));

	// Modes that return non-list arrays
	assertType('array', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
	assertType('array', $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC));
	assertType('array', $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC));

	// Multi-argument overload variants always return lists
	assertType('list', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
	assertType('list', $stmt->fetchAll(PDO::FETCH_CLASS, \stdClass::class));
	assertType('list', $stmt->fetchAll(PDO::FETCH_CLASS, \stdClass::class, []));
	assertType('list', $stmt->fetchAll(PDO::FETCH_FUNC, function () {
		return 'test';
	}));
}

/**
 * @return list<string>
 */
function get_cv_files(): array
{
	$pdo = new PDO("");
	$stmt = $pdo->prepare('SELECT `file` FROM `commonvoice`');
	$stmt->execute();

	return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
