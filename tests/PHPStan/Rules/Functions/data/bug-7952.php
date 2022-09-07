<?php

namespace Bug7952A {

	function clean(): void
	{
		echo 'cleaned';
	}

	trait FooTrait
	{
		public function doClean(): void
		{
			clean();
		}
	}

}

namespace Bug7952A\Sub {

	use Bug7952A\FooTrait;

	class FooClass
	{
		use FooTrait;
	}

}
