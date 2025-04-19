<?php declare(strict_types = 1);

namespace Bug8523b;

class HelloWorld
{
	public static ?HelloWorld $instance = null;

	public function save(): void {
		self::$instance = new HelloWorld();

		$callback = static function(): void {
			self::$instance = null;
		};

		$callback();

		var_dump(self::$instance);

		self::$instance?->save();
	}
}

(new HelloWorld())->save();
