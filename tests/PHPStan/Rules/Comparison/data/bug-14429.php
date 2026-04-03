<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14429;

function throw_if(bool $condition, string $message): void
{
	if ($condition) { throw new \Exception($message); }
}

class Foo
{
    /**
     * @param \ArrayObject<string, string> $stringMap
     * @param \ArrayObject<string, int> $intKeyMap
     */
    public function __construct(
        public \ArrayObject $stringMap,
        public \ArrayObject $intKeyMap,
    ) {
        foreach ($stringMap as $stringMapValue) {
            throw_if(!is_string($stringMapValue), 'stringMap value must be string');
        }
        foreach ($intKeyMap as $intKeyMapKey => $intKeyMapValue) {
            throw_if(!is_int($intKeyMapValue), 'intKeyMap value must be int');
        }
    }
}
