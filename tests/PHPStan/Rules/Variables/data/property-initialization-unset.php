<?php // lint >= 8.2

namespace PropertyInitializationUnset;

class NoUnset
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}

class UnsetInSameMethod
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		unset($this->string, $this->true);
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}

class UnsetInAnotherMethod
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function reset(): void
	{
		unset($this->string, $this->true);
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}
