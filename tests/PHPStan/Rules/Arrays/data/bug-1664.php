<?php

namespace Bug1664;

class A
{
	public function a()
	{
		$responses = [
			'foo',
			42,
			'bar',
		];

		return $responses[array_rand($responses)];
	}
}
