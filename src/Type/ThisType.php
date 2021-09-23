<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;

/** @api */
class ThisType extends StaticType
{

	/**
	 * @api
	 */
	public function __construct(ClassReflection $classReflection)
	{
		parent::__construct($classReflection);
	}

	public function changeBaseClass(ClassReflection $classReflection): StaticType
	{
		return new self($classReflection);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getClassName());
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if ($reflectionProvider->hasClass($properties['baseClass'])) {
			return new self($reflectionProvider->getClass($properties['baseClass']));
		}

		return new ErrorType();
	}

}
