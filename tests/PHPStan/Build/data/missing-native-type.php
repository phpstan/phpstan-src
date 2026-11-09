<?php // lint >= 8.0

namespace MissingNativeType;

use Countable;
use stdClass;

final class SameTypes
{

	/**
	 * @param int $a
	 * @return string
	 */
	public function doFoo($a)
	{
		return (string) $a;
	}

	/**
	 * Keeps the description.
	 *
	 * @param stdClass $o
	 * @param Countable|null $c
	 */
	public function doBar($o, $c): void
	{
	}

	/** @param int $a the amount */
	public function withDescription($a): void
	{
	}

	/** @param int $a */
	public function nullDefault($a = null): void
	{
	}

	/** @param string ...$parts */
	public function variadic(...$parts): void
	{
	}

	/** @param mixed $a */
	public function explicitMixed($a): void
	{
	}

	/** @param callable $cb */
	public function callable($cb): void
	{
	}

	/** @return static */
	public function returnStatic()
	{
		return $this;
	}

	/** @return void */
	public function returnVoid()
	{
	}

}

final class MoreSpecificTypes
{

	/**
	 * @param non-empty-string $s
	 * @param positive-int|null $i
	 * @return list<int>
	 */
	public function doFoo($s, $i)
	{
		return [];
	}

	/**
	 * @template T of object
	 * @param T $o
	 * @return T
	 */
	public function template($o)
	{
		return $o;
	}

	/** @return $this */
	public function returnThis()
	{
		return $this;
	}

}

final class NotConvertible
{

	/**
	 * @param resource $r
	 * @param int $byRef
	 * @param int $d
	 */
	public function doFoo($r, &$byRef, $d = 'foo', $noPhpDoc = 1): void
	{
	}

	/** @param string $name */
	public function __get($name)
	{
		return 1;
	}

	/** @return string */
	public function __toString()
	{
		return '';
	}

}

class ParentClass
{

	public function untyped($a)
	{
		return $a;
	}

	public function typed(int $a): int
	{
		return $a;
	}

	public function __construct($a)
	{
	}

	/**
	 * @param int $a
	 * @return int
	 */
	public function overridable($a)
	{
		return $a;
	}

	/**
	 * @param int $a
	 * @return int
	 */
	private function privateMethod($a)
	{
		return $a;
	}

	/**
	 * @param int $a
	 * @return int
	 */
	final public function finalMethod($a)
	{
		return $a;
	}

}

final class ChildClass extends ParentClass
{

	/**
	 * @param int $a
	 * @return int
	 */
	public function untyped($a)
	{
		return $a;
	}

	/**
	 * @param positive-int $a
	 * @return positive-int
	 */
	public function typed($a): int
	{
		return 1;
	}

	/** @param int $a */
	public function __construct($a)
	{
	}

}

interface SomeInterface
{

	/**
	 * @param int $a
	 * @return int
	 */
	public function doFoo($a);

}

/**
 * @param int $a
 * @return int
 */
function doFoo($a)
{
	return $a;
}

final class Properties
{

	/** @var int */
	private $a = 1;

	/** @var stdClass|null */
	private $nullable;

	/** @var non-empty-string */
	private $moreSpecific = 'foo';

	/** @var stdClass */
	private $noDefault;

	/** @var stdClass */
	private static $staticNoDefault;

	/** @var stdClass|null */
	private static $staticNullable;

	/** @var int */
	private $wrongDefault = 'foo';

	/** @var int */
	private $b = 1, $c = 2;

	/** @var callable */
	private $callable;

	/** @var int the description */
	public $withDescription = 1;

	public function __construct(stdClass $noDefault)
	{
		$this->noDefault = $noDefault;
	}

}

class NonFinalProperties
{

	/** @var int */
	private $private = 1;

	/** @var int */
	protected $protected = 1;

}

final class PromotedProperties
{

	/** @param int $a */
	public function __construct(private $a)
	{
	}

}

class NonFinalPromotedProperties
{

	/**
	 * @param int $a
	 * @param int $b
	 */
	public function __construct(private $a, public $b)
	{
	}

}

trait SomeTrait
{

	/** @param int $a */
	public function doFoo($a): void
	{
	}

}

final class UsesTrait
{

	use SomeTrait;

}

final class PromotedCallable
{

	/** @param callable $cb */
	public function __construct(private $cb)
	{
	}

}

final class ExplicitMixedValueType
{

	/**
	 * @param mixed[] $a
	 * @return array<mixed>
	 */
	public function doFoo($a)
	{
		return $a;
	}

}
