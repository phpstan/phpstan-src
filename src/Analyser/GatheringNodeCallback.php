<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

/**
 * Pairs an engine-feeding gatherer (impure points, execution ends, return
 * statements, ...) with the rule-facing node callback. The gatherer's
 * by-reference arrays are read as soon as the enclosing body walk returns,
 * so FiberNodeScopeResolver runs the gatherer synchronously at the emission
 * position and defers only the inner callback to a fiber - a rule parking
 * on an unsettled expression must not delay the gathering past the read.
 */
final class GatheringNodeCallback
{

	/** @var callable(Node, Scope): void */
	private $gatherer;

	/** @var callable(Node, Scope): void */
	private $inner;

	/**
	 * @param callable(Node, Scope): void $gatherer
	 * @param callable(Node, Scope): void $inner
	 */
	public function __construct(callable $gatherer, callable $inner)
	{
		$this->gatherer = $gatherer;
		$this->inner = $inner;
	}

	public function __invoke(Node $node, Scope $scope): void
	{
		($this->inner)($node, $scope);
		($this->gatherer)($node, $scope);
	}

	/** @return callable(Node, Scope): void */
	public function getGatherer(): callable
	{
		return $this->gatherer;
	}

	/** @return callable(Node, Scope): void */
	public function getInner(): callable
	{
		return $this->inner;
	}

}
