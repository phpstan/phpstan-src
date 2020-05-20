<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

require_once __DIR__ . '/AutoloadSourceLocatorException.php'; // phpcs:disable

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\File\FileReader;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function file_exists;
use function restore_error_handler;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *
 * Modified code from Roave/BetterReflection, Copyright (c) 2017 Roave, LLC.
 */
class AutoloadSourceLocator implements SourceLocator
{

	private AstLocator $astLocator;

	private static ?string $autoloadLocatedFile = null;

	private static ?AstLocator $currentAstLocator = null;

	/**
	 * Note: the constructor has been made a 0-argument constructor because `\stream_wrapper_register`
	 *       is a piece of trash, and doesn't accept instances, just class names.
	 */
	public function __construct(?AstLocator $astLocator = null)
	{
		$validLocator = $astLocator ?? self::$currentAstLocator;
		if ($validLocator === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$this->astLocator = $validLocator;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws ParseToAstFailure
	 */
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isFunction()) {
			$functionName = $identifier->getName();
			if (!function_exists($functionName)) {
				return null;
			}

			$reflection = new ReflectionFunction($functionName);
			$reflectionFileName = $reflection->getFileName();

			if (!is_string($reflectionFileName)) {
				return null;
			}

			return $this->findReflection($reflector, $reflectionFileName, $identifier);
		}

		if ($identifier->isConstant()) {
			$constantName = $identifier->getName();
			if (!defined($constantName)) {
				return null;
			}

			$reflection = ReflectionConstant::createFromNode(
				$reflector,
				new FuncCall(new Name('define'), [
					new Arg(new String_($constantName)),
					new Arg(new String_('')), // not actually used
				]),
				new LocatedSource('', null),
				null,
				null
			);
			$reflection->populateValue(constant($constantName));

			return $reflection;
		}

		if (!$identifier->isClass()) {
			return null;
		}

		$locateResult = $this->locateClassByName($identifier->getName());
		if ($locateResult === null) {
			return null;
		}
		[$potentiallyLocatedFile, $className] = $locateResult;

		return $this->findReflection($reflector, $potentiallyLocatedFile, new Identifier($className, $identifier->getType()));
	}

	private function findReflection(Reflector $reflector, string $file, Identifier $identifier): ?Reflection
	{
		try {
			$fileContents = FileReader::read($file);
		} catch (\PHPStan\File\CouldNotReadFileException $e) {
			return null;
		}

		$locatedSource = new LocatedSource(
			$fileContents,
			$file
		);

		try {
			return $this->astLocator->findReflection($reflector, $locatedSource, $identifier);
		} catch (IdentifierNotFound $exception) {
			return null;
		}
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return []; // todo
	}

	/**
	 * Attempt to locate a class by name.
	 *
	 * If class already exists, simply use internal reflection API to get the
	 * filename and store it.
	 *
	 * If class does not exist, we make an assumption that whatever autoloaders
	 * that are registered will be loading a file. We then override the file://
	 * protocol stream wrapper to "capture" the filename we expect the class to
	 * be in, and then restore it. Note that class_exists will cause an error
	 * that it cannot find the file, so we squelch the errors by overriding the
	 * error handler temporarily.
	 *
	 * @throws ReflectionException
	 * @return array{string, string}|null
	 */
	private function locateClassByName(string $className): ?array
	{
		if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
			$reflection = new ReflectionClass($className);
			$filename = $reflection->getFileName();

			if (!is_string($filename)) {
				return null;
			}

			if (!file_exists($filename)) {
				return null;
			}

			return [$filename, $reflection->getName()];
		}

		self::$autoloadLocatedFile = null;
		self::$currentAstLocator = $this->astLocator; // passing the locator on to the implicitly instantiated `self`
		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new \PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocatorException();
		});
		stream_wrapper_unregister('file');
		stream_wrapper_unregister('phar');
		stream_wrapper_register('file', self::class);
		stream_wrapper_register('phar', self::class);

		try {
			class_exists($className);
		} catch (\PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocatorException $e) {
			// $autoloadLocatedFile should be known at this point
		} finally {
			stream_wrapper_restore('file');
			stream_wrapper_restore('phar');
			restore_error_handler();
		}

		/** @var string|null $autoloadLocatedFile */
		$autoloadLocatedFile = self::$autoloadLocatedFile;
		if ($autoloadLocatedFile === null) {
			return null;
		}

		return [$autoloadLocatedFile, $className];
	}

	/**
	 * Our wrapper simply records which file we tried to load and returns
	 * boolean false indicating failure.
	 *
	 * @see https://php.net/manual/en/class.streamwrapper.php
	 * @see https://php.net/manual/en/streamwrapper.stream-open.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @param int    $options
	 * @param string $opened_path
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function stream_open($path, $mode, $options, &$opened_path): bool
	{
		self::$autoloadLocatedFile = $path;

		return false;
	}

	/**
	 * url_stat is triggered by calls like "file_exists". The call to "file_exists" must not be overloaded.
	 * This function restores the original "file" stream, issues a call to "stat" to get the real results,
	 * and then re-registers the AutoloadSourceLocator stream wrapper.
	 *
	 * @see https://php.net/manual/en/class.streamwrapper.php
	 * @see https://php.net/manual/en/streamwrapper.url-stat.php
	 *
	 * @param string $path
	 * @param int $flags
	 *
	 * @return mixed[]|bool
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function url_stat($path, $flags)
	{
		stream_wrapper_restore('file');
		stream_wrapper_restore('phar');

		if (($flags & STREAM_URL_STAT_QUIET) !== 0) {
			set_error_handler(static function (): bool {
				// Use native error handler
				return false;
			});
			$result = @stat($path);
			restore_error_handler();
		} else {
			$result = stat($path);
		}

		stream_wrapper_unregister('file');
		stream_wrapper_unregister('phar');
		stream_wrapper_register('file', self::class);
		stream_wrapper_register('phar', self::class);

		return $result;
	}

}
