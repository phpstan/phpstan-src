<?php

declare(strict_types = 1);

namespace Bug9724;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function thisWorks(?int $limit, int $offset = 0): void
	{
		if ($limit && 0 === ($offset % $limit)) {
			assertType('(float|int)', ($offset / $limit) + 1);
		}
	}

	public function thisDoesntWork(?int $limit, int $offset = 0): void
	{
		if ($limit && $offset && (0 === ($offset % $limit))) {
			assertType('(float|int<min, 0>|int<2, max>)', ($offset / $limit) + 1);
		}
	}

	/** @param int<-2, 2> $offsetRange */
	public function withRange(int $limit, int $offset, int $offsetRange): void
	{
		if ($limit) {
			assertType('(float|int)', $offset / $limit);
			assertType('(float|int<-2, 2>)', $offsetRange / $limit);
		}
	}

	private function expectInt(int $page): void
	{
	}

	public function originalIssue(?int $limit, int $offset = 0): void
	{
		if ($limit && $offset && (0 === ($offset % $limit))) {
			$this->expectInt(($offset / $limit) + 1);
		}
	}
}
