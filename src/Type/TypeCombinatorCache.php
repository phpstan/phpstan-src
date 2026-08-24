<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * Memoization seam for TypeCombinator's binary operations.
 *
 * This PHP implementation performs no caching at all — it delegates straight to
 * TypeCombinator. The native implementation memoizes each operation on a
 * structural key of its arguments; roughly 91% of the calls in a
 * self-analysis run repeat an argument tuple that was already computed. It also
 * interns the results, keeping one canonical instance per distinct type value,
 * so operations reaching the same value by different routes return the same
 * object and identity checks can stand in for structural comparison.
 *
 * The cache is scoped to a single container: it hands back shared Type
 * instances, and Type objects carry a lazily resolved ClassReflection tied to the
 * container that created them.
 *
 * @internal
 */
#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\TypeCombinatorCache', implementation: __DIR__ . '/../../turbo-ext/src/TypeCombinatorCache.cpp')]
final class TypeCombinatorCache
{

	public static function union(Type ...$types): Type
	{
		return TypeCombinator::doUnion(...$types);
	}

	public static function intersect(Type ...$types): Type
	{
		return TypeCombinator::doIntersect(...$types);
	}

	public static function remove(Type $fromType, Type $typeToRemove): Type
	{
		return TypeCombinator::doRemove($fromType, $typeToRemove);
	}

	public static function clearCache(): void
	{
	}

}
