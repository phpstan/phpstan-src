<?php

namespace ParamOutTemplate;


class FooBar
{
	/**
	 * @template S of self
	 * @param-out S $s
	 */
	function genericSelf(mixed &$s): void
	{
	}
}
