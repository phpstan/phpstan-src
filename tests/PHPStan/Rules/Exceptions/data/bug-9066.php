<?php

namespace Bug9066;

class Foo
{
	public function mayThrow()
	{	$map = new \Ds\Map();
		try {
			$map->get('1');
		} catch (\OutOfBoundsException $e) {

		}
	}
	public function neverThrows()
	{	$map = new \Ds\Map();
		try {
			$map->get('1', null);
		} catch (\OutOfBoundsException $e) {

		}
	}
}
