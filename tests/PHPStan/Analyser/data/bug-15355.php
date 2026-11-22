<?php declare(strict_types = 1);

namespace Bug15355;

final class Configuration
{

	/** @var list<string> */
	private array $ignoreDeprecationPatterns = [];

	public function __construct(string $ignoreFile)
	{
		if ($ignoreFile) {
			if (!is_file($ignoreFile)) {
				throw new \InvalidArgumentException(\sprintf('The ignoreFile "%s" does not exist.', $ignoreFile));
			}
			set_error_handler(static function ($t, $m) use ($ignoreFile, &$line) {
				throw new \RuntimeException(\sprintf('Invalid pattern found in "%s" on line "%d"', $ignoreFile, 1 + $line).substr($m, 12));
			});
			try {
				foreach (file($ignoreFile) as $line => $pattern) {
					if ('#' !== (trim($pattern)[0] ?? '#')) {
						preg_match($pattern, '');
						$this->ignoreDeprecationPatterns[] = $pattern;
					}
				}
			} finally {
				restore_error_handler();
			}
		}
	}

}
