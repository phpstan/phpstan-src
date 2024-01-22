<?php // lint >= 8.3

namespace ClassConstantNativeTypeForMissingTypehintRule;

/** @template T */
class Bar
{

}

const ConstantWithObjectInstanceForNativeType = new Bar();

class Foo
{

	public const array A = [];

	/** @var array */
	public const array B = [];

	public const Bar C = ConstantWithObjectInstanceForNativeType;

	/** @var Bar */
	public const Bar D = ConstantWithObjectInstanceForNativeType;
}
