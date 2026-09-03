<?php declare(strict_types = 1);

namespace UnresolvedTemplateArgumentNever;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class TT
{

	/** @param T $t */
	public function __construct($t)
	{
	}

}

class UsesTT
{

	/** @var TT<never> */
	private $tt;

	public function __construct()
	{
		$this->tt = new TT([]);
		assertType('UnresolvedTemplateArgumentNever\TT<array{}>', $this->tt);
	}

}

/**
 * @template TGet
 * @template TSet
 */
class Attribute
{

	/** @var (callable(mixed, array<string, mixed>): TGet)|null */
	public $get;

	/** @var (callable(TSet, array<string, mixed>): mixed)|null */
	public $set;

	/**
	 * @param (callable(mixed, array<string, mixed>): TGet)|null $get
	 * @param (callable(TSet, array<string, mixed>): mixed)|null $set
	 */
	public function __construct(?callable $get = null, ?callable $set = null)
	{
		$this->get = $get;
		$this->set = $set;
	}

	/**
	 * @template T
	 * @param callable(mixed, array<string, mixed>): T $get
	 * @return Attribute<T, never>
	 */
	public static function get(callable $get): self
	{
		$attribute = new self($get);
		assertType('UnresolvedTemplateArgumentNever\Attribute<T (method UnresolvedTemplateArgumentNever\Attribute::get(), argument), never>', $attribute);

		return $attribute;
	}

	/**
	 * @template T
	 * @param callable(T, array<string, mixed>): mixed $set
	 * @return Attribute<never, T>
	 */
	public static function set(callable $set): self
	{
		return new self(null, $set);
	}

}
