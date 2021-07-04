<?php

namespace Bug5162;

use function PHPStan\Testing\assertType;

class Foo
{


	/**
	 * @return array<string,string|array<string>>
	 */
	function Get()
	{
		switch ( rand(1,3) )
		{
			case 1:
				return ['a' => 'val'];
			case 2:
				return ['a' => '1'];
			case 3:
				return ['a' => []];
		}
		return [];
	}

	public function doFoo(): void
	{
		// This variant works
		$result1 = $this->Get();
		if ( ! array_key_exists('a', $result1))
		{
			exit(1);
		}
		if ( ! is_numeric( $result1['a'] ) )
		{
			exit(1);
		}
		$val = (float) $result1['a'];
	}

	public function doBar(): void
	{
		// This variant doesn't work .. but is logically identical
		$result2 = $this->Get();
		if ( array_key_exists('a',$result2) && ! is_numeric( $result2['a'] ) )
		{
			exit(1);
		}
		if ( ! array_key_exists('a', $result2) )
		{
			exit(1);
		}
		$val = (float) $result2['a'];
	}

}
