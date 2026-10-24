<?php declare(strict_types = 1);

namespace GenericStaticInClass;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Box
{

	/** @var static<int, string> */
	public $prop;

	/** @var static<int, string> */
	public static $staticProp;

	/** @return static<int, string> */
	public function concrete()
	{
		return new static(); // @phpstan-ignore new.static
	}

	/** @return static<int, string> */
	public static function concreteStatic()
	{
		return new static(); // @phpstan-ignore new.static
	}

	/** @return static<int, TValue> */
	public function withTemplate()
	{
		return new static(); // @phpstan-ignore new.static
	}

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return [];
	}

	/**
	 * @param static<int, string> $p
	 * @param class-string<static<int, string>> $class
	 */
	public function inside($p, string $class): void
	{
		assertType('array<int, string>', $p->all());
		assertType('array<int, string>', $this->concrete()->all());
		assertType('array<int, TValue (class GenericStaticInClass\Box, argument)>', $this->withTemplate()->all());
		assertType('array<int, string>', static::concreteStatic()->all());
		assertType('array<int, string>', $this->prop->all());
		assertType('array<int, string>', static::$staticProp->all());
		assertType('array<int, string>', (new $class())->all());
	}

}

/**
 * @template TKey of array-key
 * @template TValue
 */
final class FinalBox
{

	/** @var static<int, string> */
	public $prop;

	/** @return static<int, string> */
	public function concrete()
	{
		return new static();
	}

	/** @return static<int, string> */
	public static function concreteStatic()
	{
		return new static();
	}

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return [];
	}

	/** @param static<int, string> $p */
	public function inside($p): void
	{
		assertType('array<int, string>', $p->all());
		assertType('array<int, string>', $this->concrete()->all());
		assertType('array<int, string>', static::concreteStatic()->all());
		assertType('array<int, string>', self::concreteStatic()->all());
		assertType('array<int, string>', $this->prop->all());
	}

}

class ANode
{

}

class BNode
{

}

/**
 * @template TNode of object
 */
class NodeCursor
{

	/** @var static<ANode> */
	public ?self $a = null;

	/** @var static */
	public ?self $b = null;

	/** @var static<ANode> */
	public static ?self $staticA = null;

	/** @var TNode */
	public object $node;

	/** @param TNode $node */
	final public function __construct(object $node)
	{
		$this->node = $node;
	}

	/** @return TNode */
	public function getNode(): object
	{
		return $this->node;
	}

	public function inside(BNode $b): void
	{
		assertType('static(GenericStaticInClass\NodeCursor<GenericStaticInClass\ANode>)|null', $this->a);
		assertType('GenericStaticInClass\ANode|null', $this->a?->node);
		assertType('GenericStaticInClass\ANode|null', $this->a?->getNode());
		assertType('TNode of object (class GenericStaticInClass\NodeCursor, argument)|null', $this->b?->node);
		assertType('GenericStaticInClass\ANode|null', static::$staticA?->node);
		assertType('GenericStaticInClass\ANode|null', static::$staticA?->getNode());

		$static = new static($b);
		assertType('static(GenericStaticInClass\NodeCursor<GenericStaticInClass\BNode>)', $static);
		assertType('GenericStaticInClass\BNode', $static->node);
		assertType('GenericStaticInClass\BNode', $static->getNode());
		assertType('GenericStaticInClass\ANode|null', $static->a?->node);

		$self = new self($b);
		assertType('GenericStaticInClass\BNode', $self->getNode());
		assertType('GenericStaticInClass\ANode|null', $self->a?->node);
		assertType('GenericStaticInClass\ANode|null', $self->a?->getNode());

		function () {
			assertType('GenericStaticInClass\ANode|null', $this->a?->getNode());
		};
		fn () => assertType('GenericStaticInClass\ANode', (new static(new ANode()))->getNode());
	}

}

/**
 * @template TNode of object
 */
final class FinalNodeCursor
{

	/** @var static<ANode> */
	public ?self $a = null;

	/** @var TNode */
	public object $node;

	/** @param TNode $node */
	public function __construct(object $node)
	{
		$this->node = $node;
	}

	/** @return TNode */
	public function getNode(): object
	{
		return $this->node;
	}

	public function inside(BNode $b): void
	{
		assertType('GenericStaticInClass\ANode|null', $this->a?->node);
		assertType('GenericStaticInClass\ANode|null', $this->a?->getNode());

		$static = new static($b);
		assertType('GenericStaticInClass\BNode', $static->node);
		assertType('GenericStaticInClass\BNode', $static->getNode());
		assertType('GenericStaticInClass\ANode|null', $static->a?->getNode());
	}

}

/**
 * @template T
 */
interface ValueHolder
{

	/** @return T */
	public function get();

}

/**
 * @template T
 */
abstract class ParentHolder
{

	/** @var T */
	public $value;

	/** @return T */
	public function getFromParent()
	{
		return $this->value;
	}

}

/**
 * @template T
 * @extends ParentHolder<T>
 * @implements ValueHolder<T>
 */
abstract class ChildHolder extends ParentHolder implements ValueHolder
{

	/** @param static<int> $p */
	public function inside($p): void
	{
		assertType('int', $p->get());
		assertType('int', $p->getFromParent());
		assertType('int', $p->value);
	}

}
