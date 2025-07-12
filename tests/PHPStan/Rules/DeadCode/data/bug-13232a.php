<?php

namespace Bug13232a;

final class HelloWorld
{
	public function sayHa(): void
	{
		echo sprintf("Hello, %s no way", $this->neverReturnsMethod());
		echo 'this will never happen';
	}

	public function sayHi(): void
	{
		echo 'Hello, ' . neverReturns()
			. ' no way';
		echo 'this will never happen';
	}

	public function sayHo(): void
	{
		echo "Hello, {$this->neverReturnsMethod()} no way";
		echo 'this will never happen';
	}

	public function sayHe(): void
	{
		$callable = function (): never {
			exit();
		};
		echo sprintf("Hello, %s no way", $callable);
		echo 'this will never happen';
	}

	public function sayHuu(): void
	{
		$x = [
			$this->neverReturnsMethod()
		];
		echo 'this will never happen';
	}

	function neverReturnsMethod(): never {
		exit();
	}
}
function neverReturns(): never {
	exit();
}

