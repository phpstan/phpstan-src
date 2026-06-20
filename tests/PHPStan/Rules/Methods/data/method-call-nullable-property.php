<?php declare(strict_types = 1);

namespace MethodCallNullableProperty;

class Service
{

	public function doWork(): void
	{
	}

}

class Container
{

	private ?Service $service = null;

	public function callUnchecked(): void
	{
		$this->service->doWork();
	}

	public function callAfterNullCheck(): void
	{
		if ($this->service !== null) {
			$this->service->doWork();
		}
	}

	public function callAfterEarlyReturn(): void
	{
		if ($this->service === null) {
			return;
		}

		$this->service->doWork();
	}

}
