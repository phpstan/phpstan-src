<?php declare(strict_types=1);

namespace StaticHookedPropertyInInterface;

interface HelloWorld
{
	public static string $firstName { get; set; }

	public static string $middleName { get; }

	public static string $lastName { set; }
}
