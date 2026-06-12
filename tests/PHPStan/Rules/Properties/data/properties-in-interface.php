<?php declare(strict_types=1); // lint >= 8.4

namespace PropertiesInInterface;

interface HelloWorld
{
	public string $name { get; }

    public \DateTimeInterface $dateTime;

    public static \Closure $callable;

    public final \DateTime $finalProperty;
}
