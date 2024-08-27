<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

final class DateFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private DateFunctionReturnTypeHelper $dateFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'date';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return null;
		}

		return $this->dateFunctionReturnTypeHelper->getTypeFromFormatType(
			$scope->getType($functionCall->getArgs()[0]->value),
			false,
		);
	}

}
