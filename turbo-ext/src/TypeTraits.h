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

inline constexpr const char *type = "PHPStan\\Type\\Type";
inline constexpr const char *trinaryLogic = "PHPStan\\TrinaryLogic";
inline constexpr const char *phpVersion = "PHPStan\\Php\\PhpVersion";
inline constexpr const char *verbosityLevel = "PHPStan\\Type\\VerbosityLevel";
inline constexpr const char *generalizePrecision = "PHPStan\\Type\\GeneralizePrecision";
inline constexpr const char *classMemberAccessAnswerer = "PHPStan\\Reflection\\ClassMemberAccessAnswerer";
inline constexpr const char *reflectionProvider = "PHPStan\\Reflection\\ReflectionProvider";
inline constexpr const char *templateTypeVariance = "PHPStan\\Type\\Generic\\TemplateTypeVariance";

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

/* }}} */

/* {{{ bodies the Type ports share verbatim — their members forward here */

/* $this as an owned value (a new reference) */
inline zv::Val pt_this_value(zend_object *self)
{
	zval selfZv;
	ZVAL_OBJ(&selfZv, self);
	return zv::Val::copyOf(zv::Ref(&selfZv));
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

/* }}} */

#endif /* PHPSTANTURBO_TYPETRAITS_H */
