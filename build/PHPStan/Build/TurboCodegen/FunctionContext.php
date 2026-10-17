<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use function array_pop;
use function count;
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

	/** @var array<string, true> variables nothing in the function writes after their initialization */
	public array $stableVars = [];

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

	/**
	 * The enclosing loops and switches, innermost last: their break and
	 * continue labels and whether a jump used them.
	 *
	 * @var list<array{break: string, continue: string, switch: bool, breakUsed: bool, continueUsed: bool}>
	 */
	private array $loops = [];

	public function pushLoop(bool $switch): void
	{
		$this->loops[] = ['break' => $this->temp('brk'), 'continue' => $this->temp('cont'), 'switch' => $switch, 'breakUsed' => false, 'continueUsed' => false];
	}

	/** @return array{break: string, continue: string, switch: bool, breakUsed: bool, continueUsed: bool} */
	public function popLoop(): array
	{
		$loop = array_pop($this->loops);
		if ($loop === null) {
			throw new CompileError('loop stack underflow');
		}
		return $loop;
	}

	/** the jump of `break N` / `continue N` */
	public function jump(bool $continue, int $levels): string
	{
		$index = count($this->loops) - $levels;
		if ($index < 0 || !isset($this->loops[$index])) {
			throw new CompileError(sprintf('%s %d outside enough loops', $continue ? 'continue' : 'break', $levels));
		}
		// `continue` targeting a switch acts like `break`
		if ($continue && !$this->loops[$index]['switch']) {
			$this->loops[$index]['continueUsed'] = true;
			return sprintf('goto %s;', $this->loops[$index]['continue']);
		}
		$this->loops[$index]['breakUsed'] = true;
		return sprintf('goto %s;', $this->loops[$index]['break']);
	}

	/** @var list<callable(): void> write-backs of by-reference arguments, run after the call they belong to */
	public array $writeBacks = [];

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
