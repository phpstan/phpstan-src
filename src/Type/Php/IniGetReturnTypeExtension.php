<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function count;

#[AutowiredService]
final class IniGetReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ini_get';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$numericString = TypeCombinator::intersect(
			new StringType(),
			new AccessoryNumericStringType(),
		);
		$types = [
			'date.timezone' => new StringType(),
			'memory_limit' => new StringType(),
			'max_execution_time' => $numericString,
			'max_input_time' => $numericString,
			'default_socket_timeout' => $numericString,
			'precision' => $numericString,
		];

		if ($scope->getPhpVersion()->supportsMaxMemoryLimit()->yes()) {
			$types['max_memory_limit'] = new StringType();
		}

		$argType = $scope->getType($args[0]->value);
		$results = [];
		foreach ($argType->getConstantStrings() as $constantString) {
			if (!array_key_exists($constantString->getValue(), $types)) {
				return null;
			}
			$results[] = $types[$constantString->getValue()];
		}

		if (count($results) > 0) {
			return TypeCombinator::union(...$results);
		}

		return null;
	}

}
