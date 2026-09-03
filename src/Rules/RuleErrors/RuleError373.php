<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError373 implements RuleError, FileRuleError, IdentifierRuleError, MetadataRuleError, NonIgnorableRuleError, FileDependenciesRuleError
{

	public string $message;

	public string $file;

	public string $fileDescription;

	public string $identifier;

	/** @var mixed[] */
	public array $metadata;

	/** @var list<string> */
	public array $fileDependencies;

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

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

}
