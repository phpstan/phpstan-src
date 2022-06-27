<?php

namespace DummyCollector;

class Foo
{

	public function doFoo()
	{
		$this->doFoo();
		$this->doBar();
	}

}

class Bar
{

	public function doBar()
	{
		$this->doFoo();
		$this->doBar();
	}

}
