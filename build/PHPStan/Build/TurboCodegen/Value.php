<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

/**
 * A lowered expression: a C++ expression plus what the compiler knows
 * about it.
 *
 * - `long` / `bool`: an unboxed zend_long / bool C++ expression
 * - `zval`: a `zval *` C++ expression — borrowed (valid until the next side
 *   effect) unless $temp names the zv::Val that owns it, or $stable says
 *   it reads a variable nothing in the function writes
 * - `null`: the PHP null constant
 * - `void`: no value
 */
final class Value
{

	/**
	 * @param 'long'|'bool'|'zval'|'null'|'void' $kind
	 * @param string|null $class a class the value is proven to be an instance of (non-null object)
	 */
	public function __construct(
		public readonly string $kind,
		public readonly string $code,
		public readonly ?string $temp = null,
		public readonly ?string $class = null,
		public readonly bool $isArray = false,
		public readonly bool $stable = false,
	)
	{
	}

	public static function long(string $code): self
	{
		return new self('long', $code);
	}

	public static function bool(string $code): self
	{
		return new self('bool', $code);
	}

	public static function null(): self
	{
		return new self('null', 'NULL');
	}

	public static function void(): self
	{
		return new self('void', '');
	}

}
