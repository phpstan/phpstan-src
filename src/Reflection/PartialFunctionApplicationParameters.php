<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_keys;
use function count;
use function max;
use function min;

/**
 * Maps the parameters of a callee to the parameters of the closure its partial
 * function application creates, following PHP 8.6:
 *
 * - positional placeholders come first, then the named placeholders in the order
 *   they are written, then, with `...`, every parameter left unbound in its
 *   declaration order;
 * - a `?` gives a required parameter, `...` keeps optionality and the variadic;
 * - a `?` past the declared parameters of a variadic callee gives a required
 *   parameter named after the variadic and the number of such placeholders
 *   before it (`$rest0`, `$rest1`, …);
 * - without `...`, the closure is not variadic and unbound optional parameters
 *   keep their default values.
 *
 * Not shadowed by a native twin: it runs only for partial applications that are
 * not first-class callables.
 */
#[ReferencedByTurboExtension(key: 'partialFunctionApplicationParameters')]
final class PartialFunctionApplicationParameters
{

	/**
	 * The closure's parameters, whether it is variadic, its return type, its template types
	 * and the names of the callee's parameters the bound arguments fill (a bound argument
	 * collected by the variadic counts by its own name, when it has one).
	 *
	 * Templates the bound arguments infer are resolved: in the return type, a template
	 * no closure parameter mentions becomes the inferred type, and one a closure parameter
	 * still mentions is joined with it, since a call unions what both bring to it.
	 *
	 * @param list<ParameterReflection> $parameters
	 * @param bool $isVariadic whether the callee takes more arguments than its parameters, like one calling func_get_args()
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @param array<int, Type> $boundArgTypes the types of the bound arguments, keyed like $args
	 * @return array{list<ParameterReflection>, bool, Type, TemplateTypeMap, list<string>}|null null when creating the closure throws
	 */
	public static function apply(array $parameters, bool $isVariadic, array $args, array $boundArgTypes, Type $returnType, TemplateTypeMap $templateTypeMap): ?array
	{
		$mapped = self::map($parameters, $isVariadic, $args);
		if ($mapped === null) {
			return null;
		}
		[$closureParameters, $isVariadic, $bound] = $mapped;
		$boundNames = [];
		foreach ($bound as [$parameter, $argIndex]) {
			if (!$parameter->isVariadic()) {
				$boundNames[] = $parameter->getName();
				continue;
			}
			$arg = $args[$argIndex];
			if (!$arg instanceof Arg || $arg->name === null) {
				continue;
			}
			$boundNames[] = $arg->name->toString();
		}
		if ($templateTypeMap->isEmpty()) {
			return [$closureParameters, $isVariadic, $returnType, $templateTypeMap, $boundNames];
		}

		$inferred = TemplateTypeMap::createEmpty();
		foreach ($bound as [$parameter, $argIndex]) {
			if (!isset($boundArgTypes[$argIndex])) {
				continue;
			}
			$inferred = $inferred->union($parameter->getType()->inferTemplateTypes($boundArgTypes[$argIndex]));
		}
		if ($inferred->isEmpty()) {
			return [$closureParameters, $isVariadic, $returnType, $templateTypeMap, $boundNames];
		}

		$inClosureParameters = [];
		foreach ($closureParameters as $parameter) {
			TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use ($templateTypeMap, &$inClosureParameters): Type {
				if (self::isOwnTemplate($type, $templateTypeMap)) {
					$inClosureParameters[$type->getName()] = true;
				}

				return $traverse($type);
			});
		}

		$returnType = TypeTraverser::map($returnType, static function (Type $type, callable $traverse) use ($templateTypeMap, $inferred, $inClosureParameters): Type {
			if (!self::isOwnTemplate($type, $templateTypeMap)) {
				return $traverse($type);
			}
			$inferredType = $inferred->getType($type->getName());
			if ($inferredType === null) {
				return $type;
			}
			if (isset($inClosureParameters[$type->getName()])) {
				return TypeCombinator::union($type, $inferredType);
			}

			return $inferredType;
		});

		$closureTemplateTypes = [];
		foreach ($templateTypeMap->getTypes() as $name => $templateType) {
			if (!isset($inClosureParameters[$name]) && $inferred->hasType($name)) {
				continue;
			}
			$closureTemplateTypes[$name] = $templateType;
		}

		return [$closureParameters, $isVariadic, $returnType, new TemplateTypeMap($closureTemplateTypes), $boundNames];
	}

	/**
	 * PHP calls a method it does not declare through __call() or __callStatic(), so the
	 * closure of its partial application is one of that trampoline, (mixed ...$arguments):
	 * the placeholders become $arguments0, $arguments1..., and a named one throws.
	 *
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @return list<string>|null the names of the closure's first parameters, the ones of its
	 *                           positional placeholders; null when creating the closure throws
	 */
	public static function getMagicMethodParameterNames(FunctionReflection|MethodReflection|null $function, array $args): ?array
	{
		if (!self::isMagicMethod($function)) {
			return [];
		}

		$names = [];
		foreach ($args as $arg) {
			if (!$arg instanceof ArgPlaceholder) {
				continue;
			}
			if ($arg->name !== null) {
				return null;
			}
			$names[] = 'arguments' . count($names);
		}

		return $names;
	}

	/** @phpstan-assert-if-true MethodReflection $function */
	public static function isMagicMethod(FunctionReflection|MethodReflection|null $function): bool
	{
		return $function instanceof MethodReflection
			&& !$function->getDeclaringClass()->hasNativeMethod($function->getName());
	}

	/**
	 * @phpstan-assert-if-true TemplateType $type
	 */
	private static function isOwnTemplate(Type $type, TemplateTypeMap $templateTypeMap): bool
	{
		if (!$type instanceof TemplateType) {
			return false;
		}
		$templateType = $templateTypeMap->getType($type->getName());

		return $templateType instanceof TemplateType && $templateType->getScope()->equals($type->getScope());
	}

	/**
	 * @param list<ParameterReflection> $parameters
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @return array{list<ParameterReflection>, bool, list<array{ParameterReflection, int}>}|null the closure's parameters,
	 *         whether it is variadic and the parameters the bound arguments go to, null when creating the closure throws
	 */
	private static function map(array $parameters, bool $isVariadic, array $args): ?array
	{
		$variadicParameter = null;
		$declared = $parameters;
		if (count($parameters) > 0 && $parameters[count($parameters) - 1]->isVariadic()) {
			$variadicParameter = $parameters[count($parameters) - 1];
			unset($declared[count($parameters) - 1]);
		}

		$indexesByName = [];
		foreach ($declared as $i => $parameter) {
			$indexesByName[$parameter->getName()] = $i;
		}

		$positional = [];
		$named = [];
		$covered = [];
		$hasVariadicPlaceholder = false;
		$position = 0;
		$variadicPlaceholders = 0;
		$seenNamed = false;
		$bound = [];
		foreach ($args as $argIndex => $arg) {
			if ($hasVariadicPlaceholder) {
				// the variadic placeholder must be last and may only appear once
				return null;
			}
			if ($arg instanceof VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}

			if ($arg instanceof Arg && $arg->unpack) {
				// cannot combine partial application and unpacking
				return null;
			}

			$isPlaceholder = $arg instanceof ArgPlaceholder;
			$name = $arg->name;
			if ($name === null) {
				if ($seenNamed) {
					// positional argument after a named one
					return null;
				}
				if (isset($declared[$position])) {
					$covered[$position] = true;
					if ($isPlaceholder) {
						$positional[] = self::required($declared[$position], $declared[$position]->getName());
					} else {
						$bound[] = [$declared[$position], $argIndex];
					}
				} elseif ($variadicParameter !== null) {
					if ($isPlaceholder) {
						$positional[] = self::required($variadicParameter, $variadicParameter->getName() . $variadicPlaceholders);
						$variadicPlaceholders++;
					} else {
						$bound[] = [$variadicParameter, $argIndex];
					}
				} else {
					// too many arguments
					return null;
				}
				$position++;
				continue;
			}

			$seenNamed = true;
			$nameString = $name->toString();
			if (!array_key_exists($nameString, $indexesByName)) {
				if ($isPlaceholder || $variadicParameter === null) {
					// a named placeholder for an unknown or variadic parameter,
					// or an unknown named argument without a variadic to collect it
					return null;
				}
				$bound[] = [$variadicParameter, $argIndex];
				continue;
			}

			$index = $indexesByName[$nameString];
			if (isset($covered[$index])) {
				// overwrites a previous argument or placeholder
				return null;
			}
			$covered[$index] = true;
			if (!$isPlaceholder) {
				$bound[] = [$declared[$index], $argIndex];
				continue;
			}

			$named[] = self::required($declared[$index], $nameString);
		}

		$lastCovered = $covered === [] ? -1 : max(array_keys($covered));
		foreach ($declared as $i => $parameter) {
			if (isset($covered[$i]) || !$parameter->getDefaultValue() instanceof ErrorType) {
				continue;
			}
			if ($hasVariadicPlaceholder || ($i < $lastCovered && $parameter->isOptional())) {
				// PHP refuses to fill in a default it does not know
				return null;
			}
		}

		$closureParameters = [...$positional, ...$named];
		if (!$hasVariadicPlaceholder) {
			foreach ($declared as $i => $parameter) {
				if (!isset($covered[$i]) && !$parameter->isOptional()) {
					// too few arguments
					return null;
				}
			}

			return [$closureParameters, false, $bound];
		}

		foreach ($declared as $i => $parameter) {
			if (isset($covered[$i])) {
				continue;
			}
			$closureParameters[] = $parameter;
		}
		if ($variadicParameter !== null) {
			$closureParameters[] = $variadicParameter;
		}

		// the variadic placeholder forwards the extra arguments to the callee
		return [$closureParameters, $isVariadic, $bound];
	}

	/**
	 * @return array<int, Type> the types of the bound arguments, keyed like the raw arguments
	 */
	public static function getBoundArgTypes(Scope $scope, CallLike $call): array
	{
		$types = [];
		foreach ($call->getRawArgs() as $i => $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}
			$types[$i] = $scope->getType($arg->value);
		}

		return $types;
	}

	/**
	 * The variant whose partial application the arguments fit. When several fit,
	 * they are combined; when none does, all of them are.
	 *
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @param list<ParametersAcceptor> $variants
	 * @param list<ParametersAcceptor>|null $namedArgumentsVariants
	 */
	public static function selectVariant(array $args, array $variants, ?array $namedArgumentsVariants): ParametersAcceptor
	{
		if ($namedArgumentsVariants !== null) {
			foreach ($args as $arg) {
				if (!$arg instanceof VariadicPlaceholder && $arg->name !== null) {
					$variants = $namedArgumentsVariants;
					break;
				}
			}
		}

		foreach ($variants as $variant) {
			if (self::leavesUnknownDefaultValue([$variant], $args)) {
				return $variant;
			}
		}

		if (count($variants) === 1) {
			return $variants[0];
		}

		$fitting = [];
		foreach ($variants as $variant) {
			if (self::map($variant->getParameters(), $variant->isVariadic(), $args) === null) {
				continue;
			}
			$fitting[] = $variant;
		}

		if (count($fitting) === 1) {
			return $fitting[0];
		}

		if (count($fitting) === 0) {
			$closestVariant = self::selectVariantClosestToArgumentsCount($args, $variants);
			if ($closestVariant !== null) {
				return $closestVariant;
			}
		}

		return ParametersAcceptorSelector::combineAcceptors(count($fitting) > 0 ? $fitting : $variants);
	}

	/**
	 * The variants of a built-in describe one real signature, so when ... leaves out a parameter
	 * whose default PHP does not know in any of them, like $value of ReflectionProperty::setValue(?, ...),
	 * creating the closure throws whichever variant the arguments fit.
	 *
	 * @param ParametersAcceptor[] $variants
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 */
	public static function leavesUnknownDefaultValue(array $variants, array $args): bool
	{
		$positionalCount = 0;
		$names = [];
		$hasVariadicPlaceholder = false;
		foreach ($args as $arg) {
			if ($arg instanceof VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			if ($arg->name !== null) {
				$names[$arg->name->toString()] = true;
				continue;
			}
			$positionalCount++;
		}
		if (!$hasVariadicPlaceholder) {
			return false;
		}

		foreach ($variants as $variant) {
			foreach ($variant->getParameters() as $i => $parameter) {
				if (
					$i >= $positionalCount
					&& !$parameter->isVariadic()
					&& !array_key_exists($parameter->getName(), $names)
					&& $parameter->getDefaultValue() instanceof ErrorType
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Combining the variants () and ($min, $max) of mt_rand() would accept mt_rand(?),
	 * so like a call, a count of positional arguments that the combined variants
	 * accept but no variant does is checked against the variant closest to it.
	 *
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @param list<ParametersAcceptor> $variants
	 */
	private static function selectVariantClosestToArgumentsCount(array $args, array $variants): ?ParametersAcceptor
	{
		$argsCount = 0;
		$hasVariadicPlaceholder = false;
		foreach ($args as $arg) {
			if ($hasVariadicPlaceholder || ($arg instanceof Arg && $arg->unpack) || (!$arg instanceof VariadicPlaceholder && $arg->name !== null)) {
				return null;
			}
			if ($arg instanceof VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			$argsCount++;
		}

		$closestVariant = null;
		$closestDistance = null;
		$combinedMinCount = null;
		$combinedMaxCount = 0;
		foreach ($variants as $variant) {
			$minCount = 0;
			foreach ($variant->getParameters() as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}
				$minCount++;
			}
			$maxCount = $variant->isVariadic() ? null : count($variant->getParameters());
			$combinedMinCount = min($combinedMinCount ?? $minCount, $minCount);
			$combinedMaxCount = $maxCount === null || $combinedMaxCount === null ? null : max($combinedMaxCount, $maxCount);

			// the variadic placeholder fills in the parameters left
			$isInsufficient = !$hasVariadicPlaceholder && $argsCount < $minCount;
			if (!$isInsufficient && ($maxCount === null || $argsCount <= $maxCount)) {
				// the count fits, the arguments do not for another reason
				return null;
			}

			$distance = $isInsufficient ? $minCount - $argsCount : $argsCount - $maxCount;
			if ($closestDistance !== null && ($distance > $closestDistance || ($distance === $closestDistance && !$isInsufficient))) {
				continue;
			}

			$closestVariant = $variant;
			$closestDistance = $distance;
		}

		if (
			(!$hasVariadicPlaceholder && $argsCount < $combinedMinCount)
			|| ($combinedMaxCount !== null && $argsCount > $combinedMaxCount)
		) {
			return null;
		}

		return $closestVariant;
	}

	private static function required(ParameterReflection $parameter, string $name): ParameterReflection
	{
		if ($parameter instanceof ExtendedParameterReflection) {
			return new ExtendedDummyParameter(
				$name,
				$parameter->getType(),
				false,
				$parameter->passedByReference(),
				false,
				null,
				$parameter->getNativeType(),
				$parameter->getPhpDocType(),
				$parameter->getOutType(),
				$parameter->isImmediatelyInvokedCallable(),
				$parameter->getClosureThisType(),
				$parameter->getAttributes(),
				$parameter->getAllowedConstants(),
				$parameter->isPureUnlessCallableIsImpureParameter(),
				$parameter->isPureUnlessParameterPassedParameter(),
			);
		}

		return new DummyParameter(
			$name,
			$parameter->getType(),
			false,
			$parameter->passedByReference(),
			false,
			null,
		);
	}

}
