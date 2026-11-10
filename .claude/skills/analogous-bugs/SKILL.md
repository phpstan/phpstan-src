---
name: analogous-bugs
description: After fixing a PHPStan bug, find and fix its siblings across parallel constructs — a reported bug is usually one visible instance of a wider pattern
argument-hint: "[the bug or fix you just made]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(git *), Bash(make *), Bash(php *), Bash(vendor/bin/phpunit *)
---

# Hunting analogous bugs in PHPStan

You have fixed, or are about to fix, a PHPStan bug. A bug report almost always describes **one** visible instance of a wider pattern. This skill is about finding the other instances, proving they are broken with failing tests, and fixing them with the same approach. `CLAUDE.md` states the principle ("After fixing one bug, look for the same bug in adjacent code"); this is how to carry it out thoroughly. It is the step that distinguishes a good fix from a great one, and skipping it is how the same bug gets reported again a month later.

## How to think about it

Identify the **conceptual axis** along which PHP, or PHPStan's type system, offers parallel constructs. If your fix touches one construct on that axis, the other constructs on the same axis are prime suspects. Write a failing test for each suspect you can think of; for every suspect that actually fails, apply the same fix in the analogous location and confirm the test passes.

## Parallel-construct axes

Use this as a thinking prompt, not an exhaustive list:

- **Class members**: properties ↔ methods ↔ class constants ↔ enum cases. A bug about readonly properties may also affect method visibility, class-constant visibility, or enum-case access; a property-hooks bug may have a twin in promoted constructor properties, asymmetric visibility, or `#[\Override]`. Static vs instance members is another axis on the same group.
- **Callables**: functions ↔ methods ↔ static methods ↔ closures ↔ arrow functions ↔ first-class callable syntax (`foo(...)`) ↔ `__invoke`. A bug in `FunctionCallParametersCheck` very often has a twin in the method-call and static-method-call equivalents.
- **Type-narrowing functions**: `is_int` / `is_string` / `is_float` / `is_bool` / `is_array` / `is_object` / `is_callable` / `is_iterable` / `is_numeric` / `is_countable` — these live in parallel in `TypeSpecifier` and the `FunctionTypeSpecifyingExtension`s. A fix to one `is_*` narrowing almost always needs a mirror in the others.
- **Type-check operators**: `instanceof` ↔ `is_a()` ↔ `is_subclass_of()` ↔ `get_class() === X::class`; and `isset` ↔ `array_key_exists` ↔ `??` ↔ `empty` ↔ null-safe `?->`.
- **Type-system families**:
  - **Accessory string types**: `AccessoryNonEmptyStringType`, `AccessoryNonFalsyStringType`, `AccessoryLiteralStringType`, `AccessoryLowercaseStringType`, `AccessoryUppercaseStringType`, `AccessoryNumericStringType`. A bug in one (a string operation not preserving non-empty-ness) is almost certainly a bug in the others (not preserving lowercase-ness, numeric-ness, and so on).
  - **Accessory array types**: `NonEmptyArrayType`, `AccessoryArrayListType`, `HasOffsetType`, `HasOffsetValueType`, `OversizedArrayType`. If an array operation loses non-empty-ness, does it also lose list-ness?
  - **Constant types**: `ConstantStringType`, `ConstantIntegerType`, `ConstantFloatType`, `ConstantBooleanType`, `ConstantArrayType`. A constant-folding bug in one scalar flavor usually exists in the others.
  - **Integer refinements**: `IntegerType` ↔ `IntegerRangeType` ↔ `ConstantIntegerType`.
  - **Object-ish types**: `ObjectType` ↔ `GenericObjectType` ↔ `StaticType` ↔ `ThisType` ↔ `EnumCaseObjectType`.
  - **Composite types**: `UnionType` ↔ `IntersectionType`. If one gets wrong answers under a new operation, check the other.
  - **Generics bounds**: covariant ↔ contravariant ↔ invariant templates; `@template` ↔ `@template-covariant` ↔ `@template-contravariant`; `extends` ↔ `super` bounds.
  - **TypeSpecifier contexts**: truthy ↔ falsey ↔ true ↔ false ↔ null. A narrowing bug in the truthy branch frequently has a mirror in the falsey branch.
- **PHPDoc tags**: `@param` ↔ `@return` ↔ `@var` ↔ `@property` ↔ `@phpstan-assert` ↔ `@throws`. A parser or resolution bug in one tag often affects the others.
- **Rules directories**: `src/Rules/Classes/` ↔ `Methods/` ↔ `Properties/` ↔ `Functions/` ↔ `Variables/`. Rules come in families — an undefined-member check or a visibility check usually has siblings across categories.
- **Reflection layer**: method reflection ↔ property reflection ↔ constant reflection; `ClassReflection` ↔ `EnumCaseReflection`.

## Procedure

1. **Name the axis before you fix.** When you implement the original fix, note which axis the bug sits on and which sibling constructs to probe afterward.
2. **Probe each sibling with a failing test.** Once the original fix is green, write a small test for each suspect (same format as the regression test — see the `regression-test` skill) and run it.
   - Passes already: the sibling is fine, move on. Discard the throwaway test unless it guards a plausible future regression of the same code path.
   - Fails: apply the analogous fix in the analogous location (the sibling accessory type, the sibling `is_*` specifier, the sibling rule) and confirm the test passes. Keep the test.
3. **A new `Type` method is a strong audit signal.** If your fix adds a method to the `Type` interface, audit *every* implementation — `UnionType`, `IntersectionType`, and the accessory types all need correct implementations, not just the one that prompted the bug.
4. **Do not force-invent bugs.** If after honest probing the siblings really are fine, say so briefly (in the commit message or PR body) so a reviewer can see you considered them. Err on the side of probing more, not less.

There is no upper bound on diff size for this. A 500-line diff that correctly fixes eight parallel cases is much better than a 20-line diff that fixes one and leaves seven sibling bugs for later.
