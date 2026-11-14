<?php

namespace StrictComparisonPropertyAfterPossiblyImpureCall;

class Tokenizer
{

	const STATE_READY = 'ready';
	const STATE_MATCHED = 'matched';
	const STATE_COMPLETE = 'complete';

	/** @var self::STATE_* */
	private $state = self::STATE_READY;

	public function nextToken(): bool
	{
		$this->state = self::STATE_READY;
		if (!$this->parseNextTag()) {
			return $this->state === self::STATE_COMPLETE;
		}

		return $this->state === self::STATE_MATCHED;
	}

	private function parseNextTag(): bool
	{
		$this->state = rand(0, 1) === 1 ? self::STATE_MATCHED : self::STATE_COMPLETE;
		return $this->state === self::STATE_MATCHED;
	}

	/** @phpstan-pure */
	private function isReady(): bool
	{
		return true;
	}

	public function afterPureCall(): bool
	{
		$this->state = self::STATE_READY;
		$this->isReady();
		return $this->state === self::STATE_COMPLETE;
	}

}
