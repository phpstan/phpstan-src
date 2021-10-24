<?php

namespace ParallelAnalyserIntegrationTest;

trait FooTrait
{

	public function doFoo()
	{
		$this->test = 1;
	}

	public function getFoo(): int
	{
		return $this->test;
	}

}
