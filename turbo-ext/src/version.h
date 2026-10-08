#ifndef PHPSTANTURBO_VERSION_H
#define PHPSTANTURBO_VERSION_H

/* The short SHA of the last commit touching the watched set: baked from git
 * by the Makefile (quoted string passed directly), or from the VERSION.txt
 * the subsplit workflow commits into phpstan/turbo-ext; "dev" with neither,
 * which the enabler rejects. config.w32 and config.m4 pass the bare token as
 * PHPSTANTURBO_VERSION_RAW — quote characters do not survive the Windows
 * configure-to-nmake pipeline — and it is stringized here. */
#ifdef PHPSTANTURBO_VERSION_RAW
#define PT_VERSION_STR2(x) #x
#define PT_VERSION_STR(x) PT_VERSION_STR2(x)
#define PHPSTANTURBO_VERSION PT_VERSION_STR(PHPSTANTURBO_VERSION_RAW)
#endif
#ifndef PHPSTANTURBO_VERSION
#define PHPSTANTURBO_VERSION "dev"
#endif

#endif
