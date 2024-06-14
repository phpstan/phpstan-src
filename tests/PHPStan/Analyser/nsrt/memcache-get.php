<?php declare(strict_types = 1);

namespace MemcacheGet;

use Memcache;

use function PHPStan\Testing\assertType;

function (): void {
	$memcache = new Memcache();

	assertType('mixed', $memcache->get("key1"));
	assertType('array|false', $memcache->get(array("key1", "key2", "key3")));
};
