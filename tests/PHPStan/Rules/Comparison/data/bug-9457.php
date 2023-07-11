<?php // lint >= 8.1

namespace Bug9457;

class Randomizer {
	function bool(): bool {
		return rand(0, 1) === 1;
	}

	public function doFoo(?self $randomizer): void
	{
		// Correct
		echo match ($randomizer?->bool()) {
			true => 'true',
			false => 'false',
			null => 'null',
		};

		// Correct
		echo match ($randomizer?->bool()) {
			true => 'true',
			false, null => 'false or null',
		};

		// Unexpected error
		echo match ($randomizer?->bool()) {
			false => 'false',
			true, null => 'true or null',
		};
	}
}
