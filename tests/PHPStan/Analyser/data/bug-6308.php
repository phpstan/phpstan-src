<?php

declare(strict_types=1);

namespace Bug6308;

use function PHPStan\Testing\assertType;

class BaseFinderStatic
{
	static public function find(): false|static
	{
		return false;
	}
}

final class UnionStaticStrict extends BaseFinderStatic
{
	public function something()
	{
		assertType('static(Bug6308\UnionStaticStrict)|false', $this->find());
	}
}
