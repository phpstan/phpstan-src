<?php

namespace ReturnRuleConditionalTypes;

class Foo
{

	/**
	 * @param mixed $p
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p)
	{
		if (rand(0, 1)) {
			return new \stdClass();
		}

		return 3;
	}

}
