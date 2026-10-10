<?php // lint >= 8.0

namespace UnsetOffsetOnUnion;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{port?: int, path?: string}|false $parsed
	 */
	public function arrayOrFalse($parsed): void
	{
		unset($parsed['port']);
		assertType('array{path?: string}|false', $parsed);
	}

	/**
	 * @param array{port?: int, path?: string}|string $value
	 */
	public function arrayOrString($value): void
	{
		unset($value['port']);
		assertType('array{path?: string}', $value);
	}

	/**
	 * @param array{port?: int, path?: string}|null $value
	 */
	public function arrayOrNull($value): void
	{
		unset($value['port']);
		assertType('array{path?: string}|null', $value);
	}

	/**
	 * @param array<string, int>|int|false $value
	 */
	public function generalArray($value): void
	{
		unset($value['port']);
		assertType('array<string, int>|false', $value);
	}

	/**
	 * @param int|string $value
	 */
	public function nothingToUnset($value): void
	{
		unset($value['port']);
		assertType('*ERROR*', $value);
	}

	public function parseUrl(string $url): void
	{
		$parsed = parse_url($url);
		unset($parsed['port']);
		assertType('array{scheme?: string, host?: string, user?: string, pass?: string, path?: string, query?: string, fragment?: string}|false', $parsed);
		assertNativeType('array<mixed~\'port\', mixed>|false|null', $parsed);
	}

	/**
	 * @param array<string, int>|false $value
	 */
	public function nativeArrayOrFalse(array|false $value): void
	{
		unset($value['port']);
		assertNativeType('array<mixed~\'port\', mixed>|false', $value);
	}

	/**
	 * @param __benevolent<array{port?: int, path?: string}|false> $value
	 */
	public function benevolent($value): void
	{
		unset($value['port']);
		assertType('(array{path?: string}|false)', $value);
	}

	/**
	 * @param __benevolent<array{port?: int, path?: string}|false|null> $value
	 */
	public function benevolentStaysBenevolent($value): void
	{
		unset($value['port']);
		assertType('(array{path?: string}|false|null)', $value);
	}

	/**
	 * @param array{port?: int, path?: string}|bool $value
	 */
	public function arrayOrBool($value): void
	{
		unset($value['port']);
		assertType('array{path?: string}|false', $value);
	}

	/**
	 * @param array{port?: int, path?: string}|true $value
	 */
	public function arrayOrTrue($value): void
	{
		unset($value['port']);
		assertType('array{path?: string}', $value);
	}

	/**
	 * @param \ArrayAccess<string, int>|false $value
	 */
	public function arrayAccessOrFalse($value): void
	{
		unset($value['port']);
		assertType('ArrayAccess<string, int>|false', $value);
	}

	public function nativeArrayAccessOrFalse(\ArrayAccess|false $value): void
	{
		unset($value['port']);
		assertNativeType('ArrayAccess|false', $value);
	}

	/**
	 * @param \ArrayAccess<string, int>|array{port?: int, path?: string}|false $value
	 */
	public function arrayAccessOrArrayOrFalse($value): void
	{
		unset($value['port']);
		assertType('array{path?: string}|ArrayAccess<string, int>|false', $value);
	}

	/**
	 * @param \ArrayAccess<string, int>|string $value
	 */
	public function arrayAccessOrString($value): void
	{
		unset($value['port']);
		assertType('ArrayAccess<string, int>', $value);
	}

	/**
	 * @param \ArrayAccess<string, int>|bool $value
	 */
	public function arrayAccessOrBool($value): void
	{
		unset($value['port']);
		assertType('ArrayAccess<string, int>|false', $value);
	}

}
