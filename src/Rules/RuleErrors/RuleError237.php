<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError237 implements RuleError, FileRuleError, TipRuleError, MetadataRuleError, NonIgnorableRuleError, FixableNodeRuleError
{

	public string $message;

	public string $file;

	public string $fileDescription;

	public string $tip;

	/** @var mixed[] */
	public array $metadata;

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
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}
