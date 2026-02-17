<?php declare(strict_types = 1);

namespace Bug9748;

use RedisArray;

use function PHPStan\Testing\assertType;

function test(RedisArray $ra): void
{
	$keys = $ra->keys('session:*');
	assertType('array<string, array<int, string>>|false', $keys);
}
