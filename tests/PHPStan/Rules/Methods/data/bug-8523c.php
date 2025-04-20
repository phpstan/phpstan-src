<?php declare(strict_types = 1);

namespace Bug8523c;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public static ?HelloWorld $instance = null;

	public function save(): void {
        HelloWorld::$instance = new HelloWorld();

		$callback = static function(): void {
            HelloWorld::$instance = null;
		};

		$callback();

		var_dump(HelloWorld::$instance);

        HelloWorld::$instance?->save();
	}
}

(new HelloWorld())->save();
