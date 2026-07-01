<?php declare(strict_types = 1);

namespace Bug14891;

use function PHPStan\Testing\assertType;

function test(string $a, string $b): void {
	if ($a === $b && $a !== "" && $b !== "") {
		return;
	}

	if ($a !== "" || $b !== "") {
		if ($a !== "") {
			// ...
		}
		if ($b !== "") {
			// The empty sibling `if ($a !== "")` above used to leave behind an
			// unsound conditional-expression holder ("if $a is non-empty-string
			// then $b is ''"), which narrowed $a to '' here.
			assertType('string', $a);
			if ($a !== "") {
				assertType('non-empty-string', $a);
			}
		}
	}
}

// Same defect through a loose `==` relational antecedent.
function testLooseEqual(string $a, string $b): void {
	if ($a == $b && $a !== "" && $b !== "") {
		return;
	}

	if ($a !== "" || $b !== "") {
		if ($a !== "") {
			// ...
		}
		if ($b !== "") {
			assertType('string', $a);
			if ($a !== "") {
				assertType('non-empty-string', $a);
			}
		}
	}
}

// Same defect for a relation between two property fetches.
class Props
{
	public string $a;
	public string $b;

	public function test(): void
	{
		if ($this->a === $this->b && $this->a !== "" && $this->b !== "") {
			return;
		}

		if ($this->a !== "" || $this->b !== "") {
			if ($this->a !== "") {
				// ...
			}
			if ($this->b !== "") {
				assertType('string', $this->a);
			}
		}
	}
}

// Same defect for a relation between two integers, where the arms narrow to
// integer ranges instead of empty/non-empty strings.
function testIntRange(int $a, int $b): void {
	if ($a === $b && $a > 0 && $b > 0) {
		return;
	}

	if ($a > 0 || $b > 0) {
		if ($a > 0) {
			// ...
		}
		if ($b > 0) {
			assertType('int', $a);
		}
	}
}
