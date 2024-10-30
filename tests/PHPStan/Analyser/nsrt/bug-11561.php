<?php // lint >= 8.0

namespace Bug11561;

use function PHPStan\Testing\assertType;
use DateTime;

/** @param array{date: DateTime} $c */
function main(mixed $c): void{
	assertType('array{date: DateTime}', $c);
	$c['id']=1;
	assertType('array{date: DateTime, id: 1}', $c);

	$x = (function() use (&$c) {
		assertType("array{date: DateTime, id: 1, name?: 'ruud'}", $c);
		$c['name'] = 'ruud';
		assertType("array{date: DateTime, id: 1, name: 'ruud'}", $c);
		return 'x';
	})();

	assertType("array{date: DateTime, id: 1, name?: 'ruud'}", $c);
}


/** @param array{date: DateTime} $c */
function main2(mixed $c): void{
	assertType('array{date: DateTime}', $c);
	$c['id']=1;
	$c['name'] = 'staabm';
	assertType("array{date: DateTime, id: 1, name: 'staabm'}", $c);

	$x = (function() use (&$c) {
		assertType("array{date: DateTime, id: 1, name: 'ruud'|'staabm'}", $c);
		$c['name'] = 'ruud';
		assertType("array{date: DateTime, id: 1, name: 'ruud'}", $c);
		return 'x';
	})();

	assertType("array{date: DateTime, id: 1, name: 'ruud'|'staabm'}", $c);
}
