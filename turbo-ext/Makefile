#
# phpstan_turbo — a plain Zend extension, no framework dependencies.
#
#   make            builds phpstan_turbo.so
#   make clean
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

# The extension version is the short SHA of the last commit touching
# turbo-ext/src/ or a shadowed PHP twin (the same watched set the CI version
# job enforces against TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION),
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
MAPPED_PHP := $(shell php -n -r 'echo implode(" ", array_map(static fn ($$e) => $$e["php"], array_filter(json_decode(file_get_contents("shadowed-classes.json"), true), static fn ($$e) => !($$e["vendored"] ?? false))));' 2>/dev/null)
PHPSTANTURBO_VERSION := $(shell git -C .. log -1 --format=%H -- turbo-ext/src $(MAPPED_PHP) 2>/dev/null | cut -c1-7)
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
	$(CXX) `$(PHP_CONFIG) --ldflags` -shared $(LINK_FLAGS) -o $@ $(OBJECTS)

src/%.o: src/%.cpp src/support.h src/zv.h src/reg.h
	$(CXX) $(CXXFLAGS) -c -o $@ $<

src/main.o: version.stamp

version.stamp: FORCE
	@echo '$(PHPSTANTURBO_VERSION)' | cmp -s - $@ 2>/dev/null || echo '$(PHPSTANTURBO_VERSION)' > $@

FORCE:

$(filter src/parser/%.o,$(OBJECTS)): src/parser/ParserEngine.h src/zv.h

src/parser/ParserRunner.o: src/parser/ParserRunnerActionsSplit.h

clean:
	rm -f $(OBJECTS) phpstan_turbo.so version.stamp

.PHONY: clean FORCE
