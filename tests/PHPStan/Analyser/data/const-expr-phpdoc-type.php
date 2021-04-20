<?php

namespace ConstExprPhpDocType;

use RecursiveIteratorIterator as Rec;
use function PHPStan\Testing\assertType;

class Foo
{

	public const SOME_CONSTANT = 1;
	public const SOME_OTHER_CONSTANT = 2;

	/**
	 * @param 'foo'|'bar' $one
	 * @param self::SOME_* $two
	 * @param self::SOME_OTHER_CONSTANT $three
	 * @param \ConstExprPhpDocType\Foo::SOME_CONSTANT $four
	 * @param Rec::LEAVES_ONLY $five
	 * @param 1.0 $six
	 * @param 234 $seven
	 * @param self::SOME_OTHER_* $eight
	 * @param self::* $nine
	 */
	public function doFoo(
		$one,
		$two,
		$three,
		$four,
		$five,
		$six,
		$seven,
		$eight,
		$nine
	)
	{
		assertType("'bar'|'foo'", $one);
		assertType('1|2', $two);
		assertType('2', $three);
		assertType('1', $four);
		assertType('0', $five);
		assertType('1.0', $six);
		assertType('234', $seven);
		assertType('2', $eight);
		assertType('1|2', $nine);
	}

}
