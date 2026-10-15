<?php

namespace OffsetOnMaybeAccessible;

class Foo
{

	/**
	 * @param array{host?: string}|false $value
	 */
	public function optionalKeyOrFalse($value): void
	{
		echo $value['host'];
	}

	/**
	 * @param array{host: string}|false $value
	 */
	public function requiredKeyOrFalse($value): void
	{
		echo $value['host'];
	}

	/**
	 * @param array{host?: string}|int $value
	 */
	public function optionalKeyOrInt($value): void
	{
		echo $value['host'];
	}

	public function parseUrl(string $url): void
	{
		$parsed = parse_url($url);
		echo $parsed['host'];
	}

	/**
	 * @param array{host?: string}|false $value
	 */
	public function guarded($value): void
	{
		if ($value === false) {
			return;
		}

		echo $value['host'];
	}

	/**
	 * @param array{host?: string}|false $value
	 */
	public function writes($value): void
	{
		$value['host'] = 'x';
		$value['host'] ??= 'x';
		$value['host'][] = 'x';
		$ref = &$value['host'];
		[$value['host']] = ['x'];
	}

	/**
	 * @param array{host: string}|\stdClass $value
	 */
	public function requiredKeyOrObject($value): void
	{
		echo $value['host'];
	}

	/**
	 * @param array{host: string}|iterable<int, int> $value
	 */
	public function requiredKeyOrIterable($value): void
	{
		echo $value['host'];
	}

}
