<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SimpleXMLElement;
use function extension_loaded;

final class SimpleXMLElementXpathMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return SimpleXMLElement::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return extension_loaded('simplexml') && $methodReflection->getName() === 'xpath';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (!isset($methodCall->getArgs()[0])) {
			return null;
		}

		$argType = $scope->getType($methodCall->getArgs()[0]->value);

		$xmlElement = new SimpleXMLElement('<foo />');

		foreach ($argType->getConstantStrings() as $constantString) {
			$result = @$xmlElement->xpath($constantString->getValue());
			if ($result === false) {
				// We can't be sure since it's maybe a namespaced xpath
				return null;
			}

			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if (!$argType instanceof NeverType) {
			return null;
		}

		return new ArrayType(new MixedType(), $scope->getType($methodCall->var));
	}

}
