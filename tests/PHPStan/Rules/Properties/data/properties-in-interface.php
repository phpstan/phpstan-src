<?php declare(strict_types=1);

namespace PropertiesInInterface;

interface HelloWorld
{
    public \DateTimeInterface $dateTime;

    public static \Closure $callable;
}
