#
# phpstan_turbo — a plain Zend extension, no framework dependencies.
#
#   make            builds phpstan_turbo.so
#   make pgo        builds it profile-guided: an instrumented build, a
#                   training run (bin/pgo-train.sh: PHPStan analysing its own
#                   sources with the instrumented extension loaded), then the
#                   final build using the recorded profile — what the
#                   distributed CI binaries are built with (phar.yml)
#   make clean      also drops the recorded profile
#

PHP_CONFIG ?= php-config

CXX ?= c++
# CI overrides with stricter settings, e.g. WARN_FLAGS="-Wall -Wextra -Werror"
# (the Zend engine headers are exempted via the pragma guard in src/support.h)
WARN_FLAGS ?= -Wall
# ZEND_ENABLE_STATIC_TSRMLS_CACHE: on ZTS builds EG()/CG() go through the
# per-thread cache main.cpp defines instead of a ts_resource lookup per
# access; a no-op on NTS builds.
CXXFLAGS := $(WARN_FLAGS) -O2 -std=c++17 -fPIC \
	-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 \
	`$(PHP_CONFIG) --includes`

# Profile-guided optimisation. PGO_FLAGS is empty for a plain build; `make
# pgo` drives the two instrumented/optimised builds below with the flags of
# the compiler in use (clang writes .profraw files merged by llvm-profdata,
# GCC writes .gcda files next to the objects and reads them back from
# there). The flags go on the link line too: the instrumented runtime is
# linked in by them.
PGO_DIR := pgo
PGO_FLAGS ?=
CXXFLAGS += $(PGO_FLAGS)
CXX_IS_CLANG := $(shell $(CXX) --version 2>/dev/null | grep -qi clang && echo 1)
ifeq ($(CXX_IS_CLANG),1)
PGO_GEN_FLAGS := -fprofile-instr-generate
PGO_USE_FLAGS := -fprofile-instr-use=$(PGO_DIR)/turbo.profdata -Wno-profile-instr-out-of-date -Wno-profile-instr-unprofiled
else
PGO_GEN_FLAGS := -fprofile-generate -fprofile-update=atomic
PGO_USE_FLAGS := -fprofile-use -fprofile-correction -Wno-missing-profile
endif

# The extension version is the short SHA of the last commit touching
# turbo-ext/src/ (the same computation the CI version job enforces against
# TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION),
# computed from git at build time and baked in via -D. Outside the monorepo
# (the phpstan/turbo-ext subsplit, a release tarball) git yields nothing —
# and the subsplit's replayed commits have different SHAs anyway — so
# VERSION.txt carries the monorepo SHA instead: subsplit-turbo-ext.yml
# generates and commits it per replayed commit, and it must never exist in
# the monorepo (the .txt suffix is load-bearing — C++ stdlibs #include
# <version>, and with the extension root on the include path a file named
# VERSION satisfies that include on case-insensitive filesystems). With
# neither source it degrades to "dev", which the enabler rejects — the
# extension then simply stays inactive. version.stamp makes a SHA change
# rebuild main.o.
PHPSTANTURBO_VERSION := $(shell git -C .. log -1 --format=%H -- turbo-ext/src 2>/dev/null | cut -c1-7)
ifeq ($(PHPSTANTURBO_VERSION),)
PHPSTANTURBO_VERSION := $(strip $(shell cat VERSION.txt 2>/dev/null))
endif
ifeq ($(PHPSTANTURBO_VERSION),)
PHPSTANTURBO_VERSION := dev
endif
CXXFLAGS += -DPHPSTANTURBO_VERSION='"$(PHPSTANTURBO_VERSION)"'

# Undefined PHP engine symbols are resolved by the php binary at load time;
# GNU ld allows them in shared objects by default, Darwin needs the flag.
# On Linux, fold libstdc++/libgcc into the .so statically so a distributed
# binary does not depend on the build host's GLIBCXX_*/GCC_* symbol versions.
UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Darwin)
LINK_FLAGS := -undefined dynamic_lookup
else
LINK_FLAGS := -static-libstdc++ -static-libgcc
endif

SOURCES := $(wildcard src/*.cpp) $(wildcard src/parser/*.cpp)
OBJECTS := $(SOURCES:.cpp=.o)

phpstan_turbo.so: $(OBJECTS)
	$(CXX) `$(PHP_CONFIG) --ldflags` -shared $(LINK_FLAGS) $(PGO_FLAGS) -o $@ $(OBJECTS)
	@# a shared object links with undefined symbols allowed (the engine's are
	@# resolved at load time), so a helper declared but never defined only
	@# surfaces as a jump to NULL at run time — fail the build instead
	@if nm -u $@ | grep -E 'pt_[a-z_]+|phpstanturbo' > /dev/null; then echo "undefined extension symbols in $@:"; nm -u $@ | grep -E 'pt_[a-z_]+|phpstanturbo'; rm -f $@; exit 1; fi

# every object depends on every header: the op tables and the trait helper
# declarations live in TypeOps.h / TypeTraits.h, and a stale object built
# against an older layout jumps into the wrong entry at run time
HEADERS := $(wildcard src/*.h) $(wildcard src/generated/*.h)
src/%.o: src/%.cpp $(HEADERS)
	$(CXX) $(CXXFLAGS) -c -o $@ $<

src/main.o: version.stamp

version.stamp: FORCE
	@echo '$(PHPSTANTURBO_VERSION)' | cmp -s - $@ 2>/dev/null || echo '$(PHPSTANTURBO_VERSION)' > $@

FORCE:

$(filter src/parser/%.o,$(OBJECTS)): src/parser/ParserEngine.h src/zv.h

src/parser/ParserRunner.o: src/parser/ParserRunnerActionsSplit.h

# The profile-guided build, in three sub-makes so each stage gets its own
# flags: instrumented objects + .so, the training run, the optimised
# objects + .so. Objects never survive a stage — an object compiled with
# other flags than its neighbours is exactly the mismatch PGO cannot detect.
# bin/pgo-train.sh needs the monorepo checkout with its Composer
# dependencies installed (it runs bin/phpstan); the recorded profile is kept
# in $(PGO_DIR) (clang) or next to the objects as .gcda files (GCC).
pgo:
	$(MAKE) pgo-clean
	$(MAKE) PGO_FLAGS="$(PGO_GEN_FLAGS)" phpstan_turbo.so
	PGO_DIR="$(PGO_DIR)" bin/pgo-train.sh
	rm -f $(OBJECTS) phpstan_turbo.so
ifeq ($(CXX_IS_CLANG),1)
	$(LLVM_PROFDATA) merge -output=$(PGO_DIR)/turbo.profdata $(PGO_DIR)/*.profraw
endif
	$(MAKE) PGO_FLAGS="$(PGO_USE_FLAGS)" phpstan_turbo.so

# llvm-profdata must match the clang in use: Xcode ships it behind xcrun,
# Linux distributions suffix it with the LLVM major version
LLVM_PROFDATA ?= $(shell command -v llvm-profdata 2>/dev/null || (command -v xcrun > /dev/null 2>&1 && echo "xcrun llvm-profdata") || ls /usr/bin/llvm-profdata-* 2>/dev/null | sort -V | tail -1)

pgo-clean:
	rm -f $(OBJECTS) phpstan_turbo.so version.stamp
	rm -rf $(PGO_DIR)
	find src -name '*.gcda' -delete

clean: pgo-clean
	rm -f compile_commands.json

#
# Static analysis of the hand-written sources. Which checks run, and the
# measured reason for every exclusion, are in .clang-tidy. The generated
# sources are left out entirely: src/generated/*.h comes from
# bin/generate-declarations.php and the parser's action tables from
# bin/generate-parser-actions.php, so a finding in them could only be fixed
# in the generator, never in place.
#
GENERATED_SOURCES := src/parser/ParserRunnerActions1.cpp src/parser/ParserRunnerActions2.cpp src/parser/ParserRunnerActions3.cpp
LINT_SOURCES := $(filter-out $(GENERATED_SOURCES),$(SOURCES))
LINT_JOBS ?= $(shell getconf _NPROCESSORS_ONLN 2>/dev/null || echo 4)

# The one pin. clang-tidy gains checks in the enabled families between
# releases, so two versions report two different trees: without a pin a green
# CI run and a green local run would not be the same evidence. CI reads this
# number (print-clang-tidy-version) and installs exactly it, and `lint`
# refuses to run with another — drift fails loudly instead of quietly
# changing what the gate means. To move it: raise the number, re-run, then
# fix or exclude (with its count and reason, as .clang-tidy does) whatever
# the new checks report.
CLANG_TIDY_VERSION := 21
# Homebrew keeps LLVM off the PATH, and parks the superseded majors in
# llvm@N once its `llvm` moves on
CLANG_TIDY ?= $(shell command -v clang-tidy-$(CLANG_TIDY_VERSION) 2>/dev/null \
	|| ls /opt/homebrew/opt/llvm@$(CLANG_TIDY_VERSION)/bin/clang-tidy 2>/dev/null \
	|| ls /opt/homebrew/opt/llvm/bin/clang-tidy 2>/dev/null \
	|| command -v clang-tidy 2>/dev/null)

print-clang-tidy-version:
	@echo $(CLANG_TIDY_VERSION)

# clang-tidy takes the compile flags from a compilation database, which a
# plain Makefile build does not produce as a side effect
compile_commands.json: Makefile bin/generate-compile-commands.php
	PHP_CONFIG="$(PHP_CONFIG)" php bin/generate-compile-commands.php

lint: compile_commands.json
	@if [ -z "$(CLANG_TIDY)" ]; then \
		echo "clang-tidy $(CLANG_TIDY_VERSION) not found — install it (brew install llvm@$(CLANG_TIDY_VERSION), or apt.llvm.org) or pass CLANG_TIDY=/path/to/clang-tidy-$(CLANG_TIDY_VERSION)"; \
		exit 1; \
	fi
	@found="$$($(CLANG_TIDY) --version | sed -n 's/.*version \([0-9][0-9]*\).*/\1/p' | head -1)"; \
	if [ "$$found" != "$(CLANG_TIDY_VERSION)" ]; then \
		echo "$(CLANG_TIDY) is version $${found:-unknown}, but the check list is pinned to $(CLANG_TIDY_VERSION)."; \
		echo "A different major reports a different tree, so this gate would stop meaning what CI's means."; \
		echo "Install the pinned one (brew install llvm@$(CLANG_TIDY_VERSION), or apt.llvm.org) or pass CLANG_TIDY=/path/to/clang-tidy-$(CLANG_TIDY_VERSION)."; \
		echo "To move the pin deliberately, raise CLANG_TIDY_VERSION in this Makefile."; \
		exit 1; \
	fi
	@echo "clang-tidy $(CLANG_TIDY_VERSION) over $(words $(LINT_SOURCES)) sources, $(LINT_JOBS) at a time"
	@printf '%s\n' $(LINT_SOURCES) | xargs -P $(LINT_JOBS) -n 1 $(CLANG_TIDY) -p . --quiet

#
# The differential tests under UndefinedBehaviorSanitizer. The sanitizer
# runtime is linked into the .so, so an ordinary PHP loads it — no debug or
# instrumented PHP build is needed. Objects never mix flags (the same rule
# `make pgo` follows), so this builds from clean and cleans up after itself.
#
SANITIZE_FLAGS ?= -fsanitize=undefined -fno-omit-frame-pointer
SANITIZE_TESTS ?= smoke arena-smoke signature-parity parser-corpus

sanitize:
	$(MAKE) clean
	$(MAKE) PGO_FLAGS="$(SANITIZE_FLAGS)" phpstan_turbo.so
	@set -e; for t in $(SANITIZE_TESTS); do \
		echo "== $$t under UBSan =="; \
		(cd .. && UBSAN_OPTIONS=halt_on_error=1:print_stacktrace=1 \
			TURBO_DLL="$(CURDIR)/phpstan_turbo.so" \
			php -d extension="$(CURDIR)/phpstan_turbo.so" -d memory_limit=4G "turbo-ext/tests/$$t.php"); \
	done
	$(MAKE) clean

.PHONY: clean lint pgo pgo-clean print-clang-tidy-version sanitize FORCE
