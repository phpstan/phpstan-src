<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

/**
 * Pairs an engine-feeding gatherer (impure points, execution ends, return
 * statements, ...) with the rule-facing node callback. callNodeCallback()
 * hands the gatherer the raw walk scope at the emission position; only the
 * inner rule-facing remainder gets the storage-backed scope.
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
