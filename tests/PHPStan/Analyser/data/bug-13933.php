<?php

namespace Bug13933;

$list = [];

$list['a-1'] = static function (): A { return new A(); };
$list['a-2'] = static function (): B { return new B(); };
$list['a-3'] = static function (): C { return new C(); };
$list['a-4'] = static function (): D { return new D(); };
$list['a-5'] = static function (): E { return new E(); };
$list['a-6'] = static function (): F { return new F(); };
$list['string'] = 'hello';
// Beta
$list['b-1'] = static function (): A1 { return new A1(); };
$list['b-2'] = static function (): B1 { return new B1(); };
$list['b-3'] = static function (): C1 { return new C1(); };
$list['b-4'] = static function (): D1 { return new D1(); };
$list['b-5'] = static function (): E1 { return new E1(); };
$list['b-6'] = static function (): F1 { return new F1(); };
$list['int'] = 123;
// Delta
$list['c-1'] = static function (): A2 { return new A2(); };
$list['c-2'] = static function (): B2 { return new B2(); };
$list['c-3'] = static function (): C2 { return new C2(); };
$list['c-4'] = static function (): D2 { return new D2(); };
$list['c-5'] = static function (): E2 { return new E2(); };
$list['c-6'] = static function (): F2 { return new F2(); };
// Epsilon
$list['d-1'] = static function (): A3 { return new A3(); };
$list['d-2'] = static function (): B3 { return new B3(); };
$list['d-3'] = static function (): C3 { return new C3(); };
$list['d-4'] = static function (): D3 { return new D3(); };
$list['d-5'] = static function (): E3 { return new E3(); };
$list['d-6'] = static function (): F3 { return new F3(); };
// Eta
$list['e-1'] = static function (): A4 { return new A4(); };
$list['e-2'] = static function (): B4 { return new B4(); };
$list['e-3'] = static function (): C4 { return new C4(); };
$list['e-4'] = static function (): D4 { return new D4(); };
$list['e-5'] = static function (): E4 { return new E4(); };
$list['e-6'] = static function (): F4 { return new F4(); };
// Gamma
$list['f-1'] = static function (): A5 { return new A5(); };
$list['f-2'] = static function (): B5 { return new B5(); };
$list['f-3'] = static function (): C5 { return new C5(); };
$list['f-4'] = static function (): D5 { return new D5(); };
$list['f-5'] = static function (): E5 { return new E5(); };
$list['f-6'] = static function (): F5 { return new F5(); };
// Iota
$list['g-1'] = static function (): A6 { return new A6(); };
$list['g-2'] = static function (): B6 { return new B6(); };
$list['g-3'] = static function (): C6 { return new C6(); };
$list['g-4'] = static function (): D6 { return new D6(); };
$list['g-5'] = static function (): E6 { return new E6(); };
$list['g-6'] = static function (): F6 { return new F6(); };
// Kappa
$list['h-1'] = static function (): A7 { return new A7(); };
$list['h-2'] = static function (): B7 { return new B7(); };
$list['h-3'] = static function (): C7 { return new C7(); };
$list['h-4'] = static function (): D7 { return new D7(); };
$list['h-5'] = static function (): E7 { return new E7(); };
$list['h-6'] = static function (): F7 { return new F7(); };
// Lambda
$list['i-1'] = static function (): A8 { return new A8(); };
$list['i-2'] = static function (): B8 { return new B8(); };
$list['i-3'] = static function (): C8 { return new C8(); };
$list['i-4'] = static function (): D8 { return new D8(); };
$list['i-5'] = static function (): E8 { return new E8(); };
$list['i-6'] = static function (): F8 { return new F8(); };
// Theta
$list['j-1'] = static function (): A9 { return new A9(); };
$list['j-2'] = static function (): B9 { return new B9(); };
$list['j-3'] = static function (): C9 { return new C9(); };
$list['j-4'] = static function (): D9 { return new D9(); };
$list['j-5'] = static function (): E9 { return new E9(); };
$list['j-6'] = static function (): F9 { return new F9(); };
// Zeta
$list['k-1'] = static function (): A10 { return new A10(); };
$list['k-2'] = static function (): B10 { return new B10(); };
$list['k-3'] = static function (): C10 { return new C10(); };
$list['k-4'] = static function (): D10 { return new D10(); };
$list['k-5'] = static function (): E10 { return new E10(); };
$list['k-6'] = static function (): F10 { return new F10(); };
//
$list['l-1'] = static function (): A11 { return new A11(); };
$list['l-2'] = static function (): B11 { return new B11(); };
$list['l-3'] = static function (): C11 { return new C11(); };
$list['l-4'] = static function (): D11 { return new D11(); };
$list['l-5'] = static function (): E11 { return new E11(); };
$list['l-6'] = static function (): F11 { return new F11(); };
//
$list['m-1'] = static function (): A12 { return new A12(); };
$list['m-2'] = static function (): B12 { return new B12(); };
$list['m-3'] = static function (): C12 { return new C12(); };
$list['m-4'] = static function (): D12 { return new D12(); };
$list['m-5'] = static function (): E12 { return new E12(); };
$list['m-6'] = static function (): F12 { return new F12(); };
//
$list['n-1'] = static function (): A13 { return new A13(); };
$list['n-2'] = static function (): B13 { return new B13(); };
$list['n-3'] = static function (): C13 { return new C13(); };
$list['n-4'] = static function (): D13 { return new D13(); };
$list['n-5'] = static function (): E13 { return new E13(); };
$list['n-6'] = static function (): F13 { return new F13(); };
//
$list['o-1'] = static function (): A14 { return new A14(); };
$list['o-2'] = static function (): B14 { return new B14(); };
$list['o-3'] = static function (): C14 { return new C14(); };
$list['o-4'] = static function (): D14 { return new D14(); };
$list['o-5'] = static function (): E14 { return new E14(); };
$list['o-6'] = static function (): F14 { return new F14(); };
//
$list['p-1'] = static function (): A15 { return new A15(); };
$list['p-2'] = static function (): B15 { return new B15(); };
$list['p-3'] = static function (): C15 { return new C15(); };
$list['p-4'] = static function (): D15 { return new D15(); };
$list['p-5'] = static function (): E15 { return new E15(); };
$list['p-6'] = static function (): F15 { return new F15(); };
//
$list['q-1'] = static function (): A16 { return new A16(); };
$list['q-2'] = static function (): B16 { return new B16(); };
$list['q-3'] = static function (): C16 { return new C16(); };
$list['q-4'] = static function (): D16 { return new D16(); };
$list['q-5'] = static function (): E16 { return new E16(); };
$list['q-6'] = static function (): F16 { return new F16(); };
//
$list['r-1'] = static function (): A16 { return new A16(); };
$list['r-2'] = static function (): B16 { return new B16(); };
$list['r-3'] = static function (): C16 { return new C16(); };
$list['r-4'] = static function (): D16 { return new D16(); };
$list['r-5'] = static function (): E16 { return new E16(); };
$list['r-6'] = static function (): F16 { return new F16(); };
//
$list['s-1'] = static function (): A16 { return new A16(); };
$list['s-2'] = static function (): B16 { return new B16(); };
$list['s-3'] = static function (): C16 { return new C16(); };
$list['s-4'] = static function (): D16 { return new D16(); };
$list['s-5'] = static function (): E16 { return new E16(); };
$list['s-6'] = static function (): F16 { return new F16(); };
//
$list['t-1'] = static function (): A16 { return new A16(); };
$list['t-2'] = static function (): B16 { return new B16(); };
$list['t-3'] = static function (): C16 { return new C16(); };
$list['t-4'] = static function (): D16 { return new D16(); };
$list['t-5'] = static function (): E16 { return new E16(); };
$list['t-6'] = static function (): F16 { return new F16(); };
//
$list['u-1'] = static function (): A16 { return new A16(); };
$list['u-2'] = static function (): B16 { return new B16(); };
$list['u-3'] = static function (): C16 { return new C16(); };
$list['u-4'] = static function (): D16 { return new D16(); };
$list['u-5'] = static function (): E16 { return new E16(); };
$list['u-6'] = static function (): F16 { return new F16(); };
//
$list['v-1'] = static function (): A16 { return new A16(); };
$list['v-2'] = static function (): B16 { return new B16(); };
$list['v-3'] = static function (): C16 { return new C16(); };
$list['v-4'] = static function (): D16 { return new D16(); };
$list['v-5'] = static function (): E16 { return new E16(); };
$list['v-6'] = static function (): F16 { return new F16(); };
//
$list['w-1'] = static function (): A16 { return new A16(); };
$list['w-2'] = static function (): B16 { return new B16(); };
$list['w-3'] = static function (): C16 { return new C16(); };
$list['w-4'] = static function (): D16 { return new D16(); };
$list['w-5'] = static function (): E16 { return new E16(); };
$list['w-6'] = static function (): F16 { return new F16(); };
//
$list['x-1'] = static function (): A16 { return new A16(); };
$list['x-2'] = static function (): B16 { return new B16(); };
$list['x-3'] = static function (): C16 { return new C16(); };
$list['x-4'] = static function (): D16 { return new D16(); };
$list['x-5'] = static function (): E16 { return new E16(); };
$list['x-6'] = static function (): F16 { return new F16(); };
//
$list['y-1'] = static function (): A16 { return new A16(); };
$list['y-2'] = static function (): B16 { return new B16(); };
$list['y-3'] = static function (): C16 { return new C16(); };
$list['y-4'] = static function (): D16 { return new D16(); };
$list['y-5'] = static function (): E16 { return new E16(); };
$list['y-6'] = static function (): F16 { return new F16(); };
//
$list['z-1'] = static function (): A16 { return new A16(); };
$list['z-2'] = static function (): B16 { return new B16(); };
$list['z-3'] = static function (): C16 { return new C16(); };
$list['z-4'] = static function (): D16 { return new D16(); };
$list['z-5'] = static function (): E16 { return new E16(); };
$list['z-6'] = static function (): F16 { return new F16(); };


print 1;

class A {}
class B {}
class C {}
class D {}
class E {}
class F {}

class A1 {}
class B1 {}
class C1 {}
class D1 {}
class E1 {}
class F1 {}

class A2 {}
class B2 {}
class C2 {}
class D2 {}
class E2 {}
class F2 {}

class A3 {}
class B3 {}
class C3 {}
class D3 {}
class E3 {}
class F3 {}

class A4 {}
class B4 {}
class C4 {}
class D4 {}
class E4 {}
class F4 {}

class A5 {}
class B5 {}
class C5 {}
class D5 {}
class E5 {}
class F5 {}

class A6 {}
class B6 {}
class C6 {}
class D6 {}
class E6 {}
class F6 {}

class A7 {}
class B7 {}
class C7 {}
class D7 {}
class E7 {}
class F7 {}

class A8 {}
class B8 {}
class C8 {}
class D8 {}
class E8 {}
class F8 {}

class A9 {}
class B9 {}
class C9 {}
class D9 {}
class E9 {}
class F9 {}

class A10 {}
class B10 {}
class C10 {}
class D10 {}
class E10 {}
class F10 {}

class A11 {}
class B11 {}
class C11 {}
class D11 {}
class E11 {}
class F11 {}

class A12 {}
class B12 {}
class C12 {}
class D12 {}
class E12 {}
class F12 {}

class A13 {}
class B13 {}
class C13 {}
class D13 {}
class E13 {}
class F13 {}

class A14 {}
class B14 {}
class C14 {}
class D14 {}
class E14 {}
class F14 {}

class A15 {}
class B15 {}
class C15 {}
class D15 {}
class E15 {}
class F15 {}

class A16 {}
class B16 {}
class C16 {}
class D16 {}
class E16 {}
class F16 {}
