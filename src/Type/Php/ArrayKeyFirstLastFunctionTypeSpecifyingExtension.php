<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function in_array;
use function strtolower;

/**
 * Narrows the array argument of array_key_first()/array_key_last()/array_find_key() when its result is
 * compared against null: a non-null key means the array is non-empty. array_key_first()/array_key_last()
 * narrow in both directions (a null key means the array is empty); array_find_key() only narrows the
 * non-null direction. Driven by the narrowed return type carried by the comparison.
 */
#[AutowiredService]
final class ArrayKeyFirstLastFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $context->getNarrowedReturnType() !== null
			&& $node->name instanceof Name
			&& !$node->isFirstClassCallable()
			&& isset($node->getArgs()[0])
			&& in_array(strtolower($functionReflection->getName()), ['array_key_first', 'array_key_last', 'array_find_key'], true);
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType === null || !$narrowedReturnType->isNull()->yes()) {
			return new SpecifiedTypes();
		}

		$argValue = $node->getArgs()[0]->value;
		if (!$scope->getType($argValue)->isArray()->yes()) {
			return new SpecifiedTypes();
		}

		$functionName = strtolower($functionReflection->getName());
		$bothDirections = in_array($functionName, ['array_key_first', 'array_key_last'], true);

		if ($bothDirections || $context->falsey()) {
			return $this->typeSpecifier->create($argValue, new NonEmptyArrayType(), $context->negate(), $scope);
		}

		return new SpecifiedTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
