<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Isset_;
use PHPStan\Analyser\ExpressionResult;

/**
 * Emitted by IssetHandler once each isset() subject is processed, so IssetRule
 * reads the already-computed IssetabilityResolution (via the carried
 * ExpressionResults) instead of re-walking the chain on demand.
 *
 * @internal
 */
final class IssetExpressionNode extends Expr implements VirtualNode
{

	/**
	 * @param ExpressionResult[] $varResults
	 */
	public function __construct(Isset_ $originalNode, private array $varResults)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * @return ExpressionResult[]
	 */
	public function getVarResults(): array
	{
		return $this->varResults;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_IssetExpressionNode';
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
