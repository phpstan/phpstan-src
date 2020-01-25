<?php declare(strict_types = 1);

namespace PHPStan\Command;

class IgnoredRegexValidatorResult
{

	/** @var array<string, string> */
	private $ignoredTypes;

	/** @var bool */
	private $anchorsInTheMiddle;

	/**
	 * @param array<string, string> $ignoredTypes
	 * @param bool $anchorsInTheMiddle
	 */
	public function __construct(
		array $ignoredTypes,
		bool $anchorsInTheMiddle
	)
	{
		$this->ignoredTypes = $ignoredTypes;
		$this->anchorsInTheMiddle = $anchorsInTheMiddle;
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

}
