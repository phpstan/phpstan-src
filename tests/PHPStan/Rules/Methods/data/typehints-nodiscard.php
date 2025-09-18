<?php

namespace TestMethodTypehints;

class Demo {

	#[\NoDiscard]
	public function nothing(): void {
	}

	#[\NoDiscard]
	public static function alsoNothing(): void {
	}

	#[\NoDiscard]
	public static function returnNever(): never {
	}

	#[\NoDiscard]
	public function __construct()
	{

	}

	#[\NoDiscard]
	public function __destruct()
	{

	}

	#[\NoDiscard]
	public function __unset()
	{

	}

	#[\NoDiscard]
	public function __wakeup()
	{

	}

	#[\NoDiscard]
	public function __clone()
	{

	}
}
