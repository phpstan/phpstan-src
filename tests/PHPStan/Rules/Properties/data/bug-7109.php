<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7109;

class HelloWorld
{
	public int $aaa = 5;
	/**
	 * @return HelloWorld|null
	 */
	public function get(): ?HelloWorld
	{
		return rand() ? $this : null;
	}
	public function sayHello(): void
	{
		$this->get()?->aaa ?? 6;
		isset($this->get()?->aaa) ?: 6;
		empty($this->get()?->aaa) ?: 6;
	}

	public function moreExamples(): void
	{
		$foo = null;
		if (rand(0, 1)) {
			$foo = new self();
		}
		$foo->get()?->aaa ?? 6;
		isset($foo->get()?->aaa) ?: 6;
		empty($foo->get()?->aaa) ?: 6;
	}

	public function getNotNull(): HelloWorld
	{
		return $this;
	}

	public function notNullableExamples(): void
	{
		$this->getNotNull()?->aaa ?? 6;
		isset($this->getNotNull()?->aaa) ?: 6;
		empty($this->getNotNull()?->aaa) ?: 6;
	}

	/** @var positive-int */
	public int $notFalsy = 5;

	public function emptyNotFalsy(): void
	{
		$foo = null;
		if (rand(0, 1)) {
			$foo = new self();
		}
		empty($foo->get()?->notFalsy) ?: 6;
	}

	public function emptyNotFalsy2(): void
	{
		empty($this->getNotNull()?->notFalsy) ?: 6;
	}

	public ?HelloWorld $prop = null;
	public function edgeCaseWithMethodCall(): void
	{
		// only ?->aaa should be reported
		$this->get()?->prop?->get()?->aaa ?? 'edge';
		isset($this->get()?->prop?->get()?->aaa) ?: 'edge';
		empty($this->get()?->prop?->get()?->aaa) ?: 'edge';
	}

	public function fetchByExpr(): void
	{
		$this?->{'aaa'} ?? 'edge';
		isset($this?->{'aaa'}) ?: 'edge';
		empty($this?->{'aaa'}) ?: 'edge';
	}
}
