/*
 * PHPStanTurbo\ForkedChild — native implementation of
 * PHPStan\Turbo\ForkedChild, what a pcntl_fork()ed child needs from the
 * extension, as part of PHPStan's API. Unlike PHPStanTurbo\Runtime
 * (main.cpp), which TurboExtensionEnabler calls before shadowing is active,
 * this class only exists under its PHP name once activateShadowing() ran;
 * the PHP twin throws TurboNotActiveException.
 */

#include "support.h"
#include "generated/ForkedChild.h"

namespace sigs = ptdecl::ForkedChild::sig;

#ifdef PHP_WIN32
#include <process.h>
#else
#include <unistd.h>
#endif

static zend_class_entry *pt_ce_forked_child = nullptr;

/* {{{ registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_forked_child)
{
	reg::Class cls("PHPStan\\Turbo\\ForkedChild");
	ptdecl::ForkedChild::declareClass(cls);
	ptdecl::ForkedChild::declareProperties(cls);

	/* exitImmediately() — _exit() for a pcntl_fork()ed worker;
	 * ForkedChildTerminator registers it as the child's last shutdown
	 * function.
	 *
	 * A forked child inherits the whole parent process, every loaded
	 * extension with whatever background threads it started included — but
	 * fork() copies only the calling thread. PHP's exit() then runs
	 * destructors and each extension's module shutdown, and an extension
	 * whose shutdown waits for its threads to check out (ext-grpc's
	 * grpc_shutdown() without grpc.enable_fork_support, for one) waits
	 * forever for threads the child never had: the worker has delivered its
	 * results, the parent keeps polling waitpid(), and the run hangs at 100%.
	 * A forked child that does not exec() must end with _exit() — no
	 * destructors, no module shutdown, no atexit handlers; that teardown is
	 * the parent's. The shutdown functions have run by then, so the crash
	 * report (ForkedChildCrashReporter) is written, and everything the parent
	 * reads — the results over the socket, the captured output — went
	 * through unbuffered fds.
	 *
	 * The status is the engine's: what exit() was given, 255 after a fatal
	 * error, 0 otherwise. */
	cls.method(sigs::exitImmediately, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();

		_exit(EG(exit_status));
	});

	cls.shadow(&pt_ce_forked_child);
}

/* }}} */
