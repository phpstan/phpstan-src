<?php

namespace FixE2E;

function step1(): void {}
function step2(): void {}
function step3(): void {}
function step4(): void {}
function noArgs(int ...$args): void {}

step1();
step2();
step3();
step4();
noArgs(1, 2, 3);
