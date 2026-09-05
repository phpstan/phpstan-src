<?php declare(strict_types = 1);

namespace Bug15185;

use function curl_getinfo;
use function curl_init;
use function finfo_buffer;
use function finfo_file;
use function finfo_open;
use function in_array;
use function pg_connect;
use function pg_exec;
use function pg_free_result;
use function pg_num_rows;
use function xml_parse_into_struct;
use function xml_parser_create;
use const CURLINFO_HTTP_CODE;

function benevolentUnion(): void
{
	$handle = curl_init();
	if (in_array(curl_getinfo($handle, CURLINFO_HTTP_CODE), [301, 302], true)) {
	}
}

function narrowedResource(): void
{
	$handle = curl_init();
	if ($handle === false) {
		return;
	}

	curl_getinfo($handle, CURLINFO_HTTP_CODE);
}

function otherResourceFunctions(string $data): void
{
	$parser = xml_parser_create();
	xml_parse_into_struct($parser, $data, $values);

	$finfo = finfo_open();
	if ($finfo === false) {
		return;
	}

	finfo_file($finfo, 'test.txt');
	finfo_buffer($finfo, $data);
}

function postgres(string $connectionString, string $query): void
{
	$connection = pg_connect($connectionString);
	if ($connection === false) {
		return;
	}

	$result = pg_exec($connection, $query);
	if ($result === false) {
		return;
	}

	pg_num_rows($result);
	pg_free_result($result);
}
