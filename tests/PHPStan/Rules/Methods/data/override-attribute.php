<?php

namespace OverrideAttribute;

class Foo
{

	public function test(): void
	{

	}

}

class Bar extends Foo
{

	#[\Override]
	public function test(): void
	{

	}

	#[\Override]
	public function test2(): void
	{

	}

}
