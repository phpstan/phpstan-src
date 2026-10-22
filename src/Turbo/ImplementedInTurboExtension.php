<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension replaces with a native
 * implementation written by hand in the .cpp file $implementation points
 * at, so every method must behave exactly like its native counterpart:
 * a change to the PHP class is ported to the C++ by hand (see
 * turbo-ext/README.md for the sync machinery). Classes whose native
 * implementation is generated from the PHP class carry
 * CompiledIntoTurboExtension instead; both kinds are shadowed the same way.
 *
 * With the extension active, TurboExtensionEnabler::activateIfCompatible()
 * declares the native implementation under this very class name right
 * after the Composer autoloader registers (a linked class like any PHP
 * declaration: same final flag, parent and interfaces), so the PHP twin is
 * never loaded and every reference resolves to the native class. The twin's
 * source file stays the class's file for reflection.
 *
 * On composer dump-autoload, build/generate-turbo-manifest.php collects
 * the attributes of both kinds into vendor/turbo-shadowed-classes.json — the manifest
 * of shadowed pairs read by the enabler, the compiler's preload builder,
 * and the parity tooling. The name the differential tests declare the
 * native class under next to its twin is derived there the way the
 * extension derives it: "PHPStanTurbo\" plus the short class name.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ImplementedInTurboExtension
{

	public function __construct(public string $implementation)
	{
	}

}
