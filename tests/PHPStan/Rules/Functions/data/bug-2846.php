<?php

namespace Bug2846;

class Test {
	public static function staticFunc(): void {}
	public static function callStatic(): void {
		call_user_func([static::class, 'staticFunc']);
	}
}
