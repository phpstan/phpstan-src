<?php // lib/U.php

namespace Lib;

class U extends \App\P
{
	use \App\T;
	use \App\C;

	public function m(): void
	{
		undefinedCounted();
	}
}
