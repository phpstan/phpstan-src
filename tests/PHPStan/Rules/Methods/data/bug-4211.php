<?php

namespace Bug4211;

use DateTimeImmutable;

trait HelloWorldTraitTest
{
}

trait HelloWorldTrait
{
	public function sayHello(DateTimeImmutable $date): void
	{
		echo 'Hello, ' . $date->format('j. n. Y');
	}
}

class HelloWorld
{
	use HelloWorldTraitTest, HelloWorldTrait {
		sayHello as hello;
	}

	public function sayHello(DateTimeImmutable $date): void {
		$this->hello($date);
	}
}
