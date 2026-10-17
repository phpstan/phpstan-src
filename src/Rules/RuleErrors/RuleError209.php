<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError209.cpp')]
final class RuleError209 implements RuleError, IdentifierRuleError, NonIgnorableRuleError, FixableNodeRuleError
{

	public string $message;

	public string $identifier;

	public Node $originalNode;

	/** @var callable(Node): Node */
	public $newNodeCallable;

	public function getMessage(): string
	{
		return $this->message;
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
