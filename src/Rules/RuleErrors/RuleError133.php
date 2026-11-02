<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError133 implements RuleError, FileRuleError, FixableNodeRuleError
{

	public string $message;

	public string $file;

	public string $fileDescription;

	/** @var non-empty-list<NodeFix> */
	public array $nodeFixes;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getFileDescription(): string
	{
		return $this->fileDescription;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}
