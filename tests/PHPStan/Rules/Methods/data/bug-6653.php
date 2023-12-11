<?php

namespace Bug6653;

class HelloWorld
{
	/**
	 * @return array<string, int>|false
	 */
	public function sayHello()
	{
		$test = $this->getTest();
		return $this->filterEvent('sayHello', $test);
	}

	/**
	 * @template TValue of mixed
	 * @param TValue $value
	 * @return TValue
	 */
	private function filterEvent(string $eventName, $value)
	{
		// do event
		return $value;
	}

	/**
	 * @return array<string, int>|false
	 */
	private function getTest()
	{
		$failure = random_int(0, PHP_INT_MAX) % 2 ? true : false;
		if ($failure === true) {
			return false;
		}
		return ['foo' => 123];
	}
}

class HelloWorld2
{
	/**
	 * @return array<string, int>|false
	 */
	public function sayHello()
	{
		$test = $this->getTest();
		return $this->filterEvent('sayHello', $test);
	}

	/**
	 * @template TValue of mixed
	 * @param TValue $value
	 * @return TValue
	 */
	private function filterEvent(string $eventName, $value)
	{
		// do event
		return $value;
	}

	/**
	 * @return array<string, int>|false
	 */
	private function getTest()
	{
		$failure = random_int(0, PHP_INT_MAX) % 2 ? true : false;
		if ($failure === true) {
			return false;
		}
		return ['foo' => 123];
	}
}
