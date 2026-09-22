<?php

namespace DsMapVoidThrowType;

class Foo
{

	public function doFoo(\Ds\Map $map): void
	{
		try {
			$map->get('1', null);
		} catch (\Throwable $e) {

		}
	}

	public function doBar(\Ds\Map $map): void
	{
		try {
			$map->get('1');
		} catch (\Throwable $e) {

		}
	}

}
