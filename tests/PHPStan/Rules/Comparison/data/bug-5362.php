<?php

namespace Bug5362;

class Foo
{

	function doFoo(int $retry): void {
		echo "\nfoo: ".$retry;
		throw new \Exception();
	}

	function doBar()
	{
		$retry = 2;

		do {
			try {
				$this->doFoo($retry);

				break;
			} catch (\Exception $e) {
				if (0 === $retry) {
					throw $e;
				}

				--$retry;
			}
		} while ($retry > 0);
	}

}
