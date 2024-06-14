<?php declare(strict_types = 1);

namespace Bug8467b;

use function PHPStan\Testing\assertType;

class Test {
	public function foo (?string $cwd, bool $initialClone = false): void {
		if ($initialClone) {
			$origCwd = $cwd;
			$cwd = null;
		}

		if ($initialClone && isset($origCwd)) {
			assertType('string', $origCwd);
			assertType('string|null', $cwd); // could be null
		}
	}

	/**
	 * @param mixed[]|null $mirrors
	 *
	 * @return list<non-empty-string>
	 *
	 * @phpstan-param list<array{url: non-empty-string, preferred: bool}>|null $mirrors
	 */
	protected function getUrls(?string $url, ?array $mirrors, ?string $ref, ?string $type, string $urlType): array
	{
		if (!$url) {
			return [];
		}

		if ($urlType === 'dist' && false !== strpos($url, '%')) {
			assertType('string|null', $type);
			$url = 'test';
		}
		assertType('non-falsy-string', $url);

		$urls = [$url];
		if ($mirrors) {
			foreach ($mirrors as $mirror) {
				if ($urlType === 'dist') {
					assertType('string|null', $type);
				} elseif ($urlType === 'source' && $type === 'git') {
					assertType("'git'", $type);
				} elseif ($urlType === 'source' && $type === 'hg') {
					assertType("'hg'", $type);
				} else {
					continue;
				}
			}
		}

		return $urls;
	}
}
