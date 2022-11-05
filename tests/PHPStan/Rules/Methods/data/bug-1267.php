<?php declare(strict_types=1);

namespace Bug1267;

abstract class Base {
	protected static function get_type_config(): array
	{
		return [];
	}
}

abstract class ObjectType extends Base
{
	protected static function get_type_config(): array
	{
		$config = parent::get_type_config();

		if (method_exists(static::class, 'get_connections')) { // children that implement TypeWithConnections.php
			\PHPStan\dumpType(static::class);
			$config['connections'] = static::get_connections();
		}

		if (method_exists(static::class, 'get_interfaces')) { // children that implement TypeWithInterfaces.php
			$config['interfaces'] = static::get_interfaces();
		}

		return $config;
	}
}

