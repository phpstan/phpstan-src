<?php

namespace Bug4708Two;

use function PHPStan\Testing\assertType;

/**
 * @param string[] $columns
 *
 * @return string[]|false
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
		assertType('array<string>', $result);
		if (!isset($result['bsw']))
		{
			assertType("array<mixed~'bsw', string>", $result);
			$result['bsw'] = 1;
			assertType("non-empty-array<1|string>&hasOffsetValue('bsw', 1)", $result);
		}
		else
		{
			assertType("array<string>&hasOffsetValue('bsw', string)", $result);
			$result['bsw'] = (int) $result['bsw'];
			assertType("non-empty-array<int|string>&hasOffsetValue('bsw', int)", $result);
		}

		assertType("non-empty-array<int|string>&hasOffsetValue('bsw', int)", $result);

		if (!isset($result['bew']))
		{
			assertType("non-empty-array<mixed~'bew', int|string>&hasOffsetValue('bsw', int)", $result);
			$result['bew'] = 5;
			assertType("non-empty-array<int|string>&hasOffsetValue('bew', 5)&hasOffsetValue('bsw', int)", $result);
		}
		else
		{
			assertType("non-empty-array<int|string>&hasOffsetValue('bew', int|string)&hasOffsetValue('bsw', int)", $result);
			$result['bew'] = (int) $result['bew'];
			assertType("non-empty-array<int|string>&hasOffsetValue('bew', int)&hasOffsetValue('bsw', int)", $result);
		}

		assertType("non-empty-array<int|string>&hasOffsetValue('bew', int)&hasOffsetValue('bsw', int)", $result);

		foreach (['utc', 'ssi'] as $field)
		{
			if (array_key_exists($field, $result))
			{
				$result[$field] = (int) $result[$field];
			}
		}

		assertType("non-empty-array<int|string>&hasOffsetValue('bew', int)&hasOffsetValue('bsw', int)", $result);
	}

	assertType('non-empty-array<int|string|false>', $result);

	return $result;
}
