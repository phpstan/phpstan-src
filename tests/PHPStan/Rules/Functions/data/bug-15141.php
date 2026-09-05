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

function doLorem(): void
{
	$connection = pg_connect('');
	if ($connection === false) {
		throw new \RuntimeException('');
	}

	pg_clientencoding($connection);
	pg_errormessage($connection);

	$result = pg_query($connection, 'SELECT 1');
	if ($result === false) {
		throw new \RuntimeException('');
	}

	pg_fieldname($result, 1);
	pg_fieldnum($result, 'foo');
	pg_fieldsize($result, 1);
	pg_fieldtype($result, 1);
	pg_getlastoid($result);
	pg_numfields($result);
	pg_numrows($result);
	pg_freeresult($result);
}

/** @param resource $r */
function takesResource($r): void
{
}

function doIpsum(): void
{
	$connection = pg_connect('');
	if ($connection === false) {
		throw new \RuntimeException('');
	}

	$result = pg_exec($connection, 'SELECT 1');
	if ($result === false) {
		throw new \RuntimeException('');
	}

	takesResource($result);
	pg_fetch_row($result);
	pg_num_rows($result);

	$lob = pg_loopen($connection, 1, 'r');
	if ($lob === false) {
		throw new \RuntimeException('');
	}

	pg_lo_read($lob, 1);
	pg_lo_close($lob);
}
