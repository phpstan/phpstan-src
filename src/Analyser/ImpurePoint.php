<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\VirtualNode;

/**
 * @phpstan-type ImpurePointIdentifier = 'echo'|'die'|'exit'|'propertyAssign'|'methodCall'|'new'|'functionCall'|'include'|'require'|'print'|'eval'|'superglobal'|'yield'|'yieldFrom'
 * @api
 */
class ImpurePoint
{

	/**
	 * @param Node\Expr|Node\Stmt|VirtualNode $node
	 * @param ImpurePointIdentifier $identifier
	 */
	public function __construct(
		private Scope $scope,
		private Node $node,
		private string $identifier,
		private string $description,
		private bool $certain,
	)
	{
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

	/**
	 * @return Node\Expr|Node\Stmt|VirtualNode
	 */
	public function getNode()
	{
		return $this->node;
	}

	/**
	 * @return ImpurePointIdentifier
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function isCertain(): bool
	{
		return $this->certain;
	}

}
