<?php

namespace Bug12927;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list<array{abc: string}> $list
	 * @return list<array<string>>
	 */
	public function sayHello(array $list): array
	{
		foreach($list as $k => $v) {
			unset($list[$k]['abc']);
			assertType('non-empty-list<array{}|array{abc: string}>', $list);
			assertType('array{}|array{abc: string}', $list[$k]);
		}
		return $list;
	}

	/**
	 * @param list<array<string, string>> $list
	 */
	public function sayFoo(array $list): void
	{
		foreach($list as $k => $v) {
			unset($list[$k]['abc']);
			assertType('non-empty-list<array<string, string>>', $list);
			assertType('array<string, string>', $list[$k]);
		}
		assertType('list<array<string, string>>', $list);
	}

	/**
	 * @param list<array<string, string>> $list
	 */
	public function sayFoo2(array $list): void
	{
		foreach($list as $k => $v) {
			$list[$k]['abc'] = 'world';
			assertType("non-empty-list<non-empty-array<string, string>&hasOffsetValue('abc', 'world')>", $list);
			assertType("non-empty-array<string, string>&hasOffsetValue('abc', 'world')", $list[$k]);
		}
		assertType("list<non-empty-array<string, string>&hasOffsetValue('abc', 'world')>", $list);
	}

	/**
	 * @param list<array<string, string>> $list
	 */
	public function sayFooBar(array $list): void
	{
		foreach($list as $k => $v) {
			if (rand(0,1)) {
				unset($list[$k]);
			}
			assertType('array<int<0, max>, array<string, string>>', $list);
			assertType('array<string, string>', $list[$k]);
		}
		assertType('array<string, string>', $list[$k]);
	}
}
