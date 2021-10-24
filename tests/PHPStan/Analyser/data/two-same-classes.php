<?php

namespace TwoSame;

class Foo
{

	/** @var string */
	private $prop = 1;

	public function doFoo(): void
	{
		echo self::FOO_CONST;
	}

	/**
	 * @return string
	 */
	public function getProp()
	{
		return $this->prop;
	}

}

if (rand(0, 0)) {
	class Foo
	{

		private const FOO_CONST = 'foo';

		/** @var int */
		private $prop = 'str';

		/** @var int */
		private $prop2 = 'str';

		public function doFoo(): void
		{
			echo self::FOO_CONST;
		}

		/**
		 * @return int
		 */
		public function getProp()
		{
			return $this->prop;
		}

		/**
		 * @param int $prop
		 */
		public function setProp($prop): void
		{
			$this->prop = $prop;
		}

		/**
		 * @return int
		 */
		public function getProp2()
		{
			return $this->prop2;
		}

		/**
		 * @param int $prop2
		 */
		public function setProp2($prop2): void
		{
			$this->prop2 = $prop2;
		}

	}
}
