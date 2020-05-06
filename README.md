# PHPStan - PHP Static Analysis Tool

[![Build Status](https://travis-ci.com/phpstan/phpstan-src.svg?branch=master)](https://travis-ci.com/phpstan/phpstan-src)
[![Build](https://github.com/phpstan/phpstan-src/workflows/Build/badge.svg)](https://github.com/phpstan/phpstan-src/actions)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

---

This repository (`phpstan/phpstan-src`) is for PHPStan's development only. Head to [`phpstan/phpstan`](https://github.com/phpstan/phpstan) for the main README, or to [create an issue](https://github.com/phpstan/phpstan/issues/new/choose). 

## Code of Conduct

This project adheres to a [Contributor Code of Conduct](https://github.com/phpstan/phpstan/blob/master/CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold this code.

## Contributing

Any contributions are welcome.

### Building

Initially you need to run `composer install`, or `composer update` in case you aren't working in a folder which was built before.

Afterwards you can either run the whole build including linting and coding standards using

```bash
vendor/bin/phing
```

or run only tests using

```bash
vendor/bin/phing tests
```

### Running development version

The executable is bin/phpstan
Rut it as
bin/phpstan analyze -c your-config.neon -l 8 path-to-analyze

As you change the source code it immediately takes effect, no re-building is required.


### Debugging

1. Make sure XDebug is installed and configured using any HOWTO available.
2. Add --xdebug option when running phpstan. Without it PHPStan turns the debugger off at runtime.

Now you are ready to set breakpoints on rules and have fun.


### Fixing code style

To detect code style issues, run:
```bash
vendor/bin/phing cs
```

This requires PHP 7.4. Otherwise the check will be skipped and succeed silently.

And then to fix code style, run:

```bash
vendor/bin/phing cs-fix
```
This does not depend on PHP version.


### Running tests

If you have XDebug enabled and run PHPUnit directly instead of phing, make sure to set PHPSTAN_ALLOW_XDEBUG environment variable to 1 before running PHPUnit. Otherwise PHPStan will detect XDebug and try to disable it. It would restart the 'phpunit' command with arguments of 'phpstan' command which will break.

Run:
```bash
export PHPSTAN_ALLOW_XDEBUG=1
vendor/bin/phpunit --bootstrap=tests/bootstrap.php --stop-on-failure tests
```
