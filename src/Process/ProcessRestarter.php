<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Composer\XdebugHandler\XdebugHandler;
use function count;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use const PHP_EOL;

// see https://github.com/composer/xdebug-handler/issues/174
final class ProcessRestarter extends XdebugHandler
{

	/**
	 * @var array<string>
	 */
	private array $extensions = [];

	#[\Override]
	protected function requiresRestart(bool $default): bool
	{
		// pcov has a perf impact similar to xdebug, so we want to disable it
		// tideways seem to hook deep enough in the php engine, so it can segfault when running phpstan with turbo.
		foreach (['pcov', 'tideways'] as $extension) {
			if (!extension_loaded($extension)) {
				continue;
			}

			$this->extensions[] = $extension;
		}

		return $default || count($this->extensions) > 0;
	}

	#[\Override]
	protected function restart(array $command): void
	{
		if (count($this->extensions) > 0) {
			$content = file_get_contents((string) $this->tmpIni);

			if ($content !== false) {
				foreach ($this->extensions as $extension) {
					$regex = '/^\s*(zend_extension\s*=.*' . $extension . '.*)$/mi';
					$content = preg_replace($regex, ';$1', $content) . PHP_EOL;
				}
				file_put_contents((string) $this->tmpIni, $content);
			}
		}

		parent::restart($command);
	}

}
