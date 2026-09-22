<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

/**
 * array_all($array, $callback):
 * - true  => every element satisfies the predicate (an empty array qualifies),
 *            so element value/key types are narrowed by the predicate.
 * - false => at least one element fails, so the array is non-empty.
 */
#[AutowiredService]
final class ArrayAllFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(
		private ArrayPredicateCallbackResolver $predicateCallbackResolver,
		private ArrayAllAnyNarrowingHelper $narrowingHelper,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'array_all'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (count($args) < 2 || !$scope instanceof MutatingScope) {
			return new SpecifiedTypes();
		}

		$arrayArg = $args[0]->value;
		$callbackArg = $args[1]->value;

		$arrayType = $scope->getType($arrayArg);
		if ($arrayType->isArray()->no()) {
			return new SpecifiedTypes();
		}

		if ($context->false()) {
			// At least one element fails the predicate: the array is non-empty.
			$nonEmptyType = $arrayType->isArray()->yes()
				? new NonEmptyArrayType()
				: TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType());

			return $this->typeSpecifier->create($arrayArg, $nonEmptyType, TypeSpecifierContext::createTruthy(), $scope);
		}

		// Every element satisfies the predicate: narrow value/key types.
		$predicates = $this->predicateCallbackResolver->resolve($scope, $callbackArg, ArrayCallbackParameterMapping::valueAndKey());
		if ($predicates === null || count($predicates) !== 1) {
			return new SpecifiedTypes();
		}

		$narrowedType = $this->narrowingHelper->narrowArrayType($scope, $arrayType, $predicates[0], true);
		if ($narrowedType === null) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create($arrayArg, $narrowedType, TypeSpecifierContext::createTruthy(), $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
