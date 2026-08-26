<?php declare(strict_types = 1);

namespace Bug14932;

trait SomeTrait
{

	public function run(bool $flag, ?string $value): void
	{
		$hash = null;
		if ($value !== null) {
			if ($flag) {
				$hash = $value;
			}
		}

		if ($hash !== null && $flag) {
			echo $hash;
		}
	}

}
