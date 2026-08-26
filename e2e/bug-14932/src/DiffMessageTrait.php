<?php declare(strict_types = 1);

namespace Bug14932;

trait DiffMessageTrait
{

	public function compare(): void
	{
		if (self::NAME === 'zzz') {
			echo 'match';
		}
	}

}
