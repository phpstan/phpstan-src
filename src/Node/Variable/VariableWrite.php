<?php declare(strict_types = 1);

namespace PHPStan\Node\Variable;

use PhpParser\Node\Expr;

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

}
