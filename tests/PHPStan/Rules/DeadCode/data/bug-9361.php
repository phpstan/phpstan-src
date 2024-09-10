<?php

namespace Bug9361;

class Option
{
	/**
	 * @var mixed
	 */
	private $Bound;

	/**
	 * @param mixed $var
	 * @return $this
	 */
	public function bind(&$var)
	{
		$this->Bound = &$var;

		return $this;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function setValue($value)
	{
		if ($this->Bound !== $value) {
			$this->Bound = $value;
		}

		return $this;
	}
}

class Command
{
	/**
	 * @var mixed
	 */
	private $Value;

	/**
	 * @return Option[]
	 */
	public function getOptions()
	{
		return [
			(new Option())->bind($this->Value),
		];
	}

	public function run(): void
	{
		$value = $this->Value;
	}
}
