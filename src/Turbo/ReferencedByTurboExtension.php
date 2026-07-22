<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension's native code references at run
 * time — for instanceof-style checks, static calls, throws, or (the `…Impl`
 * keys) instantiation — under the given key of the native class-reference
 * table (pt_class_refs in turbo-ext/src/support.cpp).
 *
 * On composer dump-autoload, build/generate-turbo-stubs.php collects these
 * attributes into vendor/turbo-class-map.php — the map TurboExtensionEnabler
 * passes to PHPStanTurbo\Runtime::configure() — so a renamed class updates
 * the map on the next dump instead of going stale in a hand-written list.
 * Referenced classes living in vendor/ cannot carry the attribute and are
 * hardcoded in the generator instead.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ReferencedByTurboExtension
{

	public function __construct(public string $key)
	{
	}

}
