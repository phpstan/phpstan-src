<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError473 implements RuleError, TipRuleError, IdentifierRuleError, NonIgnorableRuleError, FixableNodeRuleError, FileDependenciesRuleError
{

	public string $message;

	public string $tip;

	public string $identifier;

	public Node $originalNode;

	/** @var callable(Node): Node */
	public $newNodeCallable;

	/** @var list<string> */
	public array $fileDependencies;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
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

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

}
