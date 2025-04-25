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
	 * @return list<array<string>>
	 */
	public function sayFoo(array $list): array
	{
		foreach($list as $k => $v) {
			unset($list[$k]['abc']);
			assertType('non-empty-list<array<string, string>>', $list);
			assertType('array<string, string>', $list[$k]);
		}
		return $list;
	}
}
