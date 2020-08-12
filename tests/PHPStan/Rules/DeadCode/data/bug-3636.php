<?php

namespace Bug3636;

class Foo
{

	/** @var \DateTimeImmutable */
	private $date;

	public function getDate(): \DateTimeImmutable
	{
		return $this->date ??= new \DateTimeImmutable();
	}

}
