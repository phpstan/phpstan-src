<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use function array_merge;

final class RuleLevelHelperAcceptsResult
{

	/**
	 * @param list<string> $reasons
	 */
	public function __construct(
		public readonly bool $result,
		public readonly array $reasons,
	)
	{
	}

	public function and(self $other): self
	{
		return new self(
			$this->result && $other->result,
			array_merge($this->reasons, $other->reasons),
		);
	}

	/**
	 * @param callable(string): string $cb
	 */
	public function decorateReasons(callable $cb): self
	{
		$reasons = [];
		foreach ($this->reasons as $reason) {
			$reasons[] = $cb($reason);
		}

		return new self($this->result, $reasons);
	}

}
