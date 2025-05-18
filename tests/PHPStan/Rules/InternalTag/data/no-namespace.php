<?php

class ClassInternal {
	/**
	 * @internal
	 */
	public const INTERNAL_CONSTANT = 'xxx';

	/**
	 * @internal
	 */
	public function internalMethod(): void
	{

	}

	/**
	 * @internal
	 */
	public static function internalStaticMethod(): void
	{

	}

	protected function getFoo(): string
	{
		$this->internalMethod();
		self::internalStaticMethod();

		return self::INTERNAL_CONSTANT;
	}
}

class ClassAccessOnInternal {
	protected function getFoo(): string
	{
		$classInternal = new ClassInternal();
		$classInternal->internalMethod();
		ClassInternal::internalStaticMethod();

		return ClassInternal::INTERNAL_CONSTANT;
	}
}
