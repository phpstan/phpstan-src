<?php

namespace Bug1903;

class Test
{

	private $answersOrder = [];

	public function doFoo(string $qId): array
	{
		if (null !== $this->answersOrder[$qId]) {
			return $this->answersOrder[$qId];
		}


		$this->answersOrder[$qId] = 5;

		return $this->answersOrder[$qId];
	}

	/** @var \ArrayAccess */
	private $arrayAccess = [];

	public function doBar(string $qId): int
	{
		if (null !== $this->arrayAccess[$qId]) {
			return $this->arrayAccess[$qId];
		}


		$this->arrayAccess[$qId] = 5;

		return $this->arrayAccess[$qId];
	}

}
