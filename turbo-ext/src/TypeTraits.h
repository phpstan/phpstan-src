/*
 * Shared native implementations of the traits PHPStan's Type classes are
 * composed of (the src/Type/Traits directory and src/Type/JustNullableTypeTrait.php):
 * one registrar per PHP trait, adding every method of that trait to a
 * reg::Class builder with the twin's return type declared (the engine checks
 * covariance against the Type interface at link time), so every Type port
 * reuses them instead of spelling the 100-odd trivial methods out again.
 *
 * Precedence follows PHP: a class registers its own methods first and then
 * runs its trait registrars, which go through reg::Class::traitMethod() and
 * skip every name the class declared itself.
 *
 * Inside trait code, `self` and `parent` mean the class the trait is used
 * in — natively that is EX(func)->common.scope, the declaring class (an
 * inherited internal method keeps its scope), so one handler serves every
 * class the trait is registered on. `static`, `$this->method()` and
 * `$this->property` are resolved through the object's own class entry so a
 * PHP subclass overriding a method is honoured (README.md, "How it works").
 *
 * Parameter types are declared exactly as the twin declares them: the
 * classes are not final, and a PHP subclass re-declaring a typed parameter
 * (TemplateBooleanType::describe(VerbosityLevel $level)) only links against
 * a parent that declares the same type — an erased parameter is `mixed`,
 * which the subclass would narrow. Real class names throughout: the
 * linker matches them by name against the interface and the subclasses
 * without loading anything. Parameters the trait never reads are only
 * counted.
 */

#ifndef PHPSTANTURBO_TYPETRAITS_H
#define PHPSTANTURBO_TYPETRAITS_H

#include "reg.h"
#include "zv.h"

/* {{{ the parameter class names the Type interface declares (persistent
 * literals — the engine references them for the process lifetime) */

namespace ptcls {
inline constexpr const char *classReflection = "PHPStan\\Reflection\\ClassReflection";

inline constexpr const char *type = "PHPStan\\Type\\Type";
inline constexpr const char *trinaryLogic = "PHPStan\\TrinaryLogic";
inline constexpr const char *phpVersion = "PHPStan\\Php\\PhpVersion";
inline constexpr const char *verbosityLevel = "PHPStan\\Type\\VerbosityLevel";
inline constexpr const char *generalizePrecision = "PHPStan\\Type\\GeneralizePrecision";
inline constexpr const char *classMemberAccessAnswerer = "PHPStan\\Reflection\\ClassMemberAccessAnswerer";
inline constexpr const char *reflectionProvider = "PHPStan\\Reflection\\ReflectionProvider";
inline constexpr const char *templateTypeVariance = "PHPStan\\Type\\Generic\\TemplateTypeVariance";
inline constexpr const char *mixedType = "PHPStan\\Type\\MixedType";

inline constexpr const char *constantStringOrIntegerType = "PHPStan\\Type\\Constant\\ConstantStringType|PHPStan\\Type\\Constant\\ConstantIntegerType";
} // namespace ptcls

/* }}} */

/* {{{ the Type interface's recurring return types */

namespace ptret {

inline constexpr reg::Arg type = reg::obj("", "PHPStan\\Type\\Type");
inline constexpr reg::Arg nullableType = reg::obj("", "PHPStan\\Type\\Type", true);
inline constexpr reg::Arg booleanType = reg::obj("", "PHPStan\\Type\\BooleanType");
inline constexpr reg::Arg trinaryLogic = reg::obj("", "PHPStan\\TrinaryLogic");
inline constexpr reg::Arg isSuperTypeOfResult = reg::obj("", "PHPStan\\Type\\IsSuperTypeOfResult");
inline constexpr reg::Arg acceptsResult = reg::obj("", "PHPStan\\Type\\AcceptsResult");
inline constexpr reg::Arg templateTypeMap = reg::obj("", "PHPStan\\Type\\Generic\\TemplateTypeMap");
inline constexpr reg::Arg typeNode = reg::obj("", "PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode");
inline constexpr reg::Arg classNameToObjectTypeResult = reg::obj("", "PHPStan\\Type\\ClassNameToObjectTypeResult");
inline constexpr reg::Arg nullableEnumCaseObjectType = reg::obj("", "PHPStan\\Type\\Enum\\EnumCaseObjectType", true);
inline constexpr reg::Arg extendedPropertyReflection = reg::obj("", "PHPStan\\Reflection\\ExtendedPropertyReflection");
inline constexpr reg::Arg unresolvedPropertyPrototypeReflection = reg::obj("", "PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection");
inline constexpr reg::Arg extendedMethodReflection = reg::obj("", "PHPStan\\Reflection\\ExtendedMethodReflection");
inline constexpr reg::Arg unresolvedMethodPrototypeReflection = reg::obj("", "PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection");
inline constexpr reg::Arg classConstantReflection = reg::obj("", "PHPStan\\Reflection\\ClassConstantReflection");
inline constexpr reg::Arg string = reg::stringArg("");
inline constexpr reg::Arg boolean = reg::boolArg("");
inline constexpr reg::Arg integer = reg::longArg("");
inline constexpr reg::Arg nullableInteger = reg::longArg("", true);
inline constexpr reg::Arg array = reg::arrayArg("");
inline constexpr reg::Arg floating = reg::doubleArg("");

inline constexpr reg::Arg constantStringOrIntegerType = reg::obj("", ptcls::constantStringOrIntegerType);
} // namespace ptret

/* }}} */

/* {{{ helpers shared by the Type ports (TypeTraits.cpp) */

/* whether the object's method of that (lowercase) name is the given native
 * handler — the fast-path test before a $this-call a PHP subclass could
 * have overridden */
bool pt_type_method_is(zend_object *object, const char *lcname, size_t len, zif_handler handler);

/* $object->method(...$args) through the object's own class entry; UNDEF =
 * pending exception */
zv::Val pt_type_call(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* $object->method(...$args) on a method returning TrinaryLogic: the
 * PT_TRI_* value, -1 = pending exception */
[[nodiscard]] zend_long pt_type_call_trinary(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* parent::method(...$args) — the method of scope's parent, called on the
 * object (the called scope stays the object's class, as PHP keeps it);
 * UNDEF = pending exception */
zv::Val pt_type_call_parent(zend_class_entry *scope, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* the PT_TRI_* value of a TrinaryLogic instance — the native class, or the
 * PHP twin declared next to it in the differential tests; -1 = pending
 * exception */
[[nodiscard]] zend_long pt_type_trinary_value(zval *trinary);

/* Class::method(...$args) on a class-map class; UNDEF = pending exception */
zv::Val pt_type_call_static(int classIdx, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* new Class(...$args) on a class-map class, its constructor called when it
 * declares one; UNDEF = pending exception */
zv::Val pt_type_new(int classIdx, uint32_t argc, zval *argv);

/* $value instanceof Class for a class-map class or interface; false with
 * an exception pending when the class cannot be resolved */
bool pt_type_instanceof(zval *value, int classIdx, bool &out);
/* the same against a shadowing class entry (never fails) */
inline bool pt_type_instanceof_ce(zval *value, zend_class_entry *ce, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* TrinaryLogic::create*() / AcceptsResult::create*() /
 * IsSuperTypeOfResult::create*() for a PT_TRI_* value (createFromBoolean()
 * maps to yes/no); UNDEF = pending exception */
zv::Val pt_type_trinary(zend_long value);
zv::Val pt_type_accepts_result(zend_long value);
zv::Val pt_type_is_super_type_of_result(zend_long value);

/* new ErrorType(), new MixedType(), new MixedType(subtractedType: new
 * NullType()) (the shadowing NullType); UNDEF = pending exception */
zv::Val pt_type_new_error_type();
zv::Val pt_type_new_mixed_type();
zv::Val pt_type_new_mixed_type_without_null();

/* new ConstantIntegerType($value), new ConstantFloatType($value), new
 * ConstantStringType($value) (the shadowing classes), new UnionType($types)
 * ($types consumed); UNDEF = pending exception */
zv::Val pt_type_new_constant_integer(zend_long value);
zv::Val pt_type_new_constant_float(double value);
zv::Val pt_type_new_constant_string(const char *value, size_t len);
zv::Val pt_type_new_union(zv::Arr types);

/* the body of ConstantScalarTypeTrait::looseCompare() for a class that
 * aliases it (`use ConstantScalarTypeTrait { looseCompare as private
 * scalarLooseCompare; }`): the handler to register under the alias, and
 * the implementation for the class's own looseCompare() to call as
 * $this->scalarLooseCompare() does (a private call, never overridden).
 * scope is the class the trait is used in. UNDEF = pending exception */
void ZEND_FASTCALL pt_type_trait_constant_scalar_loose_compare(INTERNAL_FUNCTION_PARAMETERS);
zv::Val pt_type_constant_scalar_loose_compare(zend_object *self, zend_class_entry *scope, zval *type, zval *phpVersion);

/* $this->value in a class using one of the ConstantScalar* traits: the
 * private property the class using the trait (scope) declares — found on
 * scope, so the slot is right for subclasses too; NULL with an Error
 * pending when uninitialized, as the twin's typed-property read raises */
[[nodiscard]] zval *pt_type_constant_scalar_value(zend_object *object, zend_class_entry *scope);

/* Class::method(...$args) on a class-map class / $object->method(...$args)
 * through the object's class entry, with the arguments spread from a PHP
 * array (`TypeCombinator::union(...$types)`); UNDEF = pending exception */
zv::Val pt_type_call_static_spread(int classIdx, const char *lcname, size_t len, HashTable *args);
zv::Val pt_type_call_spread(zend_object *object, const char *lcname, size_t len, HashTable *args);

/* TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
 * UNDEF = pending exception */
zv::Val pt_type_mixed_minus(HashTable *subtractedTypes);

namespace phpstanturbo {

/* a PHP ?int */
struct NullableLong
{
	bool isNull;
	zend_long value;

	static NullableLong null() { return { true, 0 }; }
	static NullableLong of(zend_long v) { return { false, v }; }
	/* the ?int held by a zval (IS_LONG, else null) */
	static NullableLong from(const zval *z) { return Z_TYPE_P(z) == IS_LONG ? of(Z_LVAL_P(z)) : null(); }

	zv::Val toVal() const { return isNull ? zv::Val::null() : zv::Val::integer(value); }
};

} // namespace phpstanturbo

/* the shadowing IntegerRangeType's factories (IntegerRangeType.cpp):
 * fromInterval() and the createAll*() family, the latter taking the
 * int|float $value zval as the twin's untyped parameter does; UNDEF =
 * pending exception */
zv::Val pt_integer_range_from_interval(phpstanturbo::NullableLong min, phpstanturbo::NullableLong max, zend_long shift);
zv::Val pt_integer_range_create_all_smaller_than(zval *value);
zv::Val pt_integer_range_create_all_smaller_than_or_equal_to(zval *value);
zv::Val pt_integer_range_create_all_greater_than(zval *value);
zv::Val pt_integer_range_create_all_greater_than_or_equal_to(zval *value);
/* $range->getMin() / $range->getMax() of an IntegerRangeType instance —
 * the slots when it is exactly the native class, the methods through its
 * class entry otherwise (a subclass may override them); false = pending
 * exception */
[[nodiscard]] bool pt_integer_range_bounds(zend_object *range, phpstanturbo::NullableLong &min, phpstanturbo::NullableLong &max);
/* $type->getValue() of a ConstantIntegerType instance, the same way
 * (ConstantIntegerType.cpp); false = pending exception */
[[nodiscard]] bool pt_constant_integer_get_value(zend_object *object, zend_long &out);

/* the bodies of IntegerType::looseCompare() and IntegerType::exponentiate()
 * (IntegerType.cpp), for the children's parent:: calls; UNDEF = pending
 * exception */
zv::Val pt_integer_type_loose_compare(zend_object *self, zval *type, zval *phpVersion);
zv::Val pt_integer_type_exponentiate(zend_object *self, zval *exponent);

/* the bodies of StringType's offset methods and tryRemove() (StringType.cpp),
 * for the children's parent:: calls; the setOffsetValueType() body takes
 * the twin's `?Type $offsetType` (NULL for null) with $unionValues at its
 * default; UNDEF = pending exception */
zv::Val pt_string_type_has_offset_value_type(zend_object *self, zval *offsetType);
zv::Val pt_string_type_get_offset_value_type(zend_object *self, zval *offsetType);
zv::Val pt_string_type_set_offset_value_type(zend_object *self, zval *offsetType, zval *valueType);
zv::Val pt_string_type_try_remove(zend_object *self, zval *typeToRemove);

/* $type->getValue() of a ConstantStringType instance — the slot when it is
 * exactly the native class, the method through its class entry otherwise
 * (a subclass may override it); an owned string, UNDEF = pending exception
 * (ConstantStringType.cpp) */
zv::Val pt_constant_string_get_value(zend_object *object);

/* the PT_TRI_* value of a result object's ->result (an IsSuperTypeOfResult /
 * AcceptsResult — the native class by its slot, anything else through the
 * public property); -1 = pending exception */
[[nodiscard]] zend_long pt_type_result_trinary(zval *result);

/* calls a zpp-parsed callable (AcceptsResult.cpp); false = pending
 * exception (*retval is then released) */
[[nodiscard]] bool pt_call_fci(zend_fcall_info *fci, zend_fcall_info_cache *fcc, uint32_t argc, zval *argv, zval *retval);

/* module startup: the internal helper classes the trait code needs */
void pt_register_type_traits();

/* }}} */

/* {{{ trait registrars — one per PHP trait, every method with its twin's
 * return type; run them after the class's own methods */

/* src/Type/JustNullableTypeTrait.php */
void pt_type_trait_just_nullable(reg::Class &cls);
/* src/Type/Traits/NonArrayTypeTrait.php */
void pt_type_trait_non_array(reg::Class &cls);
/* src/Type/Traits/NonCallableTypeTrait.php */
void pt_type_trait_non_callable(reg::Class &cls);
/* src/Type/Traits/MaybeCallableTypeTrait.php */
void pt_type_trait_maybe_callable(reg::Class &cls);
/* src/Type/Traits/NonIterableTypeTrait.php */
void pt_type_trait_non_iterable(reg::Class &cls);
/* src/Type/Traits/NonObjectTypeTrait.php */
void pt_type_trait_non_object(reg::Class &cls);
/* src/Type/Traits/UndecidedBooleanTypeTrait.php */
void pt_type_trait_undecided_boolean(reg::Class &cls);
/* src/Type/Traits/UndecidedComparisonTypeTrait.php */
void pt_type_trait_undecided_comparison(reg::Class &cls);
/* src/Type/Traits/NonGenericTypeTrait.php */
void pt_type_trait_non_generic(reg::Class &cls);
/* src/Type/Traits/NonOffsetAccessibleTypeTrait.php */
void pt_type_trait_non_offset_accessible(reg::Class &cls);
/* src/Type/Traits/NonGeneralizableTypeTrait.php */
void pt_type_trait_non_generalizable(reg::Class &cls);
/* src/Type/Traits/ConstantScalarTypeTrait.php */
void pt_type_trait_constant_scalar(reg::Class &cls);
/* src/Type/Constant/ConstantScalarToBooleanTrait.php */
void pt_type_trait_constant_scalar_to_boolean(reg::Class &cls);
/* src/Type/Traits/ConstantNumericComparisonTypeTrait.php */
void pt_type_trait_constant_numeric_comparison(reg::Class &cls);
/* src/Type/Traits/FalseyBooleanTypeTrait.php */
void pt_type_trait_falsey_boolean(reg::Class &cls);
/* src/Type/Traits/NonRemoveableTypeTrait.php */
void pt_type_trait_non_removeable(reg::Class &cls);
/* src/Type/Traits/UndecidedComparisonCompoundTypeTrait.php — only what the
 * trait declares itself; the UndecidedComparisonTypeTrait it uses is run
 * separately, as the twin's `use` chain resolves it */
void pt_type_trait_undecided_comparison_compound(reg::Class &cls);
/* src/Type/Traits/SubstractableTypeTrait.php */
void pt_type_trait_substractable(reg::Class &cls);

/* }}} */

/* {{{ helpers of the never/mixed family (NeverType.cpp, MixedType.cpp,
 * StrictMixedType.cpp) */

/* new NeverType(); UNDEF = pending exception */
zv::Val pt_type_new_never_type();

/* Class::method(...$args) on a class entry the native code holds (a
 * shadowed result class); UNDEF = pending exception */
zv::Val pt_type_call_static_ce(zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* which of $level->handle()'s callbacks a VerbosityLevel selects: the
 * type-only, value or precise one, or the fourth (the cache level — any
 * other value falls there too, as handle() does); false = pending
 * exception */
enum pt_verbosity_case
{
	PT_VERBOSITY_TYPE_ONLY,
	PT_VERBOSITY_VALUE,
	PT_VERBOSITY_PRECISE,
	PT_VERBOSITY_CACHE,
};
bool pt_type_verbosity_case(zval *level, pt_verbosity_case &out);

/* the body of SubstractableTypeTrait::describeSubtractedType(): the handler
 * the registrar declares (the fast-path identity for a $this-call), and the
 * implementation taking the ?Type as a zval (IS_NULL for null); an owned
 * string, UNDEF = pending exception */
void ZEND_FASTCALL pt_type_trait_substractable_describe_subtracted_type(INTERNAL_FUNCTION_PARAMETERS);
zv::Val pt_type_describe_subtracted_type(zval *subtractedType, zval *level);

/* JustNullableTypeTrait's identity traverse() handler, for a class whose own
 * traverse() returns $this — registering it under that handler lets
 * NonGeneralizableTypeTrait's generalize() take its no-callback fast path */
zif_handler pt_type_identity_traverse_handler();

/* }}} */

/* {{{ trait registrars of the object family (ObjectWithoutClassType.cpp,
 * ObjectShapeType.cpp, NonexistentParentClassType.cpp) */

/* src/Type/Traits/TruthyBooleanTypeTrait.php */
void pt_type_trait_truthy_boolean(reg::Class &cls);
/* src/Type/Traits/MaybeIterableTypeTrait.php */
void pt_type_trait_maybe_iterable(reg::Class &cls);
/* src/Type/Traits/MaybeOffsetAccessibleTypeTrait.php */
void pt_type_trait_maybe_offset_accessible(reg::Class &cls);
/* src/Type/Traits/ObjectTypeTrait.php — only what the trait declares
 * itself; the MaybeCallable, MaybeIterable, MaybeOffsetAccessible, NonArray
 * and TruthyBoolean traits it uses are run separately, as the twin's `use`
 * chain resolves them */
void pt_type_trait_object(reg::Class &cls);

/* }}} */

/* {{{ helpers of the object family */

/* static fn (Type $type): Type => $type — a Closure over the internal
 * IdentityCallback::identity() (MixedType.cpp) */
zv::Val pt_type_identity_callback();

/* new CallbackUnresolved{Property,Method}PrototypeReflection($member,
 * $member->getDeclaringClass(), false, static fn (Type $type): Type => $type)
 * over a Dummy{Property,Method}Reflection($name) — the body ObjectTypeTrait
 * and MixedType share; UNDEF = pending exception */
zv::Val pt_type_dummy_unresolved_prototype(bool isMethod, zval *name);

/* $self->getUnresolved*Prototype($name, $scope)->getTransformedProperty()
 * / ->getTransformedMethod(), the prototype method through the object's
 * class entry; UNDEF = pending exception */
zv::Val pt_type_transformed_member(zend_object *self, const char *prototypeLcname, size_t prototypeLen, bool isMethod, zval *name, zval *scope);

/* new ClassNameToObjectTypeResult(new UnionType([new ObjectWithoutClassType(),
 * new ClassStringType()]), false) when strings are allowed, of an
 * ObjectWithoutClassType alone otherwise — the toObjectTypeForIsACheck()
 * body the object traits share; UNDEF = pending exception */
zv::Val pt_type_object_type_for_is_a_check(bool allowString);

/* new ObjectWithoutClassType() (the shadowing class); UNDEF = pending
 * exception */
zv::Val pt_type_new_object_without_class_type();

/* new Class(...$args) on a class entry the native code holds (a shadowed
 * class), its constructor called when it declares one; UNDEF = pending
 * exception */
zv::Val pt_type_new_ce(zend_class_entry *ce, uint32_t argc, zval *argv);

/* a Closure over the method of an internal callback-holder class, bound to
 * the holder ($this; NULL for a static method) — the `fn () => ...` the
 * twins pass around, with the captured variables in the holder's slots */
zv::Val pt_type_closure_over(zend_function *fn, zend_class_entry *ce, zend_object *holder);

/* $callable(...$args) for any PHP callable value; UNDEF = pending exception */
zv::Val pt_type_call_callable(zval *callable, uint32_t argc, zval *argv);

/* }}} */

/* {{{ helpers of the static family (StaticType.cpp), for the children's
 * parent:: calls (ThisType.cpp, GenericStaticType.cpp) */

/* parent::__construct($classReflection, $subtractedType) — StaticType's
 * constructor body on the object ($subtractedType NULL for null) */
void pt_static_type_construct(zend_object *self, zval *classReflection, zval *subtractedType);

/* $this->subtractedType / $this->classReflection of StaticType's scope —
 * the slots StaticType declares (borrowed); NULL with an Error pending
 * when uninitialized */
[[nodiscard]] zval *pt_static_type_subtracted_type(zend_object *object);
zval *pt_static_type_class_reflection(zend_object *object);

/* the bodies of StaticType::getStaticObjectType(), isSuperTypeOf(),
 * changeSubtractedType(), toClassConstantType() and toPhpDocNode() run on
 * the object (its own class answering the $this-calls inside them, as
 * parent:: keeps it); UNDEF = pending exception */
zv::Val pt_static_type_get_static_object_type(zend_object *self);
zv::Val pt_static_type_is_super_type_of(zend_object *self, zval *type);
zv::Val pt_static_type_change_subtracted_type(zend_object *self, zval *subtractedType);
zv::Val pt_static_type_to_class_constant_type(zend_object *self);
zv::Val pt_static_type_to_php_doc_node();

/* $this->getStaticObjectType() / $this->getClassReflection() /
 * $this->getSubtractedType() / $this->getClassName() through the object's
 * class entry, with the direct path when the method is StaticType's own;
 * UNDEF = pending exception */
zv::Val pt_static_type_this_static_object_type(zend_object *self);
zv::Val pt_static_type_this_class_reflection(zend_object *self);
zv::Val pt_static_type_this_subtracted_type(zend_object *self);
zv::Val pt_static_type_this_class_name(zend_object *self);

/* }}} */

/* merged from the parallel port branch */

/* {{{ helpers of the array family (ArrayType.cpp, NonEmptyArrayType.cpp,
 * AccessoryArrayListType.cpp, OversizedArrayType.cpp, HasOffsetType.cpp,
 * HasOffsetValueType.cpp) */
/* the `ConstantStringType|ConstantIntegerType` the offset accessories
 * declare — a `|`-separated literal the engine turns into a union type */
/* static fn (Type $type): Type => $type — the Closure over
 * IdentityCallback::identity() (MixedType.cpp) the prototype reflections
 * of MixedType and MaybeObjectTypeTrait take */
/* A native body behind a PHP callable: the `static function (Type $type,
 * callable $traverse) use (...)` closures the twins hand to
 * TypeTraverser::map(). The callable is an instance of the internal
 * PHPStanTurbo\NativeCallback class (no PHP twin, like the generalize()
 * holder) whose __invoke(...$args) runs fn with the holder's two state
 * slots (the closure's `use` variables — state0 by reference, for a
 * `use (&$collected)`) and the call's arguments; an exception the body
 * leaves pending propagates. state0/state1 are borrowed, NULL = null. */
typedef void (*pt_native_callback)(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
zv::Val pt_type_native_callback(pt_native_callback fn, zval *state0, zval *state1);
/* the holder's state slot (0 or 1), for reading a by-reference `use` back
 * after the call; borrowed */
zval *pt_type_native_callback_state(zval *callback, int index);
/* new IsSuperTypeOfResult($trinary, []) / new AcceptsResult($trinary, [])
 * for a PT_TRI_* value — fresh instances, as the accessories' `new` spells
 * them (not the create*() singletons); UNDEF = pending exception */
zv::Val pt_type_new_is_super_type_of_result(zend_long value);
zv::Val pt_type_new_accepts_result(zend_long value);
/* $result->and($other) on two result objects, natively for two
 * AcceptsResults; UNDEF = pending exception (also for an UNDEF $result) */
zv::Val pt_type_result_and(zv::Val result, zval *other);
/* $array->getKeyType() / $array->getItemType() of an ArrayType instance —
 * class entry otherwise (ArrayType.cpp); UNDEF = pending exception */
zv::Val pt_array_type_get_key_type(zend_object *object);
zv::Val pt_array_type_get_item_type(zend_object *object);
/* $accessory->getOffsetType() / ->getValueType() of a HasOffsetType /
 * HasOffsetValueType instance, the same way (HasOffsetType.cpp,
 * HasOffsetValueType.cpp); UNDEF = pending exception */
zv::Val pt_has_offset_type_get_offset_type(zend_object *object);
zv::Val pt_has_offset_value_type_get_offset_type(zend_object *object);
zv::Val pt_has_offset_value_type_get_value_type(zend_object *object);
/* src/Type/Traits/ArrayTypeTrait.php */
void pt_type_trait_array(reg::Class &cls);
/* src/Type/Traits/MaybeArrayTypeTrait.php */
void pt_type_trait_maybe_array(reg::Class &cls);
/* src/Type/Traits/MaybeObjectTypeTrait.php */
void pt_type_trait_maybe_object(reg::Class &cls);
/* src/Type/Traits/MaybeStringTypeTrait.php */
void pt_type_trait_maybe_string(reg::Class &cls);


/* {{{ helpers of the string accessory family (AccessoryNumericStringType.cpp,
 * AccessoryNonEmptyStringType.cpp, AccessoryNonFalsyStringType.cpp,
 * AccessoryLiteralStringType.cpp, AccessoryLowercaseStringType.cpp,
 * AccessoryUppercaseStringType.cpp, AccessoryDecimalIntegerStringType.cpp)
 * and the member accessories (HasMethodType.cpp, HasPropertyType.cpp) */

/* new StringType() (the shadowing class); UNDEF = pending exception */
zv::Val pt_type_new_string_type();
/* new <Shadowed>() over a shadowed class's exported no-argument
 * constructor, as a Val; UNDEF = pending exception */
zv::Val pt_type_new_shadowed(bool (*construct)(zval *));
/* new IntersectionType($types) ($types consumed); UNDEF = pending exception */
zv::Val pt_type_new_intersection(zv::Arr types);
/* new IntersectionType([new StringType(), new <Accessory>()]) over a
 * shadowed accessory's exported constructor; UNDEF = pending exception */
zv::Val pt_type_new_string_with_accessory(bool (*construct)(zval *));
/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
 * isList: TrinaryLogic::createYes()) — the string accessories' toArray()
 * body; UNDEF = pending exception */
zv::Val pt_type_string_accessory_to_array(zend_object *self);
/* new BenevolentUnionType([new FloatType(), new IntegerType()]) — the
 * string accessories' exponentiate() body; UNDEF = pending exception */
zv::Val pt_type_new_float_or_int_benevolent_union();
/* new IdentifierTypeNode($name); UNDEF = pending exception */
zv::Val pt_type_new_identifier_type_node(const char *name, size_t len);
/* ReportUnsafeArrayStringKeyCastingToggle::getLevel() !==
 * ReportUnsafeArrayStringKeyCastingToggle::PREVENT; false = pending
 * exception */
[[nodiscard]] bool pt_type_unsafe_array_string_key_casting_not_prevented(bool &out);

/* }}} */

/* merged from the parallel port branch */
namespace ptret {
inline constexpr reg::Arg nullableClassReflection = reg::obj("", "PHPStan\\Reflection\\ClassReflection", true);
inline constexpr reg::Arg nullableObjectType = reg::obj("", "PHPStan\\Type\\ObjectType", true);
inline constexpr reg::Arg genericObjectType = reg::obj("", "PHPStan\\Type\\Generic\\GenericObjectType");
} // namespace ptret

/* {{{ the object family (ObjectType.cpp, GenericObjectType.cpp,
 * EnumCaseObjectType.cpp) */
/* new ObjectType($className) (the shadowing class), $className a string
 * zval; UNDEF = pending exception */
zv::Val pt_type_new_object_type(zval *className);
/* the private slots of an ObjectType instance, read as the twin reads
 * `$type->className` / `$type->subtractedType` / `$type->classReflection`
 * from inside the class: the class name (NULL with an Error pending when
 * uninitialized), the subtracted type (IS_NULL or an object; NULL with an
 * Error pending when uninitialized), the constructor's class reflection
 * (IS_NULL or an object); all borrowed */
[[nodiscard]] zend_string *pt_object_type_class_name(zend_object *object);
zval *pt_object_type_subtracted_type(zend_object *object);
zval *pt_object_type_class_reflection(zend_object *object);
/* the body of ObjectType::__construct(), for the children's
 * parent::__construct() ($subtractedType / $classReflection borrowed, NULL
 * for null) */
void pt_object_type_construct(zend_object *self, zend_string *className, zval *subtractedType, zval *classReflection);
/* the bodies of ObjectType's methods the children call through parent::;
 * UNDEF = pending exception (equals(): false = pending exception) */
zv::Val pt_object_type_describe(zend_object *self, zval *level);
zv::Val pt_object_type_get_class_reflection(zend_object *self);
bool pt_object_type_equals(zend_object *self, zval *type, bool &out);
zv::Val pt_object_type_get_referenced_classes(zend_object *self);
zv::Val pt_object_type_is_super_type_of(zend_object *self, zval *type);
zv::Val pt_object_type_get_unresolved_property_prototype(zend_object *self, zval *propertyName, zval *scope);
zv::Val pt_object_type_get_unresolved_instance_property_prototype(zend_object *self, zval *propertyName, zval *scope);
zv::Val pt_object_type_get_unresolved_static_property_prototype(zend_object *self, zval *propertyName, zval *scope);
zv::Val pt_object_type_get_unresolved_method_prototype(zend_object *self, zval *methodName, zval *scope);
zv::Val pt_object_type_change_subtracted_type(zend_object *self, zval *subtractedType);
zv::Val pt_object_type_to_php_doc_node(zend_object *self);
/* `static fn () => $type->getReferencedClasses()` — a Closure over the
 * object family's internal callback holder, for
 * RecursionGuard::runOnObjectIdentity(); UNDEF = pending exception */
zv::Val pt_object_type_referenced_classes_callback(zval *type);

/* merged from the parallel port branch */

/* {{{ helpers of the callable family (IterableType.cpp, CallableType.cpp,
 * ClosureType.cpp) — the bodies CallableType and ClosureType share verbatim,
 * taking the twins' private slots as borrowed zvals */

/* a $this-call a subclass may override (`$this->getParameters()`,
 * `$this->getReturnType()`), answered by the handle class of the caller
 * with its direct path when the method is the native one; UNDEF = pending
 * exception */
typedef zv::Val (*pt_callable_this_getter)(zend_object *self);

/* $into = array_merge($into, $more): string keys kept, integer keys
 * renumbered; false with a TypeError pending when $more is not an array */
bool pt_callable_array_merge_into(zv::Arr &into, zval *more);
/* the getReferencedClasses() body over an initial $classes: the
 * parameters' types', the assertions' types' and the return type's
 * merged in; UNDEF = pending exception */
zv::Val pt_callable_referenced_classes(zv::Arr classes, zval *parameters, zval *assertions, zval *returnType);
/* $assertions->getAll() as an owned array; UNDEF = pending exception */
zv::Val pt_callable_assertions_all(zval *assertions);
/* array_map(static fn ($parameter) => $parameter->getType(), $parameters);
 * UNDEF = pending exception */
zv::Val pt_callable_parameter_types(zval *parameters);
/* the DummyParameter list of describe(): every parameter without its name
 * unless an assertion refers to it, never by reference, optional only when
 * not variadic; UNDEF = pending exception */
zv::Val pt_callable_dummy_parameters(zval *parameters, zval *assertions);
/* (new Printer())->print($type->toPhpDocNode()) — the $printer created
 * first, as the twins do; UNDEF = pending exception */
zv::Val pt_callable_print_php_doc_node(zval *type);
/* the CallableTypeNode of toPhpDocNode(): new CallableTypeNode(new
 * IdentifierTypeNode($identifier), <parameter nodes>, <the conditional
 * return type node of the assertions, else $returnType->toPhpDocNode()>,
 * <template tag nodes>); UNDEF = pending exception */
zv::Val pt_callable_type_node(const char *identifier, size_t identifierLen, zval *parameters, zval *templateTags, zval *assertions, zval *returnType);
/* whether a parameter's type, out type or closure-this type, or an
 * assertion's type has a template or late-resolvable type — the
 * hasTemplateOrLateResolvableType() body before the return type's answer;
 * false = pending exception */
[[nodiscard]] bool pt_callable_parameters_or_asserts_have_template(zval *parameters, zval *assertions, bool &out);
/* the getReferencedTemplateTypes() body; UNDEF = pending exception */
zv::Val pt_callable_referenced_template_types(zend_object *self, pt_callable_this_getter getReturnType, pt_callable_this_getter getParameters, zval *assertions, zval *positionVariance);
/* the private inferTemplateTypesOnParametersAcceptor() body; UNDEF =
 * pending exception */
zv::Val pt_callable_infer_template_types_on_parameters_acceptor(zend_object *self, pt_callable_this_getter getParameters, pt_callable_this_getter getReturnType, zval *parametersAcceptor);
/* the inferTemplateTypes() tail: the empty map unioned with the inference
 * on every acceptor of $acceptors; UNDEF = pending exception */
zv::Val pt_callable_infer_template_types_on_acceptors(zend_object *self, pt_callable_this_getter getParameters, pt_callable_this_getter getReturnType, zval *acceptors);
/* the traverse() parameter mapping: every ParameterReflection rebuilt as a
 * NativeParameterReflection over $cb of its type and default value;
 * UNDEF = pending exception */
zv::Val pt_callable_traverse_parameters(zval *parameters, zend_fcall_info *fci, zend_fcall_info_cache *fcc);
/* the traverseSimultaneously() parameter mapping over the left and right
 * parameter lists of equal size; UNDEF = pending exception */
zv::Val pt_callable_traverse_parameters_simultaneously(zval *leftParameters, zval *rightParameters, zend_fcall_info *fci, zend_fcall_info_cache *fcc);
/* new IsSuperTypeOfResult($trinary, []) over a TrinaryLogic zval; UNDEF =
 * pending exception */
zv::Val pt_callable_is_super_type_of_result_of(zval *trinary);
/* new OutOfClassScope(); UNDEF = pending exception */
zv::Val pt_callable_out_of_class_scope();
/* TemplateTypeMap::createEmpty() / TemplateTypeVarianceMap::createEmpty() /
 * Assertions::createEmpty(); UNDEF = pending exception */
zv::Val pt_callable_template_type_map_empty();
zv::Val pt_callable_template_type_variance_map_empty();
zv::Val pt_callable_assertions_empty();
/* new SimpleImpurePoint($identifier, $description, $certain); UNDEF =
 * pending exception */
zv::Val pt_callable_new_simple_impure_point(const char *identifier, size_t identifierLen, const char *description, size_t descriptionLen, bool certain);
/* [$self]; UNDEF = pending exception */
zv::Val pt_callable_self_list(zend_object *self);
/* an `array $x` / `?array $x = null` parameter's arginfo (the twins'
 * `?array $parameters = null`); reg.h has no nullable array shorthand */
constexpr reg::Arg pt_callable_nullable_array_arg(const char *name)
{
	return { name, MAY_BE_ARRAY | MAY_BE_NULL | reg::detail::flagBits(false, false), nullptr };
}

/* }}} */

/* merged from the parallel port branch */
/* {{{ helpers of the array-shape type (ConstantArrayType.cpp) */
/* ArrayTypeTrait's chunkArray() handler, for a class aliasing it (`use
 * ArrayTypeTrait { chunkArray as traitChunkArray; }`) */
zif_handler pt_carr_array_trait_chunk_array_handler();
/* a real Closure over a native body — pt_type_native_callback()'s holder
 * wrapped in a Closure, for the `fn (): string => ...` values a twin stores
 * where a Closure is required (IsSuperTypeOfResult's $lazyReasons); UNDEF =
 * pending exception */
zv::Val pt_carr_native_closure(pt_native_callback fn, zval *state0, zval *state1);
/* $never->isExplicit() of a NeverType instance — the slot when it is exactly
 * the native class, the method through its class entry otherwise
 * (NeverType.cpp); false = pending exception */
[[nodiscard]] bool pt_never_type_is_explicit(zend_object *object, bool &out);

/* {{{ helpers of the compound family (UnionType.cpp, BenevolentUnionType.cpp,
 * IntersectionType.cpp) */

namespace phpstanturbo {

/* The `static fn (Type $type) => ...` closures UnionType hands to its
 * protected unionResults()/unionTypes()/pickFromTypes() — the methods
 * BenevolentUnionType overrides, so the body runs on whichever class the
 * object is of: a member call on the type ($type->method(...$args)), the
 * reversed form on the first argument ($args[0]->method($type, ...$args[1..])
 * — isGreaterThan()'s $otherType->isSmallerThan($type, $phpVersion)), the
 * looseCompare()->toTrinaryLogic() form, and toArrayKey()'s StringType
 * passthrough. argv is borrowed. */
struct UnionMemberOp
{
	enum Kind
	{
		Call,
		Reversed,
		LooseCompare,
		ToArrayKeyKeepingStrings,
	};

	Kind kind;
	const char *lcname;
	size_t len;
	uint32_t argc;
	zval *argv;

	static UnionMemberOp call(const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL) { return { Call, lcname, len, argc, argv }; }
	static UnionMemberOp reversed(const char *lcname, size_t len, uint32_t argc, zval *argv) { return { Reversed, lcname, len, argc, argv }; }
	static UnionMemberOp looseCompare(zval *argv) { return { LooseCompare, "loosecompare", sizeof("loosecompare") - 1, 2, argv }; }
	static UnionMemberOp toArrayKeyKeepingStrings() { return { ToArrayKeyKeepingStrings, "toarraykey", sizeof("toarraykey") - 1, 0, NULL }; }
};

/* the `static fn (Type $type) => $type->isX()->yes()` criteria
 * pickFromTypes() takes (NULL lcname = none) */
struct UnionCriteria
{
	const char *lcname;
	size_t len;
};

} // namespace phpstanturbo

/* the op applied to one member; UNDEF = pending exception (UnionType.cpp) */
zv::Val pt_union_apply_op(const phpstanturbo::UnionMemberOp &op, zval *type);
/* `$type->isX()->yes()`; false = pending exception */
[[nodiscard]] bool pt_union_apply_criteria(const phpstanturbo::UnionCriteria &criteria, zval *type, bool &out);
/* the op / the criteria as a PHP callable (a PHPStanTurbo\NativeCallback
 * holder), for a PHP subclass's own unionResults()/unionTypes()/
 * pickFromTypes(); UNDEF = pending exception */
zv::Val pt_union_op_callback(const phpstanturbo::UnionMemberOp &op);
zv::Val pt_union_criteria_callback(const phpstanturbo::UnionCriteria &criteria);

/* $this->types of a UnionType instance (borrowed); NULL with an Error
 * pending when uninitialized */
[[nodiscard]] zval *pt_union_type_types(zend_object *object);
/* $object->getTypes() through the object's class entry, with the direct
 * path when the method is UnionType's own; UNDEF = pending exception */
zv::Val pt_union_type_get_types(zend_object *object);

/* the bodies of UnionType::__construct(), filterTypes(), tryRemove(),
 * describe() and traverseSimultaneously() run on the object (its own class
 * answering the $this-calls inside them, as parent:: keeps it), for the
 * child's parent:: calls; false / UNDEF = pending exception */
[[nodiscard]] bool pt_union_type_construct(zend_object *self, zval *types, bool normalized);
zv::Val pt_union_type_filter_types(zend_object *self, zend_fcall_info *fci, zend_fcall_info_cache *fcc);
zv::Val pt_union_type_try_remove(zend_object *self, zval *typeToRemove);
zv::Val pt_union_type_describe(zend_object *self, zval *level);
zv::Val pt_union_type_traverse_simultaneously(zend_object *self, zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc);

/* BenevolentUnionType's unionResults()/unionTypes()/pickFromTypes() bodies
 * (BenevolentUnionType.cpp) and the handlers identifying them on a class
 * entry, so UnionType's $this-calls take the direct path for a benevolent
 * union too; UNDEF = pending exception */
zv::Val pt_union_benevolent_union_results(zend_object *self, const phpstanturbo::UnionMemberOp &op);
zv::Val pt_union_benevolent_union_types(zend_object *self, const phpstanturbo::UnionMemberOp &op);
zv::Val pt_union_benevolent_pick_from_types(zend_object *self, const phpstanturbo::UnionMemberOp &op, const phpstanturbo::UnionCriteria &criteria);
zif_handler pt_union_benevolent_union_results_handler();
zif_handler pt_union_benevolent_union_types_handler();
zif_handler pt_union_benevolent_pick_from_types_handler();

/* TypeUtils::toBenevolentUnion($type): the type itself for a
 * BenevolentUnionType, new BenevolentUnionType($type->getTypes()) for any
 * other UnionType, the type otherwise; UNDEF = pending exception */
zv::Val pt_union_to_benevolent(zval *type);

/* new IntersectionType($types) ($types consumed); UNDEF = pending exception */
zv::Val pt_intersection_of(zv::Arr types);
/* new BenevolentUnionType($types) ($types consumed); UNDEF = pending exception */
zv::Val pt_union_benevolent_of(zv::Arr types);

/* $value instanceof UnionType / BenevolentUnionType / IntersectionType —
 * the shadowed classes, whose class entries the native code holds (the
 * class-map shape kept for the call sites that switched from the table) */
static inline bool pt_union_type_instanceof(zval *value, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), pt_ce_union_type);
	return true;
}

static inline bool pt_union_benevolent_instanceof(zval *value, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), pt_ce_benevolent_union_type);
	return true;
}

static inline bool pt_intersection_type_instanceof(zval *value, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), pt_ce_intersection_type);
	return true;
}

/* CombinationsHelper::combinations($arrays) — the shadowing class's body
 * (CombinationsHelper.cpp); UNDEF = pending exception */
zv::Val pt_combinations_helper_combinations(zval *arrays);

/* }}} */

/* merged from the parallel port branch */
/* {{{ helpers of the Type-kernel classes (TypeTraverser.cpp,
 * VerbosityLevel.cpp, RecursionGuard.cpp, FiniteTypeSet.cpp) — the
 * support.h entry points as owned values; UNDEF = pending exception */

/* VerbosityLevel::typeOnly() / value() / precise() / cache() for a
 * PT_VERBOSITY_LEVEL_* value (the twin's singleton) */
inline zv::Val pt_type_verbosity_level(zend_long value)
{
	zval *level = pt_verbosity_level_singleton(value);
	return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
}

/* VerbosityLevel::getRecommendedLevelByType($acceptingType, $acceptedType)
 * ($acceptedType NULL for null) */
inline zv::Val pt_type_verbosity_recommended(zval *acceptingType, zval *acceptedType)
{
	zval result;
	return pt_verbosity_level_recommended(&result, acceptingType, acceptedType) ? zv::Val::adopt(result) : zv::Val();
}

/* TypeTraverser::map($type, $cb) */
inline zv::Val pt_type_traverser_map_of(zval *type, zval *cb)
{
	zval result;
	return pt_type_traverser_map(&result, type, cb) ? zv::Val::adopt(result) : zv::Val();
}

/* RecursionGuard::run($type, $callback) / runOnObjectIdentity($type, $callback) */
inline zv::Val pt_type_recursion_guard_run(zval *type, zval *callback)
{
	zval result;
	return pt_recursion_guard_run(&result, type, callback) ? zv::Val::adopt(result) : zv::Val();
}

inline zv::Val pt_type_recursion_guard_run_on_object_identity(zval *type, zval *callback)
{
	zval result;
	return pt_recursion_guard_run_on_object_identity(&result, type, callback) ? zv::Val::adopt(result) : zv::Val();
}

/* FiniteTypeSet::create($types) (the set or null) / FiniteTypeSet::key($type)
 * (a string or null) */
inline zv::Val pt_type_finite_type_set_create(zval *types)
{
	zval result;
	return pt_finite_type_set_create(&result, types) ? zv::Val::adopt(result) : zv::Val();
}

inline zv::Val pt_type_finite_type_set_key(zval *type)
{
	zval result;
	return pt_finite_type_set_key(&result, type) ? zv::Val::adopt(result) : zv::Val();
}

/* }}} */

/* merged from the parallel port branch */
/* {{{ helpers of the small Type classes (ErrorType.cpp,
 * NonAcceptingNeverType.cpp, StringAlwaysAcceptingObjectWithToStringType.cpp,
 * StringNeverAcceptingObjectWithToStringType.cpp), for the children's
 * parent:: calls */
/* parent::__construct($isExplicitMixed, $subtractedType) — MixedType's
 * constructor body on the object ($subtractedType NULL for null)
 * (MixedType.cpp) */
void pt_mixed_type_construct(zend_object *self, bool isExplicitMixed, zval *subtractedType);
/* the body of MixedType::describe() run on the object (its own class
 * answering the $this-calls inside it, as parent:: keeps it); UNDEF =
 * pending exception (MixedType.cpp) */
zv::Val pt_mixed_type_describe(zend_object *self, zval *level);
/* parent::__construct($isExplicit, $reason) — NeverType's constructor body
 * on the object ($reason NULL for null) (NeverType.cpp) */
void pt_never_type_construct(zend_object *self, bool isExplicit, zend_string *reason);
/* the body of StringType::accepts() run on the object (StringType.cpp);
 * UNDEF = pending exception */
zv::Val pt_string_type_accepts(zend_object *self, zval *type, bool strictTypes);
/* the body of JustNullableTypeTrait::isSuperTypeOf() run on the object
 * with `self` bound to scope — the class using the trait, as parent::
 * from a child of that class binds it; UNDEF = pending exception */
zv::Val pt_type_just_nullable_is_super_type_of(zend_object *self, zend_class_entry *scope, zval *type);
/* TypeUtils::flattenTypes($type) — the shadowing class's body
 * (TypeUtils.cpp); UNDEF = pending exception */
zv::Val pt_type_utils_flatten_types(zval *type);

/* merged from the parallel port branch */
/* }}} */

/* merged from the parallel port branch */
/* {{{ helpers of the template-type classes (TemplateTypeVariance.cpp,
 * TemplateTypeVarianceMap.cpp, TemplateTypeMap.cpp, TemplateTypeHelper.cpp) */

/* TemplateTypeVariance::create*() for a PT_TEMPLATE_TYPE_VARIANCE_* value,
 * as an owned copy of the singleton; UNDEF = pending exception */
zv::Val pt_type_template_type_variance(zend_long value);
/* TemplateTypeVarianceMap::createEmpty() / new TemplateTypeVarianceMap($variances)
 * ($variances borrowed); UNDEF = pending exception */
zv::Val pt_type_template_type_variance_map_empty();
zv::Val pt_type_template_type_variance_map_new(zval *variances);
/* TemplateTypeMap::createEmpty() / new TemplateTypeMap($types, $lowerBoundTypes)
 * (the arrays borrowed, $lowerBoundTypes NULL for []); UNDEF = pending
 * exception */
zv::Val pt_type_template_type_map_empty();
zv::Val pt_type_template_type_map_new(zval *types, zval *lowerBoundTypes = NULL);
/* TemplateTypeHelper::resolveTemplateTypes() / resolveToDefaults() /
 * resolveToBounds() / toArgument() (the arguments borrowed); UNDEF =
 * pending exception */
zv::Val pt_type_template_type_helper_resolve_template_types(zval *type, zval *standins, zval *callSiteVariances, zval *positionVariance, bool keepErrorTypes);
zv::Val pt_type_template_type_helper_resolve_to_defaults(zval *type);
zv::Val pt_type_template_type_helper_resolve_to_bounds(zval *type);
zv::Val pt_type_template_type_helper_to_argument(zval *type);
/* $reference->getType() / ->getPositionVariance() of a reference instance
 * (TemplateTypeReference.cpp): the slots of a native instance, the methods
 * of anything else (the PHP twin declared next to the native class in the
 * differential tests); owned copies, false = pending exception */
[[nodiscard]] bool pt_template_type_reference_parts(zval *reference, zv::Val &type, zv::Val &positionVariance);

/* }}} */

/* merged from the parallel port branch */
/* merged from the parallel port branch */
/* {{{ the late-resolvable family (KeyOfType.cpp, ValueOfType.cpp,
 * OffsetAccessType.cpp, ClassConstantAccessType.cpp, NewObjectType.cpp,
 * ConditionalType.cpp, ConditionalTypeForParameter.cpp,
 * LateResolvableArrayShapeType.cpp, UnresolvedTemplateArgumentType.cpp) */

/* src/Type/Traits/LateResolvableTypeTrait.php — declares the trait's
 * `private ?Type $result = null` on the class too, behind the class's own
 * properties as PHP binds a trait's properties, so run it after the
 * class's own methods and properties */
void pt_type_trait_late_resolvable(reg::Class &cls);
/* $this->resolve() on an object of a class using the trait: the trait's
 * body (`$this->result ??= $this->getResult()`) directly when the
 * object's resolve() is the native one, the object's own method otherwise;
 * scope is the class the trait is used in (where its $result slot lives);
 * UNDEF = pending exception */
zv::Val pt_type_late_resolvable_resolve(zend_object *self, zend_class_entry *scope);
/* the trait's private isSuperTypeOfDefault($type) body, for the classes
 * whose own isSuperTypeOf() falls back to it; UNDEF = pending exception */
zv::Val pt_type_late_resolvable_is_super_type_of_default(zend_object *self, zend_class_entry *scope, zval *type);
/* TypeUtils::containsTemplateType($type) — the shadowing class's body
 * (TypeUtils.cpp); false = pending exception */
[[nodiscard]] bool pt_type_utils_contains_template_type(zval *type, bool &out);
/* sprintf('<identifier><%s>', $type->describe($level)) — the describe()
 * body key-of<>, value-of<> and new<> share; UNDEF = pending exception */
zv::Val pt_type_describe_generic_of(const char *identifier, size_t identifierLen, zval *type, zval *level);
/* new GenericTypeNode(new IdentifierTypeNode($identifier), [$type->toPhpDocNode()]);
 * UNDEF = pending exception */
zv::Val pt_type_generic_node_of(const char *identifier, size_t identifierLen, zval *type);
/* $cb($type) / $cb($type, $right) for a zpp-parsed traverse callback,
 * checked to return an object (the twins' `callable(Type): Type`); UNDEF =
 * pending exception */
zv::Val pt_type_traverse_call(zend_fcall_info *fci, zend_fcall_info_cache *fcc, zval *type, zval *right = NULL);
/* `$a === $b` on two type zvals (the twins' identity checks after a
 * traverse) */
static inline bool pt_type_same_object(zval *a, zval *b)
{
	return Z_TYPE_P(a) == IS_OBJECT && Z_TYPE_P(b) == IS_OBJECT && Z_OBJ_P(a) == Z_OBJ_P(b);
}

/* }}} */

/* merged from the parallel port branch */
/* {{{ the template family (the TemplateTypeTrait registrar below; the
 * Template*Type.cpp files, TemplateTypeArgumentStrategy.cpp,
 * TemplateTypeParameterStrategy.cpp, TemplateTypeFactory.cpp) */

namespace ptcls {
inline constexpr const char *templateType = "PHPStan\\Type\\Generic\\TemplateType";
inline constexpr const char *templateTypeScope = "PHPStan\\Type\\Generic\\TemplateTypeScope";
inline constexpr const char *templateTypeStrategy = "PHPStan\\Type\\Generic\\TemplateTypeStrategy";
} // namespace ptcls

namespace ptret {
inline constexpr reg::Arg templateType = reg::obj("", ptcls::templateType);
inline constexpr reg::Arg templateTypeScope = reg::obj("", ptcls::templateTypeScope);
inline constexpr reg::Arg templateTypeStrategy = reg::obj("", ptcls::templateTypeStrategy);
inline constexpr reg::Arg templateTypeVariance = reg::obj("", ptcls::templateTypeVariance);
} // namespace ptret

/* src/Type/Generic/TemplateTypeTrait.php — besides the trait's methods the
 * registrar declares its six private properties (name, scope, strategy,
 * variance, bound, default — in that order) on the class, as the class's
 * LAST slots: a class running it declares no property of its own afterwards.
 * `self` inside the trait is the class using it (the handler's scope), the
 * $this-calls go through the object's class entry with the direct path
 * when the object is exactly that class (TemplateObjectWithoutClassType is
 * not final). */
void pt_type_trait_template_type(reg::Class &cls);

/* the twins' constructor tails after parent::__construct(): the six slots
 * written in the twin's order (scope, strategy, variance, name, bound,
 * default); scope is the class using the trait, every argument borrowed
 * (defaultType NULL or IS_NULL for null) */
void pt_template_type_init(zend_object *self, zend_class_entry *scope, zval *templateScope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);

/* the twins' `<Parent> $bound` constructor parameter: the bound must be an
 * instance of the class's parent — the TypeError the engine raises for the
 * twin otherwise (argument number argNo); false = pending exception */
[[nodiscard]] bool pt_template_type_check_bound(zend_class_entry *scope, zval *bound, uint32_t argNo);

/* parent::__construct(...$args) — the parent's constructor run on the
 * object; false = pending exception */
[[nodiscard]] bool pt_template_type_parent_construct(zend_object *self, zend_class_entry *scope, uint32_t argc, zval *argv);

/* the six borrowed slots of a class using the trait, read as the twin
 * reads `$this->name` etc. from inside the trait (scope the class using
 * it — the slot is right for a PHP subclass instance too); NULL with an
 * Error pending when uninitialized, as the typed-property read raises */
zend_string *pt_template_type_name(zend_object *object, zend_class_entry *scope);
zval *pt_template_type_scope(zend_object *object, zend_class_entry *scope);
zval *pt_template_type_strategy(zend_object *object, zend_class_entry *scope);
zval *pt_template_type_variance(zend_object *object, zend_class_entry *scope);
zval *pt_template_type_bound(zend_object *object, zend_class_entry *scope);
zval *pt_template_type_default(zend_object *object, zend_class_entry *scope);

/* the trait's isSuperTypeOf() / isSubTypeOf() bodies run on the object
 * (the $this-calls inside them through its class entry) — for the
 * `$this->isSuperTypeOf()` / `$this->isSubTypeOf()` of the final classes
 * overriding isSuperTypeOfMixed() and isAcceptedBy(); UNDEF = pending
 * exception */
zv::Val pt_template_type_is_super_type_of(zend_object *self, zend_class_entry *scope, zval *type);
zv::Val pt_template_type_is_sub_type_of(zend_object *self, zend_class_entry *scope, zval *type);

/* TemplateTypeFactory::create($scope, $name, $bound, $variance, $strategy,
 * $default) — the shadowing class's body (TemplateTypeFactory.cpp); $bound /
 * $strategy / $default NULL or IS_NULL for null; UNDEF = pending exception */
zv::Val pt_template_type_factory_create(zval *scope, zval *name, zval *bound, zval *variance, zval *strategy, zval *defaultType);

/* the constructor parameters every Template*Type twin declares; boundClass
 * is the persistent literal of the twin's bound class (its parent) */
#define PT_TEMPLATE_TYPE_CTOR_ARGS(boundClass) \
	{ reg::obj("scope", ptcls::templateTypeScope), reg::obj("templateTypeStrategy", ptcls::templateTypeStrategy), reg::obj("templateTypeVariance", ptcls::templateTypeVariance), reg::stringArg("name"), reg::obj("bound", boundClass), reg::obj("default", ptcls::type, true) }

/* the parsed constructor arguments (borrowed) */
struct pt_template_ctor_args
{
	zval *scope;
	zval *strategy;
	zval *variance;
	zend_string *name;
	zval *bound;
	zval *defaultType;
};

/* the constructor's parameter parsing, with the bound checked against the
 * class's parent as the twin's typed parameter checks it; throws out of
 * the handler on failure */
#define PT_TEMPLATE_TYPE_PARSE_CTOR(args) \
	ZEND_PARSE_PARAMETERS_START(6, 6) \
		Z_PARAM_OBJECT((args).scope) \
		Z_PARAM_OBJECT((args).strategy) \
		Z_PARAM_OBJECT((args).variance) \
		Z_PARAM_STR((args).name) \
		Z_PARAM_OBJECT((args).bound) \
		Z_PARAM_OBJECT_OR_NULL((args).defaultType) \
	ZEND_PARSE_PARAMETERS_END(); \
	if (UNEXPECTED(!pt_template_type_check_bound(EX(func)->common.scope, (args).bound, 5))) { \
		RETURN_THROWS(); \
	}

/* the strategies (TemplateTypeArgumentStrategy.cpp,
 * TemplateTypeParameterStrategy.cpp): new <Strategy>() and the accepts()
 * bodies, for the trait's `$this->strategy->accepts($this, $type,
 * $strictTypes)` direct path; UNDEF = pending exception */
zv::Val pt_template_type_argument_strategy_create();
zv::Val pt_template_type_parameter_strategy_create();
zv::Val pt_template_type_argument_strategy_accepts(zval *left, zval *right, bool strictTypes);
zv::Val pt_template_type_parameter_strategy_accepts(zval *left, zval *right, bool strictTypes);

/* new GenericClassStringType($type) (the shadowing class,
 * GenericClassStringType.cpp); UNDEF = pending exception */
zv::Val pt_type_new_generic_class_string(zval *type);

/* }}} */

/* the Template*Type twins' shared bodies: new <Class>(...) with the bound
 * checked as the twin's typed parameter checks it and the handle's
 * construct() run; the constructor of a twin whose parent takes no
 * arguments (parent::__construct(), then the trait's slot writes); the same
 * template type rebuilt around another bound (an UNDEF bound passes a
 * pending exception through). UNDEF / false = pending exception */
template <typename Handle>
zv::Val pt_template_type_create(zend_class_entry *ce, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	if (UNEXPECTED(!pt_template_type_check_bound(ce, bound, 5))) return zv::Val();
	zval object;
	if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
	zv::Val created = zv::Val::adopt(object);
	if (UNEXPECTED(!Handle(Z_OBJ(object)).construct(scope, strategy, variance, name, bound, defaultType))) return zv::Val();
	return created;
}

inline bool pt_template_type_construct(zend_object *self, zend_class_entry *ce, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	if (UNEXPECTED(!pt_template_type_parent_construct(self, ce, 0, NULL))) return false;
	pt_template_type_init(self, ce, scope, strategy, variance, name, bound, defaultType);
	return true;
}

template <typename Handle>
zv::Val pt_template_type_rebuild(zend_object *self, zend_class_entry *ce, zv::Val bound)
{
	if (UNEXPECTED(bound.isUndef())) return zv::Val();
	zend_string *name = pt_template_type_name(self, ce);
	if (UNEXPECTED(name == NULL)) return zv::Val();
	zval *scope = pt_template_type_scope(self, ce);
	zval *strategy = scope == NULL ? NULL : pt_template_type_strategy(self, ce);
	zval *variance = strategy == NULL ? NULL : pt_template_type_variance(self, ce);
	zval *defaultType = variance == NULL ? NULL : pt_template_type_default(self, ce);
	if (UNEXPECTED(defaultType == NULL)) return zv::Val();
	return Handle::create(scope, strategy, variance, name, bound.raw(), defaultType);
}

/* {{{ bodies the Type ports share verbatim — their members forward here */

/* $this as an owned value (a new reference) */
inline zv::Val pt_this_value(zend_object *self)
{
	zval selfZv;
	ZVAL_OBJ(&selfZv, self);
	return zv::Val::copyOf(zv::Ref(&selfZv));
}

/* a declared typed property slot of scope; NULL with the Error the
 * typed-property read raises pending when it was never initialized */
inline zval *pt_typed_slot(zend_object *object, uint32_t index, zend_class_entry *scope, const char *name)
{
	zval *p = OBJ_PROP_NUM(object, index);
	if (UNEXPECTED(Z_TYPE_P(p) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(scope->name), name);
		return NULL;
	}
	return p;
}

/* a constructor's write of a declared property slot: overwritten in place
 * (a repeated parent::__construct() call from a subclass would otherwise
 * leak the first value) and no longer IS_PROP_UNINIT */
inline void pt_write_slot(zend_object *self, uint32_t index, zval *value)
{
	zval *p = OBJ_PROP_NUM(self, index);
	zval previous;
	ZVAL_COPY_VALUE(&previous, p);
	ZVAL_COPY(p, value);
	Z_PROP_FLAG_P(p) = 0;
	if (Z_TYPE(previous) != IS_UNDEF) {
		zval_ptr_dtor(&previous);
	}
}

/* new Class() of a class entry whose constructor is not run; UNDEF = pending exception */
inline zv::Val pt_new_instance(zend_class_entry *ce)
{
	zval object;
	if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
	return zv::Val::adopt(object);
}

/* an owned value into *out; false (nothing written) when it is UNDEF */
inline bool pt_val_into(zv::Val value, zval *out)
{
	if (UNEXPECTED(value.isUndef())) return false;
	*out = value.take();
	return true;
}

/* the value a `bool producer(zval *out)` writes; UNDEF when it fails */
template <bool (*Producer)(zval *)>
zend_always_inline zv::Val pt_val_of()
{
	zval result;
	if (UNEXPECTED(!Producer(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

/* ->toAcceptsResult() of an isSubTypeOf() result, the result checked to be
 * an object as the Type interface's return type does; UNDEF = pending exception */
inline zv::Val pt_type_sub_type_to_accepts_result(zv::Val result)
{
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
		zend_type_error("phpstan_turbo: isSubTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
}

/* $type instanceof UnionType || $type instanceof IntersectionType */
inline bool pt_type_is_union_or_intersection(zval *type, bool &out)
{
	if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_union_type, out))) return false;
	if (out) return true;
	return pt_type_instanceof_ce(type, pt_ce_intersection_type, out);
}

/* a string type's hasOffsetValueType(): $offsetType->isInteger()->and(maybe);
 * -1 = pending exception */
[[nodiscard]] inline zend_long pt_type_string_has_offset_value_type(zval *offsetType)
{
	zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isinteger"), 0, NULL);
	if (UNEXPECTED(isInteger < 0)) return -1;
	return isInteger < PT_TRI_MAYBE ? isInteger : PT_TRI_MAYBE;
}

/* a scalar type's toArray(): the array{$this} ConstantArrayType; UNDEF = pending exception */
inline zv::Val pt_type_scalar_to_array(zend_object *self)
{
	zv::Val zero = pt_type_new_constant_integer(0);
	if (UNEXPECTED(zero.isUndef())) return zv::Val();
	zv::Arr keyTypes = zv::Arr::create(1);
	keyTypes.push(std::move(zero));
	zv::Arr valueTypes = zv::Arr::create(1);
	valueTypes.push(zv::Ref(&*zv::Val(pt_this_value(self)).raw()));
	zv::Arr nextAutoIndexes = zv::Arr::create(1);
	nextAutoIndexes.push(zv::Val::integer(1));
	zval optionalKeys, result;
	ZVAL_EMPTY_ARRAY(&optionalKeys);
	if (UNEXPECTED(!pt_constant_array_type_new(&result, keyTypes.raw(), valueTypes.raw(), nextAutoIndexes.raw(), &optionalKeys, pt_trinary_singleton(PT_TRI_YES)))) {
		return zv::Val();
	}
	return zv::Val::adopt(result);
}

/* $object->method(...$args) read as a bool; false = pending exception. These four
 * are static (a copy per translation unit, no inline hint) like the
 * file-local helpers they replaced, so the inliner decides as it did. */
[[nodiscard, maybe_unused]] static bool pt_type_call_bool(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* the same as 1 / 0, -1 = pending exception */
[[nodiscard, maybe_unused]] static int pt_type_call_is_true(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return zend_is_true(result.raw()) ? 1 : 0;
}

/* the PT_TRI_* value of the result object of $object->method(...$args)
 * (an AcceptsResult / IsSuperTypeOfResult / TrinaryLogic); -1 = pending exception */
[[nodiscard, maybe_unused]] static zend_long pt_type_call_result_trinary(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $object->method(...$args) of a method declared to return array, with the
 * TypeError a PHP override returning anything else gets; UNDEF = pending exception */
[[maybe_unused]] static zv::Val pt_type_call_array(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
		zend_type_error("phpstan_turbo: %s::%s() must return array, %s returned", ZSTR_VAL(object->ce->name), lcname, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/*
 * $this->method(...$args) from a handle: the native member (direct) when the
 * object is exactly the handle's class (exact) or its method of that name is
 * still the native handler — a PHP subclass that does not override it —
 * and through the object's class entry otherwise. The one rule every
 * $this-call of a non-final native class follows; the _bool / _trinary
 * variants read the dispatched result as the direct member returns it.
 */
template <typename Direct>
zend_always_inline zv::Val pt_this_call(zend_object *self, bool exact, const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct)
{
	if (EXPECTED(exact || pt_type_method_is(self, lcname, len, handler))) return direct();
	return pt_type_call(self, lcname, len, argc, argv);
}

template <typename Direct>
[[nodiscard]] zend_always_inline zend_long pt_this_call_trinary(zend_object *self, bool exact, const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct)
{
	if (EXPECTED(exact || pt_type_method_is(self, lcname, len, handler))) return direct();
	return pt_type_call_trinary(self, lcname, len, argc, argv);
}

/* $object->method(...$args) of a method declared to return Type, with the
 * TypeError the engine raises when a PHP override returns anything else;
 * UNDEF = pending exception */
inline zv::Val pt_type_call_type(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
		zend_type_error("phpstan_turbo: %s::%s() must return %s, %s returned", ZSTR_VAL(object->ce->name), lcname, ptcls::type, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* }}} */

#endif /* PHPSTANTURBO_TYPETRAITS_H */
