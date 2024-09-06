<?php declare(strict_types = 1);

namespace Bug11640;

$ch = curl_init('https://example.com');

if (!$ch) {

}
