<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Php\PhpFunctionReflection;

/**
 * Reads the @arguments-by-name and @optional-arguments-by-name tags that apply to a call.
 *
 * For a method, the tags come from the PHPDoc of the method, of the methods
 * it overrides, and of the class that declares the method.
 *
 * The first boolean tells whether all arguments must be passed by name.
 * The second one tells whether the arguments of optional parameters must be passed by name.
 */
final class ArgumentsByNameHelper
{

	/**
	 * @return array{bool, bool}
	 */
	public static function getForMethod(ExtendedMethodReflection $method): array
	{
		return self::getFromPhpDocs([
			$method->getResolvedPhpDoc(),
			$method->getDeclaringClass()->getResolvedPhpDoc(),
		]);
	}

	/**
	 * @return array{bool, bool}
	 */
	public static function getForFunction(FunctionReflection $function): array
	{
		if (!$function instanceof PhpFunctionReflection) {
			return [false, false];
		}

		return self::getFromPhpDocs([$function->getResolvedPhpDoc()]);
	}

	/**
	 * @param list<ResolvedPhpDocBlock|null> $phpDocs
	 * @return array{bool, bool}
	 */
	private static function getFromPhpDocs(array $phpDocs): array
	{
		$requiresArgumentsByName = false;
		$requiresOptionalArgumentsByName = false;
		foreach ($phpDocs as $phpDoc) {
			if ($phpDoc === null) {
				continue;
			}

			$requiresArgumentsByName = $requiresArgumentsByName || $phpDoc->requiresArgumentsByName();
			$requiresOptionalArgumentsByName = $requiresOptionalArgumentsByName || $phpDoc->requiresOptionalArgumentsByName();
		}

		return [$requiresArgumentsByName, $requiresOptionalArgumentsByName];
	}

}
