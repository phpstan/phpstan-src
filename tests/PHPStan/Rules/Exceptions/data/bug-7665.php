<?php declare(strict_types = 1);

namespace Bug7665;

class HelloWorld
{
	public function doStuff(): string
	{
		try {
			if(rand(0,1) === 1) {
				throw new \RuntimeException('Bad luck');
			}


			return 'yay';
			// other stuff
		} catch(\Throwable $e) {
			if (rand(0,1) === 1) {
				exit(1);
			}
			// do some stuff to reset
		} finally {
			if(rand(0,1) === 1) {
				exit(1);
			}
		}
	}
}
