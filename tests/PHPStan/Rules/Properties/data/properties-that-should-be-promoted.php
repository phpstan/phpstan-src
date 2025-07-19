<?php

namespace PropertiesThatShouldBePromoted;

class PromotedProperties
{
    public string $name;

    /** @var list<string> */
    public array $tags;

    /** @var array<string, mixed> */
    public array $options;

    public int $count;

    public int $foo;

    public function __construct(
        int $count,
        ?string $name,
        public ?string $email,
        /** @var array<int, string> */
        array $tags,
        /** @var array<string, mixed> */
        array $options,
        int $bar,
    ) {
        $this->count = $count;

        $tags          = array_values($tags);
        $this->tags    = $tags;
        $this->options = array_filter($options);
        $this->email ??= 'example@example.com';
        $this->name = $name ?? 'Default Name';
        $this->foo  = $bar;
    }
}
