<?php // lint >= 7.4

namespace Bug6243;

class Foo
{
	/** @var list<string>|(\ArrayAccess<int, string>&iterable<int, string>) */
	private iterable $values;

	/**
	 * @param list<string> $values
	 */
	public function update(array $values): void {
	    foreach ($this->values as $key => $_) {
			unset($this->values[$key]);
		}

	    foreach ($values as $value) {
			$this->values[] = $value;
		}
	}
}
