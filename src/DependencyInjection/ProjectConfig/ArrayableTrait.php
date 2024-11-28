<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

use function array_key_exists;
use function array_map;
use function get_object_vars;
use function is_array;

trait ArrayableTrait
{

	public function toArray(): array
	{
		$vars = get_object_vars($this);
		if (array_key_exists('_extra', $vars) && is_array($vars['_extra'])) {
			$vars += $vars['_extra'];
			unset($vars['_extra']);
		}

		$config = [];
		foreach ($vars as $key => $value) {
			if ($value === Undefined::Undefined) {
				continue;
			}
			if ($value instanceof Arrayable) {
				$config[$key] = $value->toArray();
			} elseif (is_array($value)) {
				$config[$key] = array_map(
					static fn (mixed $item): mixed => $item instanceof Arrayable ? $item->toArray() : $item,
					$value,
				);
			} else {
				$config[$key] = $value;
			}
		}
		return $config;
	}

}
