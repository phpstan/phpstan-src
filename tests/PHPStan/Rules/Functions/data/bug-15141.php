<?php

namespace Bug15141;

$content = '';
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === FALSE) {
	throw new \RuntimeException('Cannot create finfo instance.');
}

$type = (string) finfo_buffer($finfo, $content);
