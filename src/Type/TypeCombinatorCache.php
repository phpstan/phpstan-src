<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * Memoization seam for TypeCombinator's binary operations.
 *
 * This PHP implementation performs no caching at all — it delegates straight to
 * TypeCombinator. The native implementation memoizes each operation on a
 * structural key of its arguments; roughly 91% of the calls in a
 * self-analysis run repeat an argument tuple that was already computed.
 *
 * The cache is scoped to a single container: memoization hands back shared Type
 * instances, and Type objects carry a lazily resolved ClassReflection tied to the
 * container that created them.
 *
 * @internal
 */
#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\TypeCombinatorCache')]
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
