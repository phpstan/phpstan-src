<?php declare(strict_types = 1);

namespace Bug4526;

use DateTime;
use DateTimeImmutable;
use SplObjectStorage;

class HelloWorld
{
	/**
	 * @var SplObjectStorage<DateTime, DateTimeImmutable>|null
	 */
	private $map;

	public function __construct(){
		$this->map = new SplObjectStorage;
	}
}
