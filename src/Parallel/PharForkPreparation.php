<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use FilesystemIterator;
use Phar;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function getmypid;
use function is_dir;
use function mkdir;
use function register_shutdown_function;
use function rmdir;
use function sprintf;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Prepares a forked-worker run so that lazy phar:// reads in the children
 * don't race on the shared phar file descriptor.
 *
 * When PHPStan runs from a .phar (the composer-distributed case), every
 * phar://… access goes through libphar's internally-cached fd. After
 * pcntl_fork() that fd is shared between parent and all forked children — the
 * shared file-offset cursor means concurrent lazy class loads in different
 * workers read garbage offsets and trigger spurious parse errors.
 *
 * The fix here: extract the phar to a fresh tmp directory in the parent
 * **before** any forking, and swap PHP's built-in phar:// wrapper for
 * {@see PharRedirectStreamWrapper}, which serves every subsequent phar://
 * request from that on-disk extraction. Children then open ordinary files
 * with their own fds — no shared OFD, no race — while autoload (and any
 * stat/file_get_contents that uses phar://) keeps working transparently.
 *
 * No-op when not running inside a phar; called by ParallelAnalyser /
 * FixerApplication right before they fork their workers. Idempotent — only
 * the first call actually extracts.
 */
#[AutowiredService]
final class PharForkPreparation
{

	private bool $prepared = false;

	public function prepare(): void
	{
		if ($this->prepared) {
			return;
		}
		$this->prepared = true;

		$pharPath = Phar::running(false);
		if ($pharPath === '') {
			return;
		}

		$extractDir = sys_get_temp_dir() . '/phpstan-fork-phar-' . getmypid() . '-' . uniqid();
		if (!mkdir($extractDir, 0700, true) && !is_dir($extractDir)) {
			throw new ShouldNotHappenException(sprintf('Failed creating phar-extract directory %s.', $extractDir));
		}

		$phar = new Phar($pharPath);
		$alias = $phar->getAlias();
		$phar->extractTo($extractDir, null, true);

		PharRedirectStreamWrapper::configure($pharPath, $alias, $extractDir);
		stream_wrapper_unregister('phar');
		stream_wrapper_register('phar', PharRedirectStreamWrapper::class);

		$parentPid = getmypid();
		register_shutdown_function(static function () use ($parentPid, $extractDir): void {
			if (getmypid() !== $parentPid) {
				// Forked children must not nuke the directory the parent still needs.
				return;
			}
			self::removeDirectory($extractDir);
		});
	}

	private static function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach ($iterator as $entry) {
			if ($entry->isDir()) {
				rmdir($entry->getPathname());
			} else {
				unlink($entry->getPathname());
			}
		}
		rmdir($dir);
	}

}
