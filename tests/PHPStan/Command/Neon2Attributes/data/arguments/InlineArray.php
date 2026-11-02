<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

final class InlineArray
{

	/**
	 * @param list<string> $list
	 */
	public function __construct(
		private array $list,
	)
	{
	}

}
