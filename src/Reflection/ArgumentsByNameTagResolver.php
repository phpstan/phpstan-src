<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use function strtolower;

/**
 * Finds which of @arguments-by-name, @optional-arguments-by-name and @stable-parameter-names applies to a function or a method.
 *
 * For a method, the tag on the method comes first. Then comes the tag of the method it overrides or implements,
 * except for constructors. The class-level tag of the declaring class comes last, except for private methods.
 * A docblock with @no-named-arguments has none of these tags.
 */
final class ArgumentsByNameTagResolver
{

	public const ARGUMENTS_BY_NAME = '@arguments-by-name';

	public const OPTIONAL_ARGUMENTS_BY_NAME = '@optional-arguments-by-name';

	public const STABLE_PARAMETER_NAMES = '@stable-parameter-names';

	/**
	 * @return self::*|null
	 */
	public static function resolveForFunction(FunctionReflection $function): ?string
	{
		if (!$function instanceof PhpFunctionReflection) {
			return null;
		}

		return self::resolveFromPhpDoc($function->getResolvedPhpDoc());
	}

	/**
	 * @return self::*|null
	 */
	public static function resolveForMethod(ExtendedMethodReflection $method): ?string
	{
		$phpDoc = $method->getResolvedPhpDoc();
		if ($phpDoc !== null && !$phpDoc->acceptsNamedArguments()) {
			return null;
		}

		$tag = self::resolveFromPhpDoc($phpDoc);
		if ($tag !== null) {
			return $tag;
		}

		$declaringClass = $method->getDeclaringClass();
		if (strtolower($method->getName()) !== '__construct') {
			$tag = self::resolveFromOverriddenMethods($declaringClass, $method->getName());
			if ($tag !== null) {
				return $tag;
			}
		}

		// private methods are only called by their class
		if ($method->isPrivate()) {
			return null;
		}

		return self::resolveFromPhpDoc($declaringClass->getResolvedPhpDoc());
	}

	/**
	 * @return self::*|null
	 */
	private static function resolveFromOverriddenMethods(ClassReflection $class, string $methodName): ?string
	{
		$parentClass = $class->getParentClass();
		$ancestors = $parentClass !== null ? [$parentClass] : [];
		foreach ($class->getImmediateInterfaces() as $interface) {
			$ancestors[] = $interface;
		}

		foreach ($ancestors as $ancestor) {
			if (!$ancestor->hasNativeMethod($methodName)) {
				continue;
			}

			$overriddenMethod = $ancestor->getNativeMethod($methodName);
			if ($overriddenMethod->isPrivate()) {
				continue;
			}

			$tag = self::resolveForMethod($overriddenMethod);
			if ($tag !== null) {
				return $tag;
			}
		}

		return null;
	}

	/**
	 * @return self::*|null
	 */
	private static function resolveFromPhpDoc(?ResolvedPhpDocBlock $phpDoc): ?string
	{
		if ($phpDoc === null || !$phpDoc->acceptsNamedArguments()) {
			return null;
		}

		if ($phpDoc->requiresArgumentsByName()) {
			return self::ARGUMENTS_BY_NAME;
		}

		if ($phpDoc->requiresOptionalArgumentsByName()) {
			return self::OPTIONAL_ARGUMENTS_BY_NAME;
		}

		if ($phpDoc->hasStableParameterNames()) {
			return self::STABLE_PARAMETER_NAMES;
		}

		return null;
	}

}
