<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug13431;

use Ds\Set;

readonly class ShortStepWithElements
{
    /**
     * @var Set<string>
     */
    public Set $elementHashes;

    /**
     * @param Set<string> $elements
     */
    public function __construct(public Set $elements) {
        $this->elementHashes = $this->elements->map(
			fn (string $element): string => hash('sha256', $element),
		);
    }
}
