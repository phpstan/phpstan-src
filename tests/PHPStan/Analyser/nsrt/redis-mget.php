<?php declare(strict_types = 1);

namespace RedisMget;

use Redis;

use function PHPStan\Testing\assertType;

function func(Redis $redis): void
{
	$values = $redis->mget(['key1', 'key2']);
	assertType('(array<string, mixed>|Redis|false)', $values);

	if ($values === false) {
		return;
	}

	assertType('(array<string, mixed>|Redis)', $values);
}
