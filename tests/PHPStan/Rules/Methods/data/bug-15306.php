<?php // lint >= 8.0

namespace Bug15306Methods;

function (\ZipArchive $zip): void {
	$zip->open('foo.zip', \ZipArchive::RDONLY | \ZipArchive::OVERWRITE);
	$zip->open('foo.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
	$zip->open('foo.zip', \ZipArchive::RDONLY | \ZipArchive::CHECKCONS);
};
