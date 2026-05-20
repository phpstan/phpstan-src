<?php

namespace CompactExtension;

use function PHPStan\Testing\assertType;

assertType('array{bar?: mixed}', compact(['foo' => 'bar']));

function (string $dolor): void {
	$foo = 'bar';
	$bar = 'baz';
	if (rand(0, 1)) {
		$lorem = 'ipsum';
	}
	assertType('array{foo: \'bar\', bar: \'baz\'}', compact('foo', ['bar']));
	assertType('array{foo: \'bar\', bar: \'baz\', lorem?: \'ipsum\'}', compact([['foo']], 'bar', 'lorem'));

	assertType('array<string, mixed>', compact($dolor));
	assertType('array<string, mixed>', compact([$dolor]));

	assertType('array{}', compact([]));
};

/**
 * @param array{'foo', 'bar', ...<int, string>} $names
 */
function unsealedNames(array $names): void {
	$foo = 'x';
	$bar = 'y';
	// The unsealed `<int, string>` extras are unknown further variable
	// names, so the result can't be enumerated as a sealed shape — it
	// must widen to the general `compact()` signature.
	assertType('array<string, mixed>', compact($names));
};
