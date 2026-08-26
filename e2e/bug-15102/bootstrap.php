<?php declare(strict_types = 1);

// The shape typo3/class-alias-loader creates: the project's own Composer loader is taken
// out of the spl_autoload queue and a wrapper takes its place, resolving legacy names
// through class_alias() and delegating everything else. The wrapper is not a ClassLoader
// instance, so the project has no Composer entry left in the queue.
$composerLoader = require __DIR__ . '/vendor/autoload.php';
spl_autoload_unregister([$composerLoader, 'loadClass']);

final class AliasLoader
{

	public function __construct(private Composer\Autoload\ClassLoader $wrapped)
	{
	}

	public function loadClass(string $class): void
	{
		if ($class === 'Legacy\\Validate') {
			class_alias(Modern\Validate::class, 'Legacy\\Validate');

			return;
		}

		$this->wrapped->loadClass($class);
	}

}

spl_autoload_register([new AliasLoader($composerLoader), 'loadClass']);
