<?php declare(strict_types = 1);

namespace Bug4173;

class HelloWorld
{
	protected function _sub_render_condition($row)
	{
		if (count($row) === 3) {
			[$field, $cond, $value] = $row;
		} elseif (count($row) === 2) {
			[$field, $cond] = $row;
		} elseif (count($row) === 1) {
			[$field] = $row;
		} else {
			throw new \InvalidArgumentException();
		}

		$field = trim($field);

		if (count($row) === 1) {
			return $field;
		}

		if (count($row) === 2) {
			$value = $cond; // $cond always set, "count($row) === 1" never true here
		}


		if ($value === null) { // $value always set

		}
	}
}
