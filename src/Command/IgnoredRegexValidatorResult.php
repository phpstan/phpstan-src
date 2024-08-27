<?php declare(strict_types = 1);

namespace PHPStan\Command;

final class IgnoredRegexValidatorResult
{

	/**
	 * @param array<string, string> $ignoredTypes
	 */
	public function __construct(
		private array $ignoredTypes,
		private bool $anchorsInTheMiddle,
		private bool $allErrorsIgnored,
		private ?string $wrongSequence = null,
		private ?string $escapedWrongSequence = null,
	)
	{
	}

	/**
	 * @return array<string, string>
	 */
	public function getIgnoredTypes(): array
	{
		return $this->ignoredTypes;
	}

	public function hasAnchorsInTheMiddle(): bool
	{
		return $this->anchorsInTheMiddle;
	}

	public function areAllErrorsIgnored(): bool
	{
		return $this->allErrorsIgnored;
	}

	public function getWrongSequence(): ?string
	{
		return $this->wrongSequence;
	}

	public function getEscapedWrongSequence(): ?string
	{
		return $this->escapedWrongSequence;
	}

}
