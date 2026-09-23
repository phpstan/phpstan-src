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
UNAME_S := $(shell uname -s)

# The extension never throws and never walks its own stack (the engine
# unwinds with longjmp), so it needs no unwind tables: -fno-exceptions alone
# still leaves .eh_frame for every function, 0.8 MB of the Linux .so. The
# section flags let the linker drop whatever nothing references.
SIZE_FLAGS := -fno-asynchronous-unwind-tables -fno-unwind-tables -ffunction-sections -fdata-sections
# The strict set the CI compile legs build with, defined once here and read
# by the workflows (`make -s print-warn-flags`) instead of being spelled out
# in each of them. The three -Wno- exemptions cover third-party macro
# expansions, never our own code:
#   -Wno-assume            zend's parameter-parsing macros expand
#                          __builtin_assume with (potential) side effects
#   -Wno-unused-parameter  PHP_METHOD's fixed signature — execute_data and
#                          return_value are not used by every method
#   -Wno-unicode           zend arginfo macros stringify namespaced class
#                          names; clang lexes the \N in
#                          "PhpParser\NodeVisitor" as a universal character
#                          name (GCC ignores the unknown -Wno- flag)
# The four warnings beyond -Wall -Wextra were each measured over every
# translation unit with both compilers before being enabled, and produce
# nothing today. Three more were tried and rejected, with the counts that
# rejected them (distinct sites in our own code, GCC 11.4 — the CI floor —
# over all 173 sources; measure any candidate the same way, and beware that
# our files report relative paths while the engine's headers report absolute
# ones, which is easy to mis-split):
#   -Wshadow      142 sites. GCC also warns when a parameter shadows a
#                 global or a member function, which clang does not (clang
#                 finds 5, all in reg.h). GCC's narrower -Wshadow=local
#                 matches clang's meaning and may be worth revisiting.
#   -Wcast-qual   14 sites under clang, 9 under GCC — nearly all expansions
#                 of the engine's own ZVAL_EMPTY_ARRAY, which casts the
#                 shared const empty array to zend_array *. Not ours to fix.
#   -Wzero-as-null-pointer-constant  3 sites; not worth a gate on its own.
STRICT_WARN_FLAGS := -Wall -Wextra -Werror \
	-Wno-assume -Wno-unused-parameter -Wno-unicode \
	-Wsuggest-override -Wnon-virtual-dtor -Wdouble-promotion -Wextra-semi
# A plain local build stays lenient on purpose: a contributor's compiler may
# warn where the versions CI pins do not, and -Werror would turn that into a
# build failure for them.
WARN_FLAGS ?= -Wall
# -O2, measured against -Os in the CI image (GCC 11.4, arm64, interleaved
# A/B, user CPU with the extension loaded): -Os with LTO takes the stripped
# .so from 7.5 MB to 5.0 MB but makes the analysis 10.4% slower (6.7% with
# PGO), so the size is not worth it.
# ZEND_ENABLE_STATIC_TSRMLS_CACHE: on ZTS builds EG()/CG() go through the
# per-thread cache main.cpp defines instead of a ts_resource lookup per
# access; a no-op on NTS builds.
CXXFLAGS := $(WARN_FLAGS) -O2 -std=c++17 -fPIC \
	-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 \
	`$(PHP_CONFIG) --includes`

# Profile-guided optimisation, measured 2026-09-23 with the extension
# loaded (interleaved A/B, user CPU, n=11): 4.0% faster on Linux (GCC 11.4,
# t=-6.3), 3.6% on macOS (clang, t=-14.1), for a stripped .so within 1-2%
# of the same size. PGO_FLAGS is empty for a plain build; `make
# pgo` drives the two instrumented/optimised builds below with the flags of
# the compiler in use (clang writes .profraw files merged by llvm-profdata,
# GCC writes .gcda files next to the objects and reads them back from
# there). The flags go on the link line too: the instrumented runtime is
# linked in by them.
PGO_DIR := pgo
PGO_FLAGS ?=
CXX_IS_CLANG := $(shell $(CXX) --version 2>/dev/null | grep -qi clang && echo 1)
ifeq ($(CXX_IS_CLANG),1)
PGO_GEN_FLAGS := -fprofile-instr-generate
PGO_USE_FLAGS := -fprofile-instr-use=$(PGO_DIR)/turbo.profdata -Wno-profile-instr-out-of-date -Wno-profile-instr-unprofiled
else
PGO_GEN_FLAGS := -fprofile-generate -fprofile-update=atomic
PGO_USE_FLAGS := -fprofile-use -fprofile-correction -Wno-missing-profile
endif

# Link-time optimisation, with GCC (every Linux leg). Measured in the CI
# image (GCC 11.4, arm64, `make pgo` on both sides, interleaved A/B, user
# CPU, n=11): 3.9% faster (t=-9.4) and the stripped .so 15% smaller
# (7.4 -> 6.3 MB). Clang goes the other way on size: full LTO on macOS was
# 1.2% faster (t=-3.6) but made the stripped .so 9% larger (7.1 -> 7.7 MB),
# so clang builds stay without it. On the compile and the link line both.
ifneq ($(CXX_IS_CLANG),1)
LTO_FLAGS := -flto=auto
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
ifeq ($(UNAME_S),Darwin)
LINK_FLAGS := -undefined dynamic_lookup -Wl,-dead_strip
else
LINK_FLAGS := -static-libstdc++ -static-libgcc -Wl,--gc-sections
# main.cpp then supplies the terminate handler, keeping libstdc++'s (and its
# demangler) out of the link
CXXFLAGS += -DPHPSTANTURBO_STATIC_LIBSTDCXX
# the loader resolves and write-protects the GOT before handing control over,
# and the stack is never executable
LINK_FLAGS += -Wl,-z,relro,-z,now -Wl,-z,noexecstack
endif

# Size, measured rather than assumed (interleaved A/B, user CPU, 12-30 pairs).
# A PHP extension only has to export get_module, which ZEND_GET_MODULE marks
# visible explicitly, so hiding everything else costs nothing: __TEXT came out
# byte-identical and the timing delta was +0.20% (t=+0.26, i.e. noise) while
# the .so shrank 17.4%. The engine unwinds with longjmp, and this codebase
# neither throws nor uses dynamic_cast — a style rule, not an accident — so
# dropping the exception tables and RTTI removed a further 13.0% of __TEXT,
# again with no measurable timing effect (+0.05%, t=+0.81 at n=29, sd 0.14s).
# Both bind future code: a `throw` or a `dynamic_cast` becomes a compile
# error, which is the intent. On ELF the visibility flag does far more than
# it does on macOS: measured in the CI image (GCC 11.4), hiding the symbols
# drops 8,866 of 9,907 dynamic symbols and 18.9% of __TEXT (5,760,996 ->
# 4,672,730), because a default-visibility symbol may be interposed at load
# time and so can be neither inlined nor garbage-collected. Mach-O's
# two-level namespace already prevents that, which is why __TEXT there came
# out byte-identical and only the symbol table shrank.
# (config.m4's phpize path does not carry these, the same way it does not
# carry the strict warnings: the Makefile is the primary build.)
CXXFLAGS += -fvisibility=hidden -fvisibility-inlines-hidden -fno-exceptions -fno-rtti
CXXFLAGS += $(SIZE_FLAGS) $(LTO_FLAGS)

# Hardening. Every flag is probed against the compiler actually in use rather
# than assumed, because the targets disagree: the CI floor is GCC 11.4, which
# rejects -ftrivial-auto-var-init outright (GCC 12+); -fcf-protection is
# x86-only while one gnu leg is arm64; and -fstack-clash-protection is
# accepted but silently unused by Apple clang on arm64, which the -Werror in
# the probe turns into a rejection. _FORTIFY_SOURCE is deliberately absent:
# Ubuntu's GCC predefines it, so passing it again is a no-op at best and a
# redefinition error under -Werror at worst.
# These change codegen, unlike the warning flags, so they were measured
# (__TEXT and an interleaved A/B) before being turned on by default. Override
# with `make HARDENING_FLAGS=` to build without them.
# the probe compiles at -O2 like the real build: _FORTIFY_SOURCE warns when
# optimisation is off, and -Werror would then reject it for the wrong reason
cxx-supports = $(shell $(CXX) -O2 $(1) -Werror -c -o /dev/null -x c++ /dev/null > /dev/null 2>&1 && echo $(1))
# glibc predefines _FORTIFY_SOURCE, so it is undefined first rather than
# redefined; level 3 where the libc supports it, otherwise 2, and nothing at
# all where the probe rejects both (musl, macOS)
FORTIFY_3 := $(call cxx-supports,-U_FORTIFY_SOURCE -D_FORTIFY_SOURCE=3)
FORTIFY := $(if $(FORTIFY_3),$(FORTIFY_3),$(call cxx-supports,-U_FORTIFY_SOURCE -D_FORTIFY_SOURCE=2))
# Array-bounds checks that trap instead of linking a sanitizer runtime — the
# closest a shipped binary here gets to a bounds guarantee. The trap spelling
# differs by compiler: -fsanitize-trap= exists from GCC 12, and the CI floor
# is 11.4, which has only the older whole-program form.
# NOT added: -fstrict-flex-arrays=3. It traps immediately on ordinary string
# and property access, because the engine's public structures use the C
# struct-hack (zend_string.val[1], zend_object.properties_table[1] behind
# OBJ_PROP_NUM, smart_str). All 29 violation sites in our own code were that
# macro expanding; none is fixable here short of abandoning the Zend API.
BOUNDS_TRAP := $(call cxx-supports,-fsanitize=bounds -fsanitize-trap=bounds)
BOUNDS := $(if $(BOUNDS_TRAP),$(BOUNDS_TRAP),$(call cxx-supports,-fsanitize=bounds -fsanitize-undefined-trap-on-error))
HARDENING_FLAGS ?= $(call cxx-supports,-fstack-protector-strong) \
	$(call cxx-supports,-fstack-clash-protection) \
	$(call cxx-supports,-ftrivial-auto-var-init=zero) \
	$(call cxx-supports,-fcf-protection=full) \
	$(call cxx-supports,-fzero-call-used-regs=used-gpr) \
	$(FORTIFY) \
	$(BOUNDS) \
	-D_LIBCPP_HARDENING_MODE=_LIBCPP_HARDENING_MODE_FAST
# libstdc++'s equivalent of that last one is -D_GLIBCXX_ASSERTIONS. It is
# deliberately absent: the measurement above was taken against libc++, and a
# flag is adopted here with its own number, not by analogy. Measure it on a
# Linux host before adding it.
CXXFLAGS += $(HARDENING_FLAGS)

# PGO_FLAGS goes last on purpose: it is the injection hook `make pgo` and the
# A/B harness use, and a flag only overrides an earlier one if it comes after
# it. While this sat before the blocks above, `PGO_FLAGS=-fvisibility=default`
# was silently outranked by the -fvisibility=hidden added later, so an
# experiment measuring that flag compared two identical builds — twice.
CXXFLAGS += $(PGO_FLAGS)

SOURCES := $(wildcard src/*.cpp) $(wildcard src/parser/*.cpp)
OBJECTS := $(SOURCES:.cpp=.o)

phpstan_turbo.so: $(OBJECTS)
	$(CXX) `$(PHP_CONFIG) --ldflags` -shared $(LTO_FLAGS) $(LINK_FLAGS) $(PGO_FLAGS) -o $@ $(OBJECTS)
	@# a shared object links with undefined symbols allowed (the engine's are
	@# resolved at load time), so a helper declared but never defined only
	@# surfaces as a jump to NULL at run time — fail the build instead
	@if nm -u $@ | grep -E 'pt_[a-z_]+|phpstanturbo' > /dev/null; then echo "undefined extension symbols in $@:"; nm -u $@ | grep -E 'pt_[a-z_]+|phpstanturbo'; rm -f $@; exit 1; fi

# The distributed binaries ship without symbol tables: a quarter of the .so
# (2.4 MB on Linux, 3.2 MB on macOS) that the loader never reads, since the
# only symbol PHP looks up is get_module, which stays in the dynamic table.
# Local builds keep them — sample and perf name frames from them. Not a
# dependency of phpstan_turbo.so on purpose: this runs after `make pgo`, and
# must never rebuild what that produced with other flags.
strip:
	@test -f phpstan_turbo.so || { echo "phpstan_turbo.so is missing — build it first"; exit 1; }
ifeq ($(UNAME_S),Darwin)
	strip -x phpstan_turbo.so
else
	strip --strip-unneeded phpstan_turbo.so
endif

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

print-warn-flags:
	@echo '$(STRICT_WARN_FLAGS)'

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
# -fno-sanitize-trap matters here: the hardening set turns bounds checks into
# traps, and since PGO_FLAGS lands last that trapping would still apply to
# this build — a bounds violation would abort with no diagnostic, the exact
# opposite of what a sanitizer run is for. Probed, because the spelling is
# not available on every compiler this builds with.
# The unwind tables SIZE_FLAGS drops come back here for the same reason:
# print_stacktrace unwinds through them.
SANITIZE_FLAGS ?= -fsanitize=undefined $(call cxx-supports,-fno-sanitize-trap=all) -fno-omit-frame-pointer -funwind-tables -fasynchronous-unwind-tables
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

.PHONY: clean lint pgo pgo-clean print-clang-tidy-version print-warn-flags sanitize strip FORCE
