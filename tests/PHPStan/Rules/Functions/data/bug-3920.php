<?php

namespace Bug3920;

class HelloWorld
{
	/**
	 * @return class-string<Two>
	 */
	public function sayHello(string $a)
	{

		$arr = [
			'a' => Two::class,
			'c' => Two::class,
		];
		return $arr[$a];
	}

	public function sayType(): void
	{
		call_user_func([One::class, 'isType']);
		call_user_func([Two::class, 'isType']);
		$class = $this->sayHello('a');
		$type = $class::isType();
		$callable = [$class, 'isType'];
		call_user_func($callable);
		if (is_callable($callable)) {
			call_user_func($callable);
		}
	}
}

class One {
	public static function isType(): bool
	{
		return true;
	}
}
class Two extends One {

}
class Three {}
