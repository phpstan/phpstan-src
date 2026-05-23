<?php declare(strict_types = 1);

namespace Bug9748;

use function PHPStan\Testing\assertType;

function testKeys(\RedisArray $ra): void {
	$keys = $ra->keys('*');
	assertType('(array<string, list<string>>|false)', $keys);
	if ($keys === false) {
		return;
	}
	assertType('array<string, list<string>>', $keys);
	foreach ($keys as $host => $hostKeys) {
		assertType('string', $host);
		assertType('list<string>', $hostKeys);
		foreach ($hostKeys as $i => $hostKey) {
			assertType('int<0, max>', $i);
			assertType('string', $hostKey);
		}
	}
}

function testInfo(\RedisArray $ra): void {
	$info = $ra->info();
	assertType('(array<string, array<string, mixed>>|false)', $info);
	if ($info === false) {
		return;
	}
	assertType('array<string, array<string, mixed>>', $info);
}

function testMget(\RedisArray $ra): void {
	$values = $ra->mget(['key1', 'key2']);
	assertType('(list<mixed>|false)', $values);
}

function testScan(\RedisArray $ra): void {
	$iterator = null;
	$result = $ra->scan($iterator, 'node1');
	assertType('(list<string>|false)', $result);
}

function testHscan(\RedisArray $ra): void {
	$iterator = null;
	$result = $ra->hscan('myhash', $iterator);
	assertType('(array<string, string>|false)', $result);
}

function testSscan(\RedisArray $ra): void {
	$iterator = null;
	$result = $ra->sscan('myset', $iterator);
	assertType('(list<string>|false)', $result);
}

function testZscan(\RedisArray $ra): void {
	$iterator = null;
	$result = $ra->zscan('myzset', $iterator);
	assertType('(array<string, float>|false)', $result);
}
