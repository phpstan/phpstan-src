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

	/** @var resource|null */
	private $file = null;

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
		if (self::$autoloadLocatedFile === null) {
			//We want to capture the first file only. Since we allow the autoloading to continue, this will be called
			//multiple times if loading the class caused other files to be loaded too.
			self::$autoloadLocatedFile = $path;
		}
		return $this->runUnwrapped(function () use ($path, $mode, $options) {
			if (($options & STREAM_REPORT_ERRORS) !== 0) {
				$file = fopen($path, $mode, ($options & STREAM_USE_PATH) !== 0);
			} else {
				$file = @fopen($path, $mode, ($options & STREAM_USE_PATH) !== 0);
			}
			if ($file !== false) {
				$this->file = $file;
				return true;
			}

			return false;
		});
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
		return $this->runUnwrapped(function () use ($count) {
			return fread($this->file, $count);
		});
	}

	/**
	 * Since we allowed the open to succeed, we should allow the close to occur
	 * as well.
	 *
	 * @return void
	 */
	public function stream_close(): void
	{
		fclose($this->file);
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
		return $this->runUnwrapped(function () {
			return fstat($this->file);
		});
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
		return $this->runUnwrapped(static function () use ($path, $flags) {
			if (($flags & STREAM_URL_STAT_QUIET) !== 0) {
				return @stat($path);
			}

			return stat($path);
		});
	}

	/**
	 * Simulates behavior of reading from an empty file.
	 *
	 * @return bool
	 */
	public function stream_eof(): bool
	{
		return feof($this->file);
	}

	public function stream_flush(): bool
	{
		return fflush($this->file);
	}

	public function stream_tell(): int
	{
		return ftell($this->file);
	}

	/**
	 * @param   int  $offset
	 * @param   int  $whence
	 * @return  bool
	 */
	public function stream_seek($offset, $whence): bool
	{
		return $this->runUnwrapped(function () use ($offset, $whence): bool {
			return fseek($this->file, $offset, $whence) === 0;
		});
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

	/**
	 * @phpstan-template TReturn
	 * @phpstan-param callable() : TReturn $c
	 * @phpstan-return TReturn
	 */
	private function runUnwrapped(callable $c)
	{
		if (self::$registeredStreamWrapperProtocols === null) {
			throw new \PHPStan\ShouldNotHappenException(self::class . ' not registered: cannot operate. Do not call this method directly.');
		}

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_restore($protocol);
		}

		$result = $c();

		foreach (self::$registeredStreamWrapperProtocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, self::class);
		}

		return $result;
	}

}
