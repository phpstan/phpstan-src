<?php declare(strict_types = 1);

namespace Bug12246;

final class SkipFirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
		do {

		} while ($this->textElement(...));
	}

	public function textElement(): int
	{
		return 1;
	}
}
