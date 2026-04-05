<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;

final class GenericTypeTemplateTraverser implements TypeTraverserCallable
{

	public function __construct(
		private readonly TemplateTypeMap $resolvedTemplateTypeMap,
	)
	{
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if ($type instanceof TemplateType && !$type->isArgument()) {
			$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
			if ($newType === null || $newType instanceof ErrorType) {
				return $traverse($type->getDefault() ?? $type->getBound());
			}

			return TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);
		}

		return $traverse($type);
	}

}
