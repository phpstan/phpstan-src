<?php declare(strict_types = 1);

namespace Bug15147Rule;

use ArrayObject;
use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class Test
{
	/**
	 * @param ArrayObject<array-key, array<mixed>|mixed> $alias
	 */
	public function populate(ArrayObject $alias): void
	{
		if (rand(1, 10) > 5) {
			// Append an example array
			$alias->append(['item', 13]);
		} else {
			$alias->append('void');
		}
	}
	
	public function test(): void
	{
        $alias = new ArrayObject();
		$this->populate($alias);
        $alias = $alias->getArrayCopy();

        foreach ($alias as $k => $v) {
            if (!is_array($v)) {
                $alias[$k] = [];
            }
        }
	}
}

