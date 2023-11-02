<?php

namespace Bug6922b;

final class Configuration
{
	public function isFirstOptionActive(): bool
	{
		return true;
	}

	public function isSecondOptionActive(): bool
	{
		return true;
	}
}

function run(?Configuration $configuration): void
{
	if (
		$configuration?->isFirstOptionActive() === false ||
		$configuration?->isSecondOptionActive() === false)
	{
		// ....
	}
}
