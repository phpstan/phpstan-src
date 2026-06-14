<?php // lint >= 8.0

declare(strict_types = 1);

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
		assertType('class-string<static(Bug9341\MyAbstractBase)&Bug9341\MyInterface>', $class);
		$fileObject = new $class();
		assertType('static(Bug9341\MyAbstractBase)&Bug9341\MyInterface', $fileObject);
		return $fileObject;
	}
}

abstract class MyAbstractBase {
	use MyTrait;
}

class MyClass extends MyAbstractBase implements MyInterface
{

}
