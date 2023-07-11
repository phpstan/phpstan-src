<?php

namespace TestMethodsIsCallable;

class CheckIsCallable
{

	public function test(callable $str)
	{
		$this->test('Test\CheckIsCallable::test');
	}

	public function testClosure(\Closure $closure)
	{
		$this->testClosure(function () {

		});
	}

}

