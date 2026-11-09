<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @template TNodeType of Node
 * @template TValue
 * @implements Collector<TNodeType, TValue>
 */
class UniversalCollector implements Collector
{

	/** @var (callable(TNodeType, Scope): TValue) */
	private $processNodeCallback;

	/**
	 * @param class-string<TNodeType> $nodeType
	 * @param (callable(TNodeType, Scope): TValue) $processNodeCallback
	 */
	public function __construct(private string $nodeType, callable $processNodeCallback)
	{
		$this->processNodeCallback = $processNodeCallback;
	}

	public function getNodeType(): string
	{
		return $this->nodeType;
	}

	/**
	 * @param TNodeType $node
	 * @return TValue
	 */
	public function processNode(Node $node, Scope $scope)
	{
		$callback = $this->processNodeCallback;
		return $callback($node, $scope);
	}

}
