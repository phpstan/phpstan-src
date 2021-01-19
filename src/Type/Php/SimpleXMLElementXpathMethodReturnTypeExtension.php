<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class SimpleXMLElementXpathMethodReturnTypeExtension implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \SimpleXMLElement::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'xpath';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (!isset($methodCall->args[0])) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($methodCall->args[0]->value);

		$xmlElement = new \SimpleXMLElement('<foo />');

		$result = null;
		foreach (TypeUtils::getConstantStrings($argType) as $constantString) {
			$newResult = @$xmlElement->xpath($constantString->getValue());

			if ($result !== null && gettype($result) !== gettype($newResult)) {
				return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
			}

			$result = $newResult;
			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if ($result === null || !$argType instanceof NeverType) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		if ($result === false) {
			return new ConstantBooleanType(false);
		}

		return new ArrayType(new MixedType(), $scope->getType($methodCall->var));
	}

}
