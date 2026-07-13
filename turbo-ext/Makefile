#
# phpstan_turbo — build with a locally built PHP-CPP (statically linked).
#
#   make            builds phpstan_turbo.so
#   make phpcpp     builds the bundled PHP-CPP library first
#   make clean
#

PHP_CONFIG ?= php-config
PHPCPP_DIR := PHP-CPP

CXX ?= c++
# CI overrides with stricter settings, e.g. WARN_FLAGS="-Wall -Wextra -Werror"
# (third-party headers — PHP-CPP, zend — are exempted via the pragma guard in
# src/support.h; -isystem is not usable here because Apple clang lets the
# default /usr/local/include shadow -isystem paths, picking up a stale
# system-installed PHP-CPP)
WARN_FLAGS ?= -Wall
CXXFLAGS := $(WARN_FLAGS) -O2 -std=c++17 -fPIC \
	-I$(PHPCPP_DIR) \
	`$(PHP_CONFIG) --includes`

# The extension version is the short SHA of the last commit touching
# turbo-ext/src/ or a shadowed PHP twin (the same watched set the CI version
# job enforces against TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION),
# computed from git at build time and baked in via -D. Outside a git checkout
# it degrades to "dev", which the enabler rejects — the extension then simply
# stays inactive. version.stamp makes a SHA change rebuild main.o.
MAPPED_PHP := $(shell php -n -r 'echo implode(" ", array_map(static fn ($$e) => $$e["php"], array_filter(json_decode(file_get_contents("shadowed-classes.json"), true), static fn ($$e) => !($$e["vendored"] ?? false))));' 2>/dev/null)
PHPSTANTURBO_VERSION := $(shell git -C .. log -1 --format=%H -- turbo-ext/src $(MAPPED_PHP) 2>/dev/null | cut -c1-7)
ifeq ($(PHPSTANTURBO_VERSION),)
PHPSTANTURBO_VERSION := dev
endif
CXXFLAGS += -DPHPSTANTURBO_VERSION='"$(PHPSTANTURBO_VERSION)"'

# Undefined PHP engine symbols are resolved by the php binary at load time;
# GNU ld allows them in shared objects by default, Darwin needs the flag.
UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Darwin)
LINK_FLAGS := -undefined dynamic_lookup
endif

SOURCES := $(wildcard src/*.cpp) $(wildcard src/parser/*.cpp)
OBJECTS := $(SOURCES:.cpp=.o)

PHPCPP_LIB := $(wildcard $(PHPCPP_DIR)/libphpcpp.a.*)

phpstan_turbo.so: $(OBJECTS) $(PHPCPP_LIB)
	$(CXX) `$(PHP_CONFIG) --ldflags` -shared $(LINK_FLAGS) -o $@ $(OBJECTS) $(PHPCPP_LIB)

src/%.o: src/%.cpp src/support.h src/zv.h src/reg.h
	$(CXX) $(CXXFLAGS) -c -o $@ $<

src/main.o: version.stamp

version.stamp: FORCE
	@echo '$(PHPSTANTURBO_VERSION)' | cmp -s - $@ 2>/dev/null || echo '$(PHPSTANTURBO_VERSION)' > $@

FORCE:

$(filter src/parser/%.o,$(OBJECTS)): src/parser/ParserEngine.h src/zv.h

src/parser/ParserRunner.o: src/parser/ParserRunnerActionsSplit.h

# PHP-CPP carries one local patch (patches/php-cpp-base-count-int64.patch,
# needed on LP64 Darwin, a no-op cast on Linux); application is idempotent.
phpcpp:
	git -C $(PHPCPP_DIR) apply --reverse --check ../patches/php-cpp-base-count-int64.patch 2>/dev/null || \
		git -C $(PHPCPP_DIR) apply ../patches/php-cpp-base-count-int64.patch
	$(MAKE) -C $(PHPCPP_DIR)

clean:
	rm -f $(OBJECTS) phpstan_turbo.so version.stamp

.PHONY: phpcpp clean FORCE
