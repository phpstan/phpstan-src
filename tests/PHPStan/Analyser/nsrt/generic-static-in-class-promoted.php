<?php // lint >= 8.0

declare(strict_types = 1);

namespace GenericStaticInClassPromoted;

use function PHPStan\Testing\assertType;

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

	/** @var static<ANode> */
	public static ?self $staticA = null;

	/** @param TNode $node */
	final public function __construct(public object $node)
	{
	}

	public function inside(BNode $b): void
	{
		assertType('GenericStaticInClassPromoted\ANode|null', $this->a?->node);
		assertType('GenericStaticInClassPromoted\ANode|null', static::$staticA?->node);

		$static = new static($b);
		assertType('GenericStaticInClassPromoted\BNode', $static->node);
		assertType('GenericStaticInClassPromoted\ANode|null', $static->a?->node);

		$self = new self($b);
		assertType('GenericStaticInClassPromoted\BNode', $self->node);
		assertType('GenericStaticInClassPromoted\ANode|null', $self->a?->node);
	}

}

/**
 * @template TNode of object
 */
final class FinalNodeCursor
{

	/** @var static<ANode> */
	public ?self $a = null;

	/** @param TNode $node */
	public function __construct(public object $node)
	{
	}

	public function inside(BNode $b): void
	{
		assertType('GenericStaticInClassPromoted\ANode|null', $this->a?->node);

		$static = new static($b);
		assertType('GenericStaticInClassPromoted\BNode', $static->node);
	}

}
