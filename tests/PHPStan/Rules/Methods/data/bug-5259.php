<?php

namespace Bug5259;

class HelloWorld
{

	/** @var \DateTimeImmutable  */
	private $date;

	public function __construct(\DateTimeInterface $date)
	{
		$this->date = $date instanceof \DateTimeImmutable ? $date : \DateTimeImmutable::createFromMutable($date);
	}
}
