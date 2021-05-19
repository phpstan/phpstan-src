<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use SimpleXMLElement;

class SimpleXMLElementConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === SimpleXMLElement::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) === 0) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->args[0]->value);
		$constantStrings = TypeUtils::getConstantStrings($valueType);

		foreach ($constantStrings as $constantString) {
			try {
				new SimpleXMLElement($constantString->getValue());
			} catch (\Exception $e) { // phpcs:ignore
				return $methodReflection->getThrowType();
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
