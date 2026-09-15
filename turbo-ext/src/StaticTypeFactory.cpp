/*
 * PHPStanTurbo\StaticTypeFactory — native implementation of
 * PHPStan\Type\StaticTypeFactory.
 *
 * Declared as PHPStan\Type\StaticTypeFactory itself at activation: final,
 * static factories only. The twin memoizes falsey(), truthy(),
 * generalOffsetAccessibleType() and intOffsetAccessibleType() in function
 * statics (one instance per request each); natively the same four values
 * live in request-scoped globals, created on first use and released at
 * request shutdown, so every call hands out the same instance the twin
 * would.
 *
 * Every class the twin instantiates is a shadowed one, built through its
 * exported constructor.
 */

#include "TypeTraits.h"
#include "generated/StaticTypeFactory.h"

zend_class_entry *pt_ce_static_type_factory = nullptr;

/* the twin's function statics */
static zval pt_stf_falsey;
static zval pt_stf_truthy;
static zval pt_stf_general_offset_accessible;
static zval pt_stf_int_offset_accessible;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StaticTypeFactory. */
class StaticTypeFactory
{
public:
	/* static $falsey ??= TypeCombinator::union(null, false, 0, 0.0, '', '0', array{}) */
	static zv::Val falsey()
	{
		if (Z_TYPE(pt_stf_falsey) == IS_UNDEF) {
			zv::Val members[7];
			zval raw;
			if (UNEXPECTED(!pt_null_type_new(&raw))) return zv::Val();
			members[0] = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_constant_boolean_type_new(&raw, false))) return zv::Val();
			members[1] = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_constant_integer_type_new(&raw, 0))) return zv::Val();
			members[2] = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_constant_float_type_new(&raw, 0.0))) return zv::Val();
			members[3] = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_constant_string_type_new(&raw, ZSTR_EMPTY_ALLOC()))) return zv::Val();
			members[4] = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_constant_string_type_new(&raw, ZSTR_CHAR('0')))) return zv::Val();
			members[5] = zv::Val::adopt(raw);
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			if (UNEXPECTED(!pt_constant_array_type_new(&raw, &empty, &empty))) return zv::Val();
			members[6] = zv::Val::adopt(raw);
			zval args[7];
			for (int i = 0; i < 7; i++) {
				ZVAL_COPY_VALUE(&args[i], members[i].raw());
			}
			zv::Val result = pt_type_combinator_union(7, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			ZVAL_COPY(&pt_stf_falsey, result.raw());
		}
		return zv::Val::copyOf(zv::Ref(&pt_stf_falsey));
	}

	/* static $truthy ??= new MixedType(subtractedType: self::falsey()) */
	static zv::Val truthy()
	{
		if (Z_TYPE(pt_stf_truthy) == IS_UNDEF) {
			zv::Val falseyType = falsey();
			if (UNEXPECTED(falseyType.isUndef())) return zv::Val();
			zval raw;
			if (UNEXPECTED(!pt_mixed_type_new(&raw, false, falseyType.raw()))) return zv::Val();
			ZVAL_COPY_VALUE(&pt_stf_truthy, &raw);
		}
		return zv::Val::copyOf(zv::Ref(&pt_stf_truthy));
	}

	/* new IntersectionType([new ArrayType(int<0, max>, string), new
	 * NonEmptyArrayType(), new AccessoryArrayListType()]) */
	static zv::Val argv()
	{
		zval zero;
		ZVAL_LONG(&zero, 0);
		zv::Val keyType = pt_integer_range_create_all_greater_than_or_equal_to(&zero);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zval raw;
		if (UNEXPECTED(!pt_string_type_new(&raw))) return zv::Val();
		zv::Val itemType = zv::Val::adopt(raw);
		if (UNEXPECTED(!pt_array_type_new(&raw, keyType.raw(), itemType.raw()))) return zv::Val();
		zv::Arr types = zv::Arr::create(3);
		types.push(zv::Val::adopt(raw));
		if (UNEXPECTED(!pt_non_empty_array_type_new(&raw))) return zv::Val();
		types.push(zv::Val::adopt(raw));
		if (UNEXPECTED(!pt_accessory_array_list_type_new(&raw))) return zv::Val();
		types.push(zv::Val::adopt(raw));
		return pt_intersection_of(std::move(types));
	}

	/* IntegerRangeType::fromInterval(1, null) */
	static zv::Val argc()
	{
		return pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0);
	}

	/* static $generalOffsetAccessible ??= TypeCombinator::union(array<mixed, mixed>, ArrayAccess, null) */
	static zv::Val generalOffsetAccessibleType()
	{
		if (Z_TYPE(pt_stf_general_offset_accessible) == IS_UNDEF) {
			zv::Val mixedKey = pt_type_new_mixed_type();
			if (UNEXPECTED(mixedKey.isUndef())) return zv::Val();
			zv::Val mixedItem = pt_type_new_mixed_type();
			if (UNEXPECTED(mixedItem.isUndef())) return zv::Val();
			zval raw;
			if (UNEXPECTED(!pt_array_type_new(&raw, mixedKey.raw(), mixedItem.raw()))) return zv::Val();
			zv::Val arrayType = zv::Val::adopt(raw);
			zend_string *arrayAccess = zend_string_init("ArrayAccess", sizeof("ArrayAccess") - 1, 0);
			bool created = pt_object_type_new(&raw, arrayAccess);
			zend_string_release(arrayAccess);
			if (UNEXPECTED(!created)) return zv::Val();
			zv::Val objectType = zv::Val::adopt(raw);
			if (UNEXPECTED(!pt_null_type_new(&raw))) return zv::Val();
			zv::Val nullType = zv::Val::adopt(raw);
			zv::Args args{arrayType.raw(), objectType.raw(), nullType.raw()};
			zv::Val result = pt_type_combinator_union(3, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			ZVAL_COPY(&pt_stf_general_offset_accessible, result.raw());
		}
		return zv::Val::copyOf(zv::Ref(&pt_stf_general_offset_accessible));
	}

	/* static $intOffsetAccessible ??= TypeCombinator::union(self::generalOffsetAccessibleType(), new StringType()) */
	static zv::Val intOffsetAccessibleType()
	{
		if (Z_TYPE(pt_stf_int_offset_accessible) == IS_UNDEF) {
			zv::Val general = generalOffsetAccessibleType();
			if (UNEXPECTED(general.isUndef())) return zv::Val();
			zval raw;
			if (UNEXPECTED(!pt_string_type_new(&raw))) return zv::Val();
			zv::Val stringType = zv::Val::adopt(raw);
			zv::Args args{general.raw(), stringType.raw()};
			zv::Val result = pt_type_combinator_union(2, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			ZVAL_COPY(&pt_stf_int_offset_accessible, result.raw());
		}
		return zv::Val::copyOf(zv::Ref(&pt_stf_int_offset_accessible));
	}
};

} // namespace phpstanturbo

using phpstanturbo::StaticTypeFactory;

/* {{{ exported helpers and lifecycle */

zv::Val pt_static_type_factory_falsey()
{
	return StaticTypeFactory::falsey();
}

zv::Val pt_static_type_factory_truthy()
{
	return StaticTypeFactory::truthy();
}

zv::Val pt_static_type_factory_argc()
{
	return StaticTypeFactory::argc();
}

zv::Val pt_static_type_factory_argv()
{
	return StaticTypeFactory::argv();
}

zv::Val pt_static_type_factory_general_offset_accessible()
{
	return StaticTypeFactory::generalOffsetAccessibleType();
}

zv::Val pt_static_type_factory_int_offset_accessible()
{
	return StaticTypeFactory::intOffsetAccessibleType();
}

void pt_static_type_factory_rinit()
{
	ZVAL_UNDEF(&pt_stf_falsey);
	ZVAL_UNDEF(&pt_stf_truthy);
	ZVAL_UNDEF(&pt_stf_general_offset_accessible);
	ZVAL_UNDEF(&pt_stf_int_offset_accessible);
}

void pt_static_type_factory_rshutdown()
{
	zval_ptr_dtor(&pt_stf_int_offset_accessible);
	zval_ptr_dtor(&pt_stf_general_offset_accessible);
	zval_ptr_dtor(&pt_stf_truthy);
	zval_ptr_dtor(&pt_stf_falsey);
	pt_static_type_factory_rinit();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

/* one handler per argument-less factory returning through fn */
#define PT_STATIC_TYPE_FACTORY_0(fn) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		ZEND_PARSE_PARAMETERS_NONE(); \
		PT_RETURN_VAL(StaticTypeFactory::fn()); \
	}

void pt_register_static_type_factory()
{
	reg::Class cls("PHPStan\\Type\\StaticTypeFactory");
	ptdecl::StaticTypeFactory::declareClass(cls);
	ptdecl::StaticTypeFactory::declareProperties(cls);

	cls.method("falsey", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(falsey), &ptret::type);
	cls.method("truthy", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(truthy), &ptret::type);
	cls.method("argv", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(argv), &ptret::type);
	cls.method("argc", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(argc), &ptret::type);
	cls.method("generalOffsetAccessibleType", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(generalOffsetAccessibleType), &ptret::type);
	cls.method("intOffsetAccessibleType", reg::PublicStatic, 0, {}, PT_STATIC_TYPE_FACTORY_0(intOffsetAccessibleType), &ptret::type);

	cls.shadow(&pt_ce_static_type_factory);
}

/* }}} */
