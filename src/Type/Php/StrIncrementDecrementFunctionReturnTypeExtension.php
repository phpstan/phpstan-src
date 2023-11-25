<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use ValueError;
use function chr;
use function count;
use function function_exists;
use function implode;
use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function ord;
use function preg_match;
use function str_split;
use function stripos;

class StrIncrementDecrementFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['str_increment', 'str_decrement'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$fnName = $functionReflection->getName();
		$args = $functionCall->getArgs();

		if (count($args) !== 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($args[0]->value);
		if (count($argType->getConstantScalarValues()) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$value = $argType->getConstantScalarValues()[0];
		if (!(is_string($value) || is_int($value) || is_float($value))) {
			return new ErrorType();
		}
		$string = (string) $value;

		if (preg_match('/\A(?:0|[1-9A-Za-z][0-9A-Za-z]*)+\z/', $string) < 1) {
			return new ErrorType();
		}

		$value = null;
		if ($fnName === 'str_increment') {
			$value = $this->increment($string);
		} elseif ($fnName === 'str_decrement') {
			$value = $this->decrement($string);
		}

		return $value === null
			? new ErrorType()
			: new ConstantStringType($value);
	}

	private function increment(string $s): string
	{
		if (is_numeric($s)) {
			$offset = stripos($s, 'e');
			if ($offset !== false) {
				// Using increment operator would cast the string to float
				// Therefore we manually increment it to convert it to an "f"/"F" that doesn't get affected
				$c = $s[$offset];
				$c++;
				$s[$offset] = $c;
				$s++;
				$s[$offset] = [
					'f' => 'e',
					'F' => 'E',
					'g' => 'f',
					'G' => 'F',
				][$s[$offset]];

				return $s;
			}
		}

		return (string) ++$s;
	}

	private function decrement(string $s): ?string
	{
		if (in_array($s, ['a', 'A', '0'], true)) {
			return null;
		}

		$decremented = str_split($s, 1);
		$position = count($decremented) - 1;
		$carry = false;
		$map = [
			'0' => '9',
			'A' => 'Z',
			'a' => 'z',
		];
		do {
			$c = $decremented[$position];
			if (!in_array($c, ['a', 'A', '0'], true)) {
				$carry = false;
				$decremented[$position] = chr(ord($c) - 1);
			} else {
				$carry = true;
				$decremented[$position] = $map[$c];
			}
		} while ($carry && $position-- > 0);

		if ($carry || count($decremented) > 1 && $decremented[0] === '0') {
			if (count($decremented) === 1) {
				return null;
			}

			unset($decremented[0]);
		}

		return implode($decremented);
	}

}
