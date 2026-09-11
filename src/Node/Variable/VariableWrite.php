<?php declare(strict_types = 1);

namespace PHPStan\Node\Variable;

use PhpParser\Node;

/**
 * A write site of a local variable inside a function-like body.
 *
 * Immutable. Whether the written value was read afterwards is not a property
 * of the write - it is answered by VariableWritesNode::isRead().
 */
final class VariableWrite
{

	public const KIND_ASSIGN = 1;
	public const KIND_READ_MODIFY_WRITE = 2;
	public const KIND_PRE_INC = 3;
	public const KIND_POST_INC = 4;
	public const KIND_PRE_DEC = 5;
	public const KIND_POST_DEC = 6;
	public const KIND_ARRAY_DIM_WRITE = 7;
	public const KIND_LIST_ITEM = 8;
	public const KIND_FOREACH_VALUE = 9;
	public const KIND_FOREACH_KEY = 10;
	public const KIND_CATCH = 11;
	public const KIND_PARAMETER = 12;
	public const KIND_CLOSURE_USE = 13;
	public const KIND_ARRAY_LITERAL_ITEM = 14;

	/**
	 * @param self::KIND_* $kind
	 * @param int|string|null $offset
	 */
	public function __construct(
		private string $variableName,
		private Node $node,
		private int $id,
		private int $kind,
		private bool $offsetWrite = false,
		private $offset = null,
		private ?int $parentId = null,
		private bool $replacesOffset = true,
	)
	{
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	/**
	 * The target node of the write - the source of the reported line.
	 */
	public function getNode(): Node
	{
		return $this->node;
	}

	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return self::KIND_*
	 */
	public function getKind(): int
	{
		return $this->kind;
	}

	public function isOffsetWrite(): bool
	{
		return $this->offsetWrite;
	}

	/** @return int|string|null */
	public function getOffset()
	{
		return $this->offset;
	}

	public function getParentId(): ?int
	{
		return $this->parentId;
	}

	public function replacesOffset(): bool
	{
		return $this->replacesOffset;
	}

}
