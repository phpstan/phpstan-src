<?php

declare(strict_types = 1);

namespace Bug9724Rule;

class HelloWorld
{
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
