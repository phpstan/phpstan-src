<?php declare(strict_types = 1);

namespace App;

final class Uses
{

	public function make(): object
	{
		return new Later();
	}

}
