PHPStan Turbo extension
===========

**Highly experimental work-in-progress.**

This extension can be used to rewrite parts of PHPStan into native PHP extension using the [Zephir language](https://zephir-lang.com/en).

Requirements
-----------

Check out Zephir's [Installation Guide](https://docs.zephir-lang.com/latest/installation/#prerequisites) guide, especially the [Zephir Parser extension](https://github.com/zephir-lang/php-zephir-parser) installation so that your developer environment can compile code written in .zep files.

Compiling the extension
-----------

```bash
cd turbo-ext/phpstan_turbo
../vendor/bin/zephir generate && ../vendor/bin/zephir compile
```

Enabling the extension
------------

Once you have compiled the extension, you should have this file: `turbo-ext/phpstan_turbo/ext/modules/phpstanturbo.so`.

Put the absolute path to it to your php.ini like this:

```
extension=/home/john/dev/phpstan-src/turbo-ext/phpstan_turbo/ext/modules/phpstanturbo.so
```

Once the extension is loaded, PHPStan will use the extension automatically thanks to the `PHPStan\Turbo\TurboExtensionEnabler` class.
