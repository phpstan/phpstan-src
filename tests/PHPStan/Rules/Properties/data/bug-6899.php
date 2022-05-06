<?php

namespace Bug6899;

class HelloWorld
{
	/**
	 * @param string $string
	 * @return void
	 */
	public function foo($string): void
	{
		isset($string->prop);
		$string->prop ?? "";
		empty($string->prop);
	}

	/**
	 * @param string|object $maybeString
	 * @return void
	 */
	public function bar($maybeString): void
	{
		isset($maybeString->prop);
		$maybeString->prop ?? "";
		empty($maybeString->prop);
	}
}
