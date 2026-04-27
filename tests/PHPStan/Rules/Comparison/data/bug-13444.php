<?php declare(strict_types = 1);

namespace Bug13444;

function sayStoreCas(string $key): void
{
	$memcached = new \Memcached();

	do {
		$extendedReturn = $memcached->get($key, null, \Memcached::GET_EXTENDED);

		if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
			return;
		}

		if (!is_array($extendedReturn) || !isset($extendedReturn['value']) || !isset($extendedReturn['cas'])) {
			return;
		}

		$data = $extendedReturn['value'];
		$cas = $extendedReturn['cas'];
		\assert(is_float($cas));

		// Do some work on the data..
		$memcached->cas($cas, $key, $data);

	} while ($memcached->getResultCode() !== \Memcached::RES_SUCCESS);
}
