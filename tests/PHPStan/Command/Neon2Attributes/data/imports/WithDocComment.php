<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Imports;

use stdClass;

/**
 * Some description.
 */
#[\Attribute]
final class WithDocComment
{

	public function get(): stdClass
	{
		return new stdClass();
	}

}
