<?php declare(strict_types = 1);

namespace Bug15858;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/**
 * @param array{http_code: int, content: string} $response
 */
function f($response): void
{
	if (empty($response['content']) || (isset($response['http_code']) && $response['http_code'] !== 200)) {
		assertType("array{http_code: int, content: ''|'0'}|array{http_code: int<min, 199>|int<201, max>, content: non-falsy-string}", $response);
		assertNativeType('mixed~array{http_code: 200, content: non-falsy-string}', $response);
	}
}

/**
 * @param array{http_code: int, content: string} $response
 */
function g($response): void
{
	if (empty($response['content'])) {
		echo 'empty';
	}

	if (empty($response['content']) || $response['http_code'] !== 200) {
		assertType("array{http_code: int, content: ''|'0'}|array{http_code: int<min, 199>|int<201, max>, content: non-falsy-string}", $response);
		assertNativeType('mixed~array{http_code: 200, content: non-falsy-string}', $response);
	}
}

/**
 * @param array{http_code: int, content: string} $response
 */
function h(array $response): void
{
	if (empty($response['content']) || $response['http_code'] !== 200) {
		assertType('array{http_code: int, content: string}', $response);
		assertNativeType('array', $response);
	}
}

function i(int $httpCode, string $content): void
{
	$response = ['http_code' => $httpCode, 'content' => $content];
	if (empty($response['content']) || (isset($response['http_code']) && $response['http_code'] !== 200)) {
		assertType("array{http_code: int, content: ''|'0'}|array{http_code: int<min, 199>|int<201, max>, content: non-falsy-string}", $response);
		assertNativeType("array{http_code: int, content: ''|'0'}|array{http_code: int<min, 199>|int<201, max>, content: non-falsy-string}", $response);
	}
}
