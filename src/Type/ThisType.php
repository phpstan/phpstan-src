<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use function sprintf;

/** @api */
class ThisType extends StaticType
{

	/**
	 * @api
	 */
	public function __construct(
		ClassReflection $classReflection,
		?Type $subtractedType = null,
	)
	{
		parent::__construct($classReflection, $subtractedType);
	}

	public function changeBaseClass(ClassReflection $classReflection): StaticType
	{
		return new self($classReflection, $this->getSubtractedType());
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getStaticObjectType()->describe($level));
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if ($reflectionProvider->hasClass($properties['baseClass'])) {
			return new self($reflectionProvider->getClass($properties['baseClass']), $properties['subtractedType'] ?? null);
		}

		return new ErrorType();
	}

}
