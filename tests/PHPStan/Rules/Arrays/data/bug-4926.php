<?php

namespace Bug4926;

/**
 * @phpstan-type Data array{customer?: array{first_name: string}}
 */
class Foo
{
	/** @var Data */
	private $data = [];

	public function getFirstName(): ?string
	{
		return $this->data['customer']['first_name'] ?? null;
	}
}
