<?php // lint >= 8.1

namespace Bug15151;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $val
 * @return (T is array ? T : null)
 */
function validate_array(mixed $val): ?array {
    return \is_array($val) ? $val : null;
}

class reception_formulaire {

    // @phpstan-ignore missingType.iterableValue
    final function __construct(
        protected readonly array $params
    ) { }

    public function foo(): void {
        foreach (validate_array($this->params['Genre_Admis'] ?? null) ?? [ ] as $_) {
            assertType('mixed', $_);
        }
    }

}
