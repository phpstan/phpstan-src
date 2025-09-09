<?php // lint >= 8.0

namespace Bug7225;

use DateTimeImmutable;

class CustomDateTimeImmutable extends DateTimeImmutable
{
	public function test(): self
	{
		return CustomDateTimeImmutable::createFromInterface(new DateTime());
	}

	public function fromFormat(): CustomDateTimeImmutable|false
	{
		return CustomDateTimeImmutable::createFromFormat('H:i:s', '00:00:00');
	}
}
