<?php declare(strict_types = 1);

namespace PHPStan\Node\Variable;

use PhpParser\Node;
use PHPStan\Node\Expr\VariableWrittenExpr;

/**
 * A write site of a local variable inside a function-like body.
 *
 * Immutable. Whether the written value was used afterwards is not a property
 * of the write - it is answered by VariableWritesNode::isUsed().
 *
 * An offset write ($a['k'] = ..., an item of a literal array assigned to $a)
 * writes one offset of the variable's array instead of the variable as a whole;
 * a literal item additionally points to the whole-variable write that assigned
 * the literal (its parent).
 */
final class VariableWrite
{

	public const KIND_ASSIGN = 1;
	public const KIND_READ_MODIFY_WRITE = 2;
	public const KIND_INC_DEC = 3;
	public const KIND_ARRAY_DIM_WRITE = 4;
	public const KIND_LIST_ITEM = 5;
	public const KIND_FOREACH_VALUE = 6;
	public const KIND_FOREACH_KEY = 7;
	public const KIND_CATCH = 8;
	public const KIND_ARRAY_LITERAL_ITEM = 9;

	private VariableWrittenExpr $markerExpr;

	/**
	 * @param self::KIND_* $kind
	 * @param int|string|null $offset
	 */
	public function __construct(
		private string $variableName,
		private Node $node,
		private int $id,
		private int $kind,
		private bool $isOffsetWrite = false,
		private $offset = null,
		private ?int $parentId = null,
	)
	{
		$this->markerExpr = new VariableWrittenExpr($variableName, $id);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	/**
	 * The target of the write - the Variable, the ArrayDimFetch or the literal's
	 * ArrayItem - and the source of the reported line.
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
		return $this->isOffsetWrite;
	}

	/**
	 * The constant offset of an offset write; null when it is not statically
	 * known ($a[$i], $a[], a spread item).
	 *
	 * @return int|string|null
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * For a literal item: the whole-variable write that assigned the literal.
	 */
	public function getParentId(): ?int
	{
		return $this->parentId;
	}

	/**
	 * The scope marker that says "this write still reaches here".
	 */
	public function getMarkerExpr(): VariableWrittenExpr
	{
		return $this->markerExpr;
	}

}
