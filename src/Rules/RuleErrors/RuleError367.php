<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError367 implements RuleError, LineRuleError, FileRuleError, TipRuleError, MetadataRuleError, NonIgnorableRuleError, FileDependenciesRuleError
{

	public string $message;

	public int $line;

	public string $file;

	public string $fileDescription;

	public string $tip;

	/** @var mixed[] */
	public array $metadata;

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
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

}
