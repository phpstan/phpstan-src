<?php

namespace Bug5834;

class Bar
{
	/**
	 * @var array|mixed|null
	 */
	private $bar = null;

	/**
	 * @return array|mixed|null
	 */
	public function getBar()
	{
		return $this->bar;
	}
}

class Foo
{
	public function getFoo(Bar $bar): void
	{
		$array = (array) $bar->getBar();
		$statusCode = array_key_exists('key', $array) ? (string) $array['key'] : null;
	}
}
