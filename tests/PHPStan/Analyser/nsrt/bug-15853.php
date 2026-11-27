<?php declare(strict_types = 1);

namespace Bug15853;

use function PHPStan\Testing\assertType;

function f(string $m): void {
	if (preg_match('#<(a)|(b)>#x', $m, $hash)) {
		assertType("array{non-falsy-string, '', 'b'}|array{non-falsy-string, 'a'}", $hash);
	}
	if (preg_match('~<(a)|(b)>~x', $m, $tilde)) {
		assertType("array{non-falsy-string, '', 'b'}|array{non-falsy-string, 'a'}", $tilde);
	}
}
