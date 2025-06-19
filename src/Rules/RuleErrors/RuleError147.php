<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError147 implements RuleError, LineRuleError, IdentifierRuleError, FixableNodeRuleError
{

	public string $message;

	public int $line;

	public string $identifier;

	public Node $originalNode;

	/** @var callable(Node): Node */
	public $newNodeCallable;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getOriginalNode(): Node
	{
		return $this->originalNode;
	}

	/**
	 * @return callable(Node): Node
	 */
	public function getNewNodeCallable(): callable
	{
		return $this->newNodeCallable;
	}

}
