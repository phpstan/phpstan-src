<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_last;
use function count;
use function strtolower;

/**
 * Whether a closure's stored walk answers an ask made in another call context
 * (MutatingScope::$inFunctionCallsStack). An extension pushing its own callable
 * parameter with pushInFunctionCall() changes the contextual parameter types
 * of the closure, so it must be re-priced. The call site itself (a shallower
 * stack), the same parameter type, and a parameter referencing the callee's
 * own templates (acceptor selection before template resolution) keep the walk
 * answer - the walk already priced the closure in the resolved context.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'closureCallContextMatcher')]
final class ClosureCallContextMatcher
{

	/**
	 * @param list<array{FunctionReflection|MethodReflection|null, ParameterReflection|null}> $askStack
	 * @param list<array{FunctionReflection|MethodReflection|null, ParameterReflection|null}> $positionStack
	 */
	public static function matches(array $askStack, array $positionStack): bool
	{
		if ($askStack === $positionStack) {
			return true;
		}
		if ($askStack === [] || count($askStack) !== count($positionStack)) {
			return true;
		}

		[, $askParameter] = array_last($askStack);
		[$callee, $positionParameter] = array_last($positionStack);
		if ($askParameter === null || $askParameter === $positionParameter) {
			return true;
		}
		$askType = $askParameter->getType();
		if ($positionParameter !== null && $askType->equals($positionParameter->getType())) {
			return true;
		}
		if ($callee === null) {
			return false;
		}

		$calleeName = strtolower($callee->getName());
		$calleeIsMethod = $callee instanceof MethodReflection;
		$referencesCalleeTemplate = false;
		TypeTraverser::map($askType, static function (Type $type, callable $traverse) use ($calleeName, $calleeIsMethod, &$referencesCalleeTemplate): Type {
			if ($type instanceof TemplateType) {
				$templateScope = $type->getScope();
				$templateFunctionName = $templateScope->getFunctionName();
				if (
					$templateFunctionName !== null
					&& strtolower($templateFunctionName) === $calleeName
					&& ($templateScope->getClassName() !== null) === $calleeIsMethod
				) {
					$referencesCalleeTemplate = true;
					return $type;
				}
			}

			return $traverse($type);
		});

		return $referencesCalleeTemplate;
	}

}
