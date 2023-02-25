<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;

class FilterInputDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
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

		$typeArgExpr = $functionCall->getArgs()[0]->value;
		$varNameType = $scope->getType($functionCall->getArgs()[1]->value);
		$varNameTypeIsString = $varNameType->isString();
		if (
			$varNameTypeIsString->no()
			|| !($typeArgExpr instanceof ConstFetch)
			|| !in_array((string) $typeArgExpr->name, ['INPUT_GET', 'INPUT_POST', 'INPUT_COOKIE', 'INPUT_SERVER', 'INPUT_ENV'], true)
		) {
			return new NeverType();
		}

		if ($varNameTypeIsString->maybe()) {
			return null;
		}

		// Pragmatical solution since global expressions are not passed through the scope for performance reasons
		// See https://github.com/phpstan/phpstan-src/pull/2012 for details
		$inputType = new ArrayType(new StringType(), new MixedType());

		return $this->filterFunctionReturnTypeHelper->getArrayOffsetValueType(
			$inputType,
			$varNameType,
			isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null,
			isset($functionCall->getArgs()[3]) ? $scope->getType($functionCall->getArgs()[3]->value) : null,
		);
	}

}
