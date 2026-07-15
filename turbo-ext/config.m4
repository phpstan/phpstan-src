dnl phpstan_turbo — the phpize build path (what PIE drives on non-Windows).
dnl Development and the distributed CI binaries use the hand-written Makefile
dnl instead: it globs sources the same way, but controls distribution
dnl properties this path cannot (static libstdc++/libgcc on Linux so shipped
dnl binaries do not depend on the host's GLIBCXX symbol versions, strict
dnl warning flags). config.w32 is the Windows twin. Keep the three in sync.
dnl
dnl Note: running phpize && ./configure inside a checkout overwrites the
dnl committed Makefile with the generated one (git restore brings it back);
dnl PIE builds in its own extracted copy, where that does not matter.

PHP_ARG_ENABLE([phpstan-turbo],
  [whether to enable phpstan_turbo support],
  [AS_HELP_STRING([--enable-phpstan-turbo],
    [Enable phpstan_turbo support])],
  [yes])

if test "$PHP_PHPSTAN_TURBO" != "no"; then
  PHP_REQUIRE_CXX()

  dnl The version comes from VERSION.txt — the monorepo short SHA the
  dnl enabler pins, generated and committed into the phpstan/turbo-ext
  dnl subsplit by its workflow (never present in the monorepo, where builds
  dnl go through the Makefile and git). git is useless on this path: a PIE
  dnl tarball has no checkout, and the subsplit's replayed commits have
  dnl different SHAs than the monorepo ones. The version is passed as a bare
  dnl token (main.cpp stringizes PHPSTANTURBO_VERSION_RAW) so no quote
  dnl characters have to survive configure and make. The .txt suffix is
  dnl load-bearing: C++ stdlibs #include <version>, and this directory is on
  dnl the include path — a file named VERSION satisfies that include on
  dnl case-insensitive filesystems (macOS, Windows).
  PHPSTAN_TURBO_VERSION=`tr -d ' \t\r\n' < "$srcdir/VERSION.txt" 2>/dev/null`
  if test -z "$PHPSTAN_TURBO_VERSION"; then
    PHPSTAN_TURBO_VERSION=dev
  fi
  AC_MSG_NOTICE([phpstan_turbo version: $PHPSTAN_TURBO_VERSION])

  dnl ZEND_ENABLE_STATIC_TSRMLS_CACHE: on ZTS builds EG()/CG() go through the
  dnl per-thread cache main.cpp defines instead of a ts_resource lookup per
  dnl access; a no-op on NTS builds.
  PHPSTAN_TURBO_CXXFLAGS="-std=c++17 -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 -DPHPSTANTURBO_VERSION_RAW=$PHPSTAN_TURBO_VERSION"

  dnl libtool links shared objects with the C driver, so the C++ runtime must
  dnl be requested explicitly (on macOS the linker resolves -lstdc++ to libc++).
  PHP_ADD_LIBRARY(stdc++, 1, PHPSTAN_TURBO_SHARED_LIBADD)
  PHP_SUBST(PHPSTAN_TURBO_SHARED_LIBADD)

  PHPSTAN_TURBO_SOURCES=`cd "$srcdir" && echo src/*.cpp src/parser/*.cpp`
  PHP_NEW_EXTENSION(phpstan_turbo, $PHPSTAN_TURBO_SOURCES, $ext_shared,, $PHPSTAN_TURBO_CXXFLAGS, cxx)
  PHP_ADD_BUILD_DIR($ext_builddir/src, 1)
  PHP_ADD_BUILD_DIR($ext_builddir/src/parser, 1)
fi
