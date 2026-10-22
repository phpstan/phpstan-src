<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ValueError;
use function count;
use function in_array;
use function is_float;
use function is_int;
use function is_string;
use function str_decrement;
use function str_increment;

#[AutowiredService]
final class StrIncrementDecrementFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['str_increment', 'str_decrement'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$fnName = $functionReflection->getName();
		$args = $functionCall->getArgs();

		if (count($args) !== 1) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		if (count($argType->getConstantScalarValues()) === 0) {
			return null;
		}

		$types = [];
		foreach ($argType->getConstantScalarValues() as $value) {
			if (!(is_string($value) || is_int($value) || is_float($value))) {
				continue;
			}
			$string = (string) $value;

			$result = null;
			if ($fnName === 'str_increment') {
				$result = $this->increment($string);
			} elseif ($fnName === 'str_decrement') {
				$result = $this->decrement($string);
			}

			if ($result === null) {
				continue;
			}

			$types[] = new ConstantStringType($result);
		}

		return count($types) === 0
			? new NeverType()
			: TypeCombinator::union(...$types);
	}

	private function increment(string $s): ?string
	{
		if ($s === '') {
			return null;
		}

		try {
			return str_increment($s);
		} catch (ValueError) {
			return null;
		}
	}

	private function decrement(string $s): ?string
	{
		if ($s === '') {
			return null;
		}

		try {
			return str_decrement($s);
		} catch (ValueError) {
			return null;
		}
	}

}
