/*
 * PHPStanTurbo\UnionTypeHelper — native implementation of
 * PHPStan\Type\UnionTypeHelper.
 *
 * Declared as PHPStan\Type\UnionTypeHelper itself at activation: final,
 * static helpers only. sortTypes() is the twin's usort() over the same
 * comparator: the engine's own zend_hash_sort() (the hybrid insertion
 * sort usort() runs, with the stable-sort fallback on the elements'
 * original positions that PHP 8 usort() applies), so the members come out
 * in exactly the order the twin produces, comparator calls included — the
 * comparator describes members through their own describe() and the
 * shadowed VerbosityLevel's singletons.
 *
 * The shadowed classes the comparator tests (NullType, ConstantBooleanType,
 * ConstantIntegerType, ConstantFloatType, IntegerRangeType, IntegerType,
 * ConstantStringType, EnumCaseObjectType, CallableType, ClosureType) are
 * tested through the class entries the native code holds; the PHP
 * interfaces (AccessoryType, ConstantScalarType) through the class map.
 */

#include "TypeTraits.h"
#include "generated/UnionTypeHelper.h"

namespace sigs = ptdecl::UnionTypeHelper::sig;

zend_class_entry *pt_ce_union_type_helper = nullptr;

/* the twin's inline threshold: lists longer than this are returned unsorted */
#define PT_UTH_SORT_LIMIT 1024

namespace phpstanturbo {

/* Mirrors PHPStan\Type\UnionTypeHelper. */
class UnionTypeHelper
{
public:
	/* usort($types, <the comparator>) over a copy of the list — the twin's
	 * by-value parameter; the list unchanged beyond the limit; UNDEF =
	 * pending exception */
	static zv::Val sortTypes(zval *types)
	{
		HashTable *source = Z_ARRVAL_P(types);
		if (zend_hash_num_elements(source) > PT_UTH_SORT_LIMIT) return zv::Val::copyOf(zv::Ref(types));
		/* usort() separates its by-reference argument: a private copy */
		zv::Arr sorted = zv::Arr::adoptTable(zend_array_dup(source));
		zend_hash_sort(sorted.table(), compare, 1);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return zv::Val(std::move(sorted));
	}

	/* the private compareStrings($a, $b): strcasecmp(), then `$a <=> $b` */
	static zend_long compareStrings(zend_string *a, zend_string *b)
	{
		int cmp = zend_binary_strcasecmp(ZSTR_VAL(a), ZSTR_LEN(a), ZSTR_VAL(b), ZSTR_LEN(b));
		if (cmp != 0) return cmp;
		zval left, right;
		ZVAL_STR(&left, a);
		ZVAL_STR(&right, b);
		return zend_compare(&left, &right);
	}

private:
	/* the bucket comparator usort() runs: the closure's answer normalized
	 * to -1/0/1 (ZEND_THREEWAY_COMPARE), ties broken by the original
	 * positions zend_hash_sort() stored in Z_EXTRA (PHP 8's stable sort);
	 * once an exception is pending every comparison answers 0, as the
	 * engine's user-callback comparator does */
	static int compare(Bucket *a, Bucket *b)
	{
		zend_long result = 0;
		if (EXPECTED(!EG(exception))) {
			bool ok = closure(&a->val, &b->val, result);
			if (UNEXPECTED(!ok)) {
				result = 0;
			}
		}
		int normalized = ZEND_THREEWAY_COMPARE(result, 0);
		if (normalized != 0) return normalized;
		if (Z_EXTRA(a->val) > Z_EXTRA(b->val)) return 1;
		if (Z_EXTRA(a->val) < Z_EXTRA(b->val)) return -1;
		return 0;
	}

	/* `static function (Type $a, Type $b): int`; false = pending exception */
	[[nodiscard]] static bool closure(zval *aZv, zval *bZv, zend_long &out)
	{
		ZVAL_DEREF(aZv);
		ZVAL_DEREF(bZv);
		/* the closure's `Type $a, Type $b` parameters: a non-Type member is
		 * a TypeError at its first comparison */
		bool aIsType, bIsType;
		if (UNEXPECTED(!pt_type_instanceof(aZv, PT_CLASS_TYPE, aIsType) || !pt_type_instanceof(bZv, PT_CLASS_TYPE, bIsType))) return false;
		if (UNEXPECTED(!aIsType || !bIsType)) {
			zend_type_error("phpstan_turbo: UnionTypeHelper::sortTypes() takes a list of %s, %s given", ptcls::type, zend_zval_value_name(aIsType ? bZv : aZv));
			return false;
		}
		zend_object *a = Z_OBJ_P(aZv);
		zend_object *b = Z_OBJ_P(bZv);

		if (instanceof_function(a->ce, pt_ce_null_type)) {
			out = 1;
			return true;
		} else if (instanceof_function(b->ce, pt_ce_null_type)) {
			out = -1;
			return true;
		}

		bool aAccessory, bAccessory;
		if (UNEXPECTED(!pt_type_instanceof(aZv, PT_CLASS_ACCESSORY_TYPE, aAccessory) || !pt_type_instanceof(bZv, PT_CLASS_ACCESSORY_TYPE, bAccessory))) {
			return false;
		}
		if (aAccessory) {
			if (bAccessory) return compareDescriptions(a, b, PT_VERBOSITY_LEVEL_VALUE, out);
			out = 1;
			return true;
		}
		if (bAccessory) {
			out = -1;
			return true;
		}

		bool aIsBool = instanceof_function(a->ce, pt_ce_constant_boolean_type);
		bool bIsBool = instanceof_function(b->ce, pt_ce_constant_boolean_type);
		if (aIsBool && !bIsBool) {
			out = 1;
			return true;
		} else if (bIsBool && !aIsBool) {
			out = -1;
			return true;
		}
		bool aScalar, bScalar;
		if (UNEXPECTED(!pt_type_instanceof(aZv, PT_CLASS_CONSTANT_SCALAR_TYPE, aScalar) || !pt_type_instanceof(bZv, PT_CLASS_CONSTANT_SCALAR_TYPE, bScalar))) {
			return false;
		}
		if (aScalar && !bScalar) {
			out = -1;
			return true;
		} else if (!aScalar && bScalar) {
			out = 1;
			return true;
		}

		bool aConstInt = instanceof_function(a->ce, pt_ce_constant_integer_type);
		bool aConstFloat = instanceof_function(a->ce, pt_ce_constant_float_type);
		bool bConstInt = instanceof_function(b->ce, pt_ce_constant_integer_type);
		bool bConstFloat = instanceof_function(b->ce, pt_ce_constant_float_type);
		if ((aConstInt || aConstFloat) && (bConstInt || bConstFloat)) {
			/* $a->getValue() <=> $b->getValue() */
			zv::Val aValue = pt_type_call(a, PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(aValue.isUndef())) return false;
			zv::Val bValue = pt_type_call(b, PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(bValue.isUndef())) return false;
			int cmp = zend_compare(aValue.raw(), bValue.raw());
			if (cmp != 0) {
				out = cmp;
				return true;
			}
			if (aConstInt && bConstFloat) {
				out = -1;
				return true;
			}
			if (bConstInt && aConstFloat) {
				out = 1;
				return true;
			}
			out = 0;
			return true;
		}

		bool aRange = instanceof_function(a->ce, pt_ce_integer_range_type);
		bool bRange = instanceof_function(b->ce, pt_ce_integer_range_type);
		if (aRange && bRange) {
			/* ($a->getMin() ?? PHP_INT_MIN) <=> ($b->getMin() ?? PHP_INT_MIN) */
			NullableLong aMin, aMax, bMin, bMax;
			if (UNEXPECTED(!pt_integer_range_bounds(a, aMin, aMax) || !pt_integer_range_bounds(b, bMin, bMax))) return false;
			zend_long left = aMin.isNull ? ZEND_LONG_MIN : aMin.value;
			zend_long right = bMin.isNull ? ZEND_LONG_MIN : bMin.value;
			out = ZEND_THREEWAY_COMPARE(left, right);
			return true;
		}

		if (aRange && instanceof_function(b->ce, pt_ce_integer_type)) {
			out = 1;
			return true;
		}

		if (bRange && instanceof_function(a->ce, pt_ce_integer_type)) {
			out = -1;
			return true;
		}

		if (instanceof_function(a->ce, pt_ce_constant_string_type) && instanceof_function(b->ce, pt_ce_constant_string_type)) {
			zv::Val aValue = pt_constant_string_get_value(a);
			if (UNEXPECTED(aValue.isUndef())) return false;
			zv::Val bValue = pt_constant_string_get_value(b);
			if (UNEXPECTED(bValue.isUndef())) return false;
			out = compareStrings(Z_STR_P(aValue.raw()), Z_STR_P(bValue.raw()));
			return true;
		}

		if (instanceof_function(a->ce, pt_ce_enum_case_object_type) && instanceof_function(b->ce, pt_ce_enum_case_object_type)) {
			zv::Val aName = enumCaseDescription(a);
			if (UNEXPECTED(aName.isUndef())) return false;
			zv::Val bName = enumCaseDescription(b);
			if (UNEXPECTED(bName.isUndef())) return false;
			out = compareStrings(Z_STR_P(aName.raw()), Z_STR_P(bName.raw()));
			return true;
		}

		if ((instanceof_function(a->ce, pt_ce_callable_type) || instanceof_function(a->ce, pt_ce_closure_type))
			&& (instanceof_function(b->ce, pt_ce_callable_type) || instanceof_function(b->ce, pt_ce_closure_type))) {
			return compareDescriptions(a, b, PT_VERBOSITY_LEVEL_VALUE, out);
		}

		zend_long aConstantArray = pt_type_call_trinary(a, PT_LC("isconstantarray"), 0, NULL);
		if (UNEXPECTED(aConstantArray < 0)) return false;
		if (aConstantArray == PT_TRI_YES) {
			zend_long bConstantArray = pt_type_call_trinary(b, PT_LC("isconstantarray"), 0, NULL);
			if (UNEXPECTED(bConstantArray < 0)) return false;
			if (bConstantArray == PT_TRI_YES) {
				zend_long aAtLeastOnce = pt_type_call_trinary(a, PT_LC("isiterableatleastonce"), 0, NULL);
				if (UNEXPECTED(aAtLeastOnce < 0)) return false;
				if (aAtLeastOnce == PT_TRI_NO) {
					zend_long bAtLeastOnce = pt_type_call_trinary(b, PT_LC("isiterableatleastonce"), 0, NULL);
					if (UNEXPECTED(bAtLeastOnce < 0)) return false;
					if (bAtLeastOnce == PT_TRI_NO) {
						out = 0;
						return true;
					}
					out = -1;
					return true;
				}
				zend_long bAtLeastOnce = pt_type_call_trinary(b, PT_LC("isiterableatleastonce"), 0, NULL);
				if (UNEXPECTED(bAtLeastOnce < 0)) return false;
				if (bAtLeastOnce == PT_TRI_NO) {
					out = 1;
					return true;
				}
				return compareDescriptions(a, b, PT_VERBOSITY_LEVEL_VALUE, out);
			}
		}

		zend_long aString = pt_type_call_trinary(a, PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(aString < 0)) return false;
		if (aString == PT_TRI_YES) {
			zend_long bString = pt_type_call_trinary(b, PT_LC("isstring"), 0, NULL);
			if (UNEXPECTED(bString < 0)) return false;
			if (bString == PT_TRI_YES) return compareDescriptions(a, b, PT_VERBOSITY_LEVEL_PRECISE, out);
		}

		return compareDescriptions(a, b, PT_VERBOSITY_LEVEL_TYPE_ONLY, out);
	}

	/* self::compareStrings($a->describe($level), $b->describe($level)) for
	 * one of the shadowed VerbosityLevel's singletons; false = pending
	 * exception */
	[[nodiscard]] static bool compareDescriptions(zend_object *a, zend_object *b, zend_long level, zend_long &out)
	{
		zval *levelZv = pt_verbosity_level_singleton(level);
		if (UNEXPECTED(levelZv == NULL)) return false;
		zv::Val aDescription = describe(a, levelZv);
		if (UNEXPECTED(aDescription.isUndef())) return false;
		zv::Val bDescription = describe(b, levelZv);
		if (UNEXPECTED(bDescription.isUndef())) return false;
		out = compareStrings(Z_STR_P(aDescription.raw()), Z_STR_P(bDescription.raw()));
		return true;
	}

	/* $type->describe($level), a string; UNDEF = pending exception */
	static zv::Val describe(zend_object *type, zval *level)
	{
		zv::Val description = pt_type_call(type, PT_LC("describe"), 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return a string", ZSTR_VAL(type->ce->name));
			return zv::Val();
		}
		return description;
	}

	/* $type->getClassName() . '::' . $type->getEnumCaseName(); UNDEF =
	 * pending exception */
	static zv::Val enumCaseDescription(zend_object *type)
	{
		zv::Val className = pt_type_call(type, PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val caseName = pt_type_call(type, PT_LC("getenumcasename"), 0, NULL);
		if (UNEXPECTED(caseName.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(className.raw()).isString() || !zv::Ref(caseName.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::getClassName() and getEnumCaseName() must return strings", ZSTR_VAL(type->ce->name));
			return zv::Val();
		}
		zend_string *left = Z_STR_P(className.raw());
		zend_string *right = Z_STR_P(caseName.raw());
		return zv::Val::adoptString(zend_string_concat3(ZSTR_VAL(left), ZSTR_LEN(left), "::", 2, ZSTR_VAL(right), ZSTR_LEN(right)));
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnionTypeHelper;

zv::Val pt_union_type_helper_sort_types(zval *types)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: UnionTypeHelper::sortTypes() takes an array, %s given", zend_zval_value_name(types));
		return zv::Val();
	}
	return UnionTypeHelper::sortTypes(types);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_union_type_helper()
{
	reg::Class cls("PHPStan\\Type\\UnionTypeHelper");
	ptdecl::UnionTypeHelper::declareClass(cls);
	ptdecl::UnionTypeHelper::declareProperties(cls);

	cls.method(sigs::sortTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		PT_RETURN_VAL(UnionTypeHelper::sortTypes(types));
	});

	cls.method(sigs::compareStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *a, *b;
		if (!zp::parse<zp::Str, zp::Str>(execute_data, a, b)) RETURN_THROWS();
		RETURN_LONG(UnionTypeHelper::compareStrings(a, b));
	});

	cls.shadow(&pt_ce_union_type_helper);
}

/* }}} */
