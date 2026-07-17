<?php

namespace Bug11010;

class HelloWorld
{
	protected function sayHello(): void
	{
		echo 'Hello';
	}

	/**
	 * @param-closure-this self $cb
	 */
	public static function cb(\Closure $cb): void
	{
		$cb = $cb->bindTo(new self, self::class);
		if ($cb) {
			$cb();
		}
	}
}

function (): void {
	HelloWorld::cb(function () {
		$this->sayHello();
	});
};
