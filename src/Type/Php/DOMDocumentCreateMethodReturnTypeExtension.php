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
use function in_array;

#[AutowiredService]
final class DOMDocumentCreateMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	private const METHODS_WITH_NAME_VALIDATION = [
		'createElement',
		'createAttribute',
		'createEntityReference',
		'createProcessingInstruction',
	];

	private const METHODS_ALWAYS_SUCCESSFUL = [
		'createCDATASection',
	];

	public function getClass(): string
	{
		return DOMDocument::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		if (!extension_loaded('dom')) {
			return false;
		}

		return in_array($methodReflection->getName(), self::METHODS_WITH_NAME_VALIDATION, true)
			|| in_array($methodReflection->getName(), self::METHODS_ALWAYS_SUCCESSFUL, true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$variant = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants());

		if (in_array($methodReflection->getName(), self::METHODS_ALWAYS_SUCCESSFUL, true)) {
			return TypeCombinator::remove($variant->getReturnType(), new ConstantBooleanType(false));
		}

		$argType = $scope->getType($args[0]->value);

		$doc = new DOMDocument();
		$doc->strictErrorChecking = false;

		foreach ($argType->getConstantStrings() as $constantString) {
			try {
				$result = @$doc->createElement($constantString->getValue());
			} catch (DOMException) {
				return null;
			}
			if ($result === false) {
				return null;
			}

			$argType = TypeCombinator::remove($argType, $constantString);
		}

		if (!$argType instanceof NeverType) {
			return null;
		}

		return TypeCombinator::remove($variant->getReturnType(), new ConstantBooleanType(false));
	}

}
