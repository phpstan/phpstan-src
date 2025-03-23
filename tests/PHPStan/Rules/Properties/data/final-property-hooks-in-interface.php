<?php declare(strict_types=1);

namespace FinalPropertyHooksInInterface;

interface HelloWorld
{
	public final string $firstName { get; set; }

	public final string $middleName { get; }

	public final string $lastName { set; }

	public string $finalGetHook {
		final get;
	}

	public string $finalSetHook {
		final set;
	}
}
