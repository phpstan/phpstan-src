<?php declare(strict_types = 1);

namespace Bug9341;

use function PHPStan\Testing\assertType;

interface MyInterface {}

trait MyTrait
{
	public static function parse(): mixed
	{
		$class = get_called_class();
		assertType('class-string<static(Bug9341\MyAbstractBase)>', $class);
		if (!is_a($class, MyInterface::class, true)) {
			return false;
		}
		assertType('class-string<Bug9341\MyInterface&static(Bug9341\MyAbstractBase)>', $class);
		$fileObject = new $class();
		assertType('Bug9341\MyInterface&static(Bug9341\MyAbstractBase)', $fileObject);
		return $fileObject;
	}
}

abstract class MyAbstractBase {
	use MyTrait;
}

class MyClass extends MyAbstractBase implements MyInterface
{

}
