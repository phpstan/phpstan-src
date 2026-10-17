<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError37.cpp')]
final class RuleError37 implements RuleError, FileRuleError, MetadataRuleError
{

	public string $message;

	public string $file;

	public string $fileDescription;

	/** @var mixed[] */
	public array $metadata;

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
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
