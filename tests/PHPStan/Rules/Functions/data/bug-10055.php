<?php

declare(strict_types=1);

namespace Bug10055;

function expectInt(int $param): void
{
}

function expectBool(bool $param): void
{
}

/**
 * @param 'value1'|'value2'|'value3' $param1
 * @param ($param1 is 'value3' ? bool : int) $param2
 */
function test(string $param1, int|bool $param2): void
{
	match ($param1) {
		'value1' => expectInt($param2),
		'value2' => expectInt($param2),
		'value3' => expectBool($param2),
	};
}
