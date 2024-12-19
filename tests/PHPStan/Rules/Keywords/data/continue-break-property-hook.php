<?php // lint >= 8.4

namespace ContinueBreakPropertyHook;

class Foo
{

	public int $bar {
		set (int $foo) {
			foreach ([1, 2, 3] as $val) {
				switch ($foo) {
					case 1:
						break 3;
					default:
						break 3;
				}
			}
		}
	}

	public int $baz {
		get {
			if (rand(0, 1)) {
				break;
			} else {
				continue;
			}
		}
	}

	public int $ipsum {
		get {
			foreach ([1, 2, 3] as $val) {
				function (): void {
					break;
				};
			}
		}
	}

}

class ValidUsages
{

	public int $i {
		set (int $foo) {
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
	}

	public int $j {
		set (int $foo) {
			foreach ([1, 2, 3] as $val) {
				switch ($foo) {
					case 1:
						break 2;
					default:
						break 2;
				}
			}
		}
	}

}
