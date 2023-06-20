<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ConstantFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ConstantHelper $constantHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'constant';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$nameType = $scope->getType($functionCall->getArgs()[0]->value);

		$results = [];
		foreach ($nameType->getConstantStrings() as $constantName) {
			$results[] = $scope->getType($this->constantHelper->createExprFromConstantName($constantName->getValue()));
		}

		if (count($results) > 0) {
			return TypeCombinator::union(...$results);
		}

		return null;
	}

}
