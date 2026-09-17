<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strrev;

#[AutowiredService]
final class StrrevFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private StrShuffleFunctionReturnTypeExtension $strShuffleExtension)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strrev';
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

		$inputType = $scope->getType($args[0]->value);
		$constantStrings = $inputType->getConstantStrings();
		if (count($constantStrings) > 0) {
			$resultTypes = [];
			foreach ($constantStrings as $constantString) {
				$resultTypes[] = new ConstantStringType(strrev($constantString->getValue()));
			}

			return TypeCombinator::union(...$resultTypes);
		}

		// Reversing a string reorders its bytes, just like shuffling it.
		return $this->strShuffleExtension->getShuffledStringType($inputType);
	}

}
