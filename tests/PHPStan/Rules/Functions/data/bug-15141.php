<?php

namespace Bug15141;

function doFoo(): void
{
	$content = '';
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	if ($finfo === FALSE) {
		throw new \RuntimeException('Cannot create finfo instance.');
	}

	$type = (string) finfo_buffer($finfo, $content);
	$type2 = (string) finfo_file($finfo, 'foo.txt');
}

function doBar(): void
{
	$ch = curl_init();
	if ($ch === false) {
		throw new \RuntimeException('');
	}

	curl_getinfo($ch);
}

function doBaz(): void
{
	$ftp = ftp_connect('example.com');
	if ($ftp === false) {
		throw new \RuntimeException('');
	}

	ftp_alloc($ftp, 1);
	ftp_quit($ftp);
}
