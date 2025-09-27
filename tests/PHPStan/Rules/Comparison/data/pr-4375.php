<?php

namespace PR4375;

final class Foo
{
	public function processNode(): array
	{
		$methods = [];
		foreach ($this->get() as $collected) {
			foreach ($collected as [$className, $methodName, $classDisplayName]) {
				$className = strtolower($className);

				if (!array_key_exists($className, $methods)) {
					$methods[$className] = [];
				}
				$methods[$className][strtolower($methodName)] = $classDisplayName . '::' . $methodName;
			}
		}

		return [];
	}

	private function get(): array {
		return [];
	}
}
