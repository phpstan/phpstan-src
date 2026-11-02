<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\OutsideDirectories;

final class OutsideStatic
{

	public static function create(): self
	{
		return new self();
	}

}
