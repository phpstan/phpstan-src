<?php

namespace DuplicateKeys;

class Foo
{

	private const EQ = '=';
	private const IS = '=';
	private const NEQ = '!=';

	public function doFoo()
	{
		$a = [
			null => true,
			NULL => false,
			1 => 'aaa',
			2 => 'bbb',
			3 => 'ccc',
			1 => 'aaa',
			1.0 => 'aaa',
			true => 'aaa',
			false => 'aaa',
			0 => 'aaa',
			PHPSTAN_DUPLICATE_KEY => 'aaa',
		];
	}

	public function doBar()
	{
		$array = [
			self::EQ => '= %s',
			self::IS => '= %s',
			self::NEQ => '!= %s',
		];
	}

	public function doIncrement()
	{
		$idx = 0;

		$foo = [
			$idx++ => 'test',
			$idx++ => 'presto',
		];
	}

	public function doIncrement2()
	{
		$idx = 0;

		$foo = [
			$idx++ => 'test',
			$idx++ => 'presto',
			$idx => 'lorem',
			$idx => 'ipsum',
		];
	}

	public function doWithoutKeys(int $int)
	{
		$foo = [
			1, // Key is 0
			0 => 2,
			100 => 3,
			'This key is ignored' => 42,
			4, // Key is 101
			10 => 5,
			6, // Key is 102
			101 => 7,
			102 => 8,
		];

		$foo2 = [
			'-42' => 1,
			2, // The key is -41
			0 => 3,
			-41 => 4,
		];

		$foo3 = [
			$int => 33,
			0 => 1,
			2, // Because of `$int` key, the key value cannot be known.
			1 => 3,
		];

		$foo4 = [
			1,
			2,
			3,
		];
	}

}
