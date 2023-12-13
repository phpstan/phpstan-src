<?php declare(strict_types = 1);

namespace Bug10298;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PropAttr {}

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ParamAttr {}

class Test
{
	public function __construct(
		#[PropAttr]
		#[ParamAttr]
		public string $test
	) {}
}
