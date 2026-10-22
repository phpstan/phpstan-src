<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_merge;
use function in_array;
use function is_a;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * Finds the methods a native class could not declare in the PHAR.
 *
 * The PHAR's PHP code is downgraded to PHP 7.4 (compiler/bin/prepare,
 * simple-downgrader), which rewrites parameter types 7.4 cannot express:
 * union and `mixed` types are dropped, an intersection of PHPStan\Analyser
 * interfaces with Scope becomes Scope, any other intersection is dropped. A
 * native class keeps the source signatures, so where it overrides or
 * implements a method of a PHP class or interface whose parameter type the
 * downgrade rewrites, its own parameter has to accept the rewritten type —
 * otherwise linking the native class when the extension activates is a
 * fatal error, in the PHAR only. Return types need no check: the downgrade
 * only drops them, and a child may declare any return type then.
 */
final class DowngradedSignatureCheck
{

	/**
	 * @param ReflectionClass<object> $class the native class
	 * @param list<string> $nativeClasses every class the extension shadows: their signatures are not downgraded
	 * @return list<string> the conflicts
	 */
	public static function conflicts(ReflectionClass $class, array $nativeClasses): array
	{
		$native = [];
		foreach ($nativeClasses as $nativeClass) {
			$native[strtolower($nativeClass)] = true;
		}

		$ancestors = $class->getInterfaces();
		for ($parent = $class->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
			$ancestors[] = $parent;
		}

		$conflicts = [];
		foreach ($class->getMethods() as $method) {
			if ($method->getDeclaringClass()->getName() !== $class->getName()) {
				continue;
			}
			foreach ($ancestors as $ancestor) {
				// built-in classes are not downgraded
				if ($ancestor->isInternal() || isset($native[strtolower($ancestor->getName())]) || !$ancestor->hasMethod($method->getName())) {
					continue;
				}
				$prototype = $ancestor->getMethod($method->getName());
				if ($prototype->getDeclaringClass()->getName() !== $ancestor->getName() || $prototype->isPrivate()) {
					continue;
				}
				// PHP holds a constructor to its parent's signature only when that one is abstract
				if ($method->isConstructor() && !$prototype->isAbstract() && !$ancestor->isInterface()) {
					continue;
				}
				$conflicts = array_merge($conflicts, self::parameterConflicts($method, $prototype));
			}
		}

		return $conflicts;
	}

	/** @return list<string> */
	private static function parameterConflicts(ReflectionMethod $method, ReflectionMethod $prototype): array
	{
		$conflicts = [];
		$parameters = $method->getParameters();
		foreach ($prototype->getParameters() as $position => $prototypeParameter) {
			$parameter = $parameters[$position] ?? null;
			$sourceType = $prototypeParameter->getType();
			if ($parameter === null || $sourceType === null) {
				continue;
			}
			[$changed, $downgraded] = self::downgrade($sourceType);
			if (!$changed || self::accepts($parameter->getType(), $downgraded)) {
				continue;
			}
			$conflicts[] = sprintf(
				'%s::%s() parameter $%s is %s, but the PHAR downgrades %s::%s()\'s %s to %s, which the native class cannot narrow',
				$method->getDeclaringClass()->getName(),
				$method->getName(),
				$parameter->getName(),
				(string) $parameter->getType(),
				$prototype->getDeclaringClass()->getName(),
				$prototype->getName(),
				(string) $sourceType,
				$downgraded ?? 'no type',
			);
		}

		return $conflicts;
	}

	/**
	 * What simple-downgrader leaves of a parameter type: [whether it changes, the class it keeps (null = no type)].
	 *
	 * @return array{bool, string|null}
	 */
	private static function downgrade(ReflectionType $type): array
	{
		if ($type instanceof ReflectionNamedType) {
			if (in_array(strtolower($type->getName()), ['mixed', 'null', 'false', 'true'], true)) {
				return [true, null];
			}
			return [false, null];
		}
		if ($type instanceof ReflectionIntersectionType) {
			$hasScope = false;
			foreach ($type->getTypes() as $member) {
				if (!$member instanceof ReflectionNamedType || preg_match('~^PHPStan\\\\Analyser\\\\\w+$~', $member->getName()) !== 1) {
					return [true, null];
				}
				$hasScope = $hasScope || $member->getName() === 'PHPStan\Analyser\Scope';
			}
			return [true, $hasScope ? 'PHPStan\Analyser\Scope' : null];
		}

		// a union (`X|null` alone reflects as a nullable named type, kept)
		return [true, null];
	}

	/** Whether a native parameter of type $type accepts every value of the downgraded type. */
	private static function accepts(?ReflectionType $type, ?string $downgraded): bool
	{
		if ($type === null) {
			return true;
		}
		if ($type instanceof ReflectionNamedType) {
			if ($type->getName() === 'mixed') {
				return true;
			}
			return $downgraded !== null && !$type->isBuiltin() && is_a($downgraded, $type->getName(), true);
		}
		if ($type instanceof ReflectionUnionType) {
			foreach ($type->getTypes() as $member) {
				if (self::accepts($member, $downgraded)) {
					return true;
				}
			}
		}

		return false;
	}

}
