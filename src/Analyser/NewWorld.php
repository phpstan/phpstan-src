<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * Transitional switch between the old world (multi-pass type resolution via
 * MutatingScope::resolveType + TypeSpecifier, PHP < 8.1 or PHPSTAN_FNSR=0)
 * and the new world (single-pass ExpressionResult callbacks + Fibers).
 *
 * Deleted in PHPStan 3.0 together with the old world.
 */
final class NewWorld
{

	/**
	 * The single switch for the guard exceptions in MutatingScope::getType()/
	 * getNativeType()/getKeepVoidType() and TypeSpecifier::specifyTypesInCondition().
	 *
	 * The committed state is false = mixed mode — migrated handlers run their
	 * callbacks, everything else takes the legacy bridges; the whole test suite
	 * must be green here. Flip to true when starting to migrate a handler: the
	 * old-world entry points then throw on the new-world path — the migration
	 * meter for the per-handler TDD loop (see NEW_WORLD.md §5a).
	 *
	 * The PHP version and PHPSTAN_FNSR gating stays at the call sites.
	 */
	public static function disableOldWorld(): bool
	{
		return false;
	}

}
