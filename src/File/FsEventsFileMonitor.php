<?php declare(strict_types = 1);

namespace PHPStan\File;

use Closure;
use FFI;
use FFI\CData;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function class_exists;
use function extension_loaded;

/**
 * macOS {@see NativeFileMonitor} backed by FSEvents, reached through FFI.
 *
 * FSEvents watches whole subtrees, so a project needs a handful of watches
 * rather than one per directory, and - unlike a kqueue watch on a directory -
 * it reports a file rewritten in place, which is what an editor save usually
 * is.
 *
 * Events arrive on the run loop of the thread that scheduled the stream, so
 * they are collected by running that run loop with a zero timeout on each
 * poll: no blocking, no second thread, and the callback runs on the same
 * thread that called it.
 */
final class FsEventsFileMonitor extends NativeFileMonitor
{

	private const CDEF = <<<'C'
typedef void* CFAllocatorRef;
typedef void* CFArrayRef;
typedef void* CFStringRef;
typedef void* FSEventStreamRef;
typedef void* CFRunLoopRef;
typedef void (*PHPStanFSEventStreamCallback)(void *stream, void *info, unsigned long numEvents, void *eventPaths, unsigned int *eventFlags, unsigned long long *eventIds);
CFStringRef CFStringCreateWithCString(CFAllocatorRef alloc, const char *cStr, unsigned int encoding);
CFArrayRef CFArrayCreate(CFAllocatorRef allocator, const void **values, long numValues, const void *callBacks);
FSEventStreamRef FSEventStreamCreate(CFAllocatorRef allocator, PHPStanFSEventStreamCallback callback, void *context, CFArrayRef pathsToWatch, unsigned long long sinceWhen, double latency, unsigned int flags);
void FSEventStreamScheduleWithRunLoop(FSEventStreamRef streamRef, CFRunLoopRef runLoop, CFStringRef runLoopMode);
unsigned char FSEventStreamStart(FSEventStreamRef streamRef);
void FSEventStreamStop(FSEventStreamRef streamRef);
void FSEventStreamInvalidate(FSEventStreamRef streamRef);
void FSEventStreamRelease(FSEventStreamRef streamRef);
CFRunLoopRef CFRunLoopGetCurrent(void);
int CFRunLoopRunInMode(CFStringRef mode, double seconds, unsigned char returnAfterSourceHandled);
C;

	private const FRAMEWORK = '/System/Library/Frameworks/CoreServices.framework/CoreServices';

	private const KCF_STRING_ENCODING_UTF8 = 0x08000100;

	/** kFSEventStreamEventIdSinceNow - all ones, which is -1 as a PHP int */
	private const SINCE_NOW = -1;

	/** kFSEventStreamCreateFlagFileEvents | kFSEventStreamCreateFlagNoDefer */
	private const CREATE_FLAGS = 0x10 | 0x02;

	/** Report as soon as the kernel has the event; the poll interval paces us. */
	private const LATENCY_SECONDS = 0.0;

	private ?FFI $ffi = null;

	/** The CFStringRef for kCFRunLoopDefaultMode. */
	private ?CData $runLoopMode = null;

	/**
	 * Streams, and the CF objects they borrow. CFArrayCreate() is given no
	 * retain callbacks, so nothing but this keeps the paths alive for as long
	 * as the stream reads them.
	 *
	 * @var list<array{mixed, mixed, mixed, mixed}>
	 */
	private array $streams = [];

	private bool $changed = false;

	/**
	 * Kept referenced: an FFI callback must outlive every call into it.
	 *
	 * @var (Closure(mixed, mixed, mixed, mixed, mixed, mixed): void)|null
	 */
	private ?Closure $callback = null;

	protected function watchesRecursively(): bool
	{
		return true;
	}

	protected function open(): void
	{
		if (!extension_loaded('ffi') || !class_exists(FFI::class)) {
			throw new FileMonitorNotSupportedException();
		}

		try {
			$ffi = FFI::cdef(self::CDEF, self::FRAMEWORK);
		} catch (Throwable) {
			throw new FileMonitorNotSupportedException();
		}

		$this->ffi = $ffi;
		$this->runLoopMode = $ffi->CFStringCreateWithCString(null, 'kCFRunLoopDefaultMode', self::KCF_STRING_ENCODING_UTF8);
		$this->callback = function ($stream, $info, $numEvents, $eventPaths, $eventFlags, $eventIds): void {
			$this->changed = true;
		};
	}

	protected function addWatch(string $directory): void
	{
		// A stream's path list is fixed once created, and watchesRecursively()
		// keeps the roots down to a handful, so one stream per root is cheaper
		// than rebuilding a combined stream whenever a root appears.
		$this->startStream($directory);
	}

	protected function drainEvents(): bool
	{
		if ($this->ffi === null) {
			throw new ShouldNotHappenException();
		}

		// Runs whatever the run loop already has ready and returns immediately;
		// the callback flips $changed while it does.
		$this->ffi->CFRunLoopRunInMode($this->runLoopMode, self::LATENCY_SECONDS, 0);

		$changed = $this->changed;
		$this->changed = false;

		return $changed;
	}

	public function __destruct()
	{
		if ($this->ffi === null) {
			return;
		}

		foreach ($this->streams as [$stream]) {
			$this->ffi->FSEventStreamStop($stream);
			$this->ffi->FSEventStreamInvalidate($stream);
			$this->ffi->FSEventStreamRelease($stream);
		}

		$this->streams = [];
	}

	/**
	 * @throws FileMonitorNotSupportedException
	 */
	private function startStream(string $directory): void
	{
		if ($this->ffi === null) {
			throw new ShouldNotHappenException();
		}

		$ffi = $this->ffi;
		$paths = $ffi->new('void*[1]');
		$cfString = $ffi->CFStringCreateWithCString(null, $directory, self::KCF_STRING_ENCODING_UTF8);
		if ($cfString === null) {
			throw new FileMonitorNotSupportedException();
		}

		$paths[0] = $cfString;
		$array = $ffi->CFArrayCreate(null, $ffi->cast('const void**', FFI::addr($paths)), 1, null);
		if ($array === null) {
			throw new FileMonitorNotSupportedException();
		}

		$stream = $ffi->FSEventStreamCreate(
			null,
			$this->callback,
			null,
			$array,
			self::SINCE_NOW,
			self::LATENCY_SECONDS,
			self::CREATE_FLAGS,
		);
		if ($stream === null) {
			throw new FileMonitorNotSupportedException();
		}

		$ffi->FSEventStreamScheduleWithRunLoop($stream, $ffi->CFRunLoopGetCurrent(), $this->runLoopMode);
		if ($ffi->FSEventStreamStart($stream) === 0) {
			throw new FileMonitorNotSupportedException();
		}

		// CFArrayCreate was given no retain callbacks, so the strings (and the
		// array) are only alive as long as this holds on to them.
		$this->streams[] = [$stream, $array, $paths, $cfString];
	}

}
