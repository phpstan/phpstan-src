<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use AllowDynamicProperties;
use FilesystemIterator;
use Throwable;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function is_dir;
use function stat;
use function strlen;
use function strncmp;
use function substr;
use const SEEK_SET;
use const STREAM_REPORT_ERRORS;
use const STREAM_URL_STAT_QUIET;
use const STREAM_USE_PATH;

/**
 * A replacement for PHP's built-in phar:// stream wrapper that serves files
 * from an on-disk extraction of the running phar.
 *
 * The point: PHP's built-in wrapper opens the .phar file once and caches the
 * resulting fd internally. After pcntl_fork(), parent and all forked children
 * share that single open file description (and its seek cursor); concurrent
 * reads from siblings interleave and the child sees garbage bytes mid-file —
 * resulting in spurious parse errors at "almost valid" positions.
 *
 * By extracting the phar to a tmp directory in the parent and rerouting every
 * `phar://…/foo` access to `$extractDir/foo`, each forked child opens fresh
 * fds against ordinary disk files. No shared cursor, no race.
 *
 * Wired up by {@see PharForkPreparation}.
 *
 * phpcs:disable PSR1.Methods.CamelCapsMethodName
 * phpcs:disable Squiz.NamingConventions.ValidVariableName
 */
#[AllowDynamicProperties]
final class PharRedirectStreamWrapper
{

	private static ?string $pharPath = null;

	private static ?string $extractDir = null;

	/** @var resource|null */
	private $fp = null;

	private ?FilesystemIterator $dirIterator = null;

	public static function configure(string $pharPath, string $extractDir): void
	{
		self::$pharPath = $pharPath;
		self::$extractDir = $extractDir;
	}

	private function translate(string $pharUrl): ?string
	{
		if (self::$pharPath === null || self::$extractDir === null) {
			return null;
		}
		if (strncmp($pharUrl, 'phar://', 7) !== 0) {
			return null;
		}
		$afterScheme = substr($pharUrl, 7);
		$pharPathLen = strlen(self::$pharPath);
		if (strncmp($afterScheme, self::$pharPath, $pharPathLen) !== 0) {
			return null;
		}
		$internal = substr($afterScheme, $pharPathLen);

		return self::$extractDir . $internal;
	}

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
	{
		$real = $this->translate($path);
		if ($real === null) {
			return false;
		}
		$useIncludePath = ($options & STREAM_USE_PATH) !== 0;
		$report = ($options & STREAM_REPORT_ERRORS) !== 0;
		$fp = $report ? fopen($real, $mode, $useIncludePath) : @fopen($real, $mode, $useIncludePath);
		if ($fp === false) {
			return false;
		}
		$this->fp = $fp;
		$opened_path = $real;

		return true;
	}

	public function stream_read(int $count): string|false
	{
		if ($this->fp === null || $count < 1) {
			return false;
		}

		return fread($this->fp, $count);
	}

	public function stream_close(): void
	{
		if ($this->fp === null) {
			return;
		}
		fclose($this->fp);
		$this->fp = null;
	}

	public function stream_eof(): bool
	{
		if ($this->fp === null) {
			return true;
		}

		return feof($this->fp);
	}

	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		if ($this->fp === null) {
			return false;
		}

		return fseek($this->fp, $offset, $whence) === 0;
	}

	public function stream_tell(): int|false
	{
		if ($this->fp === null) {
			return false;
		}

		return ftell($this->fp);
	}

	/**
	 * @return array<int|string, int|false>|false
	 */
	public function stream_stat(): array|false
	{
		if ($this->fp === null) {
			return false;
		}

		return fstat($this->fp);
	}

	public function stream_flush(): bool
	{
		return true;
	}

	public function stream_set_option(int $option, int $arg1, int $arg2): bool
	{
		return false;
	}

	/**
	 * @return array<int|string, int|false>|false
	 */
	public function url_stat(string $path, int $flags): array|false
	{
		$real = $this->translate($path);
		if ($real === null) {
			return false;
		}
		$quiet = ($flags & STREAM_URL_STAT_QUIET) !== 0;

		return $quiet ? @stat($real) : stat($real);
	}

	public function dir_opendir(string $path, int $options): bool
	{
		$real = $this->translate($path);
		if ($real === null || !is_dir($real)) {
			return false;
		}
		try {
			$this->dirIterator = new FilesystemIterator(
				$real,
				FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_PATHNAME,
			);
		} catch (Throwable) {
			return false;
		}

		return true;
	}

	public function dir_readdir(): string|false
	{
		if ($this->dirIterator === null || !$this->dirIterator->valid()) {
			return false;
		}
		$name = $this->dirIterator->key();
		$this->dirIterator->next();

		return $name;
	}

	public function dir_rewinddir(): bool
	{
		if ($this->dirIterator === null) {
			return false;
		}
		$this->dirIterator->rewind();

		return true;
	}

	public function dir_closedir(): bool
	{
		$this->dirIterator = null;

		return true;
	}

}
