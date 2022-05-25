<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\ShouldNotHappenException;
use function count;
use function fclose;
use function feof;
use function fflush;
use function fopen;
use function fread;
use function fseek;
use function ftell;
use function is_resource;
use function stat;
use function stream_set_blocking;
use function stream_set_read_buffer;
use function stream_set_timeout;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use const STREAM_BUFFER_NONE;
use const STREAM_OPTION_BLOCKING;
use const STREAM_OPTION_READ_BUFFER;
use const STREAM_OPTION_READ_TIMEOUT;
use const STREAM_OPTION_WRITE_BUFFER;
use const STREAM_URL_STAT_QUIET;
use const STREAM_USE_PATH;

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

	/** @var resource|null */
	private $handle = null;

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
		self::$autoloadLocatedFiles[] = $path;
		return $this->invokeWithRealFileStreamWrapper(function ($path, $mode, $options) use (&$openedPath) {
			$this->handle = fopen($path, $mode);
			$isResource = is_resource($this->handle);
			if ($isResource && $options & STREAM_USE_PATH) {
				$openedPath = $path;
			}

			return $isResource;
		}, [$path, $mode, $options]);
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
		return $this->invokeWithRealFileStreamWrapper(fn ($count) => fread($this->handle, $count), [$count]);
	}

	/**
	 * Since we allowed the open to succeed, we should allow the close to occur
	 * as well.
	 *
	 */
	public function stream_close(): void
	{
		$this->invokeWithRealFileStreamWrapper(function (): void {
			$this->stream_flush();

			if (is_resource($this->handle)) {
				fclose($this->handle);
			}

			$this->handle = null;
		}, []);
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

		return $this->url_stat(self::$autoloadLocatedFiles[count(self::$autoloadLocatedFiles) - 1], STREAM_URL_STAT_QUIET);
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
		return $this->invokeWithRealFileStreamWrapper(fn () => feof($this->handle), []);
	}

	public function stream_flush(): bool
	{
		return $this->invokeWithRealFileStreamWrapper(fn () => fflush($this->handle), []);
	}

	public function stream_tell(): int
	{
		return $this->invokeWithRealFileStreamWrapper(fn () => ftell($this->handle), []);
	}

	/**
	 * @param   int  $offset
	 * @param   int  $whence
	 */
	public function stream_seek($offset, $whence): bool
	{
		return $this->invokeWithRealFileStreamWrapper(fn ($offset, $whence) => fseek($this->handle, $offset, $whence) === 0, [$offset, $whence]);
	}

	/**
	 * @param int  $option
	 * @param int  $arg1
	 * @param int  $arg2
	 */
	public function stream_set_option($option, $arg1, $arg2): bool
	{
		return $this->invokeWithRealFileStreamWrapper(function ($option, $arg1, $arg2) {
			switch ($option) {
				case STREAM_OPTION_BLOCKING:
					// This works for the local adapter. It doesn't do anything for
					// memory streams.
					return stream_set_blocking($this->handle, $arg1);

				case STREAM_OPTION_READ_TIMEOUT:
					return stream_set_timeout($this->handle, $arg1, $arg2);

				case STREAM_OPTION_READ_BUFFER:
					if ($arg1 === STREAM_BUFFER_NONE) {
						return stream_set_read_buffer($this->handle, 0) === 0;
					}

					return stream_set_read_buffer($this->handle, $arg2) === 0;

				case STREAM_OPTION_WRITE_BUFFER:
					// todo
					// $this->streamWriteBuffer = $arg1 === STREAM_BUFFER_NONE ? 0 : $arg2;

					return true;
			}

			return false;
		}, [$option, $arg1, $arg2]);
	}

}
