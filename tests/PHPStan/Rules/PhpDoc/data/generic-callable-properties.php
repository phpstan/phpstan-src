<?php declare(strict_types=1); // lint >= 8.0

namespace GenericCallableProperties;

use Closure;
use stdClass;

/**
 * @template T
 */
class Test
{
	/**
	 * @var Closure<T>(T): T
	 */
	private Closure $shadows;

	/**
	 * @var Closure<stdClass>(stdClass): stdClass
	 */
	private Closure $existingClass;

	/**
	 * @var callable<TypeAlias>(TypeAlias): TypeAlias
	 */
	private $typeAlias;

	/**
	 * @var callable<TNull of null>(TNull): TNull
	 */
	private $unsupported;

	/**
	 * @var callable<TInvalid of Invalid>(TInvalid): TInvalid
	 */
	private $invalid;

	/**
	 * @param Closure<T>(T): T $notReported
	 */
	public function __construct(private Closure $notReported) {}
}
