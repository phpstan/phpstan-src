<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15789Variants;

use function PHPStan\Testing\assertType;

function mbDetectOrder(): void
{
	assertType('bool', mb_detect_order(encoding: 'UTF-8'));
	assertType('bool', mb_detect_order('UTF-8'));
	assertType('list<non-falsy-string>', mb_detect_order(encoding: null));
	assertType('list<non-falsy-string>', mb_detect_order(null));
	assertType('list<non-falsy-string>', mb_detect_order());
}

function strtokVariants(string $s): void
{
	assertType('non-empty-string|false', strtok(string: $s, token: ' '));
	assertType('non-empty-string|false', strtok(token: ' ', string: $s));
	assertType('non-empty-string|false', strtok($s, token: ' '));
	assertType('non-empty-string|false', strtok($s, ' '));
	assertType('non-empty-string|false', strtok(string: ' '));
	assertType('non-empty-string|false', strtok(' '));
}

function firstClassCallables(string $s): void
{
	$strtok = strtok(...);
	assertType('non-empty-string|false', $strtok(string: $s, token: ' '));
	$mbDetectOrder = mb_detect_order(...);
	assertType('bool', $mbDetectOrder(encoding: 'UTF-8'));
	assertType('list<non-falsy-string>', $mbDetectOrder(encoding: null));
}
