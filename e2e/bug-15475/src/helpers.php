<?php

declare(strict_types=1);

interface Inbox
{
    /** @return list<string> */
    public function search(string $words): array;
}

/**
 * @param Closure(string): list<string> $search
 */
function inbox(Closure $search): Inbox
{
    return new readonly class($search) implements Inbox
    {
        /**
         * @param Closure(string): list<string> $search
         */
        public function __construct(private Closure $search) {}

        public function search(string $words): array
        {
            return ($this->search)($words);
        }
    };
}
