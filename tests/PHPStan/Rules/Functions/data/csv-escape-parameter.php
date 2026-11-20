<?php // lint >= 8.0

namespace CsvEscapeParameter;

use SplFileObject;
use SplTempFileObject;

/**
 * @param resource $stream
 * @param list<string> $fields
 * @param list<mixed> $args
 */
function doFoo($stream, array $fields, string $line, SplFileObject $file, SplTempFileObject $tempFile, array $args): void
{
	fgetcsv($stream);
	fgetcsv($stream, null, ';', '"');
	fputcsv($stream, $fields, enclosure: "'");
	\str_getcsv($line);
	str_getcsv($line, ';', '"');
	$file->setCsvControl();
	$tempFile->setCsvControl(';');

	fgetcsv($stream, null, ',', '"', '\\');
	fgetcsv($stream, escape: '');
	fputcsv($stream, $fields, ',', '"', '', "\n");
	fputcsv($stream, $fields, eol: "\r\n");
	str_getcsv($line, ...$args);
	$file->setCsvControl(';', '"', '');
	$file->fgetcsv();
	$file->fputcsv($fields);
}
