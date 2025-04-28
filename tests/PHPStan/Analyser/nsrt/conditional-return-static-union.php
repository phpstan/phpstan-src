<?php

namespace ConditionalReturnStaticUnion;

use function PHPStan\Testing\assertType;

class Config {}
class MainConfig
{
	/**
	 * @param array<mixed>|Config $value
	 * @return ($value is array ? Config : $this)
	 */
	public function invalidReturn(array|Config $value = []): Config|static
	{
		if (is_array($value)) {
			return new Config();
		}
		return $this;
	}

	/**
	 * @param array<mixed>|Config $value
	 * @return ($value is array ? Config : $this)
	 */
	public function validReturn(array|Config $value = []): Config|self
	{
		if (is_array($value)) {
			return new Config();
		}
		return $this;
	}
}

function (MainConfig $c): void {
	assertType(Config::class, (new MainConfig())->invalidReturn());
	assertType(Config::class, (new MainConfig())->validReturn());
	assertType(MainConfig::class, (new MainConfig())->invalidReturn(new Config()));
	assertType(MainConfig::class, (new MainConfig())->validReturn(new Config()));

	assertType(Config::class, $c->invalidReturn());
	assertType(Config::class, $c->validReturn());
	assertType(MainConfig::class, $c->invalidReturn(new Config()));
	assertType(MainConfig::class, $c->validReturn(new Config()));
};
