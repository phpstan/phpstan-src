<?php declare(strict_types = 1);

namespace Bug12979;

abstract class X
{
	/**
	 * @return callable-string
	 */
	abstract public function callableString(): string;

	/**
	 * @param non-empty-string $nonEmptyString
	 */
	abstract public function acceptNonEmptyString(string $nonEmptyString): void;

	public function test(): void
	{
		$this->acceptNonEmptyString($this->callableString());
	}
}
