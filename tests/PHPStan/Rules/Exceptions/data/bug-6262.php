<?php

namespace Bug6262;

class DateInterval extends \DateInterval
{
}

class Foo
{

	public function doFoo()
	{
		try {
			$interval = new DateInterval('invalid');
		} catch (\Exception $e) {
		}
	}

}
