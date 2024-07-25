<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError35 implements RuleError, LineRuleError, MetadataRuleError
{

	public string $message;

	public int $line;

	/** @var mixed[] */
	public array $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
