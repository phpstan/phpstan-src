<?php

namespace MethodReturnStatic;

class A
{
	/** @return static */
	public function returnStatic()
	{

	}
}

class B extends A
{
	/** @return static */
	public function test()
	{
		return $this->returnStatic();
	}
}

final class B2 extends A
{
	/** @return static */
	public function test()
	{
		return $this->returnStatic();
	}
}
