<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension replaces with the named native
 * implementation (found in the .cpp file $implementation points at), so
 * every method must behave exactly like its native counterpart (see
 * turbo-ext/README.md for the sync machinery).
 *
 * With the extension active, TurboExtensionEnabler::activateIfCompatible()
 * declares the native implementation under this very class name right
 * after the Composer autoloader registers (a linked class like any PHP
 * declaration: same final flag, parent and interfaces), so the PHP twin is
 * never loaded and every reference resolves to the native class. The twin's
 * source file stays the class's file for reflection.
 *
 * On composer dump-autoload, build/generate-turbo-manifest.php collects
 * these attributes into vendor/turbo-shadowed-classes.json — the manifest
 * of shadowed pairs read by the enabler, the compiler's preload builder,
 * and the parity tooling. $turboClass is the name the differential tests
 * declare the native class under next to the twin ("PHPStanTurbo\" plus
 * the short class name).
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ShadowedByTurboExtension
{

	public function __construct(public string $turboClass, public string $implementation)
	{
	}

}
