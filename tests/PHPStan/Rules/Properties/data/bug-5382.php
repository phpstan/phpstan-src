<?php

namespace Bug5382;

class HelloWorld
{
	/**
	 * @var non-empty-list<\stdClass>
	 */
	public array $providers;

	/**
	 * @param non-empty-list<\stdClass>|null $providers
	 */
	public function __construct(?array $providers = null)
	{
		$this->providers = $providers ?? [
				new \stdClass(),
			];
	}
}

class HelloWorld2
{
	/**
	 * @var non-empty-list<\stdClass>
	 */
	public array $providers;

	/**
	 * @param non-empty-list<\stdClass>|null $providers
	 */
	public function __construct(?array $providers = null)
	{
		$this->providers = $providers ?? [
				new \stdClass(),
			];

		$this->providers = $providers ?: [
			new \stdClass(),
		];

		$this->providers = $providers !== null ? $providers : [
			new \stdClass(),
		];

		$providers ??= [
			new \stdClass(),
		];
		$this->providers = $providers;
	}
}

class HelloWorld3
{
	/**
	 * @var non-empty-list<\stdClass>
	 */
	public array $providers;

	/**
	 * @param non-empty-list<\stdClass>|null $providers
	 */
	public function __construct(?array $providers = null)
	{
		/** @var non-empty-list<\stdClass> $newList */
		$newList = [new \stdClass()];
		$newList2 = [new \stdClass()];

		$this->providers = $providers ?? $newList;
		$this->providers = $providers ?? $newList2;
	}
}
