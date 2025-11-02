<?php

namespace WhileLoopTruePhp81;

class Foo
{
	public function doBar() :never
	{
		while (true) {
			// do stuff
		}
	}

}

function doFoo() : never
{
	while (true) {
		// do stuff
	}
}
