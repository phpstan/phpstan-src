<?php declare(strict_types = 1);

namespace Bug7924;

class HelloWorld
{
    /**
     * @param string|array<int, string>|array<string, 'asc'|'desc'> $field
     * @param 'asc'|'desc' $direction
     *
     * @return $this
     */
    public function setOrder($field, string $direction = 'asc')
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
				\PHPStan\dumpType($v);

                if (is_int($k)) {
                    // format "field"
                    $this->setOrder($v);
                } else {
                    // format "field" => direction
                    $this->setOrder($k, $v);
                }
            }

            return $this;
        }

        return $this;
    }
}
