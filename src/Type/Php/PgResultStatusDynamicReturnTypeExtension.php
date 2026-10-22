<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class PgResultStatusDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const STATUS_LONG = 1;
	private const STATUS_STRING = 2;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pg_result_status';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[1])) {
			return new IntegerType();
		}

		$types = [];
		foreach ($scope->getType($args[1]->value)->getConstantScalarValues() as $value) {
			if ($value === self::STATUS_LONG) {
				$types[] = new IntegerType();
			} elseif ($value === self::STATUS_STRING) {
				$types[] = new StringType();
			} else {
				return null;
			}
		}

		if ($types === []) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

}
