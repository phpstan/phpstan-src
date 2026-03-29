<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13853;

abstract class BaseReportLocator
{
    private readonly string $report;

    public function __construct(
        private readonly string $defaultPathname,
    ) {
    }

    final public function locate(): string
    {
        if (isset($this->report)) {
            return $this->report;
        }

        $this->report = is_file($this->defaultPathname)
            ? $this->defaultPathname
            : $this->lookup();

        return $this->report;
    }

	public abstract function lookup(): string;
}

class AnotherExample
{
	private readonly int $value;

	public function getValue(): int
	{
		if (!isset($this->value)) {
			$this->value = $this->compute();
		}

		return $this->value;
	}

	private function compute(): int
	{
		return 42;
	}
}

class NoIssetGuard
{
	private readonly string $prop;

	public function setProp(): void
	{
		// no isset guard - should still report error
		$this->prop = 'foo';
	}
}
