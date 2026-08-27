<?php declare(strict_types = 1);

// The shape of Composer's ClassLoader: register() + loadClass() reading a file.
final class E2eNestedClassLoader
{

	public function register(): void
	{
		spl_autoload_register([$this, 'loadClass']);
	}

	public function loadClass(string $class): void
	{
		if ($class !== 'E2eDepInternal\\SomeInterface') {
			return;
		}

		require __DIR__ . '/SomeInterface.php';
	}

}
