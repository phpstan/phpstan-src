<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_key_exists;
use function count;

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
	 * @param list<ParameterReflection> $parameters
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @return array{list<ParameterReflection>, bool}|null the closure's parameters and whether it is variadic,
	 *                                                    null when creating the closure throws
	 */
	public static function apply(array $parameters, array $args): ?array
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
		foreach ($args as $arg) {
			if ($arg instanceof VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			if ($hasVariadicPlaceholder) {
				// the variadic placeholder must be last
				return null;
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
					}
				} elseif ($variadicParameter !== null) {
					if ($isPlaceholder) {
						$positional[] = self::required($variadicParameter, $variadicParameter->getName() . $variadicPlaceholders);
						$variadicPlaceholders++;
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
				continue;
			}

			$index = $indexesByName[$nameString];
			if (isset($covered[$index])) {
				// overwrites a previous argument or placeholder
				return null;
			}
			$covered[$index] = true;
			if (!$isPlaceholder) {
				continue;
			}

			$named[] = self::required($declared[$index], $nameString);
		}

		$closureParameters = [...$positional, ...$named];
		if (!$hasVariadicPlaceholder) {
			foreach ($declared as $i => $parameter) {
				if (!isset($covered[$i]) && !$parameter->isOptional()) {
					// too few arguments
					return null;
				}
			}

			return [$closureParameters, false];
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

		return [$closureParameters, $variadicParameter !== null];
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
