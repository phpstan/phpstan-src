<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension replaces with the named native
 * implementation (found in the .cpp file $implementation points at), so
 * every method must behave exactly like its native counterpart (see
 * turbo-ext/README.md for the sync machinery).
 *
 * On composer dump-autoload, build/generate-turbo-stubs.php collects these
 * attributes and generates vendor/turbo-stubs.php — an empty stub shell per
 * class extending the native one and repeating its implements clause (a
 * parent class is rejected: the shell has no inheritance slot left for it)
 * — which TurboExtensionEnabler declares
 * before the Composer autoloader registers when the extension is active,
 * plus vendor/turbo-shadowed-classes.json — the manifest of shadowed pairs
 * read by the enabler, the compiler's preload builder, and the parity
 * tooling.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ShadowedByTurboExtension
{

	public function __construct(public string $turboClass, public string $implementation)
	{
	}

}
