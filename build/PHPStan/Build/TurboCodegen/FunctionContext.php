<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use function implode;
use function sprintf;
use function str_repeat;

/** The C++ body being emitted for one PHP function: its lines, temporaries and local variables. */
final class FunctionContext
{

	/** @var list<string> */
	private array $lines = [];

	private int $depth = 1;

	private int $temps = 0;

	/** @var array<string, LocalVariable> */
	public array $locals = [];

	/**
	 * @param string $fail the C++ statement that propagates a pending exception
	 * @param string|null $self the C++ `zend_object *` of $this
	 */
	public function __construct(
		public readonly string $label,
		public string $fail,
		public readonly ?string $self,
		public readonly ?ReturnInfo $return,
		public readonly bool $isClosure = false,
	)
	{
	}

	public function line(string $line): void
	{
		$this->lines[] = str_repeat("\t", $this->depth) . $line;
	}

	public function open(string $line): void
	{
		$this->line($line . ' {');
		$this->depth++;
	}

	public function close(string $suffix = ''): void
	{
		$this->depth--;
		$this->line('}' . $suffix);
	}

	public function temp(string $prefix = 't'): string
	{
		return sprintf('%s%d', $prefix, ++$this->temps);
	}

	/** propagates a pending exception when the C++ condition holds */
	public function failIf(string $condition): void
	{
		$this->line(sprintf('if (UNEXPECTED(%s)) %s', $condition, $this->fail));
	}

	public function render(): string
	{
		return implode("\n", $this->lines);
	}

}
