<?php declare(strict_types = 1);

namespace PHPStan\Node\Method;

use PHPStan\Analyser\Scope;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'gatheredMethodVariableWrites')]
final class MethodVariableWrites
{

	public function __construct(
		private VariableWritesNode $node,
		private Scope $scope,
	)
	{
	}

	public function getNode(): VariableWritesNode
	{
		return $this->node;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
