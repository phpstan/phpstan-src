<?php
namespace TransformElseIfToFlatElseIfVisitor;

/** @param 1|2 $v */
function test1(int $v) : string
{
    if ($v === 1) {
        return '1';
    } elseif ($v === 2) {
        return '2';
    }
}
/** @param 1|2 $v */
function test2(int $v) : string
{
    if ($v === 1) {
        return '1';
    } elseif ($v === 2) {
        return '2';
    }
}
/** @param 1|2 $v */
function test3(int $v) : string
{
    if ($v === 1) {
        return '1';
    } elseif ($v === 2) {
        return '2';
    }
}
/** @param 1|2 $v */
function test4(int $v) : string
{
    if ($v === 1) {
        return '1';
    } else {
        if ($v === 2) {
            return '2';
        }
        if ($v === 3) {
            return '3';
        }
    }
}
