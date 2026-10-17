<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_merge;

/**
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/RuleLevelHelperAcceptsResult.cpp')]
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
