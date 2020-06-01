<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;

class ThisType extends StaticType
{

	private ClassReflection $classReflection;

	private ?\PHPStan\Type\ObjectType $staticObjectType = null;

	/**
	 * @param string|ClassReflection $classReflection
	 */
	public function __construct($classReflection)
	{
		if (is_string($classReflection)) {
			$classReflection = Broker::getInstance()->getClass($classReflection);
		}
		parent::__construct($classReflection->getName());
		$this->classReflection = $classReflection;
	}

	public function getStaticObjectType(): ObjectType
	{
		if ($this->staticObjectType === null) {
			if ($this->classReflection->isGeneric()) {
				$typeMap = $this->classReflection->getTemplateTypeMap()->map(static function (string $name, Type $type): Type {
					return TemplateTypeHelper::toArgument($type);
				});
				return $this->staticObjectType = new GenericObjectType(
					$this->classReflection->getName(),
					$this->classReflection->typeMapToList($typeMap)
				);
			}

			return $this->staticObjectType = new ObjectType($this->classReflection->getName(), null, $this->classReflection);
		}

		return $this->staticObjectType;
	}

	public function changeBaseClass(ClassReflection $classReflection): StaticType
	{
		return new self($classReflection);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getClassName());
	}

}
