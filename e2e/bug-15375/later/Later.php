<?php declare(strict_types = 1);

namespace Bug15375;

/**
 * @extends Base<Entity>
 */
class Later extends Base
{

	use HasName;

	public string $label = 'later';

}
