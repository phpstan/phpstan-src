<?php declare(strict_types = 1);

namespace Bug9141;

use DateTimeImmutable;

class HelloWorld
{

	private ?DateTimeImmutable $startTime;

	public function __construct() {
		$this->startTime = new DateTimeImmutable();
	}

	public function getStartTime(): ?DateTimeImmutable
	{
		return $this->startTime;
	}

}

$helloWorld = new HelloWorld();
if ($helloWorld->getStartTime() > new DateTimeImmutable()) {
	echo sprintf('%s', $helloWorld->getStartTime()->format('d.m.y.'));
}
