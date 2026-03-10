<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DOMDocument;
use DOMException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function extension_loaded;

#[AutowiredService]
final class DomDocumentCreateElementDynamicThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return extension_loaded('dom')
			&& $methodReflection->getDeclaringClass()->getName() === DOMDocument::class
			&& $methodReflection->getName() === 'createElement';
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (!isset($args[0])) {
			return new ObjectType(DOMException::class);
		}

		$argType = $scope->getType($args[0]->value);

		$doc = new DOMDocument();

		foreach ($argType->getConstantStrings() as $constantString) {
			try {
				$doc->createElement($constantString->getValue());
			} catch (DOMException) {
				return new ObjectType(DOMException::class);
			}

			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if (!$argType instanceof NeverType) {
			return new ObjectType(DOMException::class);
		}

		return null;
	}

}
