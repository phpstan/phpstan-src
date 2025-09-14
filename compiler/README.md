# PHAR Compiler for PHPStan

## Compile the PHAR

```bash
composer install
php bin/prepare
cd build
php ../box/vendor/bin/box compile --no-parallel
```

The compiled PHAR will be in `tmp/phpstan.phar`.

Please note that running the compiler will change the contents of `composer.json` file and `vendor` directory. Revert those changes after running it.
