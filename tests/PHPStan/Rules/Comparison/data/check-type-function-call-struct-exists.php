<?php

namespace CheckTypeFunctionCall;

// see https://3v4l.org/V9nmf

interface _Interface {}
class _Class {}
trait _Trait {}
enum _Enum {}

class_exists($mixed);
interface_exists($mixed);
enum_exists($mixed);
trait_exists($mixed);

class_exists();
interface_exists();
enum_exists();
trait_exists();

class_exists(_Enum::class);
class_exists(_Interface::class); // always false
class_exists(_Class::class);
class_exists(_Trait::class); // always false

interface_exists(_Enum::class); // always false
interface_exists(_Interface::class);
interface_exists(_Class::class); // always false
interface_exists(_Trait::class); // always false

trait_exists(_Enum::class); // always false
trait_exists(_Interface::class); // always false
trait_exists(_Class::class); // always false
trait_exists(_Trait::class);

enum_exists(_Enum::class);
enum_exists(_Interface::class); // always false
enum_exists(_Class::class); // always false
enum_exists(_Trait::class); // always false



