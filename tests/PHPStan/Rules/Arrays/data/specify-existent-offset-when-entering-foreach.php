<?php

namespace SpecifyExistentOffsetWhenEnteringForeach;

class Foo
{

	public function doFoo(string $s): void
	{
		$hintsToFind = ['ext-' => 0, 'lib-' => 0, 'php' => 99, 'composer' => 99];
		foreach ($hintsToFind as $hintPrefix => $hintCount) {
			if (str_starts_with($s, $hintPrefix)) {
				if ($hintCount === 0 || $hintCount >= 99) {
					$hintsToFind[$hintPrefix]++;
				} elseif ($hintCount === 1) {
					unset($hintsToFind[$hintPrefix]);
				}
			}
		}
	}

}
