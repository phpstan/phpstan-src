<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError499 implements RuleError, LineRuleError, IdentifierRuleError, MetadataRuleError, NonIgnorableRuleError, FixableNodeRuleError, FileDependenciesRuleError
{

	public string $message;

	public int $line;

	public string $identifier;

	/** @var mixed[] */
	public array $metadata;

	public Node $originalNode;

	/** @var callable(Node): Node */
	public $newNodeCallable;

	/** @var list<string> */
	public array $fileDependencies;

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

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
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
