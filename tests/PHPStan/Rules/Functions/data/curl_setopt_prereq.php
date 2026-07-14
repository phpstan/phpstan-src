<?php declare(strict_types = 1);

namespace CurlSetOptPrereq;

class HelloWorld
{

	public function invalid(): void
	{
		$ch = curl_init();

		// CURLOPT_PREREQFUNCTION (PHP 8.4+) expects a callback returning int
		curl_setopt($ch, CURLOPT_PREREQFUNCTION, function ($ch, string $primaryIp, string $connectIp, int $primaryPort, int $connectPort): bool {
			return true;
		});
	}

	public function valid(): void
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_PREREQFUNCTION, function ($ch, string $primaryIp, string $connectIp, int $primaryPort, int $connectPort): int {
			return CURL_PREREQFUNC_OK;
		});
		curl_setopt($ch, CURLOPT_PREREQFUNCTION, null);
	}

}
