<?php declare(strict_types = 1);

namespace StaticVariablesConditionalTypes;

use DateTimeImmutable;
use Exception;
use function PHPStan\Testing\assertType;

function getCurrentSpecialEvent(string $key): string
{
	static $interimCacheKey = null;
	static $interimCacheValue = null;

	$now = new DateTimeImmutable();
	$cacheKey = sprintf('%s-%s', $key, $now->format('YmdHi'));

	if ($interimCacheKey !== $cacheKey) {
		$interimCacheKey = $cacheKey;
		$interimCacheValue = uniqid();
	}

	assertType('non-falsy-string', $interimCacheKey);
	assertType('non-falsy-string', $interimCacheValue);

	return $interimCacheValue;
}

function oneStatement(int $id): string
{
	static $cachedId = null, $cachedName = null;
	assertType('int|null', $cachedId);
	assertType('(lowercase-string&non-falsy-string)|null', $cachedName);

	if ($cachedId === $id) {
		assertType('lowercase-string&non-falsy-string', $cachedName);
		return $cachedName;
	}

	$cachedId = $id;
	$cachedName = 'name-' . $id;

	return $cachedName;
}

function compute(): string
{
	return 'x';
}

function userCodeInBetween(string $key): string
{
	static $cachedKey = null;
	static $cachedValue = null;

	if ($cachedKey !== $key) {
		$cachedKey = $key;
		// could call userCodeInBetween() again, which sees the key without the value
		$cachedValue = compute();
	}

	assertType('string|null', $cachedValue);

	return $cachedValue ?? '';
}

function throwInBetween(string $key): string
{
	static $cachedKey = null;
	static $cachedValue = null;

	if ($cachedKey !== $key) {
		$cachedKey = $key;
		if (rand(0, 1) === 1) {
			// the next call sees the key without the value
			throw new Exception();
		}
		$cachedValue = 'value';
	}

	assertType('\'value\'|null', $cachedValue);

	return $cachedValue ?? '';
}

function notAdjacent(string $key): string
{
	static $cachedKey = null;
	$prefix = 'x';
	static $cachedValue = null;

	if ($cachedKey !== $key) {
		$cachedKey = $key;
		$cachedValue = $prefix . $key;
	}

	assertType('non-falsy-string|null', $cachedValue);

	return $cachedValue ?? '';
}

function counter(): void
{
	static $count = 0;
	static $last = null;

	if ($count === 0) {
		assertType('null', $last);
	} else {
		assertType('\'x\'', $last);
	}

	$count++;
	$last = 'x';
}
