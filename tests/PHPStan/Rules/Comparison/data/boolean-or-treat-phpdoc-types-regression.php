<?php

namespace BooleanOrTreatPhpDocTypesAsCertainRegression;

class Foo
{

	/** @var int */
	protected $index = 0;

	/** @var string[][] */
	protected $data = [
		0 => ['type' => 'id', 'value' => 'foo'],
		1 => ['type' => 'special', 'value' => '.'],
		2 => ['type' => 'id', 'value' => 'bar'],
		3 => ['type' => 'special', 'value' => ';'],
	];

	protected function next(): void
	{
		$this->index = $this->index + 1;
	}

	protected function check(string $type, ?string $value = null): bool {
		return ($this->type() === $type) && (($value === null) || ($this->value() === $value));
	}

	protected function type(): string
	{
		return $this->data[$this->index]['type'];
	}

	protected function value(): string
	{
		return $this->data[$this->index]['value'];
	}

	public function separatedName(): string
	{
		$name = '';
		$previousType = null;
		$separator = '.';

		$currentValue = $this->value();

		while ((($this->check('special', $separator)) || ($this->check('id'))) &&
			(($previousType === null) || ($this->type() !== $previousType)) &&
			(($previousType !== null) || ($currentValue !== $separator))
		) {
			$name .= $currentValue;
			$previousType = $this->type();

			$this->next();
			$currentValue = $this->value();
		}

		if (($previousType === null) || ($previousType !== 'id')) {
			throw new \RuntimeException();
		}

		return $name;
	}

}
