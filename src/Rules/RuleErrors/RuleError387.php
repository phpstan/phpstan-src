<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError387.cpp')]
final class RuleError387 implements RuleError, LineRuleError, FixableNodeRuleError, FileDependenciesRuleError
{

	public string $message;

	public int $line;

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
