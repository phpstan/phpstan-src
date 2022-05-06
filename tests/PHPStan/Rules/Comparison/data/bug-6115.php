<?php // lint >= 8.0

namespace Bug6115;

class Foo
{
	public function bar()
	{
		$array = [1, 2, 3];
		try {
			foreach ($array as $value) {
				$b = match ($value) {
					1 => 0,
					2 => 1,
				};
			}
		} catch (\UnhandledMatchError $e) {
		}

		try {
			foreach ($array as $value) {
				$b = match ($value) {
					1 => 0,
					2 => 1,
				};
			}
		} catch (\Error $e) {
		}

		try {
			foreach ($array as $value) {
				$b = match ($value) {
					1 => 0,
					2 => 1,
				};
			}
		} catch (\Exception $e) {
		}

		try {
			foreach ($array as $value) {
				$b = match ($value) {
					1 => 0,
					2 => 1,
				};
			}
		} catch (\UnhandledMatchError|\Exception $e) {
		}

		try {
			try {
				foreach ($array as $value) {
					$b = match ($value) {
						1 => 0,
						2 => 1,
					};
				}
			} catch (\Exception $e) {
			}
		} catch (\UnhandledMatchError $e) {
		}
	}
}

