<?php declare(strict_types = 1);

namespace Bug7806;

class TestMethod {
	/**
	 * @param array<string>|null $reasons
	 * @throws \Exception
	 */
	function check(array &$reasons = null): void {
		$fileName = time() % 2 ? "abc":null;
		if (!$fileName) {
			$reasons[] = sprintf("Dependency check fail");
			throw new \Exception("check failed");
		}
	}

	function test():void {
		try {
			$this->check($reasons);
			printf("ok\n");
		} catch (\Exception $e) {
			if (!empty($reasons)) {
				$e = new \Exception("Dependency check failed: " . implode(', ', $reasons), 0, $e);
			}
			throw new \Exception("Failed", 0, $e);
		}
	}
}

/**
 * @param array<string>|null $reasons
 * @throws \Exception
 */
function check1(array &$reasons = null): void {
	$fileName = time() % 2 ? "abc":null;
	if (!$fileName) {
		$reasons[] = sprintf("Dependency check fail");
		throw new \Exception("check failed");
	}
}

function test1():void {
	try {
		check1($reasons);
		printf("ok\n");
	} catch (\Exception $e) {
		if (!empty($reasons)) {
			$e = new \Exception("Dependency check failed: " . implode(', ', $reasons), 0, $e);
		}
		throw new \Exception("Failed", 0, $e);
	}
}

