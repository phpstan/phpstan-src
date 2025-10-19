<?php

namespace UnusedProtectedMethod;

class Foo
{
	protected function used1()
	{
	}

	protected function unused1()
	{
		$this->used1();
	}

	final protected function unused2()
	{
		$this->used1();
	}

}

final class Bar
{

	protected function used1()
	{
	}

	protected function unused1()
	{
		$this->used1();
	}

	final protected function unused2()
	{
		$this->used1();
	}

}
