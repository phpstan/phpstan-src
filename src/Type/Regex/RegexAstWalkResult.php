<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

/** @immutable */
final class RegexAstWalkResult
{

	/**
	 * @param array<int, RegexCapturingGroup> $capturingGroups
	 * @param list<string> $markVerbs
	 */
	public function __construct(
		private int $alternationId,
		private int $captureGroupId,
		private array $capturingGroups,
		private array $markVerbs,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self(
			-1,
			100,
			[],
			[],
		);
	}

	public function nextAlternationId(): self
	{
		return new self(
			$this->alternationId + 1,
			$this->captureGroupId,
			$this->capturingGroups,
			$this->markVerbs,
		);
	}

	public function nextCaptureGroupId(): self
	{
		return new self(
			$this->alternationId,
			$this->captureGroupId + 1,
			$this->capturingGroups,
			$this->markVerbs,
		);
	}

	public function addCapturingGroup(RegexCapturingGroup $group): self
	{
		$capturingGroups = $this->capturingGroups;
		$capturingGroups[$group->getId()] = $group;

		return new self(
			$this->alternationId,
			$this->captureGroupId,
			$capturingGroups,
			$this->markVerbs,
		);
	}

	public function markVerb(string $markVerb): self
	{
		$verbs = $this->markVerbs;
		$verbs[] = $markVerb;

		return new self(
			$this->alternationId,
			$this->captureGroupId,
			$this->capturingGroups,
			$verbs,
		);
	}

	public function getAlternationId(): int
	{
		return $this->alternationId;
	}

	public function getCaptureGroupId(): int
	{
		return $this->captureGroupId;
	}

	/**
	 * @return array<int, RegexCapturingGroup>
	 */
	public function getCapturingGroups(): array
	{
		return $this->capturingGroups;
	}

	/**
	 * @return list<string>
	 */
	public function getMarkVerbs(): array
	{
		return $this->markVerbs;
	}

}
