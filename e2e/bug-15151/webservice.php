<?php

namespace MyApp;

class webservice {

    /**
     * @param array{limit_defaut: positive-int, limit_max: positive-int} $opt
     * @return array{positive-int, non-negative-int, list<non-empty-string>}
     * @phpstan-ignore missingType.iterableValue (a phpstan error seems useful in order to trigger the bug)
     */
    public static function get_limit_offset_sort(array $par, array $opt): array {
        $limit = isset($par['limit']) ? (int) $par['limit'] : $opt['limit_defaut'];
        if ($limit < 1)
            $limit = 1;
        if ($limit > $opt['limit_max'])
            $limit = $opt['limit_max'];

        $offset = isset($par['offset']) ? (int) $par['offset'] : 0;
        if ($offset < 0)
            $offset = 0;

        $sort = [ ];
        foreach (validate_array($par['sort'] ?? null) ?? [ ] as $_) {
            \PHPStan\dumpType($_); // ### should be: mixed, sometimes incorrectly: array
            $_ = validate_string($_);
            if ($_ !== null && $_ !== '')
                $sort[] = $_;
        }

        return [ $limit, $offset, $sort ];

    }


}
