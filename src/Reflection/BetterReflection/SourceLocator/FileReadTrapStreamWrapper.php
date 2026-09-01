<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\ShouldNotHappenException;
use function function_exists;
use function is_dir;
use function is_file;
use function opcache_get_status;
use function stat;
use function stream_resolve_include_path;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function strpos;
use const PHP_VERSION_ID;
use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;
use const STREAM_URL_STAT_QUIET;

/**
 * This class will operate as a stream wrapper, intercepting any access to a file while
 * in operation.
 *
 * @internal DO NOT USE: this is an implementation detail of
 *           the {@see \PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator}
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
 */
final class FileReadTrapStreamWrapper
{

	private const DEFAULT_STREAM_WRAPPER_PROTOCOLS = [
		'file',
		'phar',
	];

	/** @var string[]|null */
	private static ?array $registeredStreamWrapperProtocols;

	/** @var string[] */
	public static array $autoloadLocatedFiles = [];

	/**
	 * Served instead of the empty script where OPcache would keep that empty
	 * script, see servesParseError(). A parse error compiles to nothing, so
	 * OPcache stores nothing, and it reaches AutoloadSourceLocator as the
	 * ParseError it catches.
	 */
	private const PARSE_ERROR_SCRIPT = "<?php\n// PHPStan's autoload trap: deliberately not valid PHP, see FileReadTrapStreamWrapper\n}\n";

	private static ?bool $opcacheEnabled = null;

	private bool $readFromFile = false;

	private int $seekPosition = 0;

	private string $path = '';

	/**
	 * @param string[] $streamWrapperProtocols
	 *
	 * @return mixed
	 *
	 * @psalm-template ExecutedMethodReturnType of mixed
	 * @psalm-param callable() : ExecutedMethodReturnType $executeMeWithinStreamWrapperOverride
	 * @psalm-return ExecutedMethodReturnType
	 */
	public static function withStreamWrapperOverride(
		callable $executeMeWithinStreamWrapperOverride,
		array $streamWrapperProtocols = self::DEFAULT_STREAM_WRAPPER_PROTOCOLS,
	)
	{
		self::$registeredStreamWrapperProtocols = $streamWrapperProtocols;
		self::$autoloadLocatedFiles = [];

		try {
			foreach ($streamWrapperProtocols as $protocol) {
				stream_wrapper_unregister($protocol);
				stream_wrapper_register($protocol, self::class);
			}

			$result = $executeMeWithinStreamWrapperOverride();
		} finally {
			foreach ($streamWrapperProtocols as $protocol) {
				stream_wrapper_restore($protocol);
			}
		}

		self::$registeredStreamWrapperProtocols = null;
		self::$autoloadLocatedFiles = [];

		return $result;
	}

	/**
	 * Our wrapper simply records which file we tried to load and returns
	 * boolean false indicating failure.
	 *
	 * @internal do not call this method directly! This is stream wrapper
	 *           voodoo logic that you **DO NOT** want to touch!
	 *
	 * @see https://php.net/manual/en/class.streamwrapper.php
	 * @see https://php.net/manual/en/streamwrapper.stream-open.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @param int    $options
	 * @param string $openedPath
	 */
	public function stream_open($path, $mode, $options, &$openedPath): bool
	{
		$exists = is_file($path) || (stream_resolve_include_path($path) !== false);

		if ($exists) {
			self::$autoloadLocatedFiles[] = $path;
		}
		$this->path = $path;
		$this->readFromFile = false;
		$this->seekPosition = 0;

		return $exists;
	}

	/**
	 * Since we allow our wrapper's stream_open() to succeed, we need to
	 * simulate a successful read so autoloaders with require() don't explode.
	 *
	 * @param int $count
	 *
	 */
	public function stream_read($count): string
	{
		$this->readFromFile = true;

		if (self::servesParseError($this->path)) {
			return self::PARSE_ERROR_SCRIPT;
		}

		// Dummy return value that is also valid PHP for require(). We'll read
		// and process the file elsewhere, so it's OK to provide dummy data for
		// this read.
		return '';
	}

	/**
	 * Whether the empty script served for this path would stay in OPcache and
	 * shadow the real file for the rest of the process.
	 *
	 * OPcache caches what an include compiles under the path the include was
	 * given - the empty script this wrapper serves too. AutoloadSourceLocator
	 * undoes that with opcache_invalidate() on the trapped files, except that
	 * on PHP < 8.1 the call fails for the path of any stream wrapper other
	 * than file://: zend_accel_invalidate() resolves the path first, and
	 * php_resolve_path() refuses such URLs (PHP >= 8.1 falls back to the name
	 * as given). The poisoned entry then serves the empty script to every
	 * later include of the same path, so a class in that file is never
	 * declared - "Class not found" on its first cold use. PHPStan's own
	 * classes that preload.php leaves to the autoloader are exactly such
	 * files when running from the phar, autoloaded as
	 * phar://.../vendor/composer/../../src/....
	 */
	private static function servesParseError(string $path): bool
	{
		if (self::$opcacheEnabled === null) {
			self::$opcacheEnabled = false;
			if (function_exists('opcache_get_status')) {
				$status = opcache_get_status(false);
				self::$opcacheEnabled = $status !== false && ($status['opcache_enabled'] ?? false) === true;
			}
		}

		return self::resolveServesParseError(PHP_VERSION_ID, self::$opcacheEnabled, $path);
	}

	public static function resolveServesParseError(int $phpVersionId, bool $opcacheEnabled, string $path): bool
	{
		if (!$opcacheEnabled || $phpVersionId >= 80100) {
			return false;
		}

		return strpos($path, '://') !== false && strpos($path, 'file://') !== 0;
	}

	/**
	 * Since we allowed the open to succeed, we should allow the close to occur
	 * as well.
	 *
	 */
	public function stream_close(): void
	{
		// no op
	}

	/**
	 * Required for `require_once` and `include_once` to work per PHP.net
	 * comment referenced below. We delegate to url_stat().
	 *
	 * @see https://www.php.net/manual/en/function.stream-wrapper-register.php#51855
	 *
	 * @return mixed[]|bool
	 */
	public function stream_stat()
	{
		if (self::$autoloadLocatedFiles === []) {
			return false;
		}

		return $this->url_stat(self::$autoloadLocatedFiles[0], STREAM_URL_STAT_QUIET);
	}

	/**
	 * url_stat is triggered by calls like "file_exists". The call to "file_exists" must not be overloaded.
	 * This function restores the original "file" stream, issues a call to "stat" to get the real results,
	 * and then re-registers the AutoloadSourceLocator stream wrapper.
	 *
	 * @internal do not call this method directly! This is stream wrapper
	 *           voodoo logic that you **DO NOT** want to touch!
	 *
	 * @see https://php.net/manual/en/class.streamwrapper.php
	 * @see https://php.net/manual/en/streamwrapper.url-stat.php
	 *
	 * @param string $path
	 * @param int    $flags
	 *
	 * @return mixed[]|bool
	 */
	public function url_stat($path, $flags)
	{
		return $this->invokeWithRealFileStreamWrapper(static function ($path, $flags) {
			if (($flags & STREAM_URL_STAT_QUIET) !== 0) {
				return @stat($path);
			}

			return stat($path);
		}, [$path, $flags]);
	}

	/**
	 * @param mixed[] $args
	 * @return mixed
	 */
	private function invokeWithRealFileStreamWrapper(callable $cb, array $args)
	{
		if (self::$registeredStreamWrapperProtocols === null) {
			throw new ShouldNotHappenException(self::class . ' not registered: cannot operate. Do not call this method directly.');
		}

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_restore($protocol);
		}

		$result = $cb(...$args);

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, self::class);
		}

		return $result;
	}

	/**
	 * Simulates behavior of reading from an empty file.
	 *
	 */
	public function stream_eof(): bool
	{
		return $this->readFromFile;
	}

	/**
	 * @return true
	 */
	public function stream_flush(): bool
	{
		return true;
	}

	public function stream_tell(): int
	{
		return $this->seekPosition;
	}

	/**
	 * @param   int  $offset
	 * @param   int  $whence
	 */
	public function stream_seek($offset, $whence): bool
	{
		switch ($whence) {
			// Behavior is the same for a zero-length file
			case SEEK_SET:
			case SEEK_END:
				if ($offset < 0) {
					return false;
				}
				$this->seekPosition = $offset;
				return true;

			case SEEK_CUR:
				if ($offset < 0) {
					return false;
				}
				$this->seekPosition += $offset;
				return true;

			default:
				return false;
		}
	}

	/**
	 * @param int  $option
	 * @param int  $arg1
	 * @param int  $arg2
	 *
	 * @return false
	 */
	public function stream_set_option($option, $arg1, $arg2): bool
	{
		return false;
	}

	public function dir_opendir(string $path, int $options): bool
	{
		return is_dir($path);
	}

	public function dir_readdir(): string
	{
		return '';
	}

}
