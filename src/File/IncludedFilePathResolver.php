<?php declare(strict_types = 1);

namespace PHPStan\File;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Include_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\IncludePathChangingCallsVisitor;
use function array_values;
use function count;
use function dirname;
use function explode;
use function get_include_path;
use function in_array;
use function preg_match;
use function stream_get_wrappers;
use function strtolower;
use const PATH_SEPARATOR;

/**
 * The absolute paths an include/require of a given path could resolve to, in the order PHP would try
 * them. `stream_resolve_include_path()` cannot be used: it resolves against the running script, and
 * what matters is the analysed file - which can itself move the working directory, extend the
 * include path or register a stream wrapper before the include, see
 * IncludePathChangingCallsVisitor.
 */
#[AutowiredService]
final class IncludedFilePathResolver
{

	/**
	 * How many working directories a relative path may be resolved against. Every `chdir()` that
	 * could have run before the include grows the set - the call may be conditional, so the previous
	 * working directory stays possible too - and past this point the answer is not worth computing.
	 */
	private const WORKING_DIRECTORIES_LIMIT = 8;

	/** How many `include_path` entries are followed before the include path is given up on. */
	private const INCLUDE_PATH_ENTRIES_LIMIT = 16;

	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		private FileHelper $fileHelper,
	)
	{
	}

	/**
	 * Returns null when the analysed file moves the working directory or the include path somewhere
	 * PHPStan cannot follow: a relative path then resolves to a place that is not knowable, while an
	 * absolute one still resolves to exactly itself.
	 *
	 * @return list<string>|null
	 */
	public function resolve(string $path, Scope $scope, ?Include_ $node = null): ?array
	{
		$unavailableScheme = $this->getUnavailableStreamWrapperScheme($path);
		if ($unavailableScheme !== null) {
			// The file registering the wrapper itself is the one case where the path does come to
			// mean something at runtime, so the include must not be reported.
			return $this->registersStreamWrapper($unavailableScheme, $scope, $node) ? null : [];
		}

		if ($this->fileHelper->isAbsolutePath($path)) {
			return [$path];
		}

		$directories = $this->resolveDirectories($scope, $node);
		if ($directories === null) {
			return null;
		}

		$candidatePaths = [];
		foreach ($directories as $directory) {
			$candidatePath = (new FileHelper($directory))->absolutizePath($path);
			$candidatePaths[$candidatePath] = $candidatePath;
		}

		return array_values($candidatePaths);
	}

	/**
	 * The directories a relative include is tried against, in the order PHP would try them:
	 * 	1. The current working directory.
	 * 	2. The include path.
	 * 	3. The directory of the script that is being executed.
	 *
	 * @return list<string>|null
	 */
	private function resolveDirectories(Scope $scope, ?Include_ $node): ?array
	{
		$workingDirectories = $this->resolveWorkingDirectories($scope, $node);
		if ($workingDirectories === null) {
			return null;
		}

		$includePath = $this->resolveIncludePath($scope, $node);
		if ($includePath === null) {
			return null;
		}

		$directories = $workingDirectories;
		foreach ($includePath as $entry) {
			if ($entry === '') {
				continue;
			}

			// A relative include_path entry - `.` is there by default - is itself relative to the
			// working directory.
			if ($this->fileHelper->isAbsolutePath($entry)) {
				$directories[] = $entry;
				continue;
			}

			foreach ($workingDirectories as $workingDirectory) {
				$directories[] = (new FileHelper($workingDirectory))->absolutizePath($entry);
			}
		}

		$directories[] = dirname($this->getScopeFile($scope));

		return $directories;
	}

	/**
	 * The working directory PHPStan runs in, plus every directory a `chdir()` earlier in the file
	 * could have moved it to. The earlier directories stay in the set because such a call may be
	 * conditional, or may sit in a function that is never reached.
	 *
	 * @return list<string>|null
	 */
	private function resolveWorkingDirectories(Scope $scope, ?Include_ $node): ?array
	{
		$directories = [$this->currentWorkingDirectory];

		foreach ($this->getPathChangingCalls($node) as [$functionName, $call]) {
			if ($functionName !== IncludePathChangingCallsVisitor::CHDIR) {
				continue;
			}

			$args = $call->getArgs();
			if (count($args) < 1) {
				continue;
			}

			$constantStrings = $scope->getType($args[0]->value)->getConstantStrings();
			if (count($constantStrings) === 0) {
				return null;
			}

			$newDirectories = $directories;
			foreach ($constantStrings as $constantString) {
				foreach ($directories as $directory) {
					$newDirectory = (new FileHelper($directory))->absolutizePath($constantString->getValue());
					if (in_array($newDirectory, $newDirectories, true)) {
						continue;
					}

					$newDirectories[] = $newDirectory;
					if (count($newDirectories) > self::WORKING_DIRECTORIES_LIMIT) {
						return null;
					}
				}
			}

			$directories = $newDirectories;
		}

		return $directories;
	}

	/**
	 * PHPStan's own include path, plus every entry a `set_include_path()` or an
	 * `ini_set('include_path', ...)` earlier in the file could have added to it. Entries are only
	 * ever added: the call may be conditional, so the previous include path stays possible too.
	 *
	 * @return list<string>|null
	 */
	private function resolveIncludePath(Scope $scope, ?Include_ $node): ?array
	{
		$includePath = explode(PATH_SEPARATOR, get_include_path());

		foreach ($this->getPathChangingCalls($node) as [$functionName, $call]) {
			$setsUnknownOption = false;
			$valueExpr = $this->getIncludePathValueExpr($functionName, $call, $scope, $setsUnknownOption);
			if ($setsUnknownOption) {
				return null;
			}
			if ($valueExpr === null) {
				continue;
			}

			$constantStrings = $scope->getType($valueExpr)->getConstantStrings();
			if (count($constantStrings) === 0) {
				return null;
			}

			foreach ($constantStrings as $constantString) {
				foreach (explode(PATH_SEPARATOR, $constantString->getValue()) as $entry) {
					if (in_array($entry, $includePath, true)) {
						continue;
					}

					$includePath[] = $entry;
					if (count($includePath) > self::INCLUDE_PATH_ENTRIES_LIMIT) {
						return null;
					}
				}
			}
		}

		return $includePath;
	}

	/**
	 * The expression the call assigns to `include_path`, or null when the call leaves it alone.
	 * `$setsUnknownOption` is set when the call sets an ini option PHPStan cannot read the name of,
	 * which could be `include_path` just as well as anything else.
	 */
	private function getIncludePathValueExpr(string $functionName, Expr\FuncCall $call, Scope $scope, bool &$setsUnknownOption): ?Expr
	{
		$setsUnknownOption = false;
		$args = $call->getArgs();

		if ($functionName === IncludePathChangingCallsVisitor::SET_INCLUDE_PATH) {
			return $args[0]->value ?? null;
		}

		if (
			$functionName !== IncludePathChangingCallsVisitor::INI_SET
			&& $functionName !== IncludePathChangingCallsVisitor::INI_ALTER
		) {
			return null;
		}

		if (count($args) < 2) {
			return null;
		}

		$optionNames = $scope->getType($args[0]->value)->getConstantStrings();
		if (count($optionNames) === 0) {
			$setsUnknownOption = true;

			return null;
		}

		foreach ($optionNames as $optionName) {
			if (strtolower($optionName->getValue()) !== 'include_path') {
				continue;
			}

			return $args[1]->value;
		}

		return null;
	}

	/**
	 * @return list<array{string, Expr\FuncCall}>
	 */
	private function getPathChangingCalls(?Include_ $node): array
	{
		if ($node === null) {
			return [];
		}

		return $node->getAttribute(IncludePathChangingCallsVisitor::ATTRIBUTE_NAME, []);
	}

	/**
	 * A path like `vfs://sites/default/x.php` names a stream wrapper rather than a place on the
	 * filesystem. When that wrapper is not registered in the PHPStan process - vfsStream registers
	 * its own from a test's setUp(), which never runs here - PHP cannot stat the path at all: every
	 * is_file() on it raises "Unable to find the wrapper". Such a path has no candidates, and it
	 * cannot come to have any, so nothing downstream should keep stat'ing it - the result cache
	 * would otherwise record it as a missing file dependency and warn on every run.
	 */
	private function getUnavailableStreamWrapperScheme(string $path): ?string
	{
		if (preg_match('~^([a-z0-9+\-.]+)://~i', $path, $matches) !== 1) {
			return null;
		}

		$scheme = strtolower($matches[1]);
		if (in_array($scheme, stream_get_wrappers(), true)) {
			return null;
		}

		return $scheme;
	}

	/**
	 * Whether a `stream_wrapper_register()` earlier in the file could have registered the scheme.
	 */
	private function registersStreamWrapper(string $scheme, Scope $scope, ?Include_ $node): bool
	{
		foreach ($this->getPathChangingCalls($node) as [$functionName, $call]) {
			if ($functionName !== IncludePathChangingCallsVisitor::STREAM_WRAPPER_REGISTER) {
				continue;
			}

			$args = $call->getArgs();
			if (count($args) < 1) {
				continue;
			}

			$protocols = $scope->getType($args[0]->value)->getConstantStrings();
			if (count($protocols) === 0) {
				return true;
			}

			foreach ($protocols as $protocol) {
				if (strtolower($protocol->getValue()) === $scheme) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Both `__DIR__` and the "calling script's own directory" fallback of a relative include are
	 * resolved at compile time, so inside a trait they point at the file the trait is declared in - not
	 * at the file of the class that uses it, which is what Scope::getFile() returns in a trait context.
	 */
	private function getScopeFile(Scope $scope): string
	{
		if ($scope->isInTrait()) {
			$traitFileName = $scope->getTraitReflection()->getFileName();
			if ($traitFileName !== null) {
				return $this->fileHelper->normalizePath($traitFileName);
			}
		}

		return $scope->getFile();
	}

}
