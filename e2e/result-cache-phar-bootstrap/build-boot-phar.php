<?php declare(strict_types = 1);

// Builds the phar whose bootstrap file phpstan.neon registers. PHPStan itself ships its runtime
// stubs as bootstrapFiles inside phpstan.phar, so a phar:// bootstrap path is the normal case for
// a phar install - this scenario reproduces it without needing a compiled phpstan.phar.

$pharPath = __DIR__ . '/boot.phar';
@unlink($pharPath);

$phar = new Phar($pharPath, 0, 'boot.phar');
$phar->addFromString('boot.php', "<?php declare(strict_types = 1);\n");
$phar->setStub($phar->createDefaultStub('boot.php'));
