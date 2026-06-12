<?php

namespace SwitchConditionInTrait;

trait FooTrait
{

	public function doFoo(): void
	{
		// sometimes constant, sometimes not
		switch (true) {
			case $this->doBar():
				break;
		}
	}

	public function doFoo2(): void
	{
		// always false
		switch (true) {
			case $this->doBar2():
				break;
		}
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): false
	{

	}

	public function doBar2(): false
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): bool
	{

	}

	public function doBar2(): false
	{

	}

}
