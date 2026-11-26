<?php declare(strict_types = 1);

namespace FunctionCallAdvancingHandles;

function doFoo(\CurlMultiHandle $mh, \CurlHandle $ch): void
{
	// takes the next message off the queue
	curl_multi_info_read($mh);

	// only reads the handle
	curl_multi_getcontent($ch);
}
