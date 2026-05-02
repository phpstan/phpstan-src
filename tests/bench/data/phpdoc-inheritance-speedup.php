<?php declare(strict_types = 1);

namespace BenchPhpDocInheritanceSpeedup;

/**
 * Regression bench for PR https://github.com/phpstan/phpstan-src/pull/4829.
 *
 * The old PhpDocInheritanceResolver re-walked the parent class plus every
 * immediate interface for every method/property/constant whose PHPDoc was
 * resolved, and recursed through each ancestor again. With a deep chain,
 * fan-out at every level and overrides at every level, that grew quickly.
 *
 * Shape: 10 levels of abstract classes, 3 interfaces per level (each
 * extending all interfaces from the previous level), 10 methods
 * redeclared with PHPDoc at every class and every interface, plus a final
 * Leaf class that overrides them all and a function that calls each one.
 *
 * On 2.1.37 (before): bin/phpstan analyse -l 8 ~ 121 s.
 * On 2.1.x  (after):  bin/phpstan analyse -l 8 ~  20 s. (~6x speedup)
 */

interface I0_0 {
    /**
     * @param int $a level 0 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I0_1 {
    /**
     * @param int $a level 0 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I0_2 {
    /**
     * @param int $a level 0 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 0 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I1_0 extends I0_0, I0_1, I0_2 {
    /**
     * @param int $a level 1 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I1_1 extends I0_0, I0_1, I0_2 {
    /**
     * @param int $a level 1 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I1_2 extends I0_0, I0_1, I0_2 {
    /**
     * @param int $a level 1 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 1 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I2_0 extends I1_0, I1_1, I1_2 {
    /**
     * @param int $a level 2 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I2_1 extends I1_0, I1_1, I1_2 {
    /**
     * @param int $a level 2 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I2_2 extends I1_0, I1_1, I1_2 {
    /**
     * @param int $a level 2 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 2 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I3_0 extends I2_0, I2_1, I2_2 {
    /**
     * @param int $a level 3 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I3_1 extends I2_0, I2_1, I2_2 {
    /**
     * @param int $a level 3 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I3_2 extends I2_0, I2_1, I2_2 {
    /**
     * @param int $a level 3 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 3 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I4_0 extends I3_0, I3_1, I3_2 {
    /**
     * @param int $a level 4 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I4_1 extends I3_0, I3_1, I3_2 {
    /**
     * @param int $a level 4 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I4_2 extends I3_0, I3_1, I3_2 {
    /**
     * @param int $a level 4 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 4 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I5_0 extends I4_0, I4_1, I4_2 {
    /**
     * @param int $a level 5 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I5_1 extends I4_0, I4_1, I4_2 {
    /**
     * @param int $a level 5 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I5_2 extends I4_0, I4_1, I4_2 {
    /**
     * @param int $a level 5 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 5 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I6_0 extends I5_0, I5_1, I5_2 {
    /**
     * @param int $a level 6 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I6_1 extends I5_0, I5_1, I5_2 {
    /**
     * @param int $a level 6 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I6_2 extends I5_0, I5_1, I5_2 {
    /**
     * @param int $a level 6 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 6 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I7_0 extends I6_0, I6_1, I6_2 {
    /**
     * @param int $a level 7 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I7_1 extends I6_0, I6_1, I6_2 {
    /**
     * @param int $a level 7 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I7_2 extends I6_0, I6_1, I6_2 {
    /**
     * @param int $a level 7 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 7 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I8_0 extends I7_0, I7_1, I7_2 {
    /**
     * @param int $a level 8 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I8_1 extends I7_0, I7_1, I7_2 {
    /**
     * @param int $a level 8 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I8_2 extends I7_0, I7_1, I7_2 {
    /**
     * @param int $a level 8 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 8 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I9_0 extends I8_0, I8_1, I8_2 {
    /**
     * @param int $a level 9 iface 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I9_1 extends I8_0, I8_1, I8_2 {
    /**
     * @param int $a level 9 iface 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

interface I9_2 extends I8_0, I8_1, I8_2 {
    /**
     * @param int $a level 9 iface 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int;
    /**
     * @param int $a level 9 iface 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int;
}

abstract class C0 implements I0_0, I0_1, I0_2 {
    /**
     * @param int $a class 0 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 0 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C1 extends C0 implements I1_0, I1_1, I1_2 {
    /**
     * @param int $a class 1 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 1 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C2 extends C1 implements I2_0, I2_1, I2_2 {
    /**
     * @param int $a class 2 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 2 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C3 extends C2 implements I3_0, I3_1, I3_2 {
    /**
     * @param int $a class 3 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 3 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C4 extends C3 implements I4_0, I4_1, I4_2 {
    /**
     * @param int $a class 4 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 4 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C5 extends C4 implements I5_0, I5_1, I5_2 {
    /**
     * @param int $a class 5 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 5 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C6 extends C5 implements I6_0, I6_1, I6_2 {
    /**
     * @param int $a class 6 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 6 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C7 extends C6 implements I7_0, I7_1, I7_2 {
    /**
     * @param int $a class 7 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 7 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C8 extends C7 implements I8_0, I8_1, I8_2 {
    /**
     * @param int $a class 8 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 8 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

abstract class C9 extends C8 implements I9_0, I9_1, I9_2 {
    /**
     * @param int $a class 9 method 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a class 9 method 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

final class Leaf extends C9 {
    /**
     * @param int $a leaf override 0
     * @param string $b
     * @return int
     */
    public function m0(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 1
     * @param string $b
     * @return int
     */
    public function m1(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 2
     * @param string $b
     * @return int
     */
    public function m2(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 3
     * @param string $b
     * @return int
     */
    public function m3(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 4
     * @param string $b
     * @return int
     */
    public function m4(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 5
     * @param string $b
     * @return int
     */
    public function m5(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 6
     * @param string $b
     * @return int
     */
    public function m6(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 7
     * @param string $b
     * @return int
     */
    public function m7(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 8
     * @param string $b
     * @return int
     */
    public function m8(int $a, string $b): int { return $a; }
    /**
     * @param int $a leaf override 9
     * @param string $b
     * @return int
     */
    public function m9(int $a, string $b): int { return $a; }
}

function exercise(Leaf $obj): int {
    $sum = 0;
    $sum += $obj->m0(1, 'x');
    $sum += $obj->m1(1, 'x');
    $sum += $obj->m2(1, 'x');
    $sum += $obj->m3(1, 'x');
    $sum += $obj->m4(1, 'x');
    $sum += $obj->m5(1, 'x');
    $sum += $obj->m6(1, 'x');
    $sum += $obj->m7(1, 'x');
    $sum += $obj->m8(1, 'x');
    $sum += $obj->m9(1, 'x');
    return $sum;
}
