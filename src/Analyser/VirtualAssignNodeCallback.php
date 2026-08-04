<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;

final class VirtualAssignNodeCallback implements ShallowNodeCallback
{

	/**
	 * @param callable(Node $node, Scope $scope): void $originalNodeCallback
	 */
	private function __construct(private mixed $originalNodeCallback)
	{
	}

	/**
	 * Rebuilds the chain instead of wrapping it so that GatheringNodeCallback
	 * layers stay on the outside. Hiding a gatherer behind this filter would let
	 * FiberNodeScopeResolver defer it into a fiber, and a parked fiber can run
	 * the gatherer long after the caller already read its result.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @return callable(Node $node, Scope $scope): void
	 */
	public static function create(callable $nodeCallback): callable
	{
		if ($nodeCallback instanceof GatheringNodeCallback) {
			return new GatheringNodeCallback(
				self::create($nodeCallback->getGatherer()),
				self::create($nodeCallback->getInner()),
			);
		}

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
