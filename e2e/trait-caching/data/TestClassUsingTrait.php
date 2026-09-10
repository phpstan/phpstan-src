<?php

namespace TraitsCachingIssue;

class TestClassUsingTrait
{

	use TraitOne;

	/**
	 * @return \stdClass
	 */
	public function doBar()
	{
		return $this->doFoo();
	}

	public function doBaz(): \stdClass
	{
		$class = new class() {

			use TraitTwo;

			/**
			 * @return \stdClass
			 */
			public function doBar()
			{
				return $this->doFoo();
			}
		};

		return $class->doBar();
	}

}
