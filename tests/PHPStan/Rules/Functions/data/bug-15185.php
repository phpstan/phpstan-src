<?php declare(strict_types = 1);

namespace Bug15185;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

$handle = curl_init();

if ( in_array( curl_getinfo( $handle, CURLINFO_HTTP_CODE ), array( 301, 302 ), true ) ) {
}
