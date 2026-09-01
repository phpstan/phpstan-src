<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Seld\PharUtils\Timestamps;

$file = getcwd() . '/' . ($argv[1] ?? '');
if (!is_file($file)) {
	echo "File does not exist.\n";
	exit(1);
}

// The checksum build keeps the fixed date so it stays byte-reproducible. The
// distributed build gets its commit date instead: OPcache refuses to cache a
// member whose mtime is 0, and with opcache.validate_timestamps it tells two
// versions of the phar apart by mtime alone, so they must differ per build.
$util = new Timestamps($file);
$util->updateTimestamps(new DateTimeImmutable($argv[2] ?? '2017-10-11 08:58:00'));
$util->save($file, Phar::SHA512);

$zeroMtimeMembers = 0;
foreach (new RecursiveIteratorIterator(new Phar($file)) as $member) {
	if ($member->getMTime() !== 0) {
		continue;
	}

	$zeroMtimeMembers++;
}
if ($zeroMtimeMembers > 0) {
	echo sprintf("%d phar members still have mtime 0 - OPcache would not cache them.\n", $zeroMtimeMembers);
	exit(1);
}
