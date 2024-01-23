<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

class DateFormatFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private DateFunctionReturnTypeHelper $dateFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'date_format';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return new StringType();
		}

		return $this->dateFunctionReturnTypeHelper->getTypeFromFormatType(
			$scope->getType($functionCall->getArgs()[1]->value),
			true,
		);
	}

}
