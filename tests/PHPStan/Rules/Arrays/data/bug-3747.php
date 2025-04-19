<?php

namespace Bug3747;

class X {

	/** @var array<string,string> $x */
	private static array $x;

	public function y(): void {

		self::$x = [];

		$this->z();

		echo self::$x['foo'];

	}

	private function z(): void {
		self::$x['foo'] = 'bar';
	}

}

$x = new X();
$x->y();
