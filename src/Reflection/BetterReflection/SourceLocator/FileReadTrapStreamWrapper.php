<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use function stat;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;

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

	public static ?string $autoloadLocatedFile = null;

	private bool $readFromFile = false;

	private int $seekPosition = 0;

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
		array $streamWrapperProtocols = self::DEFAULT_STREAM_WRAPPER_PROTOCOLS
	)
	{
		self::$registeredStreamWrapperProtocols = $streamWrapperProtocols;
		self::$autoloadLocatedFile = null;

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
		self::$autoloadLocatedFile = null;

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
		self::$autoloadLocatedFile = $openedPath;
		$this->readFromFile = false;
		$this->seekPosition = 0;

		return true;
	}

	/**
	 * Since we allow our wrapper's stream_open() to succeed, we need to
	 * simulate a successful read so autoloaders with require() don't explode.
	 *
	 * @param int $count
	 *
	 * @return string
	 */
	public function stream_read($count): string
	{
		$this->readFromFile = true;

		// Dummy return value that is also valid PHP for require(). We'll read
		// and process the file elsewhere, so it's OK to provide dummy data for
		// this read.
		return '';
	}

	/**
	 * Since we allowed the open to succeed, we should allow the close to occur
	 * as well.
	 *
	 * @return void
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
		if (self::$autoloadLocatedFile === null) {
			return false;
		}

		return $this->url_stat(self::$autoloadLocatedFile, STREAM_URL_STAT_QUIET);
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
		if (self::$registeredStreamWrapperProtocols === null) {
			throw new \PHPStan\ShouldNotHappenException(self::class . ' not registered: cannot operate. Do not call this method directly.');
		}

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_restore($protocol);
		}

		if (($flags & STREAM_URL_STAT_QUIET) !== 0) {
			$result = @stat($path);
		} else {
			$result = stat($path);
		}

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, self::class);
		}

		return $result;
	}

	/**
	 * Simulates behavior of reading from an empty file.
	 *
	 * @return bool
	 */
	public function stream_eof(): bool
	{
		return $this->readFromFile;
	}

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
	 * @return  bool
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
	 * @return bool
	 */
	public function stream_set_option($option, $arg1, $arg2): bool
	{
		return false;
	}

}
