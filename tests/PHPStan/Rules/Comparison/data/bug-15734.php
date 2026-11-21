<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug15734;

class Tokenizer
{
	/** @var 'ready'|'matched'|'complete' */
	private string $state = 'ready';

	public function nextToken(): bool
	{
		$this->state = 'ready';
		if (!$this->parseNextTag()) {
			return $this->state === 'complete';
		}

		return $this->state === 'matched';
	}

	private function parseNextTag(): bool
	{
		$this->state = rand(0, 1) === 1 ? 'matched' : 'complete';
		return $this->state === 'matched';
	}
}
