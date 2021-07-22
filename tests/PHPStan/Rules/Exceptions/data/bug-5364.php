<?php

namespace Bug5364;

class Foo
{

	/**
	 * @throws \Exception
	 */
	function test(): void {
		throw new \Exception();
	}

	function call_test(): void {
		/** @throws void */
		$this->test();
	}

}
