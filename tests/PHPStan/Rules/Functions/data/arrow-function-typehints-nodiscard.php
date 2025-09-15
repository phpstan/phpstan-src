<?php

namespace ArrowFunctionExistingClassesInTypehints;

class Demo
{

	public function doFoo()
	{
		#[\NoDiscard] fn(): void => true;
	}

}
