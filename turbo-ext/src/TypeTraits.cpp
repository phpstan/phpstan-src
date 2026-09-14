/*
 * Shared native implementations of the traits PHPStan's Type classes are
 * composed of — see TypeTraits.h. Each registrar below mirrors one PHP
 * trait method for method, in the trait's source order; the handlers are
 * the engine ABI glue plus the one-line bodies the traits have.
 *
 * The trivial handlers (`return []`, a TrinaryLogic singleton, `$this`, a
 * new ErrorType) allocate nothing beyond what the PHP body allocates.
 */

#include "TypeTraits.h"
#include "generated/JustNullableTypeTrait.h"
#include "generated/NonArrayTypeTrait.h"
#include "generated/NonCallableTypeTrait.h"
#include "generated/MaybeCallableTypeTrait.h"
#include "generated/NonIterableTypeTrait.h"
#include "generated/NonObjectTypeTrait.h"
#include "generated/UndecidedBooleanTypeTrait.h"
#include "generated/UndecidedComparisonTypeTrait.h"
#include "generated/NonGenericTypeTrait.h"
#include "generated/NonOffsetAccessibleTypeTrait.h"
#include "generated/NonGeneralizableTypeTrait.h"
#include "generated/ConstantScalarTypeTrait.h"
#include "generated/ConstantScalarToBooleanTrait.h"
#include "generated/ConstantNumericComparisonTypeTrait.h"
#include "generated/FalseyBooleanTypeTrait.h"
#include "generated/NonRemoveableTypeTrait.h"
#include "generated/UndecidedComparisonCompoundTypeTrait.h"
#include "generated/SubstractableTypeTrait.h"
#include "generated/TruthyBooleanTypeTrait.h"
#include "generated/MaybeIterableTypeTrait.h"
#include "generated/MaybeOffsetAccessibleTypeTrait.h"
#include "generated/ObjectTypeTrait.h"
#include "generated/ArrayTypeTrait.h"
#include "generated/MaybeArrayTypeTrait.h"
#include "generated/MaybeObjectTypeTrait.h"
#include "generated/MaybeStringTypeTrait.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_create_closure */
#pragma GCC diagnostic pop

/* {{{ glue macros */

#define PT_RETURN_THIS() RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS))
#define PT_RETURN_ERROR_TYPE() PT_RETURN_VAL(pt_type_new_error_type())

#define PT_THIS_OBJ Z_OBJ_P(ZEND_THIS)
/* `self` / `parent` inside trait code: the class the method is declared on */
#define PT_SCOPE (EX(func)->common.scope)

/* }}} */

/* {{{ helpers */

bool pt_type_method_is(zend_object *object, const char *lcname, size_t len, zif_handler handler)
{
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&object->ce->function_table, lcname, len);
	return fn != NULL && fn->type == ZEND_INTERNAL_FUNCTION && fn->internal_function.handler == handler;
}

static zv::Val pt_type_call_fn(zend_function *fn, zend_object *object, zend_class_entry *calledScope, uint32_t argc, zval *argv)
{
	zval ret;
	zend_call_known_function(fn, object, calledScope, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

zv::Val pt_type_call(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_function *fn = pt_find_method(object->ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	return pt_type_call_fn(fn, object, object->ce, argc, argv);
}

zend_long pt_type_call_trinary(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

zv::Val pt_type_call_parent(zend_class_entry *scope, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(scope->parent == NULL)) {
		zend_throw_error(NULL, "Cannot use \"parent\" when current class scope has no parent");
		return zv::Val();
	}
	zend_function *fn = pt_find_method(scope->parent, lcname, len);
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	return pt_type_call_fn(fn, object, object->ce, argc, argv);
}

zend_long pt_type_trinary_value(zval *trinary)
{
	if (EXPECTED(Z_TYPE_P(trinary) == IS_OBJECT)) {
		zend_object *object = Z_OBJ_P(trinary);
		if (EXPECTED(object->ce == pt_ce_trinary)) return pt_trinary_value(object);
		/* the PHP twin declared next to the native class in the differential
		 * tests: the same private int $value, found by name */
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&object->ce->properties_info, PT_LC("value"));
		if (info != NULL && (info->flags & ZEND_ACC_STATIC) == 0) {
			zval *slot = OBJ_PROP(object, info->offset);
			if (Z_TYPE_P(slot) == IS_LONG) return Z_LVAL_P(slot);
		}
	}
	zend_type_error("phpstan_turbo: expected %s, %s given", ZSTR_VAL(pt_ce_trinary->name), zend_zval_value_name(trinary));
	return -1;
}

zv::Val pt_type_call_static(int classIdx, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return zv::Val();
	zend_function *fn = pt_find_method(ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	return pt_type_call_fn(fn, NULL, ce, argc, argv);
}

zv::Val pt_type_new(int classIdx, uint32_t argc, zval *argv)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return zv::Val();
	zval object;
	if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
	if (ce->constructor != NULL) {
		zend_call_known_instance_method(ce->constructor, Z_OBJ(object), NULL, argc, argv);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
	}
	return zv::Val::adopt(object);
}

bool pt_type_instanceof(zval *value, int classIdx, bool &out)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

zv::Val pt_type_trinary(zend_long value)
{
	return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
}

zv::Val pt_type_accepts_result(zend_long value)
{
	zval result;
	if (UNEXPECTED(!pt_accepts_result_singleton(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_is_super_type_of_result(zend_long value)
{
	zval result;
	if (UNEXPECTED(!pt_is_super_type_of_result_singleton(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_error_type()
{
	return pt_type_new(PT_CLASS_ERROR_TYPE, 0, NULL);
}

zv::Val pt_type_new_mixed_type()
{
	zval result;
	if (UNEXPECTED(!pt_mixed_type_new(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_mixed_type_without_null()
{
	/* new MixedType(subtractedType: new NullType()) — the named argument
	 * skips $isExplicitMixed, whose default is false */
	zval nullRaw;
	if (UNEXPECTED(!pt_null_type_new(&nullRaw))) return zv::Val();
	zv::Val nullType = zv::Val::adopt(nullRaw);
	zval result;
	if (UNEXPECTED(!pt_mixed_type_new(&result, false, nullType.raw()))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_constant_integer(zend_long value)
{
	zval result;
	if (UNEXPECTED(!pt_constant_integer_type_new(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_constant_float(double value)
{
	zval result;
	if (UNEXPECTED(!pt_constant_float_type_new(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_constant_string(const char *value, size_t len)
{
	zend_string *str = zend_string_init(value, len, 0);
	zval result;
	bool created = pt_constant_string_type_new(&result, str);
	zend_string_release(str);
	if (UNEXPECTED(!created)) return zv::Val();
	return zv::Val::adopt(result);
}

zend_long pt_type_result_trinary(zval *result)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected a result object, %s given", zend_zval_value_name(result));
		return -1;
	}
	zend_object *object = Z_OBJ_P(result);
	if (EXPECTED(object->ce == pt_ce_is_super_type_of_result || object->ce == pt_ce_accepts_result)) return pt_result_value(object);
	/* the PHP twin declared next to the native class in the differential
	 * tests: its public readonly $result */
	zval rv;
	zval *trinary = zend_read_property(object->ce, object, PT_LC("result"), 0, &rv);
	if (UNEXPECTED(trinary == NULL || EG(exception))) return -1;
	zend_long value = pt_type_trinary_value(trinary);
	zval_ptr_dtor(&rv);
	return value;
}

zv::Val pt_type_new_union(zv::Arr types)
{
	return pt_type_new(PT_CLASS_UNION_TYPE, 1, types.raw());
}

/* the spread of a PHP array into an argument vector: a packed table
 * without holes is a contiguous zval array already (borrowed); any other
 * layout is copied into an emalloc'd vector the caller frees */
static zval *pt_type_spread_args(HashTable *args, uint32_t &count, bool &owned)
{
	count = zend_hash_num_elements(args);
	if (EXPECTED(HT_IS_PACKED(args) && HT_IS_WITHOUT_HOLES(args))) {
		owned = false;
		return args->arPacked;
	}
	zval *argv = (zval *) safe_emalloc(count, sizeof(zval), 0);
	uint32_t i = 0;
	for (zv::ArrayEntry entry : zv::TableRef(args)) {
		ZVAL_COPY_VALUE(&argv[i++], entry.value().raw());
	}
	count = i;
	owned = true;
	return argv;
}

zv::Val pt_type_call_static_spread(int classIdx, const char *lcname, size_t len, HashTable *args)
{
	uint32_t count;
	bool owned;
	zval *argv = pt_type_spread_args(args, count, owned);
	zv::Val result = pt_type_call_static(classIdx, lcname, len, count, argv);
	if (owned) {
		efree(argv);
	}
	return result;
}

zv::Val pt_type_call_spread(zend_object *object, const char *lcname, size_t len, HashTable *args)
{
	uint32_t count;
	bool owned;
	zval *argv = pt_type_spread_args(args, count, owned);
	zv::Val result = pt_type_call(object, lcname, len, count, argv);
	if (owned) {
		efree(argv);
	}
	return result;
}

zv::Val pt_type_mixed_minus(HashTable *subtractedTypes)
{
	zv::Val mixed = pt_type_new_mixed_type();
	if (UNEXPECTED(mixed.isUndef())) return zv::Val();
	zv::Val unionType = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), subtractedTypes);
	if (UNEXPECTED(unionType.isUndef())) return zv::Val();
	zv::Args args{mixed.raw(), unionType.raw()};
	return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("remove"), 2, args);
}

/* $this->isObject()->yes(); false = pending exception. The fast path
 * recognizes NonObjectTypeTrait's isObject() (always no) without a call. */
static void ZEND_FASTCALL nonObjectIsObject(INTERNAL_FUNCTION_PARAMETERS);

static bool pt_this_is_object(zend_object *self, bool &out)
{
	if (EXPECTED(pt_type_method_is(self, PT_LC("isobject"), nonObjectIsObject))) {
		out = false;
		return true;
	}
	zend_long value = pt_type_call_trinary(self, PT_LC("isobject"), 0, NULL);
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* }}} */

/* {{{ the trivial bodies most trait methods share (one handler per body
 * and arity; each method is still declared exactly once, at its
 * registration line, which bin/side-by-side.php pairs with the twin's) */

static void ZEND_FASTCALL trinaryNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL trinaryNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL trinaryYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL emptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL emptyArray1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL errorType0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_ERROR_TYPE();
}

static void ZEND_FASTCALL errorType1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_ERROR_TYPE();
}

static void ZEND_FASTCALL errorType2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_ERROR_TYPE();
}

static void ZEND_FASTCALL errorType3(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(3, 3);
	PT_RETURN_ERROR_TYPE();
}

static void ZEND_FASTCALL this0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_THIS();
}

static void ZEND_FASTCALL this1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_THIS();
}

static void ZEND_FASTCALL shouldNotHappen1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL shouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

/* }}} */

/* {{{ JustNullableTypeTrait */

/* traverse(callable $cb): $this — also NonGeneralizableTypeTrait's test
 * for "the callback is never invoked" */
static void ZEND_FASTCALL identityTraverse(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_THIS();
}

void pt_type_trait_just_nullable(reg::Class &cls)
{
	namespace sigs = ptdecl::JustNullableTypeTrait::sig;
	cls.traitMethod(sigs::getReferencedClasses, emptyArray0);
	cls.traitMethod(sigs::getObjectClassNames, emptyArray0);
	cls.traitMethod(sigs::getObjectClassReflections, emptyArray0);

	cls.traitMethod(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		/* $type instanceof static — the object's own class */
		if (instanceof_function(Z_OBJCE_P(type), PT_THIS_OBJ->ce)) {
			PT_RETURN_VAL(pt_type_accepts_result(PT_TRI_YES));
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) RETURN_THROWS();
		if (compound) {
			zval args[2];
			ZVAL_COPY_VALUE(&args[0], ZEND_THIS);
			ZVAL_BOOL(&args[1], strictTypes);
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args));
		}
		PT_RETURN_VAL(pt_type_accepts_result(PT_TRI_NO));
	});

	cls.traitMethod(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		/* $type instanceof self — the class the trait is used in */
		if (instanceof_function(Z_OBJCE_P(type), PT_SCOPE)) {
			PT_RETURN_VAL(pt_type_is_super_type_of_result(PT_TRI_YES));
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) RETURN_THROWS();
		if (compound) {
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, ZEND_THIS));
		}
		PT_RETURN_VAL(pt_type_is_super_type_of_result(PT_TRI_NO));
	});

	cls.traitMethod(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		/* get_class($type) === static::class */
		RETURN_BOOL(Z_OBJCE_P(type) == PT_THIS_OBJ->ce);
	});

	cls.traitMethod(sigs::traverse, identityTraverse);

	cls.traitMethod(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_THIS();
	});

	cls.traitMethod(sigs::isNull, trinaryNo0);
	cls.traitMethod(sigs::isConstantValue, trinaryNo0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryNo0);
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitMethod(sigs::isNumericString, trinaryNo0);
	cls.traitMethod(sigs::isDecimalIntegerString, trinaryNo0);
	cls.traitMethod(sigs::isNonEmptyString, trinaryNo0);
	cls.traitMethod(sigs::isNonFalsyString, trinaryNo0);
	cls.traitMethod(sigs::isLiteralString, trinaryNo0);
	cls.traitMethod(sigs::isLowercaseString, trinaryNo0);
	cls.traitMethod(sigs::isClassString, trinaryNo0);
	cls.traitMethod(sigs::isUppercaseString, trinaryNo0);
	cls.traitMethod(sigs::getClassStringObjectType, errorType0);
	cls.traitMethod(sigs::getObjectTypeOrClassStringObjectType, errorType0);
	cls.traitMethod(sigs::isVoid, trinaryNo0);
}

/* }}} */

/* {{{ NonArrayTypeTrait */

void pt_type_trait_non_array(reg::Class &cls)
{
	namespace sigs = ptdecl::NonArrayTypeTrait::sig;
	cls.traitMethod(sigs::getArrays, emptyArray0);
	cls.traitMethod(sigs::getConstantArrays, emptyArray0);
	cls.traitMethod(sigs::isArray, trinaryNo0);
	cls.traitMethod(sigs::isConstantArray, trinaryNo0);
	cls.traitMethod(sigs::isOversizedArray, trinaryNo0);
	cls.traitMethod(sigs::isList, trinaryNo0);

	cls.traitMethod(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class, a subclass
		 * may override it */
		PT_RETURN_VAL(pt_type_call(PT_THIS_OBJ, PT_LC("getkeysarray"), 0, NULL));
	});

	cls.traitMethod(sigs::getKeysArray, errorType0);
	cls.traitMethod(sigs::getValuesArray, errorType0);
	cls.traitMethod(sigs::chunkArray, errorType2);
	cls.traitMethod(sigs::fillKeysArray, errorType1);
	cls.traitMethod(sigs::flipArray, errorType0);
	cls.traitMethod(sigs::intersectKeyArray, errorType1);
	cls.traitMethod(sigs::popArray, errorType0);
	cls.traitMethod(sigs::reverseArray, errorType1);
	cls.traitMethod(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_ERROR_TYPE();
	});
	cls.traitMethod(sigs::shiftArray, errorType0);
	cls.traitMethod(sigs::shuffleArray, errorType0);
	cls.traitMethod(sigs::sliceArray, errorType3);
	cls.traitMethod(sigs::spliceArray, errorType3);
	cls.traitMethod(sigs::truncateListToSize, errorType1);
	cls.traitMethod(sigs::makeListMaybe, this0);
	cls.traitMethod(sigs::mapValueType, this1);
	cls.traitMethod(sigs::mapKeyType, this1);
	cls.traitMethod(sigs::makeAllArrayKeysOptional, this0);
	cls.traitMethod(sigs::changeKeyCaseArray, errorType1);
	cls.traitMethod(sigs::filterArrayRemovingFalsey, errorType0);
}

/* }}} */

/* {{{ NonCallableTypeTrait */

void pt_type_trait_non_callable(reg::Class &cls)
{
	namespace sigs = ptdecl::NonCallableTypeTrait::sig;
	cls.traitMethod(sigs::isCallable, trinaryNo0);
	cls.traitMethod(sigs::getCallableParametersAcceptors, shouldNotHappen1);
}

/* }}} */

/* {{{ MaybeCallableTypeTrait */

static void ZEND_FASTCALL trinaryMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

void pt_type_trait_maybe_callable(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeCallableTypeTrait::sig;
	cls.traitMethod(sigs::isCallable, trinaryMaybe0);

	cls.traitMethod(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		/* [new TrivialParametersAcceptor()] */
		zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
		if (UNEXPECTED(acceptor.isUndef())) RETURN_THROWS();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(acceptor));
		PT_RETURN_VAL(zv::Val(std::move(acceptors)));
	});
}

/* }}} */

/* {{{ NonIterableTypeTrait */

void pt_type_trait_non_iterable(reg::Class &cls)
{
	namespace sigs = ptdecl::NonIterableTypeTrait::sig;
	cls.traitMethod(sigs::isIterable, trinaryNo0);
	cls.traitMethod(sigs::isIterableAtLeastOnce, trinaryNo0);
	cls.traitMethod(sigs::getArraySize, errorType0);
	cls.traitMethod(sigs::getIterableKeyType, errorType0);
	cls.traitMethod(sigs::getFirstIterableKeyType, errorType0);
	cls.traitMethod(sigs::getLastIterableKeyType, errorType0);
	cls.traitMethod(sigs::getIterableValueType, errorType0);
	cls.traitMethod(sigs::getFirstIterableValueType, errorType0);
	cls.traitMethod(sigs::getLastIterableValueType, errorType0);
}

/* }}} */

/* {{{ NonObjectTypeTrait */

static void ZEND_FASTCALL nonObjectIsObject(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

void pt_type_trait_non_object(reg::Class &cls)
{
	namespace sigs = ptdecl::NonObjectTypeTrait::sig;
	cls.traitMethod(sigs::isObject, nonObjectIsObject);

	cls.traitMethod(sigs::getClassStringType, errorType0);

	cls.traitMethod(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantBooleanType(false) — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.traitMethod(sigs::toClassConstantType, errorType1);

	cls.traitMethod(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassNameToObjectTypeResult(new MixedType(), false) */
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) RETURN_THROWS();
		zv::Args args{mixed.raw(), false};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.traitMethod(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Zval, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) RETURN_THROWS();
		zv::Val type;
		if (allowString) {
			/* new UnionType([new ObjectWithoutClassType(), new ClassStringType()])
			 * — the shadowing ClassStringType */
			zval classString;
			if (UNEXPECTED(!pt_class_string_type_new(&classString))) RETURN_THROWS();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(objectWithoutClass));
			types.push(zv::Val::adopt(classString));
			zval typesZv = types.take();
			type = pt_type_new(PT_CLASS_UNION_TYPE, 1, &typesZv);
			zval_ptr_dtor(&typesZv);
			if (UNEXPECTED(type.isUndef())) RETURN_THROWS();
		} else {
			type = std::move(objectWithoutClass);
		}
		zv::Args args{type.raw(), false};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.traitMethod(sigs::isEnum, trinaryNo0);
	cls.traitMethod(sigs::canAccessProperties, trinaryNo0);
	cls.traitMethod(sigs::hasProperty, trinaryNo1);
	cls.traitMethod(sigs::getProperty, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedPropertyPrototype, shouldNotHappen2);
	cls.traitMethod(sigs::hasInstanceProperty, trinaryNo1);
	cls.traitMethod(sigs::getInstanceProperty, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, shouldNotHappen2);
	cls.traitMethod(sigs::hasStaticProperty, trinaryNo1);
	cls.traitMethod(sigs::getStaticProperty, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, shouldNotHappen2);

	cls.traitMethod(sigs::canCallMethods, trinaryNo0);
	cls.traitMethod(sigs::hasMethod, trinaryNo1);
	cls.traitMethod(sigs::getMethod, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, shouldNotHappen2);

	cls.traitMethod(sigs::canAccessConstants, trinaryNo0);
	cls.traitMethod(sigs::hasConstant, trinaryNo1);
	cls.traitMethod(sigs::getConstant, shouldNotHappen1);

	cls.traitMethod(sigs::getConstantStrings, emptyArray0);
	cls.traitMethod(sigs::isCloneable, trinaryNo0);
	cls.traitMethod(sigs::getEnumCases, emptyArray0);

	cls.traitMethod(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});

	cls.traitMethod(sigs::getTemplateType, errorType2);
}

/* }}} */

/* {{{ UndecidedBooleanTypeTrait */

void pt_type_trait_undecided_boolean(reg::Class &cls)
{
	namespace sigs = ptdecl::UndecidedBooleanTypeTrait::sig;
	cls.traitMethod(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
}

/* }}} */

/* {{{ UndecidedComparisonTypeTrait */

void pt_type_trait_undecided_comparison(reg::Class &cls)
{
	namespace sigs = ptdecl::UndecidedComparisonTypeTrait::sig;
	cls.traitMethod(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		zend_long otherIsNull = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnull"), 0, NULL);
		if (UNEXPECTED(otherIsNull < 0)) RETURN_THROWS();
		if (otherIsNull == PT_TRI_YES) {
			PT_RETURN_TRINARY(PT_TRI_NO);
		}
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});

	cls.traitMethod(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		/* $otherType->isNull()->yes() && $this->isObject()->yes() —
		 * short-circuiting like the twin */
		zend_long otherIsNull = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnull"), 0, NULL);
		if (UNEXPECTED(otherIsNull < 0)) RETURN_THROWS();
		if (otherIsNull == PT_TRI_YES) {
			bool isObject;
			if (UNEXPECTED(!pt_this_is_object(PT_THIS_OBJ, isObject))) RETURN_THROWS();
			if (isObject) {
				PT_RETURN_TRINARY(PT_TRI_NO);
			}
		}
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});

	cls.traitMethod(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});

	cls.traitMethod(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});

	cls.traitMethod(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_mixed_type_without_null());
	});

	cls.traitMethod(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		bool isObject;
		if (UNEXPECTED(!pt_this_is_object(PT_THIS_OBJ, isObject))) RETURN_THROWS();
		if (isObject) {
			PT_RETURN_VAL(pt_type_new_mixed_type_without_null());
		}
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});
}

/* }}} */

/* {{{ NonGenericTypeTrait */

void pt_type_trait_non_generic(reg::Class &cls)
{
	namespace sigs = ptdecl::NonGenericTypeTrait::sig;
	cls.traitMethod(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL));
	});

	cls.traitMethod(sigs::getReferencedTemplateTypes, emptyArray1);
}

/* }}} */

/* {{{ NonOffsetAccessibleTypeTrait */

void pt_type_trait_non_offset_accessible(reg::Class &cls)
{
	namespace sigs = ptdecl::NonOffsetAccessibleTypeTrait::sig;
	cls.traitMethod(sigs::isOffsetAccessible, trinaryNo0);
	cls.traitMethod(sigs::hasOffsetValueType, trinaryNo1);
	cls.traitMethod(sigs::getOffsetValueType, errorType1);
	cls.traitMethod(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_ERROR_TYPE();
	});
	cls.traitMethod(sigs::setExistingOffsetValueType, errorType2);
	cls.traitMethod(sigs::unsetOffset, errorType1);
}

/* }}} */

/* {{{ NonGeneralizableTypeTrait */

/* the `static fn (Type $type) => $type->generalize($precision)` callback
 * generalize() hands to $this->traverse(): a Closure over the __invoke()
 * of this holder, which keeps $precision (an internal detail with no PHP
 * twin, like IsSuperTypeOfResult's DecoratedLazyReason) */
static zend_class_entry *pt_ce_generalize_callback = nullptr;
static zend_function *pt_generalize_callback_invoke = nullptr;

#define PT_GC_PROP_PRECISION 0

static void ZEND_FASTCALL invokeGeneralizeCallback(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: generalize callback called without its holder");
		RETURN_THROWS();
	}
	PT_RETURN_VAL(pt_type_call(Z_OBJ_P(type), PT_LC("generalize"), 1, OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), PT_GC_PROP_PRECISION)));
}

void pt_type_trait_non_generalizable(reg::Class &cls)
{
	namespace sigs = ptdecl::NonGeneralizableTypeTrait::sig;
	cls.traitMethod(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		/* $this->traverse(static fn (Type $type) => $type->generalize($precision)):
		 * with JustNullableTypeTrait's identity traverse() the callback is
		 * never invoked and the result is $this — no closure needed */
		if (EXPECTED(pt_type_method_is(PT_THIS_OBJ, PT_LC("traverse"), identityTraverse))) {
			PT_RETURN_THIS();
		}
		zval holder;
		object_init_ex(&holder, pt_ce_generalize_callback);
		zv::ObjRef(&holder).propAtWrite(PT_GC_PROP_PRECISION, zv::Val::copyOf(zv::Ref(precision)));
		zval closure;
#if PHP_VERSION_ID >= 80600
		/* php-src fbb2e1f23d6: $this is passed as zend_object* from 8.6 on */
		zend_create_closure(&closure, pt_generalize_callback_invoke, pt_ce_generalize_callback, pt_ce_generalize_callback, Z_OBJ(holder));
#else
		zend_create_closure(&closure, pt_generalize_callback_invoke, pt_ce_generalize_callback, pt_ce_generalize_callback, &holder);
#endif
		zval_ptr_dtor(&holder); /* the closure holds its own reference */
		zv::Val result = pt_type_call(PT_THIS_OBJ, PT_LC("traverse"), 1, &closure);
		zval_ptr_dtor(&closure);
		PT_RETURN_VAL(std::move(result));
	});
}

/* }}} */

/* {{{ ConstantScalarTypeTrait */

/* $this->value / $type->value — the private property the class using the
 * trait declares (found on the declaring class, so the slot is right for
 * subclasses too); NULL with an Error pending when uninitialized, as the
 * twin's typed-property read raises */
[[nodiscard]] zval *pt_type_constant_scalar_value(zend_object *object, zend_class_entry *scope)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&scope->properties_info, PT_LC("value"));
	if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) != 0)) {
		zend_throw_error(NULL, "phpstan_turbo: %s declares no $value property for ConstantScalarTypeTrait", ZSTR_VAL(scope->name));
		return NULL;
	}
	zval *slot = OBJ_PROP(object, info->offset);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(scope->name));
		return NULL;
	}
	return slot;
}

/* $this->equals($type) — through the object's class; false = pending
 * exception */
static void ZEND_FASTCALL constantScalarEquals(INTERNAL_FUNCTION_PARAMETERS);

static bool constantScalarEqualsImpl(zend_object *self, zend_class_entry *scope, zval *type, bool &out)
{
	/* $type instanceof self && $this->value === $type->value */
	if (!instanceof_function(Z_OBJCE_P(type), scope)) {
		out = false;
		return true;
	}
	zval *selfValue = pt_type_constant_scalar_value(self, scope);
	if (UNEXPECTED(selfValue == NULL)) return false;
	zval *typeValue = pt_type_constant_scalar_value(Z_OBJ_P(type), scope);
	if (UNEXPECTED(typeValue == NULL)) return false;
	out = zend_is_identical(selfValue, typeValue);
	return true;
}

static bool constantScalarThisEquals(zend_object *self, zend_class_entry *scope, zval *type, bool &out)
{
	if (EXPECTED(pt_type_method_is(self, PT_LC("equals"), constantScalarEquals))) return constantScalarEqualsImpl(self, scope, type, out);
	return pt_type_call_bool(self, PT_LC("equals"), 1, type, out);
}

static void ZEND_FASTCALL constantScalarEquals(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	bool equal;
	if (UNEXPECTED(!constantScalarEqualsImpl(PT_THIS_OBJ, PT_SCOPE, type, equal))) RETURN_THROWS();
	RETURN_BOOL(equal);
}

/* $this->getValue() — through the object's class; UNDEF = pending
 * exception */
static zv::Val constantScalarGetValue(zend_object *self)
{
	return pt_type_call(self, PT_LC("getvalue"), 0, NULL);
}

zv::Val pt_type_constant_scalar_loose_compare(zend_object *self, zend_class_entry *scope, zval *type, zval *phpVersion)
{
	zval selfZv;
	ZVAL_OBJ(&selfZv, self);
	bool isConstantScalar;
	if (UNEXPECTED(!pt_type_instanceof(&selfZv, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) return zv::Val();
	if (UNEXPECTED(!isConstantScalar)) {
		pt_throw_should_not_happen();
		return zv::Val();
	}

	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) return zv::Val();
	if (isConstantScalar) {
		zv::Args args{&selfZv, type, phpVersion};
		return pt_type_call_static(PT_CLASS_LOOSE_COMPARISON_HELPER, PT_LC("compareconstantscalars"), 3, args);
	}

	zend_long isConstantArray = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isconstantarray"), 0, NULL);
	if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
	if (isConstantArray == PT_TRI_YES) {
		zend_long atLeastOnce = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
		if (atLeastOnce == PT_TRI_NO) {
			/* new ConstantBooleanType($this->getValue() == []) */
			zv::Val value = constantScalarGetValue(self);
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			zval emptyArray;
			ZVAL_EMPTY_ARRAY(&emptyArray);
			int comparison = zend_compare(value.raw(), &emptyArray);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zval result;
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, comparison == 0))) return zv::Val();
			return zv::Val::adopt(result);
		}
	}

	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
	if (compound) {
		zv::Args args{&selfZv, phpVersion};
		return pt_type_call(Z_OBJ_P(type), PT_LC("loosecompare"), 2, args);
	}

	zv::Args args{type, phpVersion};
	return pt_type_call_parent(scope, self, PT_LC("loosecompare"), 2, args);
}

void ZEND_FASTCALL pt_type_trait_constant_scalar_loose_compare(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_constant_scalar_loose_compare(PT_THIS_OBJ, PT_SCOPE, type, phpVersion));
}

/* isSmallerThan() / isSmallerThanOrEqual(): the `<` / `<=` comparison of
 * $this->value with a ConstantScalarType's value, else the CompoundType
 * callback, else maybe */
static void constantScalarSmaller(INTERNAL_FUNCTION_PARAMETERS, bool orEqual)
{
	zval *otherType, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();

	bool isConstantScalar;
	if (UNEXPECTED(!pt_type_instanceof(otherType, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) RETURN_THROWS();
	if (isConstantScalar) {
		zval *selfValue = pt_type_constant_scalar_value(PT_THIS_OBJ, PT_SCOPE);
		if (UNEXPECTED(selfValue == NULL)) RETURN_THROWS();
		zv::Val otherValue = constantScalarGetValue(Z_OBJ_P(otherType));
		if (UNEXPECTED(otherValue.isUndef())) RETURN_THROWS();
		int comparison = zend_compare(selfValue, otherValue.raw());
		if (UNEXPECTED(EG(exception))) RETURN_THROWS();
		PT_RETURN_TRINARY((orEqual ? comparison <= 0 : comparison < 0) ? PT_TRI_YES : PT_TRI_NO);
	}

	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(otherType, PT_CLASS_COMPOUND_TYPE, compound))) RETURN_THROWS();
	if (compound) {
		zv::Args args{ZEND_THIS, phpVersion};
		if (orEqual) {
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(otherType), PT_LC("isgreaterthanorequal"), 2, args));
		}
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(otherType), PT_LC("isgreaterthan"), 2, args));
	}

	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

void pt_type_trait_constant_scalar(reg::Class &cls)
{
	namespace sigs = ptdecl::ConstantScalarTypeTrait::sig;
	cls.traitMethod(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		if (instanceof_function(Z_OBJCE_P(type), PT_SCOPE)) {
			bool equal;
			if (UNEXPECTED(!constantScalarThisEquals(PT_THIS_OBJ, PT_SCOPE, type, equal))) RETURN_THROWS();
			PT_RETURN_VAL(pt_type_accepts_result(equal ? PT_TRI_YES : PT_TRI_NO));
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) RETURN_THROWS();
		zval args[2];
		if (compound) {
			ZVAL_COPY_VALUE(&args[0], ZEND_THIS);
			ZVAL_BOOL(&args[1], strictTypes);
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args));
		}
		/* parent::accepts($type, $strictTypes)->and(AcceptsResult::createMaybe()) */
		ZVAL_COPY_VALUE(&args[0], type);
		ZVAL_BOOL(&args[1], strictTypes);
		zv::Val parentResult = pt_type_call_parent(PT_SCOPE, PT_THIS_OBJ, PT_LC("accepts"), 2, args);
		if (UNEXPECTED(parentResult.isUndef())) RETURN_THROWS();
		zv::Val maybe = pt_type_accepts_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) RETURN_THROWS();
		if (EXPECTED(zv::Ref(parentResult.raw()).instanceOf(pt_ce_accepts_result))) {
			zval combined;
			if (UNEXPECTED(!pt_accepts_result_and(&combined, parentResult.raw(), maybe.raw()))) RETURN_THROWS();
			RETURN_COPY_VALUE(&combined);
		}
		if (UNEXPECTED(!zv::Ref(parentResult.raw()).isObject())) {
			zend_type_error("phpstan_turbo: parent::accepts() must return %s", ZSTR_VAL(pt_ce_accepts_result->name));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(pt_type_call(zv::Ref(parentResult.raw()).asObject(), PT_LC("and"), 1, maybe.raw()));
	});

	cls.traitMethod(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (instanceof_function(Z_OBJCE_P(type), PT_SCOPE)) {
			bool equal;
			if (UNEXPECTED(!constantScalarThisEquals(PT_THIS_OBJ, PT_SCOPE, type, equal))) RETURN_THROWS();
			PT_RETURN_VAL(pt_type_is_super_type_of_result(equal ? PT_TRI_YES : PT_TRI_NO));
		}
		/* $type instanceof parent */
		if (PT_SCOPE->parent != NULL && instanceof_function(Z_OBJCE_P(type), PT_SCOPE->parent)) {
			PT_RETURN_VAL(pt_type_is_super_type_of_result(PT_TRI_MAYBE));
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) RETURN_THROWS();
		if (compound) {
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, ZEND_THIS));
		}
		PT_RETURN_VAL(pt_type_is_super_type_of_result(PT_TRI_NO));
	});

	cls.traitMethod(sigs::looseCompare, pt_type_trait_constant_scalar_loose_compare);

	cls.traitMethod(sigs::equals, constantScalarEquals);

	cls.traitMethod(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantScalarSmaller(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.traitMethod(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantScalarSmaller(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.traitMethod(sigs::isConstantValue, trinaryYes0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryYes0);
	cls.traitMethod(sigs::getConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Arr types = zv::Arr::create(1);
		types.push(zv::Ref(ZEND_THIS));
		PT_RETURN_VAL(zv::Val(std::move(types)));
	});

	cls.traitMethod(sigs::getConstantScalarValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val value = constantScalarGetValue(PT_THIS_OBJ);
		if (UNEXPECTED(value.isUndef())) RETURN_THROWS();
		zv::Arr values = zv::Arr::create(1);
		values.push(std::move(value));
		PT_RETURN_VAL(zv::Val(std::move(values)));
	});

	cls.traitMethod(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Arr types = zv::Arr::create(1);
		types.push(zv::Ref(ZEND_THIS));
		PT_RETURN_VAL(zv::Val(std::move(types)));
	});
}

/* }}} */

/* {{{ ConstantScalarToBooleanTrait */

void pt_type_trait_constant_scalar_to_boolean(reg::Class &cls)
{
	namespace sigs = ptdecl::ConstantScalarToBooleanTrait::sig;
	cls.traitMethod(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantBooleanType((bool) $this->value) */
		zval *value = pt_type_constant_scalar_value(PT_THIS_OBJ, PT_SCOPE);
		if (UNEXPECTED(value == NULL)) RETURN_THROWS();
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, zend_is_true(value)))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
}

/* }}} */

/* {{{ ConstantNumericComparisonTypeTrait */

/* the four comparison-type methods share one shape: a list of subtracted
 * types built around $this->value, removed from mixed */
enum ConstantNumericComparison
{
	CNC_SMALLER,
	CNC_SMALLER_OR_EQUAL,
	CNC_GREATER,
	CNC_GREATER_OR_EQUAL,
};

/* new NullType() — the shadowing class */
static bool cncPushNull(zv::Arr &types)
{
	zval nullType;
	if (UNEXPECTED(!pt_null_type_new(&nullType))) return false;
	types.push(zv::Val::adopt(nullType));
	return true;
}

static bool cncPushBoolean(zv::Arr &types, bool value)
{
	zval boolean;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&boolean, value))) return false;
	types.push(zv::Val::adopt(boolean));
	return true;
}

/* new ConstantFloatType(0.0) — "subtract range when we support float-ranges" */
static bool cncPushFloatZero(zv::Arr &types)
{
	zv::Val zero = pt_type_new_constant_float(0.0);
	if (UNEXPECTED(zero.isUndef())) return false;
	types.push(std::move(zero));
	return true;
}

static bool cncPushRange(zv::Arr &types, zv::Val range)
{
	if (UNEXPECTED(range.isUndef())) return false;
	types.push(std::move(range));
	return true;
}

static void constantNumericComparison(INTERNAL_FUNCTION_PARAMETERS, ConstantNumericComparison which)
{
	PT_ARGS(1, 1);
	zval *value = pt_type_constant_scalar_value(PT_THIS_OBJ, PT_SCOPE);
	if (UNEXPECTED(value == NULL)) RETURN_THROWS();
	bool truthy = zend_is_true(value); /* (bool) $this->value */
	zv::Arr types = zv::Arr::create(5);
	bool ok;
	switch (which) {
		case CNC_SMALLER:
			/* [new ConstantBooleanType(true), IntegerRangeType::createAllGreaterThanOrEqualTo($this->value)]
			 * + [new NullType(), new ConstantBooleanType(false), new ConstantFloatType(0.0)] when falsy */
			ok = cncPushBoolean(types, true)
				&& cncPushRange(types, pt_integer_range_create_all_greater_than_or_equal_to(value))
				&& (truthy || (cncPushNull(types) && cncPushBoolean(types, false) && cncPushFloatZero(types)));
			break;
		case CNC_SMALLER_OR_EQUAL:
			/* [IntegerRangeType::createAllGreaterThan($this->value)] + [new ConstantBooleanType(true)] when falsy */
			ok = cncPushRange(types, pt_integer_range_create_all_greater_than(value))
				&& (truthy || cncPushBoolean(types, true));
			break;
		case CNC_GREATER:
			/* [new NullType(), new ConstantBooleanType(false), new ConstantFloatType(0.0), IntegerRangeType::createAllSmallerThanOrEqualTo($this->value)]
			 * + [new ConstantBooleanType(true)] when truthy */
			ok = cncPushNull(types) && cncPushBoolean(types, false) && cncPushFloatZero(types)
				&& cncPushRange(types, pt_integer_range_create_all_smaller_than_or_equal_to(value))
				&& (!truthy || cncPushBoolean(types, true));
			break;
		case CNC_GREATER_OR_EQUAL:
		default:
			/* [IntegerRangeType::createAllSmallerThan($this->value)]
			 * + [new NullType(), new ConstantBooleanType(false), new ConstantFloatType(0.0)] when truthy */
			ok = cncPushRange(types, pt_integer_range_create_all_smaller_than(value))
				&& (!truthy || (cncPushNull(types) && cncPushBoolean(types, false) && cncPushFloatZero(types)));
			break;
	}
	if (UNEXPECTED(!ok)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_mixed_minus(types.table()));
}

void pt_type_trait_constant_numeric_comparison(reg::Class &cls)
{
	namespace sigs = ptdecl::ConstantNumericComparisonTypeTrait::sig;
	cls.traitMethod(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantNumericComparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, CNC_SMALLER);
	});

	cls.traitMethod(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantNumericComparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, CNC_SMALLER_OR_EQUAL);
	});

	cls.traitMethod(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantNumericComparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, CNC_GREATER);
	});

	cls.traitMethod(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantNumericComparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, CNC_GREATER_OR_EQUAL);
	});
}

/* }}} */

/* {{{ module startup */

static void pt_register_native_callback();

void pt_register_type_traits()
{
	/* the generalize() callback holder: registered under a builder name
	 * other than `cls` on purpose — the side-by-side parity scan pairs
	 * `cls.method(...)` lines with the twins' methods, and __invoke() has
	 * none */
	reg::Class holder("PHPStanTurbo\\GeneralizeCallback");
	holder.privateNullProperty("precision");
	holder.method("__invoke", reg::Public, 1, { reg::obj("type", ptcls::type) }, invokeGeneralizeCallback);
	pt_ce_generalize_callback = holder.register_();
	pt_ce_generalize_callback->ce_flags |= ZEND_ACC_FINAL;
	pt_generalize_callback_invoke = (zend_function *) zend_hash_str_find_ptr(&pt_ce_generalize_callback->function_table, PT_LC("__invoke"));
	ZEND_ASSERT(pt_generalize_callback_invoke != NULL);
	pt_register_native_callback();
}

/* }}} */

/* {{{ FalseyBooleanTypeTrait */

void pt_type_trait_falsey_boolean(reg::Class &cls)
{
	namespace sigs = ptdecl::FalseyBooleanTypeTrait::sig;
	cls.traitMethod(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantBooleanType(false) — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
}

/* }}} */

/* {{{ NonRemoveableTypeTrait */

void pt_type_trait_non_removeable(reg::Class &cls)
{
	namespace sigs = ptdecl::NonRemoveableTypeTrait::sig;
	cls.traitMethod(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_NULL();
	});
}

/* }}} */

/* {{{ UndecidedComparisonCompoundTypeTrait (its own two methods; the
 * UndecidedComparisonTypeTrait it uses is registered separately) */

void pt_type_trait_undecided_comparison_compound(reg::Class &cls)
{
	namespace sigs = ptdecl::UndecidedComparisonCompoundTypeTrait::sig;
	cls.traitMethod(sigs::isGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		/* $otherType->isNull()->yes() && $this->isObject()->yes() —
		 * short-circuiting like the twin */
		zend_long otherIsNull = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnull"), 0, NULL);
		if (UNEXPECTED(otherIsNull < 0)) RETURN_THROWS();
		if (otherIsNull == PT_TRI_YES) {
			bool isObject;
			if (UNEXPECTED(!pt_this_is_object(PT_THIS_OBJ, isObject))) RETURN_THROWS();
			if (isObject) {
				PT_RETURN_TRINARY(PT_TRI_YES);
			}
		}
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});

	cls.traitMethod(sigs::isGreaterThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		zend_long otherIsNull = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnull"), 0, NULL);
		if (UNEXPECTED(otherIsNull < 0)) RETURN_THROWS();
		if (otherIsNull == PT_TRI_YES) {
			PT_RETURN_TRINARY(PT_TRI_YES);
		}
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});
}

/* }}} */

/* {{{ SubstractableTypeTrait */

zv::Val pt_type_describe_subtracted_type(zval *subtractedType, zval *level)
{
	if (Z_TYPE_P(subtractedType) == IS_NULL) return zv::Val::string("", 0);
	if (UNEXPECTED(Z_TYPE_P(subtractedType) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: describeSubtractedType(): Argument #1 ($subtractedType) must be of type ?%s, %s given", ptcls::type, zend_zval_value_name(subtractedType));
		return zv::Val();
	}

	/* $subtractedType instanceof UnionType
	 * || ($subtractedType instanceof SubtractableType && $subtractedType->getSubtractedType() !== null) */
	bool wrap;
	if (UNEXPECTED(!pt_type_instanceof(subtractedType, PT_CLASS_UNION_TYPE, wrap))) return zv::Val();
	if (!wrap) {
		bool subtractable;
		if (UNEXPECTED(!pt_type_instanceof(subtractedType, PT_CLASS_SUBTRACTABLE_TYPE, subtractable))) return zv::Val();
		if (subtractable) {
			zv::Val inner = pt_type_call(Z_OBJ_P(subtractedType), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(inner.isUndef())) return zv::Val();
			wrap = !inner.isNull();
		}
	}

	zv::Val description = pt_type_call(Z_OBJ_P(subtractedType), PT_LC("describe"), 1, level);
	if (UNEXPECTED(description.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
		zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(subtractedType)->name));
		return zv::Val();
	}
	zend_string *inner = zv::Ref(description.raw()).asString();
	smart_str str = {NULL, 0};
	if (wrap) {
		smart_str_appendl(&str, "~(", 2);
		smart_str_append(&str, inner);
		smart_str_appendc(&str, ')');
	} else {
		smart_str_appendc(&str, '~');
		smart_str_append(&str, inner);
	}
	smart_str_0(&str);
	return zv::Val::adoptString(str.s);
}

void ZEND_FASTCALL pt_type_trait_substractable_describe_subtracted_type(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *subtractedType, *level;
	if (!zp::parse<zp::ObjOrNull, zp::Obj>(execute_data, subtractedType, level)) RETURN_THROWS();
	zval nullZv;
	if (subtractedType == NULL) {
		ZVAL_NULL(&nullZv);
		subtractedType = &nullZv;
	}
	PT_RETURN_VAL(pt_type_describe_subtracted_type(subtractedType, level));
}

void pt_type_trait_substractable(reg::Class &cls)
{
	namespace sigs = ptdecl::SubstractableTypeTrait::sig;
	cls.traitMethod(sigs::describeSubtractedType, pt_type_trait_substractable_describe_subtracted_type);
}

/* }}} */

/* {{{ helpers of the never/mixed family */

zv::Val pt_type_new_never_type()
{
	zval result;
	if (UNEXPECTED(!pt_never_type_new(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_call_static_ce(zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_function *fn = pt_find_method(ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	return pt_type_call_fn(fn, NULL, ce, argc, argv);
}

/* VerbosityLevel's private level constants, read once from the class (the
 * cache is keyed on the class entry: a later request declares a new one) */
static zend_class_entry *pt_verbosity_case_ce = nullptr;
static zend_long pt_verbosity_case_type_only = 0;
static zend_long pt_verbosity_case_value = 0;
static zend_long pt_verbosity_case_precise = 0;

/* Class::NAME — a literal class constant, borrowed; NULL = pending
 * exception */
static zval *pt_verbosity_constant(zend_class_entry *ce, const char *name, size_t len)
{
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
	return &constant->value;
}

bool pt_type_verbosity_case(zval *level, pt_verbosity_case &out)
{
	zv::Val levelValueZv = pt_type_call(Z_OBJ_P(level), PT_LC("getlevelvalue"), 0, NULL);
	if (UNEXPECTED(levelValueZv.isUndef())) return false;
	if (UNEXPECTED(!zv::Ref(levelValueZv.raw()).isLong())) {
		zend_type_error("phpstan_turbo: %s::getLevelValue() must return int", ZSTR_VAL(Z_OBJCE_P(level)->name));
		return false;
	}
	zend_long levelValue = zv::Ref(levelValueZv.raw()).asLong();

	zend_class_entry *ce = pt_class(PT_CLASS_VERBOSITY_LEVEL);
	if (UNEXPECTED(ce == NULL)) return false;
	if (UNEXPECTED(ce != pt_verbosity_case_ce)) {
		zval *typeOnly = pt_verbosity_constant(ce, PT_LC("TYPE_ONLY"));
		zval *value = typeOnly != NULL ? pt_verbosity_constant(ce, PT_LC("VALUE")) : NULL;
		zval *precise = value != NULL ? pt_verbosity_constant(ce, PT_LC("PRECISE")) : NULL;
		if (UNEXPECTED(precise == NULL)) return false;
		pt_verbosity_case_type_only = zval_get_long(typeOnly);
		pt_verbosity_case_value = zval_get_long(value);
		pt_verbosity_case_precise = zval_get_long(precise);
		pt_verbosity_case_ce = ce;
	}

	if (levelValue == pt_verbosity_case_type_only) {
		out = PT_VERBOSITY_TYPE_ONLY;
	} else if (levelValue == pt_verbosity_case_value) {
		out = PT_VERBOSITY_VALUE;
	} else if (levelValue == pt_verbosity_case_precise) {
		out = PT_VERBOSITY_PRECISE;
	} else {
		out = PT_VERBOSITY_CACHE;
	}
	return true;
}

zif_handler pt_type_identity_traverse_handler()
{
	return identityTraverse;
}

/* }}} */

/* {{{ trait registrars of the object family */

/* TruthyBooleanTypeTrait */

void pt_type_trait_truthy_boolean(reg::Class &cls)
{
	namespace sigs = ptdecl::TruthyBooleanTypeTrait::sig;
	cls.traitMethod(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantBooleanType(true) — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, true))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
}

/* MaybeIterableTypeTrait */

static void ZEND_FASTCALL maybeIterableMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL maybeIterableMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

void pt_type_trait_maybe_iterable(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeIterableTypeTrait::sig;
	cls.traitMethod(sigs::isIterable, maybeIterableMaybe0);
	cls.traitMethod(sigs::isIterableAtLeastOnce, maybeIterableMaybe0);

	cls.traitMethod(sigs::getArraySize, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* $this->isIterable()->no() / $this->isIterableAtLeastOnce()->yes()
		 * — through the object's class, a subclass may override them; the
		 * trait's own answers need no call */
		zend_long iterable = pt_type_method_is(PT_THIS_OBJ, PT_LC("isiterable"), maybeIterableMaybe0) ? PT_TRI_MAYBE : pt_type_call_trinary(PT_THIS_OBJ, PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(iterable < 0)) RETURN_THROWS();
		if (iterable == PT_TRI_NO) {
			PT_RETURN_ERROR_TYPE();
		}
		zend_long atLeastOnce = pt_type_method_is(PT_THIS_OBJ, PT_LC("isiterableatleastonce"), maybeIterableMaybe0) ? PT_TRI_MAYBE : pt_type_call_trinary(PT_THIS_OBJ, PT_LC("isiterableatleastonce"), 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) RETURN_THROWS();
		if (atLeastOnce == PT_TRI_YES) {
			PT_RETURN_VAL(pt_integer_range_from_interval(phpstanturbo::NullableLong::of(1), phpstanturbo::NullableLong::null(), 0));
		}
		PT_RETURN_VAL(pt_integer_range_from_interval(phpstanturbo::NullableLong::of(0), phpstanturbo::NullableLong::null(), 0));
	});

	cls.traitMethod(sigs::getIterableKeyType, maybeIterableMixed0);
	cls.traitMethod(sigs::getFirstIterableKeyType, maybeIterableMixed0);
	cls.traitMethod(sigs::getLastIterableKeyType, maybeIterableMixed0);
	cls.traitMethod(sigs::getIterableValueType, maybeIterableMixed0);
	cls.traitMethod(sigs::getFirstIterableValueType, maybeIterableMixed0);
	cls.traitMethod(sigs::getLastIterableValueType, maybeIterableMixed0);
}

/* MaybeOffsetAccessibleTypeTrait */

void pt_type_trait_maybe_offset_accessible(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeOffsetAccessibleTypeTrait::sig;
	cls.traitMethod(sigs::isOffsetAccessible, trinaryMaybe0);
	cls.traitMethod(sigs::isOffsetAccessLegal, trinaryMaybe0);
	cls.traitMethod(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});
	cls.traitMethod(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});
	cls.traitMethod(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_THIS();
	});
	cls.traitMethod(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_THIS();
	});
	cls.traitMethod(sigs::unsetOffset, this1);
}

/* ObjectTypeTrait */

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * the transformed member of the prototype, through the object's class */
static void objectTraitTransformedMember(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_transformed_member(PT_THIS_OBJ, prototypeLcname, prototypeLen, isMethod, name, scope));
}

/* getUnresolvedPropertyPrototype() & co.: (string $name, ClassMemberAccessAnswerer $scope) */
static void objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAMETERS, bool isMethod)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL(pt_type_dummy_unresolved_prototype(isMethod, &nameZv));
}

static void ZEND_FASTCALL trinaryMaybe1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

void pt_type_trait_object(reg::Class &cls)
{
	namespace sigs = ptdecl::ObjectTypeTrait::sig;
	cls.traitMethod(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});

	cls.traitMethod(sigs::isObject, trinaryYes0);

	cls.traitMethod(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* $this->getClassStringType() — through the object's class */
		PT_RETURN_VAL(pt_type_call(PT_THIS_OBJ, PT_LC("getclassstringtype"), 0, NULL));
	});

	cls.traitMethod(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		if (!zp::parse<zp::Obj>(execute_data, reflectionProvider)) RETURN_THROWS();
		/* $classNames = $this->getObjectClassNames() — through the object's class */
		zv::Val classNames = pt_type_call(PT_THIS_OBJ, PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) RETURN_THROWS();
		if (UNEXPECTED(!zv::Ref(classNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassNames() must return array");
			RETURN_THROWS();
		}
		if (zv::ArrRef(classNames.raw()).size() == 1) {
			zval *className = zend_hash_index_find(zv::ArrRef(classNames.raw()).table(), 0);
			if (className != NULL) {
				zv::Val hasClass = pt_type_call(Z_OBJ_P(reflectionProvider), PT_LC("hasclass"), 1, className);
				if (UNEXPECTED(hasClass.isUndef())) RETURN_THROWS();
				if (zend_is_true(hasClass.raw())) {
					zv::Val reflection = pt_type_call(Z_OBJ_P(reflectionProvider), PT_LC("getclass"), 1, className);
					if (UNEXPECTED(reflection.isUndef())) RETURN_THROWS();
					if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
						zend_type_error("phpstan_turbo: getClass() must return an object");
						RETURN_THROWS();
					}
					zv::Val finalByKeyword = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
					if (UNEXPECTED(finalByKeyword.isUndef())) RETURN_THROWS();
					if (zend_is_true(finalByKeyword.raw())) {
						/* new ConstantStringType($reflection->getName(), true) */
						zv::Val name = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("getname"), 0, NULL);
						if (UNEXPECTED(name.isUndef())) RETURN_THROWS();
						if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
							zend_type_error("phpstan_turbo: getName() must return string");
							RETURN_THROWS();
						}
						zval result;
						if (UNEXPECTED(!pt_constant_string_type_new(&result, zv::Ref(name.raw()).asString(), true))) RETURN_THROWS();
						RETURN_COPY_VALUE(&result);
					}
				}
			}
		}
		/* new IntersectionType([$this->getClassStringType(), new AccessoryLiteralStringType()]) */
		zv::Val classString = pt_type_call(PT_THIS_OBJ, PT_LC("getclassstringtype"), 0, NULL);
		if (UNEXPECTED(classString.isUndef())) RETURN_THROWS();
		zv::Val literal = pt_type_new_shadowed(pt_accessory_literal_string_type_new);
		if (UNEXPECTED(literal.isUndef())) RETURN_THROWS();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(classString));
		types.push(std::move(literal));
		PT_RETURN_VAL(pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw()));
	});

	cls.traitMethod(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassNameToObjectTypeResult($this, true) */
		zv::Args args{ZEND_THIS, true};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.traitMethod(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Zval, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_object_type_for_is_a_check(allowString));
	});

	cls.traitMethod(sigs::isEnum, trinaryMaybe0);
	cls.traitMethod(sigs::canAccessProperties, trinaryYes0);
	cls.traitMethod(sigs::hasProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::hasInstanceProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::hasStaticProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.traitMethod(sigs::canCallMethods, trinaryYes0);
	cls.traitMethod(sigs::hasMethod, trinaryMaybe1);
	cls.traitMethod(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.traitMethod(sigs::canAccessConstants, trinaryYes0);
	cls.traitMethod(sigs::hasConstant, trinaryMaybe1);
	cls.traitMethod(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		/* new DummyClassConstantReflection($constantName) */
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(pt_type_new(PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION, 1, &nameZv));
	});

	cls.traitMethod(sigs::getConstantStrings, emptyArray0);
	cls.traitMethod(sigs::isCloneable, trinaryYes0);
	cls.traitMethod(sigs::isNull, trinaryNo0);
	cls.traitMethod(sigs::isConstantValue, trinaryNo0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryNo0);
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitMethod(sigs::isNumericString, trinaryNo0);
	cls.traitMethod(sigs::isDecimalIntegerString, trinaryNo0);
	cls.traitMethod(sigs::isNonEmptyString, trinaryNo0);
	cls.traitMethod(sigs::isNonFalsyString, trinaryNo0);
	cls.traitMethod(sigs::isLiteralString, trinaryNo0);
	cls.traitMethod(sigs::isLowercaseString, trinaryNo0);
	cls.traitMethod(sigs::isUppercaseString, trinaryNo0);
	cls.traitMethod(sigs::isClassString, trinaryNo0);
	cls.traitMethod(sigs::getClassStringObjectType, errorType0);
	cls.traitMethod(sigs::getObjectTypeOrClassStringObjectType, this0);
	cls.traitMethod(sigs::isVoid, trinaryNo0);
	cls.traitMethod(sigs::isScalar, trinaryNo0);

	cls.traitMethod(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.traitMethod(sigs::toNumber, errorType0);
	cls.traitMethod(sigs::toBitwiseNotType, errorType0);
	cls.traitMethod(sigs::toAbsoluteNumber, errorType0);
	cls.traitMethod(sigs::toString, errorType0);
	cls.traitMethod(sigs::toInteger, errorType0);
	cls.traitMethod(sigs::toFloat, errorType0);

	cls.traitMethod(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ArrayType(new MixedType(), new MixedType()) */
		zv::Val key = pt_type_new_mixed_type();
		zv::Val value = pt_type_new_mixed_type();
		if (UNEXPECTED(key.isUndef() || value.isUndef())) RETURN_THROWS();
		zval array;
		if (UNEXPECTED(!pt_array_type_new(&array, key.raw(), value.raw()))) RETURN_THROWS();
		RETURN_COPY_VALUE(&array);
	});

	cls.traitMethod(sigs::toArrayKey, errorType0);

	cls.traitMethod(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		if (strictTypes) {
			PT_RETURN_THIS();
		}
		/* TypeCombinator::union($this, $this->toString()) — through the object's class */
		zv::Val string = pt_type_call(PT_THIS_OBJ, PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(string.isUndef())) RETURN_THROWS();
		zv::Args args{ZEND_THIS, string.raw()};
		PT_RETURN_VAL(pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args));
	});
}

/* }}} */

/* {{{ helpers of the object family */

zv::Val pt_type_dummy_unresolved_prototype(bool isMethod, zval *name)
{
	zv::Val member = pt_type_new(isMethod ? PT_CLASS_DUMMY_METHOD_REFLECTION : PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, name);
	if (UNEXPECTED(member.isUndef())) return zv::Val();
	zv::Val declaringClass = pt_type_call(Z_OBJ_P(member.raw()), PT_LC("getdeclaringclass"), 0, NULL);
	if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
	zv::Val callback = pt_type_identity_callback();
	zv::Args args{member.raw(), declaringClass.raw(), false, callback.raw()};
	return pt_type_new(isMethod ? PT_CLASS_CALLBACK_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION : PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args);
}

zv::Val pt_type_transformed_member(zend_object *self, const char *prototypeLcname, size_t prototypeLen, bool isMethod, zval *name, zval *scope)
{
	zv::Args args{name, scope};
	zv::Val prototype = pt_type_call(self, prototypeLcname, prototypeLen, 2, args);
	if (UNEXPECTED(prototype.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
		zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
		return zv::Val();
	}
	if (isMethod) return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedmethod"), 0, NULL);
	return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedproperty"), 0, NULL);
}

zv::Val pt_type_object_type_for_is_a_check(bool allowString)
{
	zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
	if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
	zv::Val type;
	if (allowString) {
		/* new UnionType([new ObjectWithoutClassType(), new ClassStringType()])
		 * — the shadowing ClassStringType */
		zval classString;
		if (UNEXPECTED(!pt_class_string_type_new(&classString))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(objectWithoutClass));
		types.push(zv::Val::adopt(classString));
		type = pt_type_new_union(std::move(types));
		if (UNEXPECTED(type.isUndef())) return zv::Val();
	} else {
		type = std::move(objectWithoutClass);
	}
	zv::Args args{type.raw(), false};
	return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
}

zv::Val pt_type_new_object_without_class_type()
{
	zval result;
	if (UNEXPECTED(!pt_object_without_class_type_new(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_ce(zend_class_entry *ce, uint32_t argc, zval *argv)
{
	zval object;
	if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
	if (ce->constructor != NULL) {
		zend_call_known_instance_method(ce->constructor, Z_OBJ(object), NULL, argc, argv);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
	}
	return zv::Val::adopt(object);
}

zv::Val pt_type_closure_over(zend_function *fn, zend_class_entry *ce, zend_object *holder)
{
	zval closure;
#if PHP_VERSION_ID >= 80600
	/* php-src fbb2e1f23d6: $this is passed as zend_object* from 8.6 on */
	zend_create_closure(&closure, fn, ce, ce, holder);
#else
	zval holderZv;
	if (holder != NULL) {
		ZVAL_OBJ(&holderZv, holder);
	}
	zend_create_closure(&closure, fn, ce, ce, holder != NULL ? &holderZv : NULL);
#endif
	return zv::Val::adopt(closure);
}

zv::Val pt_type_call_callable(zval *callable, uint32_t argc, zval *argv)
{
	zval ret;
	if (UNEXPECTED(call_user_function(NULL, NULL, callable, &ret, argc, argv) != SUCCESS)) {
		if (!EG(exception)) {
			zend_throw_error(NULL, "phpstan_turbo: the callable could not be called");
		}
		return zv::Val();
	}
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

/* }}} */

/* {{{ helpers of the array family */

/* AcceptsResult.cpp: new <result class>($trinary, $reasons) */
bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons);

/* the NativeCallback holder: the function pointer (as an integer slot) and
 * the two state slots */
static zend_class_entry *pt_ce_native_callback = nullptr;

#define PT_NC_PROP_FN 0
#define PT_NC_PROP_STATE0 1
#define PT_NC_PROP_STATE1 2

static void ZEND_FASTCALL invokeNativeCallback(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *args;
	uint32_t argc;
	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('*', args, argc)
	ZEND_PARSE_PARAMETERS_END();
	if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: native callback called without its holder");
		RETURN_THROWS();
	}
	zend_object *holder = Z_OBJ_P(ZEND_THIS);
	zval *fnSlot = OBJ_PROP_NUM(holder, PT_NC_PROP_FN);
	if (UNEXPECTED(Z_TYPE_P(fnSlot) != IS_LONG)) {
		zend_throw_error(NULL, "phpstan_turbo: native callback holder without a body");
		RETURN_THROWS();
	}
	pt_native_callback fn = (pt_native_callback) (uintptr_t) Z_LVAL_P(fnSlot);
	fn(OBJ_PROP_NUM(holder, PT_NC_PROP_STATE0), OBJ_PROP_NUM(holder, PT_NC_PROP_STATE1), argc, args, return_value);
	if (UNEXPECTED(EG(exception))) {
		/* the engine releases the return value of a throwing call; a body
		 * that set one before throwing must not leave it to be released
		 * twice */
		zval_ptr_dtor(return_value);
		ZVAL_NULL(return_value);
		RETURN_THROWS();
	}
}

static void pt_register_native_callback()
{
	/* registered under a builder name other than `cls` on purpose — the
	 * side-by-side parity scan pairs `cls.method(...)` lines with the
	 * twins' methods, and __invoke() has none */
	reg::Class holder("PHPStanTurbo\\NativeCallback");
	holder.privateNullProperty("fn");
	holder.privateNullProperty("state0");
	holder.privateNullProperty("state1");
	holder.method("__invoke", reg::Public, 0, { reg::Arg{ "args", reg::detail::flagBits(false, true), nullptr } }, invokeNativeCallback);
	pt_ce_native_callback = holder.register_();
	pt_ce_native_callback->ce_flags |= ZEND_ACC_FINAL;
}

zv::Val pt_type_native_callback(pt_native_callback fn, zval *state0, zval *state1)
{
	zval holder;
	if (UNEXPECTED(object_init_ex(&holder, pt_ce_native_callback) != SUCCESS)) return zv::Val();
	zv::ObjRef ref(&holder);
	ref.propAtWrite(PT_NC_PROP_FN, zv::Val::integer((zend_long) (uintptr_t) fn));
	if (state0 != NULL) {
		ref.propAtWrite(PT_NC_PROP_STATE0, zv::Val::copyOf(zv::Ref(state0)));
	}
	if (state1 != NULL) {
		ref.propAtWrite(PT_NC_PROP_STATE1, zv::Val::copyOf(zv::Ref(state1)));
	}
	return zv::Val::adopt(holder);
}

zval *pt_type_native_callback_state(zval *callback, int index)
{
	return OBJ_PROP_NUM(Z_OBJ_P(callback), index == 0 ? PT_NC_PROP_STATE0 : PT_NC_PROP_STATE1);
}

zv::Val pt_type_new_is_super_type_of_result(zend_long value)
{
	zval reasons, lazyReasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	ZVAL_EMPTY_ARRAY(&lazyReasons); /* the constructor's default [] */
	zval result;
	if (UNEXPECTED(!pt_result_object_create(&result, pt_ce_is_super_type_of_result, pt_trinary_singleton(value), &reasons, &lazyReasons))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_accepts_result(zend_long value)
{
	zval reasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	zval result;
	if (UNEXPECTED(!pt_accepts_result_create(&result, pt_trinary_singleton(value), &reasons))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_result_and(zv::Val result, zval *other)
{
	if (UNEXPECTED(result.isUndef() || other == NULL)) return zv::Val();
	if (EXPECTED(zv::Ref(result.raw()).instanceOf(pt_ce_accepts_result) && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_accepts_result)) {
		zval combined;
		if (UNEXPECTED(!pt_accepts_result_and(&combined, result.raw(), other))) return zv::Val();
		return zv::Val::adopt(combined);
	}
	if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
		zend_type_error("phpstan_turbo: expected a result object, %s given", zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, other);
}

/* }}} */

/* {{{ ArrayTypeTrait */

/* new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $valueType), new AccessoryArrayListType()]);
 * $valueType consumed */
static zv::Val pt_trait_list_of(zv::Val valueType)
{
	if (UNEXPECTED(valueType.isUndef())) return zv::Val();
	zval zero;
	ZVAL_LONG(&zero, 0);
	zv::Val keyType = pt_integer_range_create_all_greater_than_or_equal_to(&zero);
	if (UNEXPECTED(keyType.isUndef())) return zv::Val();
	zval arrayRaw;
	if (UNEXPECTED(!pt_array_type_new(&arrayRaw, keyType.raw(), valueType.raw()))) return zv::Val();
	zval listRaw;
	if (UNEXPECTED(!pt_accessory_array_list_type_new(&listRaw))) {
		zval_ptr_dtor(&arrayRaw);
		return zv::Val();
	}
	zv::Arr types = zv::Arr::create(2);
	types.push(zv::Val::adopt(arrayRaw));
	types.push(zv::Val::adopt(listRaw));
	return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
}

/* TypeCombinator::intersect($type, new NonEmptyArrayType()) */
static zv::Val pt_trait_intersect_non_empty(zval *type)
{
	zval nonEmpty;
	if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
	zv::Args args{type, &nonEmpty};
	zv::Val result = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("intersect"), 2, args);
	zval_ptr_dtor(&nonEmpty);
	return result;
}

void pt_type_trait_array(reg::Class &cls)
{
	namespace sigs = ptdecl::ArrayTypeTrait::sig;
	cls.traitMethod(sigs::isArray, trinaryYes0);
	cls.traitMethod(sigs::toArray, this0);
	cls.traitMethod(sigs::toArrayKey, errorType0);
	cls.traitMethod(sigs::toCoercedArgumentType, this1);
	cls.traitMethod(sigs::isOffsetAccessible, trinaryYes0);
	cls.traitMethod(sigs::isOffsetAccessLegal, trinaryYes0);

	cls.traitMethod(sigs::getArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* [$this] */
		zv::Arr arrays = zv::Arr::create(1);
		arrays.push(zv::Ref(ZEND_THIS));
		PT_RETURN_VAL(zv::Val(std::move(arrays)));
	});

	cls.traitMethod(sigs::isConstantScalarValue, trinaryNo0);
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitMethod(sigs::getObjectClassNames, emptyArray0);
	cls.traitMethod(sigs::getObjectClassReflections, emptyArray0);
	cls.traitMethod(sigs::toNumber, errorType0);
	cls.traitMethod(sigs::toBitwiseNotType, errorType0);
	cls.traitMethod(sigs::toAbsoluteNumber, errorType0);
	cls.traitMethod(sigs::toString, errorType0);
	cls.traitMethod(sigs::isIterable, trinaryYes0);
	cls.traitMethod(sigs::isOversizedArray, trinaryMaybe0);
	cls.traitMethod(sigs::isNull, trinaryNo0);
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitMethod(sigs::isNumericString, trinaryNo0);
	cls.traitMethod(sigs::isDecimalIntegerString, trinaryNo0);
	cls.traitMethod(sigs::isNonEmptyString, trinaryNo0);
	cls.traitMethod(sigs::isNonFalsyString, trinaryNo0);
	cls.traitMethod(sigs::isLiteralString, trinaryNo0);
	cls.traitMethod(sigs::isLowercaseString, trinaryNo0);
	cls.traitMethod(sigs::isUppercaseString, trinaryNo0);
	cls.traitMethod(sigs::isClassString, trinaryNo0);
	cls.traitMethod(sigs::getClassStringObjectType, errorType0);
	cls.traitMethod(sigs::getObjectTypeOrClassStringObjectType, errorType0);
	cls.traitMethod(sigs::isVoid, trinaryNo0);
	cls.traitMethod(sigs::isScalar, trinaryNo0);
	cls.traitMethod(sigs::exponentiate, errorType1);

	cls.traitMethod(sigs::chunkArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *lengthType, *preserveKeys;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(lengthType)
			Z_PARAM_OBJECT(preserveKeys)
		ZEND_PARSE_PARAMETERS_END();
		/* $chunkType = $preserveKeys->yes() ? $this : list<$this->getIterableValueType()> */
		zend_long preserve = pt_type_trinary_value(preserveKeys);
		if (UNEXPECTED(preserve < 0)) RETURN_THROWS();
		zv::Val chunkType;
		if (preserve == PT_TRI_YES) {
			chunkType = zv::Val::copyOf(zv::Ref(ZEND_THIS));
		} else {
			chunkType = pt_trait_list_of(pt_type_call(PT_THIS_OBJ, PT_LC("getiterablevaluetype"), 0, NULL));
			if (UNEXPECTED(chunkType.isUndef())) RETURN_THROWS();
		}
		/* $chunkType = TypeCombinator::intersect($chunkType, new NonEmptyArrayType()) */
		chunkType = pt_trait_intersect_non_empty(chunkType.raw());
		if (UNEXPECTED(chunkType.isUndef())) RETURN_THROWS();
		/* $arrayType = list<$chunkType> */
		zv::Val arrayType = pt_trait_list_of(std::move(chunkType));
		if (UNEXPECTED(arrayType.isUndef())) RETURN_THROWS();
		/* $this->isIterableAtLeastOnce()->yes() ? TypeCombinator::intersect($arrayType, new NonEmptyArrayType()) : $arrayType */
		zend_long atLeastOnce = pt_type_call_trinary(PT_THIS_OBJ, PT_LC("isiterableatleastonce"), 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) RETURN_THROWS();
		if (atLeastOnce == PT_TRI_YES) {
			PT_RETURN_VAL(pt_trait_intersect_non_empty(arrayType.raw()));
		}
		PT_RETURN_VAL(std::move(arrayType));
	});
}

/* }}} */

/* {{{ TruthyBooleanTypeTrait */

/* }}} */

/* {{{ MaybeArrayTypeTrait */

void pt_type_trait_maybe_array(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeArrayTypeTrait::sig;
	cls.traitMethod(sigs::getArrays, emptyArray0);
	cls.traitMethod(sigs::getConstantArrays, emptyArray0);
	cls.traitMethod(sigs::isArray, trinaryMaybe0);
	cls.traitMethod(sigs::isConstantArray, trinaryMaybe0);
	cls.traitMethod(sigs::isOversizedArray, trinaryMaybe0);
	cls.traitMethod(sigs::isList, trinaryMaybe0);

	cls.traitMethod(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class */
		PT_RETURN_VAL(pt_type_call(PT_THIS_OBJ, PT_LC("getkeysarray"), 0, NULL));
	});

	cls.traitMethod(sigs::getKeysArray, errorType0);
	cls.traitMethod(sigs::getValuesArray, errorType0);
	cls.traitMethod(sigs::chunkArray, errorType2);
	cls.traitMethod(sigs::fillKeysArray, errorType1);
	cls.traitMethod(sigs::flipArray, errorType0);
	cls.traitMethod(sigs::intersectKeyArray, errorType1);
	cls.traitMethod(sigs::popArray, errorType0);
	cls.traitMethod(sigs::reverseArray, errorType1);
	cls.traitMethod(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_ERROR_TYPE();
	});
	cls.traitMethod(sigs::shiftArray, errorType0);
	cls.traitMethod(sigs::shuffleArray, errorType0);
	cls.traitMethod(sigs::sliceArray, errorType3);
	cls.traitMethod(sigs::spliceArray, errorType3);
	cls.traitMethod(sigs::truncateListToSize, errorType1);
	cls.traitMethod(sigs::makeListMaybe, this0);
	cls.traitMethod(sigs::mapValueType, this1);
	cls.traitMethod(sigs::mapKeyType, this1);
	cls.traitMethod(sigs::makeAllArrayKeysOptional, this0);
	cls.traitMethod(sigs::changeKeyCaseArray, errorType1);
	cls.traitMethod(sigs::filterArrayRemovingFalsey, errorType0);
}

/* }}} */

/* {{{ MaybeObjectTypeTrait */

/* new CallbackUnresolved{Property,Method}PrototypeReflection($member,
 * $member->getDeclaringClass(), false, static fn (Type $type): Type => $type)
 * over a Dummy{Property,Method}Reflection($name) */
static void pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAMETERS, bool isMethod)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	zv::Val member = pt_type_new(isMethod ? PT_CLASS_DUMMY_METHOD_REFLECTION : PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, &nameZv);
	if (UNEXPECTED(member.isUndef())) RETURN_THROWS();
	zv::Val declaringClass = pt_type_call(Z_OBJ_P(member.raw()), PT_LC("getdeclaringclass"), 0, NULL);
	if (UNEXPECTED(declaringClass.isUndef())) RETURN_THROWS();
	zv::Val callback = pt_type_identity_callback();
	zval args[4];
	ZVAL_COPY_VALUE(&args[0], member.raw());
	ZVAL_COPY_VALUE(&args[1], declaringClass.raw());
	ZVAL_FALSE(&args[2]);
	ZVAL_COPY_VALUE(&args[3], callback.raw());
	PT_RETURN_VAL(pt_type_new(isMethod ? PT_CLASS_CALLBACK_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION : PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args));
}

/* $this->getUnresolved*Prototype($name, $scope)->getTransformedProperty() /
 * ->getTransformedMethod() — through the object's class */
static void pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zv::Args args{name, scope};
	zv::Val prototype = pt_type_call(PT_THIS_OBJ, prototypeLcname, prototypeLen, 2, args);
	if (UNEXPECTED(prototype.isUndef())) RETURN_THROWS();
	if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
		zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
		RETURN_THROWS();
	}
	if (isMethod) {
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedmethod"), 0, NULL));
	}
	PT_RETURN_VAL(pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedproperty"), 0, NULL));
}

void pt_type_trait_maybe_object(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeObjectTypeTrait::sig;
	cls.traitMethod(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});

	cls.traitMethod(sigs::isObject, trinaryMaybe0);

	cls.traitMethod(sigs::getClassStringType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassStringType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_class_string_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.traitMethod(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new UnionType([$this->getClassStringType(), new ConstantBooleanType(false)]) */
		zv::Val classString = pt_type_call(PT_THIS_OBJ, PT_LC("getclassstringtype"), 0, NULL);
		if (UNEXPECTED(classString.isUndef())) RETURN_THROWS();
		zval falseRaw;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&falseRaw, false))) RETURN_THROWS();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(classString));
		types.push(zv::Val::adopt(falseRaw));
		PT_RETURN_VAL(pt_type_new_union(std::move(types)));
	});

	cls.traitMethod(sigs::toClassConstantType, errorType1);

	cls.traitMethod(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassNameToObjectTypeResult(new MixedType(), false) */
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) RETURN_THROWS();
		zv::Args args{mixed.raw(), false};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.traitMethod(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Zval, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) RETURN_THROWS();
		zv::Val type;
		if (allowString) {
			/* new UnionType([new ObjectWithoutClassType(), new ClassStringType()]) */
			zval classString;
			if (UNEXPECTED(!pt_class_string_type_new(&classString))) RETURN_THROWS();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(objectWithoutClass));
			types.push(zv::Val::adopt(classString));
			type = pt_type_new_union(std::move(types));
			if (UNEXPECTED(type.isUndef())) RETURN_THROWS();
		} else {
			type = std::move(objectWithoutClass);
		}
		zv::Args args{type.raw(), false};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.traitMethod(sigs::isEnum, trinaryMaybe0);
	cls.traitMethod(sigs::canAccessProperties, trinaryMaybe0);
	cls.traitMethod(sigs::hasProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::hasInstanceProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::hasStaticProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::canCallMethods, trinaryMaybe0);
	cls.traitMethod(sigs::hasMethod, trinaryMaybe1);
	cls.traitMethod(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});
	cls.traitMethod(sigs::canAccessConstants, trinaryMaybe0);
	cls.traitMethod(sigs::hasConstant, trinaryMaybe1);

	cls.traitMethod(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		/* new DummyClassConstantReflection($constantName) */
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(pt_type_new(PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION, 1, &nameZv));
	});

	cls.traitMethod(sigs::isCloneable, trinaryMaybe0);
}

/* }}} */

/* {{{ MaybeStringTypeTrait */

void pt_type_trait_maybe_string(reg::Class &cls)
{
	namespace sigs = ptdecl::MaybeStringTypeTrait::sig;
	cls.traitMethod(sigs::getConstantStrings, emptyArray0);
	cls.traitMethod(sigs::isString, trinaryMaybe0);
	cls.traitMethod(sigs::isNumericString, trinaryMaybe0);
	cls.traitMethod(sigs::isDecimalIntegerString, trinaryMaybe0);
	cls.traitMethod(sigs::isNonEmptyString, trinaryMaybe0);
	cls.traitMethod(sigs::isNonFalsyString, trinaryMaybe0);
	cls.traitMethod(sigs::isLiteralString, trinaryMaybe0);
	cls.traitMethod(sigs::isLowercaseString, trinaryMaybe0);
	cls.traitMethod(sigs::isUppercaseString, trinaryMaybe0);
	cls.traitMethod(sigs::isClassString, trinaryMaybe0);
	cls.traitMethod(sigs::isScalar, trinaryMaybe0);
}

/* }}} */

/* {{{ helpers of the string accessory family */

zv::Val pt_type_new_string_type()
{
	return pt_val_of<pt_string_type_new>();
}

zv::Val pt_type_new_shadowed(bool (*construct)(zval *))
{
	zval result;
	if (UNEXPECTED(!construct(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_type_new_intersection(zv::Arr types)
{
	return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
}

zv::Val pt_type_new_string_with_accessory(bool (*construct)(zval *))
{
	zv::Val string = pt_type_new_string_type();
	if (UNEXPECTED(string.isUndef())) return zv::Val();
	zval accessory;
	if (UNEXPECTED(!construct(&accessory))) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(string));
	types.push(zv::Val::adopt(accessory));
	return pt_type_new_intersection(std::move(types));
}

zv::Val pt_type_string_accessory_to_array(zend_object *self)
{
	zv::Val zero = pt_type_new_constant_integer(0);
	if (UNEXPECTED(zero.isUndef())) return zv::Val();
	zv::Arr keyTypes = zv::Arr::create(1);
	keyTypes.push(std::move(zero));
	zv::Arr valueTypes = zv::Arr::create(1);
	zval selfZv;
	ZVAL_OBJ(&selfZv, self);
	valueTypes.push(zv::Ref(&selfZv));
	zv::Arr nextAutoIndexes = zv::Arr::create(1);
	nextAutoIndexes.push(zv::Val::integer(1));
	/* the named argument isList: skips $optionalKeys, whose default is [] */
	zval args[5];
	args[0] = keyTypes.take();
	args[1] = valueTypes.take();
	args[2] = nextAutoIndexes.take();
	ZVAL_EMPTY_ARRAY(&args[3]);
	ZVAL_COPY_VALUE(&args[4], pt_trinary_singleton(PT_TRI_YES));
	zv::Val result = pt_type_new(PT_CLASS_CONSTANT_ARRAY_TYPE, 5, args);
	zval_ptr_dtor(&args[0]);
	zval_ptr_dtor(&args[1]);
	zval_ptr_dtor(&args[2]);
	return result;
}

zv::Val pt_type_new_float_or_int_benevolent_union()
{
	zval floatRaw, integerRaw;
	if (UNEXPECTED(!pt_float_type_new(&floatRaw))) return zv::Val();
	zv::Val floatType = zv::Val::adopt(floatRaw);
	if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(floatType));
	types.push(zv::Val::adopt(integerRaw));
	return pt_type_new(PT_CLASS_BENEVOLENT_UNION_TYPE, 1, types.raw());
}

zv::Val pt_type_new_identifier_type_node(const char *name, size_t len)
{
	zv::Val nameZv = zv::Val::string(name, len);
	return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameZv.raw());
}

bool pt_type_unsafe_array_string_key_casting_not_prevented(bool &out)
{
	zv::Val level = pt_type_call_static(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE, PT_LC("getlevel"), 0, NULL);
	if (UNEXPECTED(level.isUndef())) return false;
	zend_class_entry *ce = pt_class(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE);
	if (UNEXPECTED(ce == NULL)) return false;
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, PT_LC("PREVENT"));
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::PREVENT not found", ZSTR_VAL(ce->name));
		return false;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return false;
	/* $level !== ReportUnsafeArrayStringKeyCastingToggle::PREVENT */
	out = !zend_is_identical(level.raw(), &constant->value);
	return true;
}

/* }}} */
