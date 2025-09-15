<?php

namespace Bug4969;

class Config
{
	/**
	 * @param array{host:string,port?:int} $config
	 */
	public function set(array $config): void
	{
		if (!is_string($config['host'])) {
			throw new \InvalidArgumentException('error');
		}
		if (isset($config['port']) && !is_int($config['port'])) { // error: Result of && is always false., tip: Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.
			throw new \InvalidArgumentException('error');
		}
	}
}
