<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

/**
 * Scope marker: the value written to the variable by the write site $writeId
 * still reaches this point - no later write site overwrote it on this path.
 *
 * Exposes no sub-nodes on purpose. The marker must survive the scope's
 * non-site assignVariable() calls (@var re-assigns, by-ref write-backs); only a
 * real write site kills it, explicitly, through MutatingScope::assignVariable().
 */
final class VariableWrittenExpr extends Expr implements VirtualNode
{

	public function __construct(private string $variableName, private int $writeId)
	{
		parent::__construct([]);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	public function getWriteId(): int
	{
		return $this->writeId;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_VariableWrittenExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
