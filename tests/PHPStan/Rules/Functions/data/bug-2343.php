<?php declare(strict_types = 1);

namespace Bug2343;

class A {
	public static function say(string $name, int $age) {
		echo "Name: {$name}, Age: {$age}\n";
	}

	public static function bye(string $name, int $age) {
		echo "Bye, Name: {$name}, Age: {$age}\n";
	}

	public static function all() {
		$name = 'Jack';
		$age = 20;
		$functions = [
			'say',
			'bye'
		];

		foreach ($functions as $function) {
			call_user_func([__CLASS__, $function], $name, $age);
		}
	}
}
