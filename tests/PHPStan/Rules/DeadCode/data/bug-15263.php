<?php

namespace Bug15263;

use function PHPStan\dumpType;

final class SinglePickSKU 
{
    public function renderEditControls(string $id): string
    {
        preg_match('/(\d+)$/', $id, $result);
        $counter = '';

        if (is_numeric($result[1])) {
            if ($result[1] < 10) {
                $counter = '0' . $result[1] . '. ';
            } else {
                $counter = $result[1] . '. ';
            }
        }

        $html = '';
        $html .= doFoo($counter . 'abc');
    
        return $html;
    }
}

function doFoo(string $s):string { return $s; }
