<?php declare(strict_types = 1);

namespace CurlSetOptCallback;

class HelloWorld
{

	public function invalid(): void
	{
		$ch = curl_init();

		// WRITEFUNCTION expects a callback returning int
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, string $data): bool {
			return true;
		});
		// HEADERFUNCTION expects a callback returning int
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $data): bool {
			return true;
		});
		// READFUNCTION expects a callback returning string|int<0, max>
		curl_setopt($ch, CURLOPT_READFUNCTION, function ($ch, $stream, int $length): bool {
			return false;
		});
		// PROGRESSFUNCTION expects a callback returning int
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, int $a, int $b, int $c, int $d): bool {
			return false;
		});
		// XFERINFOFUNCTION expects a callback returning int
		curl_setopt($ch, CURLOPT_XFERINFOFUNCTION, function ($ch, int $a, int $b, int $c, int $d): string {
			return '';
		});
		// READFUNCTION returns the read length, a negative int is invalid
		curl_setopt($ch, CURLOPT_READFUNCTION, function ($ch, $stream, int $length): int {
			return -1;
		});
	}

	public function valid(): void
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, string $data): int {
			return strlen($data);
		});
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $header): int {
			return strlen($header);
		});
		curl_setopt($ch, CURLOPT_READFUNCTION, function ($ch, $stream, int $length): string {
			return '';
		});
		// CURL_READFUNC_PAUSE and CURL_READFUNC_ABORT are returned as int
		curl_setopt($ch, CURLOPT_READFUNCTION, function ($ch, $stream, int $length): int {
			return CURL_READFUNC_PAUSE;
		});
		// 0 signals the end of the data
		curl_setopt($ch, CURLOPT_READFUNCTION, function ($ch, $stream, int $length): int {
			return 0;
		});
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, int $a, int $b, int $c, int $d): int {
			return 0;
		});
		curl_setopt($ch, CURLOPT_XFERINFOFUNCTION, function ($ch, int $a, int $b, int $c, int $d): int {
			return 0;
		});
		// null resets the callback and is allowed
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, null);
	}

}
