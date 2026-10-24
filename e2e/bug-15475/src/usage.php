<?php

declare(strict_types=1);

function usage(): void
{
    inbox(static fn (string $words): array => [$words])->search('a b');
}
