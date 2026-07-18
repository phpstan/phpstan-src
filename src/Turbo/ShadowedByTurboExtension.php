<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension replaces with the named native
 * implementation, so every method must behave exactly like its native
 * counterpart (see turbo-ext/README.md for the sync machinery).
 *
 * On composer dump-autoload, build/generate-turbo-stubs.php collects these
 * attributes and generates vendor/turbo-stubs.php — an empty stub shell per
 * class extending the native one — which TurboExtensionEnabler declares
 * before the Composer autoloader registers when the extension is active.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ShadowedByTurboExtension
{

	/**
	 * @param class-string $turboClass
	 */
	public function __construct(public string $turboClass)
	{
	}

}
