<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PHPStan\Type\Type;

/**
 * A PHP local variable's C++ home.
 *
 * - `long` / `bool`: a C++ scalar
 * - `zval`: an owned zv::Val (`param` = a borrowed `zval *` parameter never reassigned)
 * - `callable`: a pgrt::Callable parameter
 * - `vector`: a variadic parameter (`zval *` + `uint32_t` count)
 * - `constarray`: a `static` variable holding a constant array that is never written
 * - `list`: a local list that never escapes — only `[]`, `$x[] = ...` and `...$x` — kept in a pgrt::ValVec
 */
final class LocalVariable
{

	/**
	 * @param 'long'|'bool'|'zval'|'param'|'callable'|'vector'|'constarray'|'list' $kind
	 */
	public function __construct(
		public readonly string $kind,
		public readonly string $cName,
		public readonly ?string $class = null,
		public readonly bool $isArray = false,
		public readonly ?Type $constant = null,
	)
	{
	}

}
