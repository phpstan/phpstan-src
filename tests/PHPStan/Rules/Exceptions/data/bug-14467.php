<?php // lint >= 8.3

// FQN must sort after \DateTime so methods[0] resolves to DateTime.
namespace TestBug14467;

class Foo
{

	/** @throws \DateMalformedStringException */
	public function modify(string $s): self
	{
		return $this;
	}

}

class Bar
{

	/** @param \DateTime|Foo $obj */
	public function caseDateTimeFirst($obj): void
	{
		try {
			$obj->modify('+1 day');
		} catch (\DateMalformedStringException $e) {
		}
	}

	/** @param Foo|\DateTime $obj */
	public function caseFooFirst($obj): void
	{
		try {
			$obj->modify('+1 day');
		} catch (\DateMalformedStringException $e) {
		}
	}

}
