<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Regexp\PatternValidator;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class PregGrepDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PatternValidator $patternValidator)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'preg_grep';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$patternType = $scope->getType($args[0]->value);
		$constantStrings = $patternType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return null;
		}

		foreach ($constantStrings as $constantString) {
			if ($this->patternValidator->validatePattern($constantString->getValue()) !== null) {
				return null;
			}
		}

		return TypeCombinator::remove(
			ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType(),
			new ConstantBooleanType(false),
		);
	}

}
