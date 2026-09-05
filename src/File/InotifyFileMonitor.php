<?php declare(strict_types = 1);

namespace PHPStan\File;

use FFI;
use FFI\CData;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function class_exists;
use function extension_loaded;

/**
 * Linux {@see NativeFileMonitor} backed by inotify, reached through FFI.
 *
 * inotify watches a single directory, not a subtree, so every directory below
 * the analysed paths costs one watch - which is what
 * `fs.inotify.max_user_watches` bounds, and why {@see self::WATCH_LIMIT} exists.
 *
 * The queue is drained rather than parsed: this monitor only has to answer
 * "did anything change", and the wrapped hashing monitor works out what.
 */
final class InotifyFileMonitor extends NativeFileMonitor
{

	private const CDEF = <<<'C'
int inotify_init1(int flags);
int inotify_add_watch(int fd, const char *pathname, unsigned int mask);
long read(int fd, void *buf, unsigned long count);
int close(int fd);
C;

	/** IN_NONBLOCK - a poll must never wait for an event that is not there */
	private const IN_NONBLOCK = 04000;

	/**
	 * IN_MODIFY | IN_ATTRIB | IN_CLOSE_WRITE | IN_MOVED_FROM | IN_MOVED_TO
	 * | IN_CREATE | IN_DELETE | IN_DELETE_SELF | IN_MOVE_SELF - every way a
	 * file an editor just saved can show up, including the write-and-rename
	 * that most editors do.
	 */
	private const WATCH_MASK = 0x2 | 0x4 | 0x8 | 0x40 | 0x80 | 0x100 | 0x200 | 0x400 | 0x800;

	/** Events are variable length; this only has to be big enough to drain in few reads. */
	private const READ_BUFFER_BYTES = 65536;

	private ?FFI $ffi = null;

	private int $fd = -1;

	private ?CData $buffer = null;

	protected function watchesRecursively(): bool
	{
		return false;
	}

	protected function open(): void
	{
		if (!extension_loaded('ffi') || !class_exists(FFI::class)) {
			throw new FileMonitorNotSupportedException();
		}

		try {
			$ffi = FFI::cdef(self::CDEF);
			$fd = $ffi->inotify_init1(self::IN_NONBLOCK);
		} catch (Throwable) {
			throw new FileMonitorNotSupportedException();
		}

		if ($fd < 0) {
			throw new FileMonitorNotSupportedException();
		}

		$this->ffi = $ffi;
		$this->fd = $fd;
		$this->buffer = $ffi->new('char[' . self::READ_BUFFER_BYTES . ']');
	}

	protected function addWatch(string $directory): void
	{
		if ($this->ffi === null) {
			throw new ShouldNotHappenException();
		}

		// -1 means the per-user watch limit is exhausted (or the directory went
		// away between listing and arming) - either way this monitor cannot
		// promise to see every change, so it must not be used at all.
		if ($this->ffi->inotify_add_watch($this->fd, $directory, self::WATCH_MASK) < 0) {
			throw new FileMonitorNotSupportedException();
		}
	}

	protected function drainEvents(): bool
	{
		if ($this->ffi === null || $this->buffer === null) {
			throw new ShouldNotHappenException();
		}

		$changed = false;
		while ($this->ffi->read($this->fd, $this->buffer, self::READ_BUFFER_BYTES) > 0) {
			$changed = true;
		}

		return $changed;
	}

}
