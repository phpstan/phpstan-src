<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\VirtualNode;
use PHPStan\Reflection\SideEffects;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @phpstan-type ImpurePointIdentifier = 'echo'|'die'|'exit'|'propertyAssign'|'propertyAssignByRef'|'propertyUnset'|'propertyHookCall'|'methodCall'|'new'|'functionCall'|'include'|'require'|'print'|'eval'|'superglobal'|'yield'|'yieldFrom'|'static'|'global'|'betweenPhpTags'|'staticPropertyAccess'
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ImpurePoint.cpp')]
final class ImpurePoint
{

	/**
	 * @param Node\Expr|Node\Stmt|VirtualNode $node
	 * @param ImpurePointIdentifier $identifier
	 * @param SideEffects|null $sideEffects the side effects of the called function, null when unknown
	 */
	public function __construct(
		private Scope $scope,
		private Node $node,
		private string $identifier,
		private string $description,
		private bool $certain,
		private ?SideEffects $sideEffects = null,
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

	public function getSideEffects(): ?SideEffects
	{
		return $this->sideEffects;
	}

}
