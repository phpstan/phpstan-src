<?php

namespace ContinueBreak;

class Foo
{

	public function doFoo($foo): void
	{
		switch ($foo) {
			case 1:
				break;
			default:
				break;
		}

		foreach ([1, 2, 3] as $val) {
			if (rand(0, 1)) {
				break;
			} else {
				continue;
			}
		}

		for ($i = 0; $i < 5; $i++) {
			if (rand(0, 1)) {
				break;
			} else {
				continue;
			}
		}

		while (true) {
			if (rand(0, 1)) {
				break;
			} else {
				continue;
			}
		}

		do {
			if (rand(0, 1)) {
				break;
			} else {
				continue;
			}
		} while (true);
	}

	public function doLorem($foo)
	{
		foreach ([1, 2, 3] as $val) {
			switch ($foo) {
				case 1:
					break 2;
				default:
					break 2;
			}
		}
	}

	public function doBar($foo)
	{
		foreach ([1, 2, 3] as $val) {
			switch ($foo) {
				case 1:
					break 3;
				default:
					break 3;
			}
		}
	}

	public function doBaz()
	{
		if (rand(0, 1)) {
			break;
		} else {
			continue;
		}
	}

	public function doIpsum($foo)
	{
		foreach ([1, 2, 3] as $val) {
			function (): void {
				break;
			};
		}
	}

}

if (rand(0, 1)) {
	break;
}
