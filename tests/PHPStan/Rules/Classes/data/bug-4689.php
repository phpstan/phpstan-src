<?php

namespace Bug4689;

abstract class KeywordList
{
}

abstract class AbstractPlatform
{
	/**
	 * @return KeywordList
	 */
	final public function getReservedKeywordsList()
	{
		$class    = $this->getReservedKeywordsClass();
		$keywords = new $class();
		if (! $keywords instanceof KeywordList) {
			throw new \Exception();
		}

		return $keywords;
	}

	/**
	 * @throws \Exception If not supported on this platform.
	 *
	 * @psalm-return class-string<KeywordList>
	 */
	protected function getReservedKeywordsClass(): string
	{
		throw new \Exception();
	}
}
