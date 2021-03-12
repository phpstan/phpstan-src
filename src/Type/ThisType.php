<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;

class ThisType extends StaticType
{

	/**
	 * @param ClassReflection|string $classReflection
	 * @return self
	 */
	public function changeBaseClass($classReflection): StaticType
	{
		return new self($classReflection);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getClassName());
	}

}
