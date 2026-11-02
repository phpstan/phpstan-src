<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError141 implements RuleError, FileRuleError, TipRuleError, FixableNodeRuleError
{

	public string $message;

	public string $file;

	public string $fileDescription;

	public string $tip;

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

	public function getTip(): string
	{
		return $this->tip;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}
