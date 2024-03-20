<?php

namespace Bug10687;

class HelloWorld
{
	public function ipCheckData(string $host, ?\stdClass &$ipdata): bool
	{
		$ipdata = null;

		// See if this is an ip address
		if (!filter_var($host, FILTER_VALIDATE_IP)) {
			return false;
		}

		$ipdata = new \stdClass;

		return true;
	}
}
