<?php // lint >= 8.1

namespace Bug8179;

enum Row
{
    case I;
    case II;
    case III;
}

enum Column
{
    case A;
    case B;
    case C;
}

function prepareMatrix(): array
{
    $matrix = array_fill_keys(
        array_map(fn($v) => $v->name, Row::cases()),
        array_fill_keys(array_map(fn($v) => $v->name, Column::cases()), null)
    );

    foreach ($matrix as $row => $columns) {
        foreach ($columns as $column => $value) {
            $matrix[$row][$column] = $row.$column;
        }
    }

    return $matrix;
}

function showMatrix(array $matrix): void
{
    foreach ($matrix as $rows) {
        foreach ($rows as $cell) {
            echo $cell."  \t";
        }
        echo PHP_EOL;
    }
}
