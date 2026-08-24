<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;

final class VirtualAssignNodeCallback
{

	/**
	 * @param callable(Node $node, Scope $scope): void $originalNodeCallback
	 */
	private function __construct(private mixed $originalNodeCallback)
	{
	}

	/**
	 * Filters the rule-facing callback down to assign nodes. Engine-feeding
	 * gatherer frames live on NodeScopeResolver and observe every emission
	 * regardless of this filter.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @return callable(Node $node, Scope $scope): void
	 */
	public static function create(callable $nodeCallback): callable
	{
		return new self($nodeCallback);
	}

	public function __invoke(Node $node, Scope $scope): void
	{
		if (!$node instanceof PropertyAssignNode && !$node instanceof VariableAssignNode) {
			return;
		}

		($this->originalNodeCallback)($node, $scope);
	}

}
