<?php declare(strict_types = 1);

namespace Bug7734;

use function PHPStan\Testing\assertType;

function test(): void
{
	$file = fopen('somefile.txt','r');
	if ($file === false) { goto quickexit; }

	assertType('resource', $file);
	fgetcsv($file);

	quickexit:
	assertType('resource|false', $file);
	echo "Do something";
}
