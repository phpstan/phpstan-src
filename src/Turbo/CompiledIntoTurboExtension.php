<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Attribute;

/**
 * Marks a class the phpstan_turbo extension replaces with a native class
 * compiled from this PHP class by turbo-ext/bin/compile-twins.php. After
 * editing the class, rerun the compiler; CI fails while a generated file is
 * stale (`compile-twins.php --check`). Classes ported by hand carry
 * ImplementedInTurboExtension instead.
 *
 * The generated file is turbo-ext/src/compiled/<stem>.cpp, the stem being the short
 * class name — or the name past its first namespace segment, without the
 * separators, when another shadowed class has the same short name. At run
 * time the class is shadowed like a hand-written port (see
 * ImplementedInTurboExtension), and the manifest marks its entry compiled.
 *
 * The arguments describe the native C API the rest of the extension uses:
 *
 * - $ce: the class-entry variable, pt_ce_<stem in snake case> by default
 * - $defineCe: whether the generated file defines that variable (false when
 *   hand-written code declared it before the class was compiled)
 * - $new: [function, flavor] — a `new X(...)` entry for native callers
 *   (val: zv::Val f(<constructor parameters>), out: bool f(zval *out, ...),
 *   argv: zv::Val f(uint32_t argc, zval *argv))
 * - $dispatch: a reflection family's member dispatch — function, enum,
 *   prefix of its constants, receiver parameter name, fallback for members
 *   the class does not declare
 *
 * Exports with any other shape stay hand-written in CompiledSupport.cpp.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class CompiledIntoTurboExtension
{

	/**
	 * @param array{string, 'val'|'out'|'argv'}|null $new
	 * @param array{function: string, enum: string, prefix: string, receiver: string, fallback?: string}|null $dispatch
	 */
	public function __construct(
		public ?string $ce = null,
		public bool $defineCe = true,
		public ?array $new = null,
		public ?array $dispatch = null,
	)
	{
	}

}
