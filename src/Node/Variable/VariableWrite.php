<?php declare(strict_types = 1);

namespace PHPStan\Node\Variable;

use PhpParser\Node\Expr;
use PHPStan\Node\Expr\VariableWrittenExpr;

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
	public const KIND_INC_DEC = 3;
	public const KIND_ARRAY_DIM_WRITE = 4;
	public const KIND_LIST_ITEM = 5;
	public const KIND_FOREACH_VALUE = 6;
	public const KIND_FOREACH_KEY = 7;
	public const KIND_CATCH = 8;

	private VariableWrittenExpr $markerExpr;

	/**
	 * @param self::KIND_* $kind
	 */
	public function __construct(
		private string $variableName,
		private Expr\Variable $variable,
		private int $id,
		private int $kind,
	)
	{
		$this->markerExpr = new VariableWrittenExpr($variableName, $id);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	/**
	 * The target node of the write - the source of the reported line.
	 */
	public function getVariable(): Expr\Variable
	{
		return $this->variable;
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

	/**
	 * The scope marker that says "this write still reaches here".
	 */
	public function getMarkerExpr(): VariableWrittenExpr
	{
		return $this->markerExpr;
	}

}
