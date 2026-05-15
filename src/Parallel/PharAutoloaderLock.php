<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Phar;
use PHPStan\DependencyInjection\AutowiredService;
use function fclose;
use function flock;
use function fopen;
use function getmypid;
use function register_shutdown_function;
use function spl_autoload_functions;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;
use const LOCK_EX;
use const LOCK_UN;

/**
 * Serialises Composer autoload reads from a shared phar fd so concurrent
 * forked workers can't race on its seek cursor.
 *
 * When PHPStan is run from a .phar (composer-distributed install), PHP's
 * built-in phar:// stream wrapper caches a single fd internally; after
 * pcntl_fork() that fd's open file description (and its seek cursor) is
 * shared between parent and every forked child, so concurrent lazy class
 * loads across workers can interleave and read garbage offsets — surfacing
 * as spurious parse errors against phar-internal files.
 *
 * The minimal-surgery fix here: wrap every registered Composer ClassLoader
 * so that loadClass() acquires an exclusive flock on a tmp file before its
 * `include`, and releases it after. Two workers can never be inside
 * `include 'phar://…'` at the same time, so the cursor can't be moved out
 * from under either of them.
 *
 * Cost model: per-class load takes one flock pair. A worker contends with
 * siblings only during its initial "loading wave" — the few hundred classes
 * it touches once. After that its symbol table is populated and the lock
 * is never taken again, so workers run fully parallel for the rest of the
 * analysis.
 *
 * Covers only autoload. Non-class phar reads — file_get_contents('phar://…')
 * etc. — still go through the unlocked built-in wrapper; in practice those
 * happen during boot, which is pre-fork, so they don't race. If a lazy
 * non-class phar read does fire post-fork, the alternative extract-and-
 * reroute approach (see #5669) is the comprehensive fix.
 *
 * No-op when not running inside a phar; called by ParallelAnalyser and
 * FixerApplication right before they fork. Idempotent.
 */
#[AutowiredService]
final class PharAutoloaderLock
{

	private bool $installed = false;

	/**
	 * Re-entrancy depth for the lock-protected autoload section, per process.
	 *
	 * PHP class declarations can trigger nested autoloads (declaring `class
	 * Foo extends Bar` autoloads Bar mid-declaration), so the wrapper can be
	 * called recursively in a single process. `flock` is per-OFD on BSD/macOS,
	 * so two `fopen` + `LOCK_EX` calls from the same process on the same file
	 * self-deadlock. The race we actually care about is cross-process — only
	 * the outermost call needs to take the lock; nested ones are already
	 * inside the critical section.
	 */
	private static int $depth = 0;

	public function install(): void
	{
		if ($this->installed) {
			return;
		}
		$this->installed = true;

		if (Phar::running(false) === '') {
			return;
		}

		$lockPath = sys_get_temp_dir() . '/phpstan-fork-phar-lock-' . getmypid() . '-' . uniqid();
		touch($lockPath);

		// Wrap every registered autoloader callable. Using spl_autoload_*
		// rather than Composer\Autoload\ClassLoader::getRegisteredLoaders()
		// keeps this file free of any reference to Composer's namespace —
		// php-scoper would otherwise rewrite that reference to the prefixed
		// form, which does not exist at runtime inside the built phar.
		foreach (spl_autoload_functions() as $callback) {
			spl_autoload_unregister($callback);
			spl_autoload_register(static function (string $class) use ($callback, $lockPath): void {
				if (self::$depth > 0) {
					// Already inside this process's locked autoload — nested
					// reads from libphar are sequential within a single
					// process, so they don't need (and would deadlock on) a
					// second flock.
					$callback($class);
					return;
				}
				$fh = fopen($lockPath, 'r');
				if ($fh === false) {
					$callback($class);
					return;
				}
				flock($fh, LOCK_EX);
				self::$depth++;
				try {
					$callback($class);
				} finally {
					self::$depth--;
					flock($fh, LOCK_UN);
					fclose($fh);
				}
			});
		}

		$parentPid = getmypid();
		register_shutdown_function(static function () use ($parentPid, $lockPath): void {
			if (getmypid() !== $parentPid) {
				return;
			}
			@unlink($lockPath);
		});
	}

}
