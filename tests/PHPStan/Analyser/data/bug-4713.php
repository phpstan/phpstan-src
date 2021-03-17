<?php

namespace Bug4713;

class Service
{
	public static function createInstance(string $class = self::class): Service
	{
		return new $class();
	}
}

function (): void {
	$service = Service::createInstance();
};
