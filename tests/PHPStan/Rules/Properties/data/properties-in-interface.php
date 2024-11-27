<?php declare(strict_types=1);

namespace PropertiesInInterface;

interface HelloWorld
{
	public string $name { get; }

    public \DateTimeInterface $dateTime;

    public static \Closure $callable;
}
