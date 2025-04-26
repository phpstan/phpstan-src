<?php

namespace Bug12754;

class HelloWorld
{
	/**
	 * @param list<array{string, string}> $list
	 * @return void
	 */
	public function modify(array &$list): void
	{
		foreach ($list as $int => $array) {
			$list[$int][1] = $this->apply($array[1]);
		}
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public function apply(string $value): mixed
	{
		return $value;
	}
}
