<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DOMDocument;
use DOMException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function extension_loaded;

#[AutowiredService]
final class DomDocumentCreateElementDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return DOMDocument::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return extension_loaded('dom') && $methodReflection->getName() === 'createElement';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);

		$doc = new DOMDocument();

		foreach ($argType->getConstantStrings() as $constantString) {
			try {
				$doc->createElement($constantString->getValue());
			} catch (DOMException) {
				return null;
			}

			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if (!$argType instanceof NeverType) {
			return null;
		}

		$variant = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants());

		return TypeCombinator::remove($variant->getReturnType(), new ConstantBooleanType(false));
	}

}
