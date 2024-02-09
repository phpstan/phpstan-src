<?php declare(strict_types=1);

namespace ForbiddenInterfaceInClassImplements;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use EmptyIterator;
use Error;
use Exception;
use IteratorAggregate;
use ReturnTypeWillChange;
use Throwable;
use Traversable;

class Traverser implements Traversable
{

    public function iterator(): Traversable
    {
        return new class implements Traversable {};
    }

}

class Iterate implements IteratorAggregate
{

    public function getIterator(): Traversable
    {
        return new EmptyIterator;
    }

}

class MyException implements Throwable
{

    public function getMessage(): string
    {
        return '';
    }

    public function getCode(): int
    {
        return 1;
    }

    public function getFile(): string
    {
        return __FILE__;
    }

    public function getLine(): int
    {
        return __LINE__;
    }

    public function getTrace(): array
    {
        return [];
    }

    public function getTraceAsString(): string
    {
        return '';
    }

    public function getPrevious(): ?Throwable
    {
        return null;
    }

    public function __toString(): string
    {
        return self::class;
    }

}

class ChildException extends Exception
{
}

class ChildError extends Error
{
}

class MyDateTime implements DateTimeInterface
{

    public function diff(DateTimeInterface $targetObject, bool $absolute = false): DateInterval
    {
        return new DateInterval('1 day');
    }

    public function format(string $format): string
    {
        return '';
    }

    public function getOffset(): int
    {
        return 1;
    }

    public function getTimestamp(): int
    {
        return time();
    }

    /**
     * @return DateTimeZone|false
     */
    #[ReturnTypeWillChange]
    public function getTimezone()
    {
        return new DateTimeZone('UTC');
    }

    public function __wakeup(): void
    {
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
    }

}

class ChildDate extends DateTime
{
}

class ChildDateImmutable extends DateTimeImmutable
{
}
