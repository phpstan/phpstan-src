<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use function array_key_exists;

final class ConstructorClassTemplateTraverser
{

	/**
	 * @param array<string, Type> $classTemplateTypes
	 */
	public function __construct(
		private array $classTemplateTypes,
	)
	{
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function __invoke(Type $type, callable $traverse): Type
	{
		if ($type instanceof TemplateType && array_key_exists($type->getName(), $this->classTemplateTypes)) {
			$classTemplateType = $this->classTemplateTypes[$type->getName()];
			if ($classTemplateType instanceof TemplateType && $classTemplateType->getScope()->equals($type->getScope())) {
				unset($this->classTemplateTypes[$type->getName()]);
			}
			return $type;
		}

		return $traverse($type);
	}

	/**
	 * @return array<string, Type>
	 */
	public function getClassTemplateTypes(): array
	{
		return $this->classTemplateTypes;
	}

}
