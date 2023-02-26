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
		if (isset($config['port']) && !is_int($config['port'])) {
			throw new \InvalidArgumentException('error');
		}
	}
}
