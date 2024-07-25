<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

final class StrWordCountFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_word_count';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		Node\Expr\FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount === 1) {
			return new IntegerType();
		} elseif ($argsCount === 2 || $argsCount === 3) {
			$formatType = $scope->getType($functionCall->getArgs()[1]->value);
			if ($formatType instanceof ConstantIntegerType) {
				$val = $formatType->getValue();
				if ($val === 0) {
					// return word count
					return new IntegerType();
				} elseif ($val === 1 || $val === 2) {
					// return [word] or [offset => word]
					return new ArrayType(new IntegerType(), new StringType());
				}

				// return false, invalid format value specified
				return new ConstantBooleanType(false);
			}

			// Could be invalid format type as well, but parameter type checks will catch that.

			return new UnionType([
				new IntegerType(),
				new ArrayType(new IntegerType(), new StringType()),
				new ConstantBooleanType(false),
			]);
		}

		// else fatal error; too many or too few arguments
		return new ErrorType();
	}

}
