<?php

namespace TestClosureFunctionTypehints;

$callback = #[\NoDiscard] function (): void
{

};

$callbackNever = #[\NoDiscard] function (): never
{

};
