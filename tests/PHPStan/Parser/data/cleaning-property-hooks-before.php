<?php

namespace CleaningPropertyHooks;

class Foo
{

	public int $i {
		get {
			echo 'irrelevant';

			// other property, clean up
			echo $this->j;

			// backed property, leave this here
			return $this->i;
		}
	}

}

class FooParam
{

	public function __construct(
		public int $i {
			get {
				echo 'irrelevant';

				// other property, clean up
				echo $this->j;

				// backed property, leave this here
				return $this->i;
			}
		}
	)
	{

	}

}
