<?php declare(strict_types = 1);

namespace Bug13043;

class HelloWorld
{
	private string $prefix = '';

	/** @var non-empty-string $name */
	private string $name = 'identifier';

	/** @return non-empty-string */
	public function getPrefixedName(): string
	{
		$name = $this->prefix;

		$search = str_split('()<>@');
		$replace = array_map(rawurlencode(...), $search);

		$name .= str_replace($search, $replace, $this->name);

		return $name;
	}
}
