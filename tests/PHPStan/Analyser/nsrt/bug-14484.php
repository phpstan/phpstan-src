<?php // lint >= 8.0

namespace Bug14484;

use function PHPStan\Testing\assertType;

class A {}
class B {}
class C {}
class D {}

class Bug
{
	/**
	 * @return int|bool|string|A|B|C|D|list<A>|null
	 */
	public function getValue(): mixed
	{
		return null;
	}

	public function test(): void
	{
		$value = $this->getValue();
		if (!is_string($value)) {
			return;
		}
		assertType('string', $value);
	}

}

class Bug2
{
	/**
	 * @return int|bool|string|A|B|C|D|list<A>|null
	 */
	public function getValue(): mixed
	{
		return null;
	}

	public function testInstanceof(): void
	{
		$value = $this->getValue();
		if (!($value instanceof A)) {
			return;
		}
		// Expected: narrowed to A
		// Actual in 2.1.49: entire union reported (narrowing lost)
		assertType(A::class, $value);
	}

	public function testIfElseifInstanceof(): void
	{
		$value = $this->getValue();
		if ($value === null) {
			return;
		}
		if ($value instanceof A) {
			assertType(A::class, $value);
		} elseif ($value instanceof B) {
			assertType(B::class, $value);
		} elseif (is_array($value)) {
			assertType('list<Bug14484\\A>', $value);
		} elseif (is_string($value)) {
			assertType('string', $value);
		}
	}
}
