<?php

namespace ThisVariable;

class Foo
{

	public function doFoo()
	{
		$this->test;

	}

	public static function doBar()
	{
		$this->test;



		$this->blabla = 'fooo';
	}

}

function () {
	$this->foo;
};

new class () {

	public function doFoo()
	{
		$this->foo;
	}

	public static function doBar()
	{
		$this->foo;
	}

};

function () {
	\Closure::bind(function (int $time) {
		$this->setTimestamp($time);
	}, new \DateTime());
};
