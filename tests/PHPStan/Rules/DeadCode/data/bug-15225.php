<?php declare(strict_types = 1);

namespace Bug15225;

final class ScopeGuard
{
        public function __construct()
        {
                echo "enter\n";
        }

        public function __destruct()
        {
                echo "leave\n";
        }
}

function runWithGuard(): void
{
        $guard = new ScopeGuard();
        echo "work\n";
}

runWithGuard();
