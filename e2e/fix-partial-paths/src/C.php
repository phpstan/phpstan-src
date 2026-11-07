<?php

namespace App;

trait C
{
	public function c(): void
	{
		undefinedCountedInTrait();
	}
}

class V
{
	use C;
}
