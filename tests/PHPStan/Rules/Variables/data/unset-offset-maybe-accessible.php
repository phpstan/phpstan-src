<?php // lint >= 8.0

namespace UnsetOffsetMaybeAccessible;

class Foo
{

	/**
	 * @param array{port?: int, path?: string}|false $parsed
	 */
	public function arrayOrFalse($parsed): void
	{
		unset($parsed['port']);
	}

	/**
	 * @param array<string, int>|int $value
	 */
	public function arrayOrInt($value): void
	{
		unset($value['port']);
	}

	/**
	 * @param array<string, int>|null $value
	 */
	public function arrayOrNull($value): void
	{
		unset($value['port']);
	}

	/**
	 * @param array<string, int>|\ArrayAccess<string, int> $value
	 */
	public function arrayOrArrayAccess($value): void
	{
		unset($value['port']);
	}

	/**
	 * @param array<string, int> $value
	 */
	public function array($value): void
	{
		unset($value['port']);
	}

	public function object(\stdClass $value): void
	{
		unset($value['port']);
	}

	public function parseUrl(string $url): void
	{
		$parsed = parse_url($url);
		unset($parsed['port']);
	}

	public function explicitMixed(mixed $value): void
	{
		unset($value['port']);
	}

	public function implicitMixed($value): void
	{
		unset($value['port']);
	}

	public function mixedWithoutFalse(mixed $value): void
	{
		if ($value !== false) {
			unset($value['port']);
		}
	}

	public function jsonDecodedAfterIsset(string $json): void
	{
		$body = json_decode($json, true);
		if (isset($body['ttl'])) {
			unset($body['ttl']);
		}
	}

	/**
	 * @param array<string, int>|bool $bool
	 * @param array<string, int>|true $true
	 * @param array<string, int>|float $float
	 */
	public function scalars($bool, $true, $float): void
	{
		unset($bool['port']);
		unset($true['port']);
		unset($float['port']);
	}

	/**
	 * @param array{x: array<string, int>|false} $value
	 */
	public function nested(array $value): void
	{
		unset($value['x']['port']);
	}

	/**
	 * @param array<string, int>|\stdClass $value
	 */
	public function arrayOrObject($value): void
	{
		unset($value['port']);
	}

	/**
	 * @param \stdClass|int $value
	 */
	public function objectOrInt($value): void
	{
		unset($value['port']);
	}

}
