<?php declare(strict_types = 1);

namespace PHPStan\File;

use Exception;

/**
 * A native file monitor cannot run here - no FFI, the kernel refused a watch,
 * or the project has more directories than we are willing to watch.
 * {@see FileMonitorFactory} answers with {@see HashingFileMonitor} instead.
 */
final class FileMonitorNotSupportedException extends Exception
{

}
