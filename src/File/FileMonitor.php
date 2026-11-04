<?php declare(strict_types = 1);

namespace PHPStan\File;

/**
 * Watches the analysed and scanned files for changes between PHPStan Pro analyses.
 *
 * {@see HashingFileMonitor} is the portable implementation: it re-hashes every
 * monitored file on every poll. The native implementations
 * ({@see FsEventsFileMonitor}, {@see InotifyFileMonitor}) wrap it and let the
 * kernel answer "did anything change at all", so an idle poll touches no files;
 * once the kernel says yes, they delegate to the hashing monitor so the reported
 * result is identical either way.
 *
 * {@see FileMonitorFactory} picks the implementation for the current platform
 * and hands it back initialized.
 */
interface FileMonitor
{

	public function getChanges(): FileMonitorResult;

	/**
	 * Files to watch besides the analysed and scanned ones, from now on.
	 *
	 * @param array<string> $filePaths
	 */
	public function watchFiles(array $filePaths): void;

	/**
	 * How often the caller should poll. A monitor whose idle poll is free can
	 * afford a much shorter interval, which is what makes an edit noticed sooner.
	 */
	public function getPollInterval(): float;

}
