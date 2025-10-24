<?php // lint >= 8.5

namespace CallToFunctionWithoutImpurePointsPipe;

function myFunc()
{
}

5 |> myFunc(...);
5 |> (fn ($x) => myFunc($x));
