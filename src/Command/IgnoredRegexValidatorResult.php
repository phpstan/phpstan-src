<?php declare(strict_types = 1);

namespace PHPStan\Command;

class IgnoredRegexValidatorResult
{

	/** @var array<string, string> */
	private array $ignoredTypes;

	private bool $anchorsInTheMiddle;

	private bool $allErrorsIgnored;

	/**
	 * @param array<string, string> $ignoredTypes
	 * @param bool $anchorsInTheMiddle
	 * @param bool $allErrorsIgnored
	 */
	public function __construct(
		array $ignoredTypes,
		bool $anchorsInTheMiddle,
		bool $allErrorsIgnored
	)
	{
		$this->ignoredTypes = $ignoredTypes;
		$this->anchorsInTheMiddle = $anchorsInTheMiddle;
		$this->allErrorsIgnored = $allErrorsIgnored;
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

}
