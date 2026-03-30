<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6119;

class Locker {

	public function __construct(private bool $locked = false) {
	}

	public function acquireLock(object $obj): bool {
		if(rand(0,10) > 5) {
			return $this->locked = true;
		}
		return $this->locked = false;
	}

	public function isLocked(): bool {
		return $this->locked;
	}
}

class HelloWorld
{

	public function __construct(private Locker $locker) {
	}

	public function doStuff(object $obj): string
	{
		try {

			// code
			if(!$this->locker->acquireLock($obj)) {
				throw new \RuntimeException('Lock not acquired');
			}
			// other stuff
		} catch(\Throwable $e) {
			// do some stuff to reset
		} finally {
			return 'OK';
		}
	}
}
