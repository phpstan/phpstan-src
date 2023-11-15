# PHPStan - PHP Static Analysis Tool

[![Build](https://github.com/phpstan/phpstan-src/workflows/Tests/badge.svg)](https://github.com/phpstan/phpstan-src/actions)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

---

This repository (`phpstan/phpstan-src`) is for PHPStan's development only. Head to [`phpstan/phpstan`](https://github.com/phpstan/phpstan) for the main README, or to [create an issue](https://github.com/phpstan/phpstan/issues/new/choose).

## Contributing

Any contributions are welcome.

### Installation

```bash
composer install
```

If you encounter dependency problem, try using `export COMPOSER_ROOT_VERSION=1.11.x-dev`

If you are using macOS and are using an older version of `patch`, you may have problems with patch application failure during `composer install`. Try using `brew install gpatch` to install a newer and supported `patch` version.

### Building

PHPStan's source code is developed on PHP 8.1. For distribution in `phpstan/phpstan` package and as a PHAR file, the source code is transformed to run on PHP 7.2 and higher.

Initially you need to run `composer install` in case you aren't working in a directory which was built before.

Afterwards you can either run the whole build including linting and coding standards using

```bash
make
```

### Running development version

You can also choose to run only part of the build. To analyse PHPStan by PHPStan itself, run:

```bash
make phpstan
```

### Fixing code style

To detect code style issues, run:

```bash
make cs
```

And then to fix code style, run:

```bash
make cs-fix
```

### Running tests

Run:
```bash
make tests
```

### Debugging

1. Make sure XDebug is installed and configured.
2. Add `--xdebug` option when running PHPStan. Without it PHPStan turns the debugger off at runtime.
3. If you're not debugging the [result cache](https://phpstan.org/user-guide/result-cache), also add the `--debug` option.

## Code of Conduct

This project adheres to a [Contributor Code of Conduct](https://github.com/phpstan/phpstan/blob/master/CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold this code.
