<?php declare(strict_types = 1);

namespace Bug15734;

use function PHPStan\Testing\assertType;

class Tokenizer
{
	/** @var 'ready'|'matched'|'complete' */
	private string $state = 'ready';

	public function nextToken(): bool
	{
		$this->state = 'ready';
		if (!$this->parseNextTag()) {
			assertType("'complete'|'matched'|'ready'", $this->state);
			return $this->state === 'complete';
		}

		assertType("'complete'|'matched'|'ready'", $this->state);
		return $this->state === 'matched';
	}

	private function parseNextTag(): bool
	{
		$this->state = rand(0, 1) === 1 ? 'matched' : 'complete';
		return $this->state === 'matched';
	}
}
