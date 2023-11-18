<?php

namespace Bug10149;

trait WarnDynamicPropertyTrait
{
	/**
	 * @return mixed
	 */
	#[\Override]
	public function &__get(string $name)
	{
		return $this->{$name};
	}
}

class StdSat extends \stdClass
{
	use WarnDynamicPropertyTrait;
}
