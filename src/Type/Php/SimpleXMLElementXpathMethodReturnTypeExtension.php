<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SimpleXMLElement;

class SimpleXMLElementXpathMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return SimpleXMLElement::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return extension_loaded('simplexml') && $methodReflection->getName() === 'xpath';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (!isset($methodCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($methodCall->getArgs()[0]->value);

		$xmlElement = new SimpleXMLElement('<foo />');

		foreach ($argType->getConstantStrings() as $constantString) {
			$result = @$xmlElement->xpath($constantString->getValue());
			if ($result === false) {
				// We can't be sure since it's maybe a namespaced xpath
				return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
			}

			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if (!$argType instanceof NeverType) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		return new ArrayType(new MixedType(), $scope->getType($methodCall->var));
	}

}
