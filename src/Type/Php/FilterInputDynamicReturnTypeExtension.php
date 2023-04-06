<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class FilterInputDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper, private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'filter_input';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$supportedTypes = TypeCombinator::union(
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_GET'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_POST'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_COOKIE'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_SERVER'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_ENV'), null)->getValueType(),
		);
		$typeType = $scope->getType($functionCall->getArgs()[0]->value);
		if (!$typeType->isInteger()->yes() || $supportedTypes->isSuperTypeOf($typeType)->no()) {
			return null;
		}

		// Pragmatical solution since global expressions are not passed through the scope for performance reasons
		// See https://github.com/phpstan/phpstan-src/pull/2012 for details
		$inputType = new ArrayType(new StringType(), new MixedType());

		return $this->filterFunctionReturnTypeHelper->getOffsetValueType(
			$inputType,
			$scope->getType($functionCall->getArgs()[1]->value),
			isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null,
			isset($functionCall->getArgs()[3]) ? $scope->getType($functionCall->getArgs()[3]->value) : null,
		);
	}

}
