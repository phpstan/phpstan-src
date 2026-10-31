<?php declare(strict_types = 1);

namespace Bug15006;

class base {
    /**
     * @param ?non-negative-int $field
     * @return ($field is null ? list<?string> : array<?string>)
     */
    public function bar(?int $field = null): array {
        return [ ];
    }
}

class child extends base {
    /**
     * @param ?non-negative-int $field
     * @return ($field is null
     *   ? ($nullable is true ? ($numeric is true ? list<?numeric-string> : list<?string>) : ($numeric is true ? list<numeric-string> : list<string>))
     *   : ($nullable is true ? ($numeric is true ? array<?numeric-string> : array<?string>) : ($numeric is true ? array<numeric-string> : array<string>))
     * )
     */
    #[\Override]
    public function bar(?int $field = null, bool $nullable = true, bool $numeric = false): array {
        return [ ];
    }
}
