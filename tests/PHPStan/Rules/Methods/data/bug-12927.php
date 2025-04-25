<?php

namespace Bug12927;

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
		}
		return $list;
	}
}
