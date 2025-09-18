<?php

namespace TestFunctionTypehints;

#[\NoDiscard]
function nothing(): void {
}

#[\NoDiscard]
function returnNever(): never {
}
