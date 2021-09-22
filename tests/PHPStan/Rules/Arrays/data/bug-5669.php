<?php

namespace Bug5669;

abstract class a
{


	/**
	 *
	 * @return arr<str>
	 */
	public function getReplacer()
	{
		return [];
	}

}

class c extends a
{

	public function getReplacer()
	{
		$replacer = parent::getReplacer();
		$replacer['%customer_salutation%'] = 'test';

		return $replacer;
	}
}
