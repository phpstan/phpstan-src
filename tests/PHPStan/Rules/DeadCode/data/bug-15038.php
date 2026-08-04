<?php // lint >= 8.4

namespace Bug15038;

enum Counter {
    case A;
    case B;
}

class Statistics {
    public private(set) int $counterA = 0;
    public private(set) int $counterB = 0;

    public function inc(Counter $counter): void {
        match ($counter) {
            Counter::A => $this->counterA++,
            Counter::B => $this->counterB++,
        };
    }
}

$s = new Statistics();
$s->inc(Counter::A);
$s->inc(Counter::A);
$s->inc(Counter::A);

var_dump($s->counterA);

class MoreVirtualAssigns
{

	public int $a = 0;

	public int $b = 0;

	/** @var array{a: int, b: int} */
	public array $arr = ['a' => 0, 'b' => 0];

	public function postDec(Counter $counter): void
	{
		match ($counter) {
			Counter::A => $this->a--,
			Counter::B => $this->b--,
		};
	}

	public function preIncDec(Counter $counter): void
	{
		match ($counter) {
			Counter::A => ++$this->a,
			Counter::B => --$this->b,
		};
	}

	public function offset(Counter $counter): void
	{
		match ($counter) {
			Counter::A => $this->arr['a']++,
			Counter::B => $this->arr['b']--,
		};
	}

	public function nestedMatch(Counter $counter): void
	{
		match ($counter) {
			Counter::A => match (true) {
				default => $this->a++,
			},
			Counter::B => $this->b++,
		};
	}

	public function booleanOperators(bool $cond): void
	{
		$cond && ($this->a++ > 0);
		$cond || ($this->b-- > 0);
	}

	public function arrayLiteral(): void
	{
		[$this->a++];
	}

}
