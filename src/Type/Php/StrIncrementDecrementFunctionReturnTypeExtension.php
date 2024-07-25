<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function chr;
use function count;
use function implode;
use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function ord;
use function preg_match;
use function str_split;
use function stripos;

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

			if (preg_match('/\A(?:0|[1-9A-Za-z][0-9A-Za-z]*)+\z/', $string) < 1) {
				continue;
			}

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
			? new ErrorType()
			: TypeCombinator::union(...$types);
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
