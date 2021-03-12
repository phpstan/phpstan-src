<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;

class ThisType extends StaticType
{

	public function changeBaseClass(ClassReflection $classReflection): StaticType
	{
		return new self($classReflection);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getClassName());
	}

}
