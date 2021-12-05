<?php declare(strict_types = 1);

namespace PHPStan\Command;

class IgnoredRegexValidatorResult
{

	/** @var array<string, string> */
	private array $ignoredTypes;

	private bool $anchorsInTheMiddle;

	private bool $allErrorsIgnored;

	private ?string $wrongSequence;

	private ?string $escapedWrongSequence;

	/**
	 * @param array<string, string> $ignoredTypes
	 */
	public function __construct(
		array $ignoredTypes,
		bool $anchorsInTheMiddle,
		bool $allErrorsIgnored,
		?string $wrongSequence = null,
		?string $escapedWrongSequence = null
	)
	{
		$this->ignoredTypes = $ignoredTypes;
		$this->anchorsInTheMiddle = $anchorsInTheMiddle;
		$this->allErrorsIgnored = $allErrorsIgnored;
		$this->wrongSequence = $wrongSequence;
		$this->escapedWrongSequence = $escapedWrongSequence;
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
