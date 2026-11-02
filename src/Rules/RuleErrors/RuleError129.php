<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError129 implements RuleError, FixableNodeRuleError
{

	public string $message;

	public Node $originalNode;

	/** @var callable(Node): (Node|NodeVisitor::REMOVE_NODE) */
	public $newNodeCallable;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getOriginalNode(): Node
	{
		return $this->originalNode;
	}

	/**
	 * @return callable(Node): (Node|NodeVisitor::REMOVE_NODE)
	 */
	public function getNewNodeCallable(): callable
	{
		return $this->newNodeCallable;
	}

}
