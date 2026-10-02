<?php declare(strict_types = 1);

namespace BaselineTraitContextOwnError;

final class AMailer
{

	use SomeTrait;

	public function send(bool $flag, ?string $value): void
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
