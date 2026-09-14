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
#include "generated/LateResolvableTypeTrait.h"

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
	zval result;
	if (UNEXPECTED(!pt_error_type_new(&result))) return zv::Val();
	return zv::Val::adopt(result);
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
	 * tests: its public readonly $result (rv is only written for a magic
	 * read, so it starts UNDEF for the release below to be a no-op) */
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *trinary = zend_read_property(object->ce, object, PT_LC("result"), 0, &rv);
	if (UNEXPECTED(trinary == NULL || EG(exception))) return -1;
	zend_long value = pt_type_trinary_value(trinary);
	zval_ptr_dtor(&rv);
	return value;
}

zv::Val pt_type_new_union(zv::Arr types)
{
	zval result;
	if (UNEXPECTED(!pt_union_type_new(&result, types.raw()))) return zv::Val();
	return zv::Val::adopt(result);
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
	zv::Val unionType = pt_type_combinator_call_spread(PT_LC("union"), subtractedTypes);
	if (UNEXPECTED(unionType.isUndef())) return zv::Val();
	zv::Args args{mixed.raw(), unionType.raw()};
	return pt_type_combinator_call(PT_LC("remove"), 2, args);
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
			type = pt_type_new_union(std::move(types));
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
		PT_RETURN_VAL(pt_type_template_type_map_empty());
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
	if (UNEXPECTED(!pt_union_type_instanceof(subtractedType, wrap))) return zv::Val();
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

bool pt_type_verbosity_case(zval *level, pt_verbosity_case &out)
{
	/* the level's value — the shadowing VerbosityLevel's slot, or the PHP
	 * twin's getLevelValue() (VerbosityLevel.cpp); the constants are the
	 * twin's private ones */
	zend_long levelValue;
	if (UNEXPECTED(!pt_verbosity_level_value_of(level, levelValue))) return false;
	if (levelValue == PT_VERBOSITY_LEVEL_TYPE_ONLY) {
		out = PT_VERBOSITY_TYPE_ONLY;
	} else if (levelValue == PT_VERBOSITY_LEVEL_VALUE) {
		out = PT_VERBOSITY_VALUE;
	} else if (levelValue == PT_VERBOSITY_LEVEL_PRECISE) {
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
		PT_RETURN_VAL(pt_intersection_of(std::move(types)));
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
		PT_RETURN_VAL(pt_type_combinator_call(PT_LC("union"), 2, args));
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
	return pt_intersection_of(std::move(types));
}

/* TypeCombinator::intersect($type, new NonEmptyArrayType()) */
static zv::Val pt_trait_intersect_non_empty(zval *type)
{
	zval nonEmpty;
	if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
	zv::Args args{type, &nonEmpty};
	zv::Val result = pt_type_combinator_call(PT_LC("intersect"), 2, args);
	zval_ptr_dtor(&nonEmpty);
	return result;
}

/* chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): a list of
 * non-empty chunks — $this when keys are preserved, else a list of the
 * iterable value type — non-empty for a non-empty $this; also registered
 * under an alias by ConstantArrayType (`chunkArray as traitChunkArray`) */
static void ZEND_FASTCALL arrayTraitChunkArray(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *lengthType, *preserveKeys;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, lengthType, preserveKeys)) RETURN_THROWS();
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

	cls.traitMethod(sigs::chunkArray, arrayTraitChunkArray);
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
	return pt_intersection_of(std::move(types));
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
	zval resultRaw;
	zv::Val result = pt_constant_array_type_new(&resultRaw, &args[0], &args[1], &args[2], &args[3], &args[4]) ? zv::Val::adopt(resultRaw) : zv::Val();
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
	return pt_union_benevolent_of(std::move(types));
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

/* {{{ the object family */

/* {{{ MaybeIterableTypeTrait */

/* }}} */

/* {{{ new ObjectType($className) — the shadowing class (ObjectType.cpp) */

zv::Val pt_type_new_object_type(zval *className)
{
	if (UNEXPECTED(Z_TYPE_P(className) != IS_STRING)) {
		zend_type_error("phpstan_turbo: ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_type_name(className));
		return zv::Val();
	}
	zval object;
	if (UNEXPECTED(!pt_object_type_new(&object, Z_STR_P(className)))) return zv::Val();
	return zv::Val::adopt(object);
}

/* }}} */

/* merged from the parallel port branch */

/* {{{ helpers of the callable family (IterableType.cpp, CallableType.cpp,
 * ClosureType.cpp) */

/* $object->method(...$args) requiring an object result; UNDEF = pending
 * exception (a TypeError when the method returned something else) */
static zv::Val pt_callable_call_object(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
		zend_type_error("phpstan_turbo: %s::%s() must return an object, %s returned", ZSTR_VAL(object->ce->name), lcname, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* the same requiring an array result */
static zv::Val pt_callable_call_array(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
		zend_type_error("phpstan_turbo: %s::%s() must return an array, %s returned", ZSTR_VAL(object->ce->name), lcname, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* the same requiring a string result */
static zv::Val pt_callable_call_string(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
		zend_type_error("phpstan_turbo: %s::%s() must return a string, %s returned", ZSTR_VAL(object->ce->name), lcname, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* the same on a method returning bool; -1 = pending exception */
static int pt_callable_call_bool(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return zend_is_true(result.raw()) ? 1 : 0;
}

/* an element the twins call methods on (a ParameterReflection, an
 * AssertTag, a TemplateTag); NULL with an Error pending for a non-object,
 * as the twin's member call on it raises */
[[nodiscard]] static zend_object *pt_callable_element_object(zval *element, const char *what)
{
	if (UNEXPECTED(Z_TYPE_P(element) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: %s must be an object, %s given", what, zend_zval_value_name(element));
		return NULL;
	}
	return Z_OBJ_P(element);
}

bool pt_callable_array_merge_into(zv::Arr &into, zval *more)
{
	if (UNEXPECTED(Z_TYPE_P(more) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(more));
		return false;
	}
	for (zv::ArrayEntry entry : zv::ArrRef(more)) {
		if (entry.hasStringKey()) {
			into.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
		} else {
			into.push(entry.value());
		}
	}
	return true;
}

/* $classes = array_merge($classes, $type->getReferencedClasses()) */
static bool pt_callable_merge_referenced_classes(zv::Arr &classes, zval *type)
{
	zend_object *object = pt_callable_element_object(type, "a type");
	if (UNEXPECTED(object == NULL)) return false;
	zv::Val referenced = pt_callable_call_array(object, PT_LC("getreferencedclasses"), 0, NULL);
	if (UNEXPECTED(referenced.isUndef())) return false;
	return pt_callable_array_merge_into(classes, referenced.raw());
}

zv::Val pt_callable_assertions_all(zval *assertions)
{
	zend_object *object = pt_callable_element_object(assertions, "the assertions");
	if (UNEXPECTED(object == NULL)) return zv::Val();
	return pt_callable_call_array(object, PT_LC("getall"), 0, NULL);
}

zv::Val pt_callable_referenced_classes(zv::Arr classes, zval *parameters, zval *assertions, zval *returnType)
{
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: the parameters must be an array");
		return zv::Val();
	}
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *parameter = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(parameter == NULL)) return zv::Val();
		zv::Val type = pt_callable_call_object(parameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef() || !pt_callable_merge_referenced_classes(classes, type.raw()))) return zv::Val();
	}
	zv::Val assertTags = pt_callable_assertions_all(assertions);
	if (UNEXPECTED(assertTags.isUndef())) return zv::Val();
	for (zv::ArrayEntry entry : zv::ArrRef(assertTags.raw())) {
		zend_object *assertTag = pt_callable_element_object(entry.value().raw(), "an assert tag");
		if (UNEXPECTED(assertTag == NULL)) return zv::Val();
		zv::Val type = pt_callable_call_object(assertTag, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef() || !pt_callable_merge_referenced_classes(classes, type.raw()))) return zv::Val();
	}
	if (UNEXPECTED(!pt_callable_merge_referenced_classes(classes, returnType))) return zv::Val();
	return zv::Val(std::move(classes));
}

zv::Val pt_callable_parameter_types(zval *parameters)
{
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY)) {
		zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(parameters));
		return zv::Val();
	}
	/* array_map() over one array keeps its keys */
	zv::Arr types = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *parameter = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(parameter == NULL)) return zv::Val();
		zv::Val type = pt_type_call(parameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (entry.hasStringKey()) {
			types.set(entry.stringKey(), std::move(type));
		} else {
			types.separate();
			zval v = type.take();
			zend_hash_index_update(types.table(), entry.indexKey(), &v);
		}
	}
	return zv::Val(std::move(types));
}

zv::Val pt_callable_dummy_parameters(zval *parameters, zval *assertions)
{
	/* $assertedParameterNames[$assertTag->getParameter()->getParameterName()] = true */
	zv::Val assertTags = pt_callable_assertions_all(assertions);
	if (UNEXPECTED(assertTags.isUndef())) return zv::Val();
	zv::Arr assertedParameterNames = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(assertTags.raw())));
	for (zv::ArrayEntry entry : zv::ArrRef(assertTags.raw())) {
		zend_object *assertTag = pt_callable_element_object(entry.value().raw(), "an assert tag");
		if (UNEXPECTED(assertTag == NULL)) return zv::Val();
		zv::Val parameter = pt_callable_call_object(assertTag, PT_LC("getparameter"), 0, NULL);
		if (UNEXPECTED(parameter.isUndef())) return zv::Val();
		zv::Val name = pt_callable_call_string(Z_OBJ_P(parameter.raw()), PT_LC("getparametername"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		assertedParameterNames.set(Z_STR_P(name.raw()), zv::Val::boolean(true));
	}
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY)) {
		zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(parameters));
		return zv::Val();
	}
	zv::Arr dummies = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *p = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(p == NULL)) return zv::Val();
		/* array_key_exists('$' . $p->getName(), $assertedParameterNames) ? $p->getName() : '' */
		zv::Val name = pt_callable_call_string(p, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *dollarName = zend_string_concat2("$", 1, ZSTR_VAL(Z_STR_P(name.raw())), ZSTR_LEN(Z_STR_P(name.raw())));
		bool asserted = zend_symtable_exists(assertedParameterNames.table(), dollarName);
		zend_string_release(dollarName);
		zv::Val dummyName = asserted ? zv::Val::copyOf(zv::Ref(name.raw())) : zv::Val::string("", 0);
		zv::Val type = pt_type_call(p, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		/* optional: $p->isOptional() && !$p->isVariadic() */
		int optional = pt_callable_call_bool(p, PT_LC("isoptional"), 0, NULL);
		if (UNEXPECTED(optional < 0)) return zv::Val();
		if (optional == 1) {
			int variadic = pt_callable_call_bool(p, PT_LC("isvariadic"), 0, NULL);
			if (UNEXPECTED(variadic < 0)) return zv::Val();
			optional = variadic == 1 ? 0 : 1;
		}
		zv::Val passedByReference = pt_type_call_static(PT_CLASS_PASSED_BY_REFERENCE, PT_LC("createno"), 0, NULL);
		if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
		int variadic = pt_callable_call_bool(p, PT_LC("isvariadic"), 0, NULL);
		if (UNEXPECTED(variadic < 0)) return zv::Val();
		zv::Val defaultValue = pt_type_call(p, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
		zv::Args args{dummyName.raw(), type.raw(), bool(optional == 1), passedByReference.raw(), bool(variadic == 1), defaultValue.raw()};
		zv::Val dummy = pt_type_new(PT_CLASS_DUMMY_PARAMETER, 6, args);
		if (UNEXPECTED(dummy.isUndef())) return zv::Val();
		if (entry.hasStringKey()) {
			dummies.set(entry.stringKey(), std::move(dummy));
		} else {
			dummies.separate();
			zval v = dummy.take();
			zend_hash_index_update(dummies.table(), entry.indexKey(), &v);
		}
	}
	return zv::Val(std::move(dummies));
}

zv::Val pt_callable_print_php_doc_node(zval *type)
{
	zv::Val printer = pt_type_new(PT_CLASS_PHPDOC_PRINTER, 0, NULL);
	if (UNEXPECTED(printer.isUndef())) return zv::Val();
	zv::Val node = pt_type_call(Z_OBJ_P(type), PT_LC("tophpdocnode"), 0, NULL);
	if (UNEXPECTED(node.isUndef())) return zv::Val();
	return pt_type_call(Z_OBJ_P(printer.raw()), PT_LC("print"), 1, node.raw());
}

zv::Val pt_callable_type_node(const char *identifier, size_t identifierLen, zval *parameters, zval *templateTags, zval *assertions, zval *returnType)
{
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY || Z_TYPE_P(templateTags) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: the parameters and template tags must be arrays");
		return zv::Val();
	}
	/* $parameters[] = new CallableTypeParameterNode($parameter->getType()->toPhpDocNode(),
	 * !$parameter->passedByReference()->no(), $parameter->isVariadic(),
	 * $parameter->getName() === '' ? '' : '$' . $parameter->getName(), $parameter->isOptional()) */
	zv::Arr parameterNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *parameter = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(parameter == NULL)) return zv::Val();
		zv::Val type = pt_callable_call_object(parameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val typeNode = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(typeNode.isUndef())) return zv::Val();
		zv::Val passedByReference = pt_callable_call_object(parameter, PT_LC("passedbyreference"), 0, NULL);
		if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
		int byReferenceNo = pt_callable_call_bool(Z_OBJ_P(passedByReference.raw()), PT_LC("no"), 0, NULL);
		if (UNEXPECTED(byReferenceNo < 0)) return zv::Val();
		int variadic = pt_callable_call_bool(parameter, PT_LC("isvariadic"), 0, NULL);
		if (UNEXPECTED(variadic < 0)) return zv::Val();
		zv::Val name = pt_callable_call_string(parameter, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val parameterName;
		if (ZSTR_LEN(Z_STR_P(name.raw())) == 0) {
			parameterName = zv::Val::string("", 0);
		} else {
			parameterName = zv::Val::adoptString(zend_string_concat2("$", 1, ZSTR_VAL(Z_STR_P(name.raw())), ZSTR_LEN(Z_STR_P(name.raw()))));
		}
		int optional = pt_callable_call_bool(parameter, PT_LC("isoptional"), 0, NULL);
		if (UNEXPECTED(optional < 0)) return zv::Val();
		zv::Args args{typeNode.raw(), bool(byReferenceNo == 0), bool(variadic == 1), parameterName.raw(), bool(optional == 1)};
		zv::Val node = pt_type_new(PT_CLASS_CALLABLE_TYPE_PARAMETER_NODE, 5, args);
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		parameterNodes.push(std::move(node));
	}
	/* $templateTags[] = new TemplateTagValueNode($templateName, $templateTag->getBound()->toPhpDocNode(), '') */
	zv::Arr templateTagNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(templateTags)));
	for (zv::ArrayEntry entry : zv::ArrRef(templateTags)) {
		zend_object *templateTag = pt_callable_element_object(entry.value().raw(), "a template tag");
		if (UNEXPECTED(templateTag == NULL)) return zv::Val();
		zv::Val bound = pt_callable_call_object(templateTag, PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val boundNode = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(boundNode.isUndef())) return zv::Val();
		zv::Val templateName = entry.hasStringKey() ? zv::Val::string(entry.stringKey()) : zv::Val::adoptString(zend_long_to_str((zend_long) entry.indexKey()));
		zval args[3];
		ZVAL_COPY_VALUE(&args[0], templateName.raw());
		ZVAL_COPY_VALUE(&args[1], boundNode.raw());
		ZVAL_EMPTY_STRING(&args[2]);
		zv::Val node = pt_type_new(PT_CLASS_TEMPLATE_TAG_VALUE_NODE, 3, args);
		zval_ptr_dtor(&args[2]);
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		templateTagNodes.push(std::move(node));
	}
	/* CallableAssertionsHelper::toConditionalReturnTypeNode($this->assertions, $this->parameters, $this->returnType) ?? $this->returnType->toPhpDocNode() */
	zv::Args helperArgs{assertions, parameters, returnType};
	zv::Val returnTypeNode = pt_type_call_static(PT_CLASS_CALLABLE_ASSERTIONS_HELPER, PT_LC("toconditionalreturntypenode"), 3, helperArgs);
	if (UNEXPECTED(returnTypeNode.isUndef())) return zv::Val();
	if (returnTypeNode.isNull()) {
		returnTypeNode = pt_type_call(Z_OBJ_P(returnType), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(returnTypeNode.isUndef())) return zv::Val();
	}
	zv::Val identifierNode = pt_type_new_identifier_type_node(identifier, identifierLen);
	if (UNEXPECTED(identifierNode.isUndef())) return zv::Val();
	zv::Args args{identifierNode.raw(), parameterNodes.raw(), returnTypeNode.raw(), templateTagNodes.raw()};
	return pt_type_new(PT_CLASS_CALLABLE_TYPE_NODE, 4, args);
}

/* $type->hasTemplateOrLateResolvableType(); -1 = pending exception */
static int pt_callable_type_has_template(zval *type)
{
	zend_object *object = pt_callable_element_object(type, "a type");
	if (UNEXPECTED(object == NULL)) return -1;
	return pt_callable_call_bool(object, PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
}

/* $parameter->getOutType() / getClosureThisType() !== null && ->hasTemplateOrLateResolvableType() */
static int pt_callable_nullable_type_has_template(zend_object *parameter, const char *lcname, size_t len)
{
	zv::Val type = pt_type_call(parameter, lcname, len, 0, NULL);
	if (UNEXPECTED(type.isUndef())) return -1;
	if (type.isNull()) return 0;
	return pt_callable_type_has_template(type.raw());
}

bool pt_callable_parameters_or_asserts_have_template(zval *parameters, zval *assertions, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: the parameters must be an array");
		return false;
	}
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *parameter = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(parameter == NULL)) return false;
		zv::Val type = pt_type_call(parameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return false;
		int has = pt_callable_type_has_template(type.raw());
		if (UNEXPECTED(has < 0)) return false;
		if (has == 1) {
			out = true;
			return true;
		}
		bool extended;
		if (UNEXPECTED(!pt_type_instanceof(entry.value().raw(), PT_CLASS_EXTENDED_PARAMETER_REFLECTION, extended))) return false;
		if (!extended) continue;
		has = pt_callable_nullable_type_has_template(parameter, PT_LC("getouttype"));
		if (UNEXPECTED(has < 0)) return false;
		if (has == 1) {
			out = true;
			return true;
		}
		has = pt_callable_nullable_type_has_template(parameter, PT_LC("getclosurethistype"));
		if (UNEXPECTED(has < 0)) return false;
		if (has == 1) {
			out = true;
			return true;
		}
	}
	zv::Val assertTags = pt_callable_assertions_all(assertions);
	if (UNEXPECTED(assertTags.isUndef())) return false;
	for (zv::ArrayEntry entry : zv::ArrRef(assertTags.raw())) {
		zend_object *assertTag = pt_callable_element_object(entry.value().raw(), "an assert tag");
		if (UNEXPECTED(assertTag == NULL)) return false;
		zv::Val type = pt_type_call(assertTag, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return false;
		int has = pt_callable_type_has_template(type.raw());
		if (UNEXPECTED(has < 0)) return false;
		if (has == 1) {
			out = true;
			return true;
		}
	}
	out = false;
	return true;
}

/* $positionVariance->compose(TemplateTypeVariance::<factory>()) for a
 * PT_TEMPLATE_TYPE_VARIANCE_* value; UNDEF = pending exception */
static zv::Val pt_callable_compose_variance(zval *positionVariance, zend_long value)
{
	zval *variance = pt_template_type_variance_singleton(value);
	if (UNEXPECTED(variance == NULL)) return zv::Val();
	zval composed;
	if (UNEXPECTED(!pt_template_type_variance_compose(&composed, positionVariance, variance))) return zv::Val();
	return zv::Val::adopt(composed);
}

/* foreach ($type->getReferencedTemplateTypes($variance) as $reference) $references[] = $reference */
static bool pt_callable_append_referenced_template_types(zv::Arr &references, zval *type, zval *variance)
{
	zend_object *object = pt_callable_element_object(type, "a type");
	if (UNEXPECTED(object == NULL)) return false;
	zv::Val referenced = pt_callable_call_array(object, PT_LC("getreferencedtemplatetypes"), 1, variance);
	if (UNEXPECTED(referenced.isUndef())) return false;
	for (zv::ArrayEntry entry : zv::ArrRef(referenced.raw())) {
		references.push(entry.value());
	}
	return true;
}

zv::Val pt_callable_referenced_template_types(zend_object *self, pt_callable_this_getter getReturnType, pt_callable_this_getter getParameters, zval *assertions, zval *positionVariance)
{
	/* $references = $this->getReturnType()->getReferencedTemplateTypes($positionVariance->compose(TemplateTypeVariance::createCovariant())) */
	zv::Val returnType = getReturnType(self);
	if (UNEXPECTED(returnType.isUndef())) return zv::Val();
	zend_object *returnTypeObject = pt_callable_element_object(returnType.raw(), "the return type");
	if (UNEXPECTED(returnTypeObject == NULL)) return zv::Val();
	zv::Val covariant = pt_callable_compose_variance(positionVariance, PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
	if (UNEXPECTED(covariant.isUndef())) return zv::Val();
	zv::Val initial = pt_callable_call_array(returnTypeObject, PT_LC("getreferencedtemplatetypes"), 1, covariant.raw());
	if (UNEXPECTED(initial.isUndef())) return zv::Val();
	zv::Arr references = zv::Arr::adoptVal(std::move(initial));
	zv::Val assertTags = pt_callable_assertions_all(assertions);
	if (UNEXPECTED(assertTags.isUndef())) return zv::Val();
	for (zv::ArrayEntry entry : zv::ArrRef(assertTags.raw())) {
		zend_object *assertTag = pt_callable_element_object(entry.value().raw(), "an assert tag");
		if (UNEXPECTED(assertTag == NULL)) return zv::Val();
		zv::Val type = pt_type_call(assertTag, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val tagVariance = pt_callable_compose_variance(positionVariance, PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(tagVariance.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_callable_append_referenced_template_types(references, type.raw(), tagVariance.raw()))) return zv::Val();
	}
	zv::Val paramVariance = pt_callable_compose_variance(positionVariance, PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT);
	if (UNEXPECTED(paramVariance.isUndef())) return zv::Val();
	zv::Val parameters = getParameters(self);
	if (UNEXPECTED(parameters.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(parameters.raw()).isArray())) {
		zend_type_error("phpstan_turbo: getParameters() must return an array");
		return zv::Val();
	}
	for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
		zend_object *param = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(param == NULL)) return zv::Val();
		zv::Val type = pt_type_call(param, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_callable_append_referenced_template_types(references, type.raw(), paramVariance.raw()))) return zv::Val();
	}
	return zv::Val(std::move(references));
}

/* $typeMap->union($other); UNDEF = pending exception */
static zv::Val pt_callable_type_map_union(zv::Val typeMap, zval *other)
{
	if (UNEXPECTED(typeMap.isUndef() || other == NULL)) return zv::Val();
	return pt_type_call(Z_OBJ_P(typeMap.raw()), PT_LC("union"), 1, other);
}

zv::Val pt_callable_infer_template_types_on_parameters_acceptor(zend_object *self, pt_callable_this_getter getParameters, pt_callable_this_getter getReturnType, zval *parametersAcceptor)
{
	/* $parameterTypes = array_map(static fn ($parameter) => $parameter->getType(), $this->getParameters()) */
	zv::Val parameters = getParameters(self);
	if (UNEXPECTED(parameters.isUndef())) return zv::Val();
	zv::Val parameterTypes = pt_callable_parameter_types(parameters.raw());
	if (UNEXPECTED(parameterTypes.isUndef())) return zv::Val();
	/* $parametersAcceptor = ParametersAcceptorSelector::selectFromTypes($parameterTypes, [$parametersAcceptor], false) */
	zv::Arr acceptors = zv::Arr::create(1);
	acceptors.push(zv::Ref(parametersAcceptor));
	zv::Args selectArgs{parameterTypes.raw(), acceptors.raw(), false};
	zv::Val selected = pt_type_call_static(PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR, PT_LC("selectfromtypes"), 3, selectArgs);
	if (UNEXPECTED(selected.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(selected.raw()).isObject())) {
		zend_type_error("phpstan_turbo: ParametersAcceptorSelector::selectFromTypes() must return an object");
		return zv::Val();
	}
	zv::Val args = pt_callable_call_array(Z_OBJ_P(selected.raw()), PT_LC("getparameters"), 0, NULL);
	if (UNEXPECTED(args.isUndef())) return zv::Val();
	zv::Val returnType = pt_type_call(Z_OBJ_P(selected.raw()), PT_LC("getreturntype"), 0, NULL);
	if (UNEXPECTED(returnType.isUndef())) return zv::Val();
	zv::Val typeMap = pt_callable_template_type_map_empty();
	if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
	zv::Val ownParameters = getParameters(self);
	if (UNEXPECTED(ownParameters.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(ownParameters.raw()).isArray())) {
		zend_type_error("phpstan_turbo: getParameters() must return an array");
		return zv::Val();
	}
	for (zv::ArrayEntry entry : zv::ArrRef(ownParameters.raw())) {
		zend_object *param = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(param == NULL)) return zv::Val();
		zv::Val paramType = pt_callable_call_object(param, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(paramType.isUndef())) return zv::Val();
		/* isset($args[$i]) */
		zval *arg = entry.hasStringKey() ? zend_symtable_find(Z_ARRVAL_P(args.raw()), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(args.raw()), entry.indexKey());
		zv::Val argType;
		if (arg != NULL && Z_TYPE_P(arg) != IS_NULL) {
			zend_object *argObject = pt_callable_element_object(arg, "a parameter");
			if (UNEXPECTED(argObject == NULL)) return zv::Val();
			argType = pt_type_call(argObject, PT_LC("gettype"), 0, NULL);
		} else {
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(paramType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (isTemplate) {
				argType = pt_type_template_type_helper_resolve_to_bounds(paramType.raw());
			} else {
				argType = pt_type_new_never_type();
			}
		}
		if (UNEXPECTED(argType.isUndef())) return zv::Val();
		zv::Val inferred = pt_type_call(Z_OBJ_P(paramType.raw()), PT_LC("infertemplatetypes"), 1, argType.raw());
		if (UNEXPECTED(inferred.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(inferred.raw()).isObject())) {
			zend_type_error("phpstan_turbo: inferTemplateTypes() must return an object");
			return zv::Val();
		}
		zv::Val lower = pt_type_call(Z_OBJ_P(inferred.raw()), PT_LC("converttolowerboundtypes"), 0, NULL);
		if (UNEXPECTED(lower.isUndef())) return zv::Val();
		typeMap = pt_callable_type_map_union(std::move(typeMap), lower.raw());
		if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
	}
	/* $typeMap = $typeMap->union(CallableAssertionsHelper::inferTemplateTypesOnAsserts($this, $parametersAcceptor)) */
	zv::Args assertArgs{self, selected.raw()};
	zv::Val onAsserts = pt_type_call_static(PT_CLASS_CALLABLE_ASSERTIONS_HELPER, PT_LC("infertemplatetypesonasserts"), 2, assertArgs);
	if (UNEXPECTED(onAsserts.isUndef())) return zv::Val();
	typeMap = pt_callable_type_map_union(std::move(typeMap), onAsserts.raw());
	if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
	/* return $typeMap->union($this->getReturnType()->inferTemplateTypes($returnType)) */
	zv::Val ownReturnType = getReturnType(self);
	if (UNEXPECTED(ownReturnType.isUndef())) return zv::Val();
	zend_object *ownReturnTypeObject = pt_callable_element_object(ownReturnType.raw(), "the return type");
	if (UNEXPECTED(ownReturnTypeObject == NULL)) return zv::Val();
	zv::Val onReturn = pt_type_call(ownReturnTypeObject, PT_LC("infertemplatetypes"), 1, returnType.raw());
	if (UNEXPECTED(onReturn.isUndef())) return zv::Val();
	return pt_callable_type_map_union(std::move(typeMap), onReturn.raw());
}

zv::Val pt_callable_infer_template_types_on_acceptors(zend_object *self, pt_callable_this_getter getParameters, pt_callable_this_getter getReturnType, zval *acceptors)
{
	if (UNEXPECTED(Z_TYPE_P(acceptors) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: getCallableParametersAcceptors() must return an array");
		return zv::Val();
	}
	zv::Val typeMap = pt_callable_template_type_map_empty();
	if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
	for (zv::ArrayEntry entry : zv::ArrRef(acceptors)) {
		zv::Val inferred = pt_callable_infer_template_types_on_parameters_acceptor(self, getParameters, getReturnType, entry.value().raw());
		if (UNEXPECTED(inferred.isUndef())) return zv::Val();
		typeMap = pt_callable_type_map_union(std::move(typeMap), inferred.raw());
		if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
	}
	return typeMap;
}

/* new NativeParameterReflection($name, $optional, $type, $passedByReference, $variadic, $defaultValue);
 * UNDEF = pending exception */
static zv::Val pt_callable_new_native_parameter(zval *name, zval *optional, zval *type, zval *passedByReference, zval *variadic, zval *defaultValue)
{
	zv::Args args{name, optional, type, passedByReference, variadic, defaultValue};
	return pt_type_new(PT_CLASS_NATIVE_PARAMETER_REFLECTION, 6, args);
}

zv::Val pt_callable_traverse_parameters(zval *parameters, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
{
	if (UNEXPECTED(Z_TYPE_P(parameters) != IS_ARRAY)) {
		zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(parameters));
		return zv::Val();
	}
	/* array_map() over one array keeps its keys */
	zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zend_object *param = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(param == NULL)) return zv::Val();
		zv::Val defaultValue = pt_type_call(param, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
		zv::Val name = pt_type_call(param, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val optional = pt_type_call(param, PT_LC("isoptional"), 0, NULL);
		if (UNEXPECTED(optional.isUndef())) return zv::Val();
		zv::Val type = pt_type_call(param, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval mappedTypeRaw;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, type.raw(), &mappedTypeRaw))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mappedTypeRaw);
		zv::Val passedByReference = pt_type_call(param, PT_LC("passedbyreference"), 0, NULL);
		if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
		zv::Val variadic = pt_type_call(param, PT_LC("isvariadic"), 0, NULL);
		if (UNEXPECTED(variadic.isUndef())) return zv::Val();
		zv::Val mappedDefault = zv::Val::null();
		if (!defaultValue.isNull()) {
			zval mappedDefaultRaw;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, defaultValue.raw(), &mappedDefaultRaw))) return zv::Val();
			mappedDefault = zv::Val::adopt(mappedDefaultRaw);
		}
		zv::Val parameter = pt_callable_new_native_parameter(name.raw(), optional.raw(), mappedType.raw(), passedByReference.raw(), variadic.raw(), mappedDefault.raw());
		if (UNEXPECTED(parameter.isUndef())) return zv::Val();
		if (entry.hasStringKey()) {
			mapped.set(entry.stringKey(), std::move(parameter));
		} else {
			mapped.separate();
			zval v = parameter.take();
			zend_hash_index_update(mapped.table(), entry.indexKey(), &v);
		}
	}
	return zv::Val(std::move(mapped));
}

zv::Val pt_callable_traverse_parameters_simultaneously(zval *leftParameters, zval *rightParameters, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
{
	if (UNEXPECTED(Z_TYPE_P(leftParameters) != IS_ARRAY || Z_TYPE_P(rightParameters) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: the parameters must be arrays");
		return zv::Val();
	}
	zv::Arr parameters = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(leftParameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(leftParameters)) {
		zend_object *leftParam = pt_callable_element_object(entry.value().raw(), "a parameter");
		if (UNEXPECTED(leftParam == NULL)) return zv::Val();
		/* $rightParam = $rightParameters[$i] */
		zval *right = entry.hasStringKey() ? zend_symtable_find(Z_ARRVAL_P(rightParameters), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(rightParameters), entry.indexKey());
		if (UNEXPECTED(right == NULL || Z_TYPE_P(right) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getDefaultValue() on %s", right == NULL ? "null" : zend_zval_value_name(right));
			return zv::Val();
		}
		zend_object *rightParam = Z_OBJ_P(right);
		zv::Val leftDefaultValue = pt_type_call(leftParam, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(leftDefaultValue.isUndef())) return zv::Val();
		zv::Val rightDefaultValue = pt_type_call(rightParam, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(rightDefaultValue.isUndef())) return zv::Val();
		zv::Val defaultValue = zv::Val::copyOf(zv::Ref(leftDefaultValue.raw()));
		if (!leftDefaultValue.isNull() && !rightDefaultValue.isNull()) {
			zv::Args args{leftDefaultValue.raw(), rightDefaultValue.raw()};
			zval mappedRaw;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &mappedRaw))) return zv::Val();
			defaultValue = zv::Val::adopt(mappedRaw);
		}
		zv::Val name = pt_type_call(leftParam, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val optional = pt_type_call(leftParam, PT_LC("isoptional"), 0, NULL);
		if (UNEXPECTED(optional.isUndef())) return zv::Val();
		zv::Val leftType = pt_type_call(leftParam, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		zv::Val rightType = pt_type_call(rightParam, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();
		zv::Args typeArgs{leftType.raw(), rightType.raw()};
		zval mappedTypeRaw;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, typeArgs, &mappedTypeRaw))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mappedTypeRaw);
		zv::Val passedByReference = pt_type_call(leftParam, PT_LC("passedbyreference"), 0, NULL);
		if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
		zv::Val variadic = pt_type_call(leftParam, PT_LC("isvariadic"), 0, NULL);
		if (UNEXPECTED(variadic.isUndef())) return zv::Val();
		zv::Val parameter = pt_callable_new_native_parameter(name.raw(), optional.raw(), mappedType.raw(), passedByReference.raw(), variadic.raw(), defaultValue.raw());
		if (UNEXPECTED(parameter.isUndef())) return zv::Val();
		parameters.push(std::move(parameter));
	}
	return zv::Val(std::move(parameters));
}

zv::Val pt_callable_is_super_type_of_result_of(zval *trinary)
{
	zval reasons, lazyReasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	ZVAL_EMPTY_ARRAY(&lazyReasons); /* the constructor's default [] */
	zval result;
	if (UNEXPECTED(!pt_result_object_create(&result, pt_ce_is_super_type_of_result, trinary, &reasons, &lazyReasons))) return zv::Val();
	return zv::Val::adopt(result);
}

zv::Val pt_callable_out_of_class_scope()
{
	return pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
}

zv::Val pt_callable_template_type_map_empty()
{
	return pt_type_template_type_map_empty();
}

zv::Val pt_callable_template_type_variance_map_empty()
{
	return pt_type_template_type_variance_map_empty();
}

zv::Val pt_callable_assertions_empty()
{
	return pt_type_call_static(PT_CLASS_ASSERTIONS, PT_LC("createempty"), 0, NULL);
}

zv::Val pt_callable_new_simple_impure_point(const char *identifier, size_t identifierLen, const char *description, size_t descriptionLen, bool certain)
{
	zval args[3];
	ZVAL_STRINGL(&args[0], identifier, identifierLen);
	ZVAL_STRINGL(&args[1], description, descriptionLen);
	ZVAL_BOOL(&args[2], certain);
	zv::Val point = pt_type_new(PT_CLASS_SIMPLE_IMPURE_POINT, 3, args);
	zval_ptr_dtor(&args[0]);
	zval_ptr_dtor(&args[1]);
	return point;
}

zv::Val pt_callable_self_list(zend_object *self)
{
	zv::Arr list = zv::Arr::create(1);
	zval selfZv;
	ZVAL_OBJ(&selfZv, self);
	list.push(zv::Ref(&selfZv));
	return zv::Val(std::move(list));
}

/* }}} */

/* {{{ helpers of the array-shape type (ConstantArrayType.cpp) */

zif_handler pt_carr_array_trait_chunk_array_handler()
{
	return arrayTraitChunkArray;
}

zv::Val pt_carr_native_closure(pt_native_callback fn, zval *state0, zval *state1)
{
	zv::Val holder = pt_type_native_callback(fn, state0, state1);
	if (UNEXPECTED(holder.isUndef())) return zv::Val();
	zend_function *invoke = (zend_function *) zend_hash_str_find_ptr(&pt_ce_native_callback->function_table, PT_LC("__invoke"));
	ZEND_ASSERT(invoke != NULL);
	return pt_type_closure_over(invoke, pt_ce_native_callback, Z_OBJ_P(holder.raw()));
}

/* }}} */

/* merged from the parallel port branch */
/* {{{ helpers of the small Type classes */

zv::Val pt_type_just_nullable_is_super_type_of(zend_object *self, zend_class_entry *scope, zval *type)
{
	/* $type instanceof self — the class the trait is used in */
	if (instanceof_function(Z_OBJCE_P(type), scope)) return pt_type_is_super_type_of_result(PT_TRI_YES);
	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
	if (compound) {
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &thisValue);
	}
	return pt_type_is_super_type_of_result(PT_TRI_NO);
}

/* }}} */

/* merged from the parallel port branch */
/* {{{ LateResolvableTypeTrait (src/Type/Traits/LateResolvableTypeTrait.php) */

/* resolve()'s handler, the fast-path identity for the trait's own
 * `$this->resolve()` calls */
static void ZEND_FASTCALL lrResolve(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors the trait on an object of a class using it. `self` is that class
 * (scope), which declares the trait's private ?Type $result slot; the
 * $this-calls the trait makes — resolve(), isResolvable(), getResult() —
 * go through the object's class entry (a PHP subclass of a non-final
 * shadowing class may override them), resolve() directly when it is the
 * native one. */
class LateResolvable
{
public:
	LateResolvable(zend_object *self, zend_class_entry *scope) : self(self), scope(scope) {}

	/* $this->result: the slot the trait declares on scope (found there, so
	 * it is right for a PHP subclass of the shadowing class too); NULL with
	 * an Error pending when scope declares none */
	zval *resultSlot() const
	{
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&scope->properties_info, PT_LC("result"));
		if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) != 0)) {
			zend_throw_error(NULL, "phpstan_turbo: %s declares no $result property for LateResolvableTypeTrait", ZSTR_VAL(scope->name));
			return NULL;
		}
		return OBJ_PROP(self, info->offset);
	}

	/* $this->result ??= $this->getResult() */
	zv::Val resolve() const
	{
		zval *slot = resultSlot();
		if (UNEXPECTED(slot == NULL)) return zv::Val();
		if (Z_TYPE_P(slot) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(slot));
		zv::Val result = pt_type_call(self, PT_LC("getresult"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getResult() must return %s", ZSTR_VAL(self->ce->name), ptcls::type);
			return zv::Val();
		}
		/* the slot survives the call (the properties table is part of the
		 * object); the typed ?Type property takes the Type returned */
		zv::Ref(slot).assign(zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* $this->resolve() through the object's class entry, direct when native */
	zv::Val thisResolve() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("resolve"), lrResolve))) return resolve();
		return pt_type_call(self, PT_LC("resolve"), 0, NULL);
	}

	/* $this->resolve() checked to be a Type; UNDEF = pending exception */
	zv::Val resolved() const
	{
		zv::Val result = thisResolve();
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::resolve() must return %s", ZSTR_VAL(self->ce->name), ptcls::type);
			return zv::Val();
		}
		return result;
	}

	/* $this->resolve()->method(...$args) */
	zv::Val delegate(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zv::Val result = resolved();
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), lcname, len, argc, argv);
	}

	/* the same with the method named by the trait method's own frame (the
	 * one forward every `return $this->resolve()->x(...)` body is) */
	zv::Val delegateNamed(zend_string *name, uint32_t argc, zval *argv) const
	{
		zv::Val result = resolved();
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_object *target = Z_OBJ_P(result.raw());
		zend_function *fn = (zend_function *) zend_hash_find_ptr_lc(&target->ce->function_table, name);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "Call to undefined method %s::%s()", ZSTR_VAL(target->ce->name), ZSTR_VAL(name));
			return zv::Val();
		}
		zval ret;
		zend_call_known_function(fn, target, target->ce, &ret, argc, argv, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&ret);
			return zv::Val();
		}
		return zv::Val::adopt(ret);
	}

	/* yes for a NeverType; a late-resolvable $type resolved first; the
	 * resolved type's answer, held to maybe while $this is not resolvable */
	zv::Val isSuperTypeOfDefault(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);
		bool lateResolvable;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_LATE_RESOLVABLE_TYPE, lateResolvable))) return zv::Val();
		zv::Val resolvedType;
		if (lateResolvable) {
			resolvedType = pt_type_call(Z_OBJ_P(type), PT_LC("resolve"), 0, NULL);
			if (UNEXPECTED(resolvedType.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(resolvedType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: %s::resolve() must return %s", ZSTR_VAL(Z_OBJCE_P(type)->name), ptcls::type);
				return zv::Val();
			}
			type = resolvedType.raw();
		}
		zv::Val isSuperType = delegate(PT_LC("issupertypeof"), 1, type);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zv::Val resolvable = pt_type_call(self, PT_LC("isresolvable"), 0, NULL);
		if (UNEXPECTED(resolvable.isUndef())) return zv::Val();
		if (!zend_is_true(resolvable.raw())) {
			zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			if (UNEXPECTED(maybe.isUndef())) return zv::Val();
			return pt_type_result_and(std::move(isSuperType), maybe.raw());
		}
		return isSuperType;
	}

	/* $result->isSubTypeOf($otherType) for a compound result, else
	 * $otherType->isSuperTypeOf($result) */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		return compoundOrReversed(PT_LC("issubtypeof"), PT_LC("issupertypeof"), 1, otherType);
	}

	/* $result->isAcceptedBy($acceptingType, $strictTypes) for a compound
	 * result, else $acceptingType->accepts($result, $strictTypes) */
	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zv::Args args{acceptingType, strictTypes};
		return compoundOrReversed(PT_LC("isacceptedby"), PT_LC("accepts"), 2, args);
	}

	/* $result->isGreaterThan($otherType, $phpVersion) for a compound
	 * result, else $otherType->isSmallerThan($result, $phpVersion) */
	zv::Val isGreaterThan(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return compoundOrReversed(PT_LC("isgreaterthan"), PT_LC("issmallerthan"), 2, args);
	}

	zv::Val isGreaterThanOrEqual(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return compoundOrReversed(PT_LC("isgreaterthanorequal"), PT_LC("issmallerthanorequal"), 2, args);
	}

private:
	zend_object *self;
	zend_class_entry *scope;

	/* $result = $this->resolve(); a CompoundType result answers
	 * $result->compound(...$args), any other is asked the other way round:
	 * $args[0]->reversed($result, ...$args[1..]) */
	zv::Val compoundOrReversed(const char *compoundLcname, size_t compoundLen, const char *reversedLcname, size_t reversedLen, uint32_t argc, zval *argv) const
	{
		zv::Val result = resolved();
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(result.raw(), PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) return pt_type_call(Z_OBJ_P(result.raw()), compoundLcname, compoundLen, argc, argv);
		if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected %s, %s given", ptcls::type, zend_zval_value_name(&argv[0]));
			return zv::Val();
		}
		zval reversedArgs[2];
		ZVAL_COPY_VALUE(&reversedArgs[0], result.raw());
		if (argc > 1) {
			ZVAL_COPY_VALUE(&reversedArgs[1], &argv[1]);
		}
		return pt_type_call(Z_OBJ_P(&argv[0]), reversedLcname, reversedLen, argc, reversedArgs);
	}
};

} // namespace phpstanturbo

using phpstanturbo::LateResolvable;

zv::Val pt_type_late_resolvable_resolve(zend_object *self, zend_class_entry *scope)
{
	return LateResolvable(self, scope).thisResolve();
}

zv::Val pt_type_late_resolvable_is_super_type_of_default(zend_object *self, zend_class_entry *scope, zval *type)
{
	return LateResolvable(self, scope).isSuperTypeOfDefault(type);
}

zv::Val pt_type_describe_generic_of(const char *identifier, size_t identifierLen, zval *type, zval *level)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", ptcls::type, zend_zval_value_name(type));
		return zv::Val();
	}
	zv::Val description = pt_type_call(Z_OBJ_P(type), PT_LC("describe"), 1, level);
	if (UNEXPECTED(description.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
		zend_type_error("phpstan_turbo: describe() must return a string");
		return zv::Val();
	}
	return zv::Val::adoptString(zend_strpprintf(0, "%.*s<%s>", (int) identifierLen, identifier, ZSTR_VAL(Z_STR_P(description.raw()))));
}

zv::Val pt_type_generic_node_of(const char *identifier, size_t identifierLen, zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", ptcls::type, zend_zval_value_name(type));
		return zv::Val();
	}
	zv::Val identifierNode = pt_type_new_identifier_type_node(identifier, identifierLen);
	if (UNEXPECTED(identifierNode.isUndef())) return zv::Val();
	zv::Val typeNode = pt_type_call(Z_OBJ_P(type), PT_LC("tophpdocnode"), 0, NULL);
	if (UNEXPECTED(typeNode.isUndef())) return zv::Val();
	zv::Arr genericTypes = zv::Arr::create(1);
	genericTypes.push(std::move(typeNode));
	zv::Args args{identifierNode.raw(), genericTypes.raw()};
	return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
}

zv::Val pt_type_traverse_call(zend_fcall_info *fci, zend_fcall_info_cache *fcc, zval *type, zval *right)
{
	zval args[2];
	ZVAL_COPY_VALUE(&args[0], type);
	if (right != NULL) {
		ZVAL_COPY_VALUE(&args[1], right);
	}
	zval mapped;
	if (UNEXPECTED(!pt_call_fci(fci, fcc, right != NULL ? 2 : 1, args, &mapped))) return zv::Val();
	zv::Val result = zv::Val::adopt(mapped);
	if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
		zend_type_error("phpstan_turbo: the traverse callback must return %s, %s returned", ptcls::type, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

#define PT_LR_THIS LateResolvable(PT_THIS_OBJ, PT_SCOPE)

static void ZEND_FASTCALL lrResolve(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_LR_THIS.resolve());
}

/* `return $this->resolve()->x(...$args)` — the body of every forwarding
 * method, for every arity: the method is the frame's own, the arguments
 * the frame's (counted as the twin counts them, their types checked by the
 * resolved type's method as the twin's typed parameters would) */
static void ZEND_FASTCALL lrDelegate(INTERNAL_FUNCTION_PARAMETERS)
{
	const zend_function *fn = EX(func);
	uint32_t argc = ZEND_NUM_ARGS();
	if (UNEXPECTED(argc < fn->common.required_num_args || argc > fn->common.num_args)) {
		zend_wrong_parameters_count_error(fn->common.required_num_args, fn->common.num_args);
		RETURN_THROWS();
	}
	PT_RETURN_VAL(PT_LR_THIS.delegateNamed(fn->common.function_name, argc, argc > 0 ? ZEND_CALL_ARG(execute_data, 1) : NULL));
}

/* getFirstIterableKeyType() / getLastIterableKeyType(): $this->resolve()->getIterableKeyType() */
static void ZEND_FASTCALL lrIterableKeyType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_LR_THIS.delegate(PT_LC("getiterablekeytype"), 0, NULL));
}

/* getFirstIterableValueType() / getLastIterableValueType(): $this->resolve()->getIterableValueType() */
static void ZEND_FASTCALL lrIterableValueType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_LR_THIS.delegate(PT_LC("getiterablevaluetype"), 0, NULL));
}

static void ZEND_FASTCALL lrIsSuperTypeOfDefault(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(PT_LR_THIS.isSuperTypeOfDefault(type));
}

void pt_type_trait_late_resolvable(reg::Class &cls)
{
	namespace sigs = ptdecl::LateResolvableTypeTrait::sig;
	/* the trait's `private ?Type $result = null`, bound behind the class's
	 * own properties as PHP binds a trait's */
	cls.privateTypedClassPropertyDefaultNull("result", ptcls::type);

	cls.traitMethod(sigs::getObjectClassNames, lrDelegate);
	cls.traitMethod(sigs::getObjectClassReflections, lrDelegate);
	cls.traitMethod(sigs::getArrays, lrDelegate);
	cls.traitMethod(sigs::getConstantArrays, lrDelegate);
	cls.traitMethod(sigs::getConstantStrings, lrDelegate);
	cls.traitMethod(sigs::accepts, lrDelegate);
	cls.traitMethod(sigs::isSuperTypeOf, lrIsSuperTypeOfDefault);
	cls.traitMethod(sigs::isSuperTypeOfDefault, lrIsSuperTypeOfDefault);
	cls.traitMethod(sigs::getTemplateType, lrDelegate);
	cls.traitMethod(sigs::isObject, lrDelegate);
	cls.traitMethod(sigs::getClassStringType, lrDelegate);
	cls.traitMethod(sigs::isEnum, lrDelegate);
	cls.traitMethod(sigs::canAccessProperties, lrDelegate);
	cls.traitMethod(sigs::hasProperty, lrDelegate);
	cls.traitMethod(sigs::getProperty, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedPropertyPrototype, lrDelegate);
	cls.traitMethod(sigs::hasInstanceProperty, lrDelegate);
	cls.traitMethod(sigs::getInstanceProperty, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, lrDelegate);
	cls.traitMethod(sigs::hasStaticProperty, lrDelegate);
	cls.traitMethod(sigs::getStaticProperty, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, lrDelegate);
	cls.traitMethod(sigs::canCallMethods, lrDelegate);
	cls.traitMethod(sigs::hasMethod, lrDelegate);
	cls.traitMethod(sigs::getMethod, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, lrDelegate);
	cls.traitMethod(sigs::canAccessConstants, lrDelegate);
	cls.traitMethod(sigs::hasConstant, lrDelegate);
	cls.traitMethod(sigs::getConstant, lrDelegate);
	cls.traitMethod(sigs::isIterable, lrDelegate);
	cls.traitMethod(sigs::isIterableAtLeastOnce, lrDelegate);
	cls.traitMethod(sigs::getArraySize, lrDelegate);
	cls.traitMethod(sigs::getIterableKeyType, lrDelegate);
	cls.traitMethod(sigs::getFirstIterableKeyType, lrIterableKeyType);
	cls.traitMethod(sigs::getLastIterableKeyType, lrIterableKeyType);
	cls.traitMethod(sigs::getIterableValueType, lrDelegate);
	cls.traitMethod(sigs::getFirstIterableValueType, lrIterableValueType);
	cls.traitMethod(sigs::getLastIterableValueType, lrIterableValueType);
	cls.traitMethod(sigs::isArray, lrDelegate);
	cls.traitMethod(sigs::isConstantArray, lrDelegate);
	cls.traitMethod(sigs::isOversizedArray, lrDelegate);
	cls.traitMethod(sigs::isList, lrDelegate);
	cls.traitMethod(sigs::isOffsetAccessible, lrDelegate);
	cls.traitMethod(sigs::isOffsetAccessLegal, lrDelegate);
	cls.traitMethod(sigs::hasOffsetValueType, lrDelegate);
	cls.traitMethod(sigs::getOffsetValueType, lrDelegate);
	cls.traitMethod(sigs::setOffsetValueType, lrDelegate);
	cls.traitMethod(sigs::setExistingOffsetValueType, lrDelegate);
	cls.traitMethod(sigs::unsetOffset, lrDelegate);
	cls.traitMethod(sigs::getKeysArrayFiltered, lrDelegate);
	cls.traitMethod(sigs::getKeysArray, lrDelegate);
	cls.traitMethod(sigs::getValuesArray, lrDelegate);
	cls.traitMethod(sigs::chunkArray, lrDelegate);
	cls.traitMethod(sigs::fillKeysArray, lrDelegate);
	cls.traitMethod(sigs::flipArray, lrDelegate);
	cls.traitMethod(sigs::intersectKeyArray, lrDelegate);
	cls.traitMethod(sigs::popArray, lrDelegate);
	cls.traitMethod(sigs::reverseArray, lrDelegate);
	cls.traitMethod(sigs::searchArray, lrDelegate);
	cls.traitMethod(sigs::shiftArray, lrDelegate);
	cls.traitMethod(sigs::shuffleArray, lrDelegate);
	cls.traitMethod(sigs::sliceArray, lrDelegate);
	cls.traitMethod(sigs::spliceArray, lrDelegate);
	cls.traitMethod(sigs::truncateListToSize, lrDelegate);
	cls.traitMethod(sigs::makeListMaybe, lrDelegate);
	cls.traitMethod(sigs::mapValueType, lrDelegate);
	cls.traitMethod(sigs::mapKeyType, lrDelegate);
	cls.traitMethod(sigs::makeAllArrayKeysOptional, lrDelegate);
	cls.traitMethod(sigs::changeKeyCaseArray, lrDelegate);
	cls.traitMethod(sigs::filterArrayRemovingFalsey, lrDelegate);
	cls.traitMethod(sigs::isCallable, lrDelegate);
	cls.traitMethod(sigs::getEnumCases, lrDelegate);
	cls.traitMethod(sigs::getEnumCaseObject, lrDelegate);
	cls.traitMethod(sigs::getCallableParametersAcceptors, lrDelegate);
	cls.traitMethod(sigs::isCloneable, lrDelegate);
	cls.traitMethod(sigs::toBoolean, lrDelegate);
	cls.traitMethod(sigs::toNumber, lrDelegate);
	cls.traitMethod(sigs::toBitwiseNotType, lrDelegate);
	cls.traitMethod(sigs::toGetClassResultType, lrDelegate);
	cls.traitMethod(sigs::toClassConstantType, lrDelegate);
	cls.traitMethod(sigs::toObjectTypeForInstanceofCheck, lrDelegate);
	cls.traitMethod(sigs::toObjectTypeForIsACheck, lrDelegate);
	cls.traitMethod(sigs::toAbsoluteNumber, lrDelegate);
	cls.traitMethod(sigs::toInteger, lrDelegate);
	cls.traitMethod(sigs::toFloat, lrDelegate);
	cls.traitMethod(sigs::toString, lrDelegate);
	cls.traitMethod(sigs::toArray, lrDelegate);
	cls.traitMethod(sigs::toArrayKey, lrDelegate);
	cls.traitMethod(sigs::toCoercedArgumentType, lrDelegate);
	cls.traitMethod(sigs::isSmallerThan, lrDelegate);
	cls.traitMethod(sigs::isSmallerThanOrEqual, lrDelegate);
	cls.traitMethod(sigs::isNull, lrDelegate);
	cls.traitMethod(sigs::isConstantValue, lrDelegate);
	cls.traitMethod(sigs::isConstantScalarValue, lrDelegate);
	cls.traitMethod(sigs::getConstantScalarTypes, lrDelegate);
	cls.traitMethod(sigs::getConstantScalarValues, lrDelegate);
	cls.traitMethod(sigs::isTrue, lrDelegate);
	cls.traitMethod(sigs::isFalse, lrDelegate);
	cls.traitMethod(sigs::isBoolean, lrDelegate);
	cls.traitMethod(sigs::isFloat, lrDelegate);
	cls.traitMethod(sigs::isInteger, lrDelegate);
	cls.traitMethod(sigs::isString, lrDelegate);
	cls.traitMethod(sigs::isNumericString, lrDelegate);
	cls.traitMethod(sigs::isDecimalIntegerString, lrDelegate);
	cls.traitMethod(sigs::isNonEmptyString, lrDelegate);
	cls.traitMethod(sigs::isNonFalsyString, lrDelegate);
	cls.traitMethod(sigs::isLiteralString, lrDelegate);
	cls.traitMethod(sigs::isLowercaseString, lrDelegate);
	cls.traitMethod(sigs::isUppercaseString, lrDelegate);
	cls.traitMethod(sigs::isClassString, lrDelegate);
	cls.traitMethod(sigs::getClassStringObjectType, lrDelegate);
	cls.traitMethod(sigs::getObjectTypeOrClassStringObjectType, lrDelegate);
	cls.traitMethod(sigs::isVoid, lrDelegate);
	cls.traitMethod(sigs::isScalar, lrDelegate);
	cls.traitMethod(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
	cls.traitMethod(sigs::getSmallerType, lrDelegate);
	cls.traitMethod(sigs::getSmallerOrEqualType, lrDelegate);
	cls.traitMethod(sigs::getGreaterType, lrDelegate);
	cls.traitMethod(sigs::getGreaterOrEqualType, lrDelegate);
	cls.traitMethod(sigs::inferTemplateTypes, lrDelegate);
	cls.traitMethod(sigs::tryRemove, lrDelegate);
	cls.traitMethod(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType;
		if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_LR_THIS.isSubTypeOf(otherType));
	});
	cls.traitMethod(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_LR_THIS.isAcceptedBy(acceptingType, strictTypes));
	});
	cls.traitMethod(sigs::isGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_LR_THIS.isGreaterThan(otherType, phpVersion));
	});
	cls.traitMethod(sigs::isGreaterThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_LR_THIS.isGreaterThanOrEqual(otherType, phpVersion));
	});
	cls.traitMethod(sigs::exponentiate, lrDelegate);
	cls.traitMethod(sigs::getFiniteTypes, lrDelegate);
	cls.traitMethod(sigs::resolve, lrResolve);
	cls.traitMethod(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_TRUE;
	});
}

/* }}} */
