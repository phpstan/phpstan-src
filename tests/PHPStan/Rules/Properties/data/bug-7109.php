<?php declare(strict_types = 1); // lint >= 8.0

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
}
