/*
 * Linked into the shared core and into each thin per-version extension
 * alike: both fold libstdc++ in statically on Linux.
 */

/* The Makefile's Linux build folds libstdc++ in statically, and with it the
 * default terminate handler, which demangles the type of the exception that
 * escaped — 45 KB of demangler for a path this code reaches only when a
 * standard container fails an internal check (it never throws or catches
 * itself). This definition keeps the archive's handler, and so the
 * demangler, out of the link. Only for the static link: against a shared
 * libstdc++ (the phpize build) the handler is the process's, not ours. The
 * signature must match <exception>'s declaration exactly — GCC rejects a
 * redeclaration adding [[noreturn]] or hidden visibility. */
#ifdef PHPSTANTURBO_STATIC_LIBSTDCXX
#include <cstdio>
#include <cstdlib>
namespace __gnu_cxx {
void __verbose_terminate_handler()
{
	fputs("phpstan_turbo: terminate called (a C++ standard library check failed)\n", stderr);
	abort();
}
} // namespace __gnu_cxx
#endif
