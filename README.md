# PHPStan - PHP Static Analysis Tool

[![Build Status](https://travis-ci.com/phpstan/phpstan-src.svg?branch=master)](https://travis-ci.com/phpstan/phpstan-src)
[![Build](https://github.com/phpstan/phpstan-src/workflows/Build/badge.svg)](https://github.com/phpstan/phpstan-src/actions)
[![Coverage Status](https://coveralls.io/repos/github/phpstan/phpstan-src/badge.svg)](https://coveralls.io/github/phpstan/phpstan-src)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

---

This repository (`phpstan/phpstan-src`) is for PHPStan's development only. Head to [`phpstan/phpstan`](https://github.com/phpstan/phpstan) for the main README, or to [create an issue](https://github.com/phpstan/phpstan/issues/new/choose).

## Contributing

Any contributions are welcome.

### Building

PHPStan's source code is developed on PHP 7.4. For distribution in `phpstan/phpstan` package and as a PHAR file, the source code is transformed to run on PHP 7.1 and higher.

Initially you need to run `composer install`, or `composer update` in case you aren't working in a directory which was built before.

Afterwards you can either run the whole build including linting and coding standards using

```bash
vendor/bin/phing
```

### Running development version

You can also choose to run only part of the build. To analyse PHPStan by PHPStan itself, run:

```bash
vendor/bin/phing phpstan
```

### Fixing code style

To detect code style issues, run:

```bash
vendor/bin/phing cs
```

This requires PHP 7.4. On older versions the build target will be skipped and succeed silently.

And then to fix code style, run:

```bash
vendor/bin/phing cs-fix
```

### Running tests

Run:
```bash
vendor/bin/phing tests
```

You can find most issues by running `tests-fast` only which is recommended during development:

```bash
vendor/bin/phing tests-fast
```

### Debugging

1. Make sure XDebug is installed and configured.
2. Add `--xdebug` option when running PHPStan. Without it PHPStan turns the debugger off at runtime.
3. If you're not debugging the [result cache](https://phpstan.org/user-guide/result-cache), also add the `--debug` option.

## Code of Conduct

This project adheres to a [Contributor Code of Conduct](https://github.com/phpstan/phpstan/blob/master/CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold this code.
