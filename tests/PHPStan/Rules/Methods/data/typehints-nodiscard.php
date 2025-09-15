<?php

namespace TestMethodTypehints;

class Demo {

	#[\NoDiscard]
	public function nothing(): void {
	}

	#[\NoDiscard]
	public static function alsoNothing(): void {
	}
}
