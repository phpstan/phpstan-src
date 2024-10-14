<?php declare(strict_types = 1);

namespace Bug11663;

class BuilderA
{
	/**
	 * @param string $test
	 * @return $this
	 */
	public function where(string $test)
	{
		return $this;
	}
}

class BuilderB
{
	/**
	 * @param string $test
	 * @return $this
	 */
	public function where(string $test)
	{
		return $this;
	}
}


class Test
{
	/**
	 * @template B of BuilderA|BuilderB
	 * @param B $template
	 * @return B
	 */
	public function test($template)
	{
		return $template->where('test');
	}
}
