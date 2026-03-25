<?php declare(strict_types = 1);

namespace Bug14366;

/** @param non-empty-string $uri */
function retrieve($uri): void
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($ch);
	if (false === $response) {
		throw new \Exception('JSON schema not found');
	}


	if (PHP_VERSION_ID < 80000) {
		curl_close($ch);
	}
}

function noGuard(): void
{
	$ch = curl_init();
	curl_close($ch);
}
