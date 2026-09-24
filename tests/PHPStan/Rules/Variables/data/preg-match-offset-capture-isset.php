<?php declare(strict_types = 1);

namespace PregMatchOffsetCaptureIsset;

function offsetCapture(string $s): ?string
{
	if (! preg_match('/(a)(b)?/', $s, $m, PREG_OFFSET_CAPTURE)) {
		return null;
	}

	return isset($m[2]) ? $m[2][0] : null;
}
