<?php

namespace Bug10349;

class Foo
{
    /** @return void */
    public function testSomething()
    {
        $expected    = [];
        $expected[0] = [
            'number-1'      => 3, // Offset
            'name'          => '$a',
            'number-2'      => 3, // Offset
            'number-3'      => 3, // Offset
            'has_something' => false,
        ];

        $this->issue_1_A(10, $expected);
        $this->issue_1_B(10, $expected);
        $this->issue_2(10, $expected);
    }

    /**
     * Test helper.
     *
     * @param int                               $ptr
     * @param array<int, array<string, scalar>> $expected
     *
     * @return array<int, array<string, scalar>>
     */
    private function issue_1_A($ptr, $expected)
    {
        foreach ($expected as $key => $param) {
            if ($param['number-1'] !== false) {
                $expected[$key]['number-1'] += $ptr;
            }

            if ($param['number-2'] !== false) {
                $expected[$key]['number-2'] += $ptr;
            }
        }

        return $expected;
    }

    /**
     * Test helper.
     *
     * @param int                               $ptr
     * @param array<int, array<string, scalar>> $expected
     *
     * @return array<int, array<string, scalar>>
     */
    private function issue_1_B($ptr, $expected)
    {
        foreach ($expected as $key => $param) {
            if (is_int($expected[$key]['number-1'])) {
                $expected[$key]['number-1'] += $ptr;
            }

            if ($param['number-2'] !== false) {
                $expected[$key]['number-2'] += $ptr;
            }
        }

        return $expected;
    }

    /**
     * Test helper.
     *
     * @param int                               $ptr
     * @param array<int, array<string, scalar>> $expected
     *
     * @return array<int, array<string, scalar>>
     */
    private function issue_2($ptr, $expected)
    {
        foreach ($expected as $key => $param) {
            if (is_int($param['number-1'])) {
                $expected[$key]['number-1'] += $ptr;
            }
            if (is_int($param['number-2'])) {
                $expected[$key]['number-2'] += $ptr;
            }
        }

        return $expected;
    }
}
