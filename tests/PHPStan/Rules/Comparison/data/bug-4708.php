<?php

namespace Bug4708;

use function PHPStan\Testing\assertType;

/**
 * @param string[] $columns
 *
 * @return string[]|bool
 */

function GenerateData($columns)
{
	$res = [];

	if (!empty($columns))
	{
		foreach ($columns as $col)
		{
			$res[$col] = $col;
		}
	}
	else
	{
		$res = false;
	}

	return $res;
}

/**
 * Get a bunch of configurational fields about an ASC.
 *
 * @return array
 */

function GetASCConfig()
{
	$columns = ['bsw', 'bew', 'utc', 'ssi'];

	$result = GenerateData($columns);

	if ($result === FALSE)
	{
		$result = ['result'  => FALSE,
			'dberror' => 'xyz'];
	}
	else
	{
		assertType('array<string>|true', $result);
		if (!isset($result['bsw']))
		{
			assertType('array<string>|true', $result);
			$result['bsw'] = 1;
			assertType("non-empty-array<1|string>&hasOffsetValue('bsw', 1)", $result);
		}
		else
		{
			assertType('array<string>&hasOffsetValue(\'bsw\', string)', $result);
			$result['bsw'] = (int) $result['bsw'];
			assertType('*NEVER*', $result); // should be non-empty-array<string|int>&hasOffsetValue('bsw', int)
		}

		assertType("non-empty-array<1|string>&hasOffsetValue('bsw', 1)", $result); // should have an int

		if (!isset($result['bew']))
		{
			$result['bew'] = 5;
		}
		else
		{
			$result['bew'] = (int) $result['bew'];
		}

		assertType("non-empty-array<int|string>&hasOffsetValue('bsw', 1)", $result); // missing bsw key

		foreach (['utc', 'ssi'] as $field)
		{
			if (array_key_exists($field, $result))
			{
				$result[$field] = (int) $result[$field];
			}
		}
	}

	assertType("non-empty-array<int|string|false>", $result);

	return $result;
}
