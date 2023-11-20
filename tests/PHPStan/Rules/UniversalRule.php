<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @template TNodeType of Node
 * @implements Rule<TNodeType>
 */
class UniversalRule implements Rule
{

	/** @phpstan-var class-string<TNodeType> */
	private $nodeType;

	/** @var (callable(TNodeType, Scope): list<IdentifierRuleError>) */
	private $processNodeCallback;

	/**
	 * @param class-string<TNodeType> $nodeType
	 * @param (callable(TNodeType, Scope): list<IdentifierRuleError>) $processNodeCallback
	 */
	public function __construct(string $nodeType, callable $processNodeCallback)
	{
		$this->nodeType = $nodeType;
		$this->processNodeCallback = $processNodeCallback;
	}

	public function getNodeType(): string
	{
		return $this->nodeType;
	}

	/**
	 * @param TNodeType $node
	 * @return list<IdentifierRuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$callback = $this->processNodeCallback;
		return $callback($node, $scope);
	}

}
