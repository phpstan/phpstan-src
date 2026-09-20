<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Arg;
use function array_search;

/**
 * Maps the arguments of a call onto the positions of the callee's parameters.
 *
 * Named arguments can be written in any order, so the position an argument
 * occupies in the source says nothing about the parameter it fills. The
 * intrinsic argument visitors and the overrides in ParametersAcceptorSelector
 * both reason about specific parameters of well-known functions, so they look
 * their arguments up through here instead of indexing the call's arguments.
 *
 * @internal
 */
final class ArgumentPositionHelper
{

	/**
	 * Named arguments that don't match any of $parameterNames are left out -
	 * they don't fill any of the parameters the caller asks about.
	 *
	 * @param Arg[] $args
	 * @param list<string> $parameterNames parameter names in signature order
	 * @return array<int, Arg>
	 */
	public static function getArgsByPosition(array $args, array $parameterNames): array
	{
		$hasNamedArgs = false;
		foreach ($args as $arg) {
			if ($arg->name === null) {
				continue;
			}

			$hasNamedArgs = true;
			break;
		}

		if (!$hasNamedArgs) {
			return $args;
		}

		$argsByPosition = [];
		foreach ($args as $i => $arg) {
			if ($arg->name === null) {
				// positional arguments always precede named ones
				$argsByPosition[$i] = $arg;
				continue;
			}

			$position = array_search($arg->name->toString(), $parameterNames, true);
			if ($position === false) {
				continue;
			}

			$argsByPosition[$position] = $arg;
		}

		return $argsByPosition;
	}

}
