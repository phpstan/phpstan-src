<?php declare(strict_types = 1);

namespace Bug15271;

class MondayMorning
{

	public function test(): void
	{
		str_decrement('B');
		str_increment('B');
		get_class($this);
		get_called_class();
	}

}
