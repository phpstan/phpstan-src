<?php declare(strict_types = 1);

namespace Bug10884;

class Cat {}

/** @var \SplObjectStorage<Cat, null> $map */
$map = new \SplObjectStorage();
$map->attach(new Cat());
$map->attach(new Cat());

class Manager
{
	/**
	 * @param SplObjectStorage<Cat, null> $map
	 */
	public function doSomething(\SplObjectStorage $map): void
	{
		/** @var \SplObjectStorage<Cat, null> $other */
		$other = new \SplObjectStorage();

		if (count($map) === 0) {
			return;
		}

		foreach ($map as $cat) {
			if (!$this->someCheck($cat)) {
				continue;
			}

			$other->attach($cat);
		}

		$map->removeAll($other);

		if (count($map) === 0) {
			return;
		}

		// ok!
	}

	private function someCheck(Cat $cat): bool {
		// just some random
		return $cat == true;
	}
}

(new Manager())->doSomething($map);
