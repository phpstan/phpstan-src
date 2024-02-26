<?php declare(strict_types=1); // lint > 8.0

namespace GenericCallablesIncompatible;

use Closure;
use stdClass;

/**
 * @param Closure<stdClass>(stdClass $val): stdClass $existingClass
 */
function existingClass(Closure $existingClass): void
{
}

/**
 * @param Closure<TypeAlias>(TypeAlias $val): TypeAlias $existingTypeAlias
 */
function existingTypeAlias(Closure $existingTypeAlias): void
{
}

/**
 * @param Closure<T of Invalid>(T $val): T $invalidBoundType
 */
function invalidBoundType(Closure $invalidBoundType): void
{
}

/**
 * @param Closure<T of null>(T $val): T $notSupported
 */
function notSupported(Closure $notSupported): void
{
}

/**
 * @template T
 * @param Closure<T>(T $val): T $shadows
 */
function testShadowFunction(Closure $shadows): void
{
}

/**
 * @param-out Closure<stdClass>(stdClass $val): stdClass $existingClass
 */
function existingClassParamOut(Closure &$existingClass): void
{
}

/**
 * @template U
 */
class Test
{
	/**
	 * @template T
	 * @param Closure<T, U>(T $val): T $shadows
	 */
	function testShadowMethod(Closure $shadows): void
	{
	}

	/**
	 * @template T
	 * @return Closure<T, U>(T $val): T
	 */
	function testShadowMethodReturn(): Closure
	{
	}
}

/**
 * @return Closure<stdClass>(stdClass $val): stdClass
 */
function existingClassReturn(): Closure
{
}

/**
 * @return Closure<TypeAlias>(TypeAlias $val): TypeAlias
 */
function existingTypeAliasReturn(): Closure
{
}

/**
 * @return Closure<T of Invalid>(T $val): T
 */
function invalidBoundTypeReturn(): Closure
{
}

/**
 * @return Closure<T of null>(T $val): T
 */
function notSupportedReturn(): Closure
{
}

/**
 * @template T
 * @return Closure<T>(T $val): T
 */
function testShadowFunctionReturn(): Closure
{
}

/**
 * @template U
 */
class Test2
{
	/**
	 * @param Closure<stdClass>(stdClass $val): stdClass $existingClass
	 */
	public function existingClass(Closure $existingClass): void
	{
	}

	/**
	 * @param Closure<TypeAlias>(TypeAlias $val): TypeAlias $existingTypeAlias
	 */
	public function existingTypeAlias(Closure $existingTypeAlias): void
	{
	}

	/**
	 * @param Closure<T of Invalid>(T $val): T $invalidBoundType
	 */
	public function invalidBoundType(Closure $invalidBoundType): void
	{
	}

	/**
	 * @param Closure<T of null>(T $val): T $notSupported
	 */
	public function notSupported(Closure $notSupported): void
	{
	}

	/**
	 * @return Closure<stdClass>(stdClass $val): stdClass
	 */
	public function existingClassReturn(): Closure
	{
	}

	/**
	 * @return Closure<TypeAlias>(TypeAlias $val): TypeAlias
	 */
	public function existingTypeAliasReturn(): Closure
	{
	}

	/**
	 * @return Closure<T of Invalid>(T $val): T
	 */
	public function invalidBoundTypeReturn(): Closure
	{
	}

	/**
	 * @return Closure<T of null>(T $val): T
	 */
	public function notSupportedReturn(): Closure
	{
	}
}

/**
 * @template T
 * @param-out Closure<T>(T $val): T $existingClass
 */
function shadowsParamOut(Closure &$existingClass): void
{
}

/**
 * @template T
 * @param-out list<Closure<T>(T $val): T> $existingClasses
 */
function shadowsParamOutArray(array &$existingClasses): void
{
}

/**
 * @template T
 * @return list<Closure<T>(T $val): T>
 */
function shadowsReturnArray(): array
{
}

/**
 * @template T
 */
class Test3
{
	/**
	 * @param Closure<T>(T): T $shadows
	 */
	public function __construct(private Closure $shadows) {}
}
