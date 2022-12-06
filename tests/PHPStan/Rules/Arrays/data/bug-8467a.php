<?php declare(strict_types = 1);

namespace Bug8467a;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type AutoloadRules array{psr-0?: array<string, string|string[]>, psr-4?: array<string, string|string[]>, classmap?: list<string>, files?: list<string>, exclude-from-classmap?: list<string>}
 */
interface CompletePackageInterface {
	/**
	 * Returns an associative array of autoloading rules
	 *
	 * {"<type>": {"<namespace": "<directory>"}}
	 *
	 * Type is either "psr-4", "psr-0", "classmap" or "files". Namespaces are mapped to
	 * directories for autoloading using the type specified.
	 *
	 * @return array Mapping of autoloading rules
	 * @phpstan-return AutoloadRules
	 */
	public function getAutoload(): array;
}

class Test {
	public function foo (CompletePackageInterface $package): void {
		if (\count($package->getAutoload()) > 0) {
			$autoloadConfig = $package->getAutoload();
			foreach ($autoloadConfig as $type => $autoloads) {
				assertType('array<int<0, max>|string, array<string>|string>', $autoloadConfig[$type]);
				if ($type === 'psr-0' || $type === 'psr-4') {

				} elseif ($type === 'classmap') {
					assertType('list<string>', $autoloadConfig[$type]);
					implode(', ', $autoloadConfig[$type]);
				}
			}
		}
	}
}
