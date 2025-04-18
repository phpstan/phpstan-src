<?php
declare(strict_types = 1);

namespace InstanceMethodsParameterScopeTest;

class HelloWorld
{
	public function __construct()
	{
	}

	public function foo(\DateTime $d): void
	{
	}

	public function bar(\Baz\Waldo $d): void
	{
	}
}
