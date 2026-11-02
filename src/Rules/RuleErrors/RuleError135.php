<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError135 implements RuleError, LineRuleError, FileRuleError, FixableNodeRuleError
{

	public string $message;

	public int $line;

	public string $file;

	public string $fileDescription;

	public Node $originalNode;

	/** @var callable(Node): (Node|NodeVisitor::REMOVE_NODE) */
	public $newNodeCallable;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getFileDescription(): string
	{
		return $this->fileDescription;
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
