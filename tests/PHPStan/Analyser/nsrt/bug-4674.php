<?php declare(strict_types = 1);

namespace Bug4674;

use function PHPStan\Testing\assertType;

/**
 * @return string|false
 */
function string_or_false()
{
	if (rand(1,2)==1)
		return "string";
	return false;
}

function takes_string(string $s): void
{
	echo $s;
}

function test(): void
{
	$a = string_or_false();
	if ($a === false)
		goto end;

	assertType('string', $a);
	takes_string($a);

	end:
	assertType('string|false', $a);
	echo "finished";
}
