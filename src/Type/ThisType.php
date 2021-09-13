<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;

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
		$broker = Broker::getInstance();
		if ($broker->hasClass($properties['baseClass'])) {
			return new self($broker->getClass($properties['baseClass']));
		}

		return new ErrorType();
	}

}
