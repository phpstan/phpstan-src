<?php declare(strict_types = 1);

namespace Bug5946;

class Model
{
	/**
	 * @return static
	 */
	public function getParent()
	{
		return new static();
	}

	/**
	 * @return $this
	 */
	public function getModel(bool $useParent)
	{
		if ($useParent) {
			return $this->getParent()->getModel(false); // error - returns static not $this
		} elseif (mt_rand() === 0) {
			return $this->getParent(); // error - returns static not $this
		}

		return $this;
	}
}
