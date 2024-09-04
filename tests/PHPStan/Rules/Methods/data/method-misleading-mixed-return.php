<?php

namespace MethodMisleadingMixedReturn;

class Foo
{

	public function misleadingMixedReturnType(): mixed
	{
		if (rand(0, 1)) {
			return 1;
		}
		if (rand(0, 1)) {
			return true;
		}
		if (rand(0, 1)) {
			return new mixed();
		}
	}

}
