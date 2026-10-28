<?php declare(strict_types = 1);

namespace IntlBreakIteratorFactories;

use function PHPStan\Testing\assertType;

class Foo
{

	public function factories(string $locale): void
	{
		assertType('IntlBreakIterator|null', \IntlBreakIterator::createCharacterInstance($locale));
		assertType('IntlCodePointBreakIterator', \IntlBreakIterator::createCodePointInstance());
		assertType('IntlBreakIterator|null', \IntlBreakIterator::createLineInstance($locale));
		assertType('IntlBreakIterator|null', \IntlBreakIterator::createSentenceInstance($locale));
		assertType('IntlBreakIterator|null', \IntlBreakIterator::createTitleInstance($locale));
		assertType('IntlBreakIterator|null', \IntlBreakIterator::createWordInstance($locale));
	}

}
