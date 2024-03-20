<?php

namespace ParamOutBenevolentArrayKey;

class HelloWorld
{
	/**
	 * @param array<mixed> $matches
	 * @param-out array<int|string, list<string>> $matches
	 */
	public static function matchAllStrictGroups(array &$matches): int
	{
		$result = self::matchAll($matches);

		return $result;
	}

	/**
	 * @param array<mixed> $matches
	 * @param-out array<list<string>> $matches
	 */
	public static function matchAll(array &$matches): int
	{
		$matches = [['foo']];

		return 1;
	}
}

class HelloWorld2
{
	/**
	 * @param array<mixed> $matches
	 * @param-out array<list<string>> $matches
	 */
	public static function matchAllStrictGroups(array &$matches): int
	{
		$result = self::matchAll($matches);

		return $result;
	}

	/**
	 * @param array<mixed> $matches
	 * @param-out array<int|string, list<string>> $matches
	 */
	public static function matchAll(array &$matches): int
	{
		$matches = [['foo']];

		return 1;
	}
}
