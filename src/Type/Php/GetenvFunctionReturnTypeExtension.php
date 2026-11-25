<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

/**
 * Since PHP 8.0, getenv() returns all the environment variables when the name is null or left out,
 * and a single one otherwise. The name is read from the normalized arguments, so that a named call
 * like getenv(local_only: true) is typed like a positional one.
 */
#[AutowiredService]
final class GetenvFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'getenv';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		// the name became nullable in PHP 8.0, as named arguments appeared, and the signature map covers older versions
		$supportsNullName = $scope->getPhpVersion()->supportsNamedArguments();
		if ($supportsNullName->no()) {
			return null;
		}

		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$allVariables = new ArrayType(new StringType(), new StringType());
		$singleVariable = TypeCombinator::union(new StringType(), new ConstantBooleanType(false));
		$nullName = $supportsNullName->yes() ? $allVariables : TypeCombinator::union($allVariables, $singleVariable);

		$isNull = $scope->getType($args[0]->value)->isNull();
		if ($isNull->yes()) {
			return $nullName;
		}

		if ($isNull->no()) {
			return $singleVariable;
		}

		return TypeCombinator::union($nullName, $singleVariable);
	}

}
