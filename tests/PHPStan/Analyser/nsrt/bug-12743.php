<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12743;

use function PHPStan\Testing\assertType;

/**
 * @template T of self::NULL|self::INT|self::STRING
 */
abstract class Root
{
	public const NULL = 0;
	public const INT = 1;
	public const STRING = 2;

	/** @var self::NULL|self::INT|self::STRING $type */
	protected int $type = self::NULL;

	/**
	 * @return Base<self::NULL>
	 */
	public static function create()
	{
		return new static();
	}

	/**
	 * Fluent interface, changes the generic type
	 *
	 * @return static<self::INT>
	 */
	public function becomeInt()
	{
		$this->type = self::INT;
		return $this;
	}

	/**
	 * Fluent interface, changes the generic type
	 *
	 * @return static<self::STRING>
	 */
	public function becomeString()
	{
		$this->type = self::STRING;
		return $this;
	}

	/**
	 * @return (T is self::INT ? int : (T is self::STRING ? string : null))
	 */
	public function execute() {
		return match ($this->type) {
			self::NULL => null,
			self::INT => 12345,
			self::STRING => 'hello world',
		};
	}
}

/**
 * @template T of self::NULL|self::INT|self::STRING
 * @extends Root<T>
 */
class Base extends Root
{

}

$a = Base::create();
assertType('Bug12743\Base<0>', $a);

assertType('null', $a->execute());

$a = $a->becomeInt();
assertType('Bug12743\Base<1>', $a);
assertType('int', $a->execute());

$a = $a->becomeString();
assertType('Bug12743\Base<2>', $a);
assertType('string', $a->execute());

