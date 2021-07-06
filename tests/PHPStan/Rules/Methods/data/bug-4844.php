<?php

namespace Bug4844;

class HelloWorld
{
	/**
	 * Updates a attribute and saves the record.
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return bool
	 */
	public function update_attribute($name, $value = '')
	{
		return $this->update_attributes([
			$name => $value,
		]);
	}

	/**
	 * Updates as an array given attributes and saves the record.
	 *
	 * @param non-empty-array<string, mixed> $attributes
	 *
	 * @return bool
	 */
	public function update_attributes($attributes)
	{
		return true;
	}
}
