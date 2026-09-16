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
#include "Engine.h"
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
#include "generated/TemplateTypeTrait.h"

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

zv::Val pt_type_call_engine(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_function *fn = pt_find_method(object->ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	return pt_type_call_fn(fn, object, object->ce, argc, argv);
}

zv::Val pt_type_call(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	/* a receiver of a native class: the direct entry when the name is an
	 * op it registered (TypeOps.h) */
	const pt_type_ops *ops = pt_type_ops_of(object->ce);
	if (ops != NULL) {
		pt_type_op_id op = pt_type_op_of_name(lcname, len);
		if (op != PT_OP_COUNT) {
			const pt_type_op_entry &entry = ops->entries[op];
			if (entry.fn != NULL && pt_type_op_args_ok(pt_type_op_infos[op], argc, argv)) return entry.fn(object, entry.scope, argc, argv);
		}
	}
	return pt_type_call_engine(object, lcname, len, argc, argv);
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

zv::Val pt_is_super_type_of_result_spread(zend_object *self, bool isAnd, HashTable *args)
{
	uint32_t count;
	bool owned;
	zval *argv = pt_type_spread_args(args, count, owned);
	zv::Val result = pt_is_super_type_of_result_combine(self, isAnd, count, argv);
	if (owned) {
		efree(argv);
	}
	return result;
}

zv::Val pt_is_super_type_of_result_extreme_identity_spread(HashTable *args)
{
	uint32_t count;
	bool owned;
	zval *argv = pt_type_spread_args(args, count, owned);
	zv::Val result = pt_is_super_type_of_result_extreme_identity(count, argv);
	if (owned) {
		efree(argv);
	}
	return result;
}

zv::Val pt_accepts_result_extreme_identity_spread(HashTable *args)
{
	uint32_t count;
	bool owned;
	zval *argv = pt_type_spread_args(args, count, owned);
	zv::Val result = pt_accepts_result_extreme_identity(count, argv);
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

/* the accepts() body of JustNullableTypeTrait — the handler's and the
 * direct entry's; UNDEF = pending exception */
static zv::Val justNullableAccepts(zend_object *self, zval *type, bool strictTypes)
{
	/* $type instanceof static — the object's own class */
	if (instanceof_function(Z_OBJCE_P(type), self->ce)) return pt_type_accepts_result(PT_TRI_YES);
	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
	if (compound) {
		zv::Args args{self, strictTypes};
		return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
	}
	return pt_type_accepts_result(PT_TRI_NO);
}

void pt_type_trait_just_nullable(reg::Class &cls)
{
	namespace sigs = ptdecl::JustNullableTypeTrait::sig;
	cls.traitMethod(sigs::getReferencedClasses, emptyArray0);
	cls.traitOp(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::getObjectClassNames, emptyArray0);
	cls.traitOp(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::getObjectClassReflections, emptyArray0);
	cls.traitOp(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });

	cls.traitMethod(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(justNullableAccepts(PT_THIS_OBJ, type, strictTypes));
	});
	cls.traitOp(PT_OP_ACCEPTS, PT_OP_LAMBDA { return justNullableAccepts(self, argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.traitMethod(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_just_nullable_is_super_type_of(PT_THIS_OBJ, PT_SCOPE, type));
	});
	cls.traitOp(PT_OP_IS_SUPER_TYPE_OF, PT_OP_LAMBDA { return pt_type_just_nullable_is_super_type_of(self, scope, argv); });

	cls.traitMethod(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		/* get_class($type) === static::class */
		RETURN_BOOL(Z_OBJCE_P(type) == PT_THIS_OBJ->ce);
	});
	cls.traitOp(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(Z_OBJCE_P(argv) == self->ce); });

	cls.traitMethod(sigs::traverse, identityTraverse);
	cls.traitOp(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_this(self); });

	cls.traitMethod(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_THIS();
	});

	cls.traitMethod(sigs::isNull, trinaryNo0);
	cls.traitOp(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isConstantValue, trinaryNo0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryNo0);
	cls.traitOp(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitOp(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitOp(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitOp(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitOp(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitOp(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
}

/* }}} */

/* {{{ NonArrayTypeTrait */

void pt_type_trait_non_array(reg::Class &cls)
{
	namespace sigs = ptdecl::NonArrayTypeTrait::sig;
	cls.traitMethod(sigs::getArrays, emptyArray0);
	cls.traitMethod(sigs::getConstantArrays, emptyArray0);
	cls.traitOp(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::isArray, trinaryNo0);
	cls.traitOp(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isConstantArray, trinaryNo0);
	cls.traitOp(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isOversizedArray, trinaryNo0);
	cls.traitMethod(sigs::isList, trinaryNo0);
	cls.traitOp(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });

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
	cls.traitOp(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });

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
	cls.traitOp(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getArraySize, errorType0);
	cls.traitMethod(sigs::getIterableKeyType, errorType0);
	cls.traitOp(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.traitMethod(sigs::getFirstIterableKeyType, errorType0);
	cls.traitMethod(sigs::getLastIterableKeyType, errorType0);
	cls.traitMethod(sigs::getIterableValueType, errorType0);
	cls.traitOp(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_error_type(); });
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
	cls.traitOp(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getInstanceProperty, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, shouldNotHappen2);
	cls.traitOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { pt_throw_should_not_happen(); return zv::Val(); });
	cls.traitMethod(sigs::hasStaticProperty, trinaryNo1);
	cls.traitMethod(sigs::getStaticProperty, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, shouldNotHappen2);

	cls.traitMethod(sigs::canCallMethods, trinaryNo0);
	cls.traitMethod(sigs::hasMethod, trinaryNo1);
	cls.traitOp(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getMethod, shouldNotHappen2);
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, shouldNotHappen2);
	cls.traitOp(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, PT_OP_LAMBDA { pt_throw_should_not_happen(); return zv::Val(); });

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
	cls.traitOp(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });

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
		zend_long otherIsNull = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_NULL, 0, NULL);
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
		zend_long otherIsNull = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_NULL, 0, NULL);
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
	cls.traitOp(PT_OP_GET_REFERENCED_TEMPLATE_TYPES, PT_OP_LAMBDA { return pt_op_empty_array(); });
}

/* }}} */

/* {{{ NonOffsetAccessibleTypeTrait */

void pt_type_trait_non_offset_accessible(reg::Class &cls)
{
	namespace sigs = ptdecl::NonOffsetAccessibleTypeTrait::sig;
	cls.traitMethod(sigs::isOffsetAccessible, trinaryNo0);
	cls.traitMethod(sigs::hasOffsetValueType, trinaryNo1);
	cls.traitOp(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getOffsetValueType, errorType1);
	cls.traitOp(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_error_type(); });
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
		zv::Val result = pt_type_op(PT_THIS_OBJ, PT_OP_TRAVERSE, 1, &closure);
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
	return pt_type_op_bool(self, PT_OP_EQUALS, 1, type, out);
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

	zend_long isConstantArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
	if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
	if (isConstantArray == PT_TRI_YES) {
		zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
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

/* the accepts() / isSuperTypeOf() / getConstantScalarValues() bodies of
 * ConstantScalarTypeTrait — the handlers' and the direct entries'; scope
 * is the class using the trait; UNDEF = pending exception */
static zv::Val constantScalarAccepts(zend_object *self, zend_class_entry *scope, zval *type, bool strictTypes)
{
	if (instanceof_function(Z_OBJCE_P(type), scope)) {
		bool equal;
		if (UNEXPECTED(!constantScalarThisEquals(self, scope, type, equal))) return zv::Val();
		return pt_type_accepts_result(equal ? PT_TRI_YES : PT_TRI_NO);
	}
	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
	zval args[2];
	if (compound) {
		ZVAL_OBJ(&args[0], self);
		ZVAL_BOOL(&args[1], strictTypes);
		return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
	}
	/* parent::accepts($type, $strictTypes)->and(AcceptsResult::createMaybe()) */
	ZVAL_COPY_VALUE(&args[0], type);
	ZVAL_BOOL(&args[1], strictTypes);
	zv::Val parentResult = pt_type_call_parent(scope, self, PT_LC("accepts"), 2, args);
	if (UNEXPECTED(parentResult.isUndef())) return zv::Val();
	zv::Val maybe = pt_type_accepts_result(PT_TRI_MAYBE);
	if (UNEXPECTED(maybe.isUndef())) return zv::Val();
	if (EXPECTED(zv::Ref(parentResult.raw()).instanceOf(pt_ce_accepts_result))) {
		zval combined;
		if (UNEXPECTED(!pt_accepts_result_and(&combined, parentResult.raw(), maybe.raw()))) return zv::Val();
		return zv::Val::adopt(combined);
	}
	if (UNEXPECTED(!zv::Ref(parentResult.raw()).isObject())) {
		zend_type_error("phpstan_turbo: parent::accepts() must return %s", ZSTR_VAL(pt_ce_accepts_result->name));
		return zv::Val();
	}
	return pt_type_op(zv::Ref(parentResult.raw()).asObject(), PT_OP_AND, 1, maybe.raw());
}

static zv::Val constantScalarIsSuperTypeOf(zend_object *self, zend_class_entry *scope, zval *type)
{
	if (instanceof_function(Z_OBJCE_P(type), scope)) {
		bool equal;
		if (UNEXPECTED(!constantScalarThisEquals(self, scope, type, equal))) return zv::Val();
		return pt_type_is_super_type_of_result(equal ? PT_TRI_YES : PT_TRI_NO);
	}
	/* $type instanceof parent */
	if (scope->parent != NULL && instanceof_function(Z_OBJCE_P(type), scope->parent)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
	bool compound;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
	if (compound) {
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &thisValue);
	}
	return pt_type_is_super_type_of_result(PT_TRI_NO);
}

static zv::Val constantScalarGetConstantScalarValues(zend_object *self)
{
	zv::Val value = constantScalarGetValue(self);
	if (UNEXPECTED(value.isUndef())) return zv::Val();
	zv::Arr values = zv::Arr::create(1);
	values.push(std::move(value));
	return zv::Val(std::move(values));
}

void pt_type_trait_constant_scalar(reg::Class &cls)
{
	namespace sigs = ptdecl::ConstantScalarTypeTrait::sig;
	cls.traitMethod(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(constantScalarAccepts(PT_THIS_OBJ, PT_SCOPE, type, strictTypes));
	});
	cls.traitOp(PT_OP_ACCEPTS, PT_OP_LAMBDA { return constantScalarAccepts(self, scope, argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.traitMethod(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(constantScalarIsSuperTypeOf(PT_THIS_OBJ, PT_SCOPE, type));
	});
	cls.traitOp(PT_OP_IS_SUPER_TYPE_OF, PT_OP_LAMBDA { return constantScalarIsSuperTypeOf(self, scope, argv); });

	cls.traitMethod(sigs::looseCompare, pt_type_trait_constant_scalar_loose_compare);

	cls.traitMethod(sigs::equals, constantScalarEquals);
	cls.traitOp(PT_OP_EQUALS, PT_OP_LAMBDA { bool equal; bool ok = constantScalarEqualsImpl(self, scope, argv, equal); return pt_op_bool(ok, equal); });

	cls.traitMethod(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantScalarSmaller(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.traitMethod(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		constantScalarSmaller(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.traitMethod(sigs::isConstantValue, trinaryYes0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryYes0);
	cls.traitOp(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.traitMethod(sigs::getConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Arr types = zv::Arr::create(1);
		types.push(zv::Ref(ZEND_THIS));
		PT_RETURN_VAL(zv::Val(std::move(types)));
	});

	cls.traitMethod(sigs::getConstantScalarValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(constantScalarGetConstantScalarValues(PT_THIS_OBJ));
	});
	cls.traitOp(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return constantScalarGetConstantScalarValues(self); });

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
		zend_long otherIsNull = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_NULL, 0, NULL);
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
		zend_long otherIsNull = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_NULL, 0, NULL);
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

	zv::Val description = pt_type_op(Z_OBJ_P(subtractedType), PT_OP_DESCRIBE, 1, level);
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
	cls.traitOp(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });

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
		zend_long atLeastOnce = pt_type_method_is(PT_THIS_OBJ, PT_LC("isiterableatleastonce"), maybeIterableMaybe0) ? PT_TRI_MAYBE : pt_type_op_trinary(PT_THIS_OBJ, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) RETURN_THROWS();
		if (atLeastOnce == PT_TRI_YES) {
			PT_RETURN_VAL(pt_integer_range_from_interval(phpstanturbo::NullableLong::of(1), phpstanturbo::NullableLong::null(), 0));
		}
		PT_RETURN_VAL(pt_integer_range_from_interval(phpstanturbo::NullableLong::of(0), phpstanturbo::NullableLong::null(), 0));
	});

	cls.traitMethod(sigs::getIterableKeyType, maybeIterableMixed0);
	cls.traitOp(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.traitMethod(sigs::getFirstIterableKeyType, maybeIterableMixed0);
	cls.traitMethod(sigs::getLastIterableKeyType, maybeIterableMixed0);
	cls.traitMethod(sigs::getIterableValueType, maybeIterableMixed0);
	cls.traitOp(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
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
	cls.traitOp(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});
	cls.traitOp(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
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
		zv::Val classNames = pt_type_op(PT_THIS_OBJ, PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) RETURN_THROWS();
		if (UNEXPECTED(!zv::Ref(classNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassNames() must return array");
			RETURN_THROWS();
		}
		if (zv::ArrRef(classNames.raw()).size() == 1) {
			zval *className = zend_hash_index_find(zv::ArrRef(classNames.raw()).table(), 0);
			if (className != NULL) {
				zv::Val hasClass = pt_reflection_provider_has_class_zv(Z_OBJ_P(reflectionProvider), className);
				if (UNEXPECTED(hasClass.isUndef())) RETURN_THROWS();
				if (zend_is_true(hasClass.raw())) {
					zv::Val reflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className);
					if (UNEXPECTED(reflection.isUndef())) RETURN_THROWS();
					if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
						zend_type_error("phpstan_turbo: getClass() must return an object");
						RETURN_THROWS();
					}
					zv::Val finalByKeyword = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
					if (UNEXPECTED(finalByKeyword.isUndef())) RETURN_THROWS();
					if (zend_is_true(finalByKeyword.raw())) {
						/* new ConstantStringType($reflection->getName(), true) */
						zv::Val name = pt_class_reflection_get_name(Z_OBJ_P(reflection.raw()));
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
	cls.traitOp(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { return pt_type_dummy_unresolved_prototype(false, argv); });
	cls.traitMethod(sigs::hasStaticProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.traitMethod(sigs::canCallMethods, trinaryYes0);
	cls.traitMethod(sigs::hasMethod, trinaryMaybe1);
	cls.traitOp(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		objectTraitUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});
	cls.traitOp(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, PT_OP_LAMBDA { return pt_type_dummy_unresolved_prototype(true, argv); });

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
	cls.traitOp(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isConstantValue, trinaryNo0);
	cls.traitMethod(sigs::isConstantScalarValue, trinaryNo0);
	cls.traitOp(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitOp(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitOp(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitOp(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitOp(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitOp(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });

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
	return (isMethod ? pt_callback_unresolved_method_prototype_reflection_new(4, args) : pt_callback_unresolved_property_prototype_reflection_new(4, args));
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

/* the NativeCallback holder's class entry and __invoke() handler
 * (registered below) */
static zend_class_entry *pt_ce_native_callback = nullptr;
static void ZEND_FASTCALL invokeNativeCallback(INTERNAL_FUNCTION_PARAMETERS);

/* $callable(...$args) for a callable value native code holds: a
 * NativeCallback holder, a Closure over a holder's __invoke(), or a
 * TypeTraverser's [$this, 'mapInternal'] / [$this, 'traverseInternal']
 * array is entered directly (TypeOps.h); anything else goes through the
 * engine. The direct entries see the arguments the handlers' zpp would
 * deliver: mapInternal()/traverseInternal() take one object (Z_PARAM_OBJECT),
 * the holders' __invoke() anything. */
zv::Val pt_type_call_callable(zval *callable, uint32_t argc, zval *argv)
{
	zval *target = callable;
	ZVAL_DEREF(target);
	if (Z_TYPE_P(target) == IS_OBJECT) {
		zend_object *object = Z_OBJ_P(target);
		/* the engine ports' closures (Engine.h) */
		if (object->ce == pt_ce_native_closure) {
			zval ret;
			return pt_native_closure_invoke(object, argc, argv, &ret) ? zv::Val::adopt(ret) : zv::Val();
		}
		if (object->ce == pt_ce_native_callback) {
			zval ret;
			return pt_native_callback_invoke(object, argc, argv, &ret) ? zv::Val::adopt(ret) : zv::Val();
		}
		/* a node callback recording a convergence pass
		 * (RecordingNodeCallback.cpp): its __invoke(Node $node, Scope $scope)
		 * without the call, when the arguments are what its parsing accepts */
		if (object->ce == pt_ce_recording_node_callback && argc == 2 && Z_TYPE(argv[0]) == IS_OBJECT && Z_TYPE(argv[1]) == IS_OBJECT) {
			return pt_recording_node_callback_record(object, &argv[0], &argv[1]) ? zv::Val::null() : zv::Val();
		}
		if (object->ce == zend_ce_closure) {
			const zend_function *fn = zend_get_closure_method_def(object);
			if (fn->type == ZEND_INTERNAL_FUNCTION) {
#if PHP_VERSION_ID >= 80600
				/* PHP 8.6 holds the bound $this as a zend_object (NULL when unbound) */
				zend_object *bound = zend_get_closure_this_ptr(target);
#else
				zval *thisZv = zend_get_closure_this_ptr(target);
				zend_object *bound = thisZv != NULL && Z_TYPE_P(thisZv) == IS_OBJECT ? Z_OBJ_P(thisZv) : NULL;
#endif
				zval ret;
				bool handled;
				bool ok = pt_direct_invoke(fn, bound, argc, argv, &ret, handled);
				if (handled) return ok ? zv::Val::adopt(ret) : zv::Val();
			}
		}
	} else if (Z_TYPE_P(target) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(target)) == 2 && argc == 1 && Z_TYPE_P(argv) == IS_OBJECT) {
		zval *object = zend_hash_index_find(Z_ARRVAL_P(target), 0);
		zval *method = zend_hash_index_find(Z_ARRVAL_P(target), 1);
		if (object != NULL && method != NULL) {
			ZVAL_DEREF(object);
			ZVAL_DEREF(method);
			if (Z_TYPE_P(object) == IS_OBJECT && Z_OBJCE_P(object) == pt_ce_type_traverser && Z_TYPE_P(method) == IS_STRING) {
				zend_string *name = Z_STR_P(method);
				if (zend_string_equals_literal_ci(name, "mapInternal")) return pt_type_traverser_map_internal(Z_OBJ_P(object), argv);
				if (zend_string_equals_literal_ci(name, "traverseInternal")) return pt_type_traverser_traverse_internal(Z_OBJ_P(object), argv);
			}
		}
	}
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

#define PT_NC_PROP_FN 0
#define PT_NC_PROP_STATE0 1
#define PT_NC_PROP_STATE1 2

bool pt_native_callback_invoke(zend_object *holder, uint32_t argc, zval *argv, zval *retval)
{
	zval *fnSlot = OBJ_PROP_NUM(holder, PT_NC_PROP_FN);
	if (UNEXPECTED(Z_TYPE_P(fnSlot) != IS_LONG)) {
		zend_throw_error(NULL, "phpstan_turbo: native callback holder without a body");
		ZVAL_UNDEF(retval);
		return false;
	}
	pt_native_callback fn = (pt_native_callback) (uintptr_t) Z_LVAL_P(fnSlot);
	ZVAL_NULL(retval); /* the engine's initial return value */
	fn(OBJ_PROP_NUM(holder, PT_NC_PROP_STATE0), OBJ_PROP_NUM(holder, PT_NC_PROP_STATE1), argc, argv, retval);
	if (UNEXPECTED(EG(exception))) {
		/* the engine releases the return value of a throwing call; a body
		 * that set one before throwing must not leave it to be released
		 * twice */
		zval_ptr_dtor(retval);
		ZVAL_UNDEF(retval);
		return false;
	}
	return true;
}

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
	zval ret;
	if (UNEXPECTED(!pt_native_callback_invoke(Z_OBJ_P(ZEND_THIS), argc, args, &ret))) RETURN_THROWS();
	RETURN_COPY_VALUE(&ret);
}

zif_handler pt_native_callback_invoke_handler()
{
	return invokeNativeCallback;
}

zend_class_entry *pt_native_callback_ce()
{
	return pt_ce_native_callback;
}

bool pt_direct_invoke(const zend_function *fn, zend_object *object, uint32_t argc, zval *argv, zval *retval, bool &handled)
{
	handled = true;
	zif_handler handler = fn->internal_function.handler;
	zend_class_entry *scope = fn->common.scope;
	if (handler == pt_native_closure_invoke_handler() || (scope == pt_ce_native_closure && zend_string_equals_literal(fn->common.function_name, "__invoke"))) {
		if (EXPECTED(object != NULL && object->ce == pt_ce_native_closure)) return pt_native_closure_invoke(object, argc, argv, retval);
	} else if (handler == invokeNativeCallback || (scope == pt_ce_native_callback && zend_string_equals_literal(fn->common.function_name, "__invoke"))) {
		if (EXPECTED(object != NULL && object->ce == pt_ce_native_callback)) return pt_native_callback_invoke(object, argc, argv, retval);
	} else if (handler == pt_object_type_callback_invoke_handler() || (scope == pt_object_type_callback_ce() && zend_string_equals_literal(fn->common.function_name, "__invoke"))) {
		if (EXPECTED(object != NULL && object->ce == pt_object_type_callback_ce())) {
			zv::Val result = pt_object_type_callback_invoke(object);
			if (UNEXPECTED(result.isUndef())) {
				ZVAL_UNDEF(retval);
				return false;
			}
			*retval = result.take();
			return true;
		}
	} else if (handler == pt_type_traverser_map_internal_handler() || handler == pt_type_traverser_traverse_internal_handler()) {
		/* the twins' (Type $type) parameter: one object, as Z_PARAM_OBJECT
		 * delivers it */
		if (EXPECTED(object != NULL && object->ce == pt_ce_type_traverser && argc == 1 && Z_TYPE_P(argv) == IS_OBJECT)) {
			zv::Val result = handler == pt_type_traverser_map_internal_handler()
				? pt_type_traverser_map_internal(object, argv)
				: pt_type_traverser_traverse_internal(object, argv);
			if (UNEXPECTED(result.isUndef())) {
				ZVAL_UNDEF(retval);
				return false;
			}
			*retval = result.take();
			return true;
		}
	} else {
		/* the StaticType callback holder (StaticType.cpp) and the identity
		 * callback (MixedType.cpp) */
		if (pt_static_type_callbacks_direct_invoke(fn, object, argc, argv, retval, handled)) return true;
		if (handled) return false;
		if (pt_identity_callback_direct_invoke(fn, object, argc, argv, retval, handled)) return true;
		if (handled) return false;
	}
	handled = false;
	return false;
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
	return pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, other);
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
		chunkType = pt_trait_list_of(pt_type_op(PT_THIS_OBJ, PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
		if (UNEXPECTED(chunkType.isUndef())) RETURN_THROWS();
	}
	/* $chunkType = TypeCombinator::intersect($chunkType, new NonEmptyArrayType()) */
	chunkType = pt_trait_intersect_non_empty(chunkType.raw());
	if (UNEXPECTED(chunkType.isUndef())) RETURN_THROWS();
	/* $arrayType = list<$chunkType> */
	zv::Val arrayType = pt_trait_list_of(std::move(chunkType));
	if (UNEXPECTED(arrayType.isUndef())) RETURN_THROWS();
	/* $this->isIterableAtLeastOnce()->yes() ? TypeCombinator::intersect($arrayType, new NonEmptyArrayType()) : $arrayType */
	zend_long atLeastOnce = pt_type_op_trinary(PT_THIS_OBJ, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
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
	cls.traitOp(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.traitMethod(sigs::toArray, this0);
	cls.traitMethod(sigs::toArrayKey, errorType0);
	cls.traitOp(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
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
	cls.traitOp(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::getConstantScalarTypes, emptyArray0);
	cls.traitMethod(sigs::getConstantScalarValues, emptyArray0);
	cls.traitOp(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::getObjectClassNames, emptyArray0);
	cls.traitOp(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::getObjectClassReflections, emptyArray0);
	cls.traitOp(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::toNumber, errorType0);
	cls.traitMethod(sigs::toBitwiseNotType, errorType0);
	cls.traitMethod(sigs::toAbsoluteNumber, errorType0);
	cls.traitMethod(sigs::toString, errorType0);
	cls.traitMethod(sigs::isIterable, trinaryYes0);
	cls.traitMethod(sigs::isOversizedArray, trinaryMaybe0);
	cls.traitMethod(sigs::isNull, trinaryNo0);
	cls.traitOp(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isTrue, trinaryNo0);
	cls.traitMethod(sigs::isFalse, trinaryNo0);
	cls.traitMethod(sigs::isBoolean, trinaryNo0);
	cls.traitOp(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isFloat, trinaryNo0);
	cls.traitOp(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isInteger, trinaryNo0);
	cls.traitOp(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.traitMethod(sigs::isString, trinaryNo0);
	cls.traitOp(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
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
	cls.traitOp(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.traitMethod(sigs::isArray, trinaryMaybe0);
	cls.traitOp(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::isConstantArray, trinaryMaybe0);
	cls.traitOp(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::isOversizedArray, trinaryMaybe0);
	cls.traitMethod(sigs::isList, trinaryMaybe0);
	cls.traitOp(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });

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
/* the body, shared with the direct entries; UNDEF = pending exception */
static zv::Val maybeObjectUnresolvedPrototype(bool isMethod, zval *name)
{
	zv::Val member = pt_type_new(isMethod ? PT_CLASS_DUMMY_METHOD_REFLECTION : PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, name);
	if (UNEXPECTED(member.isUndef())) return zv::Val();
	zv::Val declaringClass = pt_type_call(Z_OBJ_P(member.raw()), PT_LC("getdeclaringclass"), 0, NULL);
	if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
	zv::Val callback = pt_type_identity_callback();
	zv::Args args{member.raw(), declaringClass.raw(), false, callback.raw()};
	return pt_type_new_ce(isMethod ? pt_ce_callback_unresolved_method_prototype_reflection : pt_ce_callback_unresolved_property_prototype_reflection, 4, args);
}

static void pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAMETERS, bool isMethod)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL(maybeObjectUnresolvedPrototype(isMethod, &nameZv));

	zv::Val member = pt_type_new(isMethod ? PT_CLASS_DUMMY_METHOD_REFLECTION : PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, &nameZv);
	if (UNEXPECTED(member.isUndef())) RETURN_THROWS();
	zv::Val declaringClass = pt_type_call(Z_OBJ_P(member.raw()), PT_LC("getdeclaringclass"), 0, NULL);
	if (UNEXPECTED(declaringClass.isUndef())) RETURN_THROWS();
	zv::Val callback = pt_type_identity_callback();
	zv::Args args{member.raw(), declaringClass.raw(), false, callback.raw()};
	PT_RETURN_VAL((isMethod ? pt_callback_unresolved_method_prototype_reflection_new(4, args) : pt_callback_unresolved_property_prototype_reflection_new(4, args)));
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
	cls.traitOp(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { return maybeObjectUnresolvedPrototype(false, argv); });
	cls.traitMethod(sigs::hasStaticProperty, trinaryMaybe1);
	cls.traitMethod(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.traitMethod(sigs::canCallMethods, trinaryMaybe0);
	cls.traitMethod(sigs::hasMethod, trinaryMaybe1);
	cls.traitOp(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.traitMethod(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_maybe_object_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});
	cls.traitOp(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, PT_OP_LAMBDA { return maybeObjectUnresolvedPrototype(true, argv); });
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
	cls.traitOp(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
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
	return pt_native_parameter_reflection_new(6, args);
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
		return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &thisValue);
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

	/* the same for a hot operation (TypeOps.h): the resolved type's direct
	 * entry when it is a native class */
	zv::Val delegateOp(pt_type_op_id op, uint32_t argc, zval *argv) const
	{
		zv::Val result = resolved();
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return pt_type_op(Z_OBJ_P(result.raw()), op, argc, argv);
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
	zv::Val description = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, level);
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
	cls.traitOp(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, argc, argv); });
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
	cls.traitOp(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_HAS_INSTANCE_PROPERTY, argc, argv); });
	cls.traitMethod(sigs::getInstanceProperty, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedInstancePropertyPrototype, lrDelegate);
	cls.traitOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, argc, argv); });
	cls.traitMethod(sigs::hasStaticProperty, lrDelegate);
	cls.traitMethod(sigs::getStaticProperty, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedStaticPropertyPrototype, lrDelegate);
	cls.traitMethod(sigs::canCallMethods, lrDelegate);
	cls.traitMethod(sigs::hasMethod, lrDelegate);
	cls.traitOp(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_HAS_METHOD, argc, argv); });
	cls.traitMethod(sigs::getMethod, lrDelegate);
	cls.traitMethod(sigs::getUnresolvedMethodPrototype, lrDelegate);
	cls.traitOp(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, argc, argv); });
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
	cls.traitOp(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_HAS_OFFSET_VALUE_TYPE, argc, argv); });
	cls.traitMethod(sigs::getOffsetValueType, lrDelegate);
	cls.traitOp(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_GET_OFFSET_VALUE_TYPE, argc, argv); });
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
	cls.traitOp(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return LateResolvable(self, scope).delegateOp(PT_OP_GET_ENUM_CASE_OBJECT, argc, argv); });
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
	cls.traitOp(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(true); });
}

/* }}} */

/* {{{ TemplateTypeTrait (src/Type/Generic/TemplateTypeTrait.php) */

/* the trait's six private properties, the class's last slots in this
 * order (pt_type_trait_template_type() declares them so) */
#define PT_TT_PROP_NAME 0
#define PT_TT_PROP_SCOPE 1
#define PT_TT_PROP_STRATEGY 2
#define PT_TT_PROP_VARIANCE 3
#define PT_TT_PROP_BOUND 4
#define PT_TT_PROP_DEFAULT 5
#define PT_TT_PROP_COUNT 6

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeTrait, run on the object with
 * `self` bound to scope — the class using the trait. State lives in the six
 * slots the registrar declares on that class. */
class TemplateTypeTrait
{
public:
	TemplateTypeTrait(zend_object *self, zend_class_entry *scope) : self(self), scope(scope) {}

	/* {{{ the slots */

	static zval *slotOf(zend_object *object, zend_class_entry *scope, int index)
	{
		return OBJ_PROP_NUM(object, (uint32_t) scope->default_properties_count - PT_TT_PROP_COUNT + (uint32_t) index);
	}

	/* a slot read as the twin's typed-property read: NULL with an Error
	 * pending when the constructor never ran */
	[[nodiscard]] static zval *slotOrThrow(zend_object *object, zend_class_entry *scope, int index, const char *propertyName)
	{
		zval *slot = slotOf(object, scope, index);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(scope->name), propertyName);
			return NULL;
		}
		return slot;
	}

	zval *nameSlot() const { return slotOrThrow(self, scope, PT_TT_PROP_NAME, "name"); }
	zval *scopeSlot() const { return slotOrThrow(self, scope, PT_TT_PROP_SCOPE, "scope"); }
	zval *strategySlot() const { return slotOrThrow(self, scope, PT_TT_PROP_STRATEGY, "strategy"); }
	zval *varianceSlot() const { return slotOrThrow(self, scope, PT_TT_PROP_VARIANCE, "variance"); }
	zval *boundSlot() const { return slotOrThrow(self, scope, PT_TT_PROP_BOUND, "bound"); }
	zval *defaultSlot() const { return slotOrThrow(self, scope, PT_TT_PROP_DEFAULT, "default"); }

	/* the constructor tail: the six slots written in the twin's order */
	void init(zval *templateScope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		zv::ObjRef object(self);
		uint32_t base = (uint32_t) scope->default_properties_count - PT_TT_PROP_COUNT;
		object.propAtWrite(base + PT_TT_PROP_SCOPE, zv::Val::copyOf(zv::Ref(templateScope)));
		object.propAtWrite(base + PT_TT_PROP_STRATEGY, zv::Val::copyOf(zv::Ref(strategy)));
		object.propAtWrite(base + PT_TT_PROP_VARIANCE, zv::Val::copyOf(zv::Ref(variance)));
		object.propAtWrite(base + PT_TT_PROP_NAME, zv::Val::string(name));
		object.propAtWrite(base + PT_TT_PROP_BOUND, zv::Val::copyOf(zv::Ref(bound)));
		object.propAtWrite(base + PT_TT_PROP_DEFAULT, defaultType == NULL || Z_TYPE_P(defaultType) == IS_NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(defaultType)));
		for (int i = 0; i < PT_TT_PROP_COUNT; i++) {
			Z_PROP_FLAG_P(OBJ_PROP_NUM(self, base + (uint32_t) i)) = 0; /* no longer IS_PROP_UNINIT */
		}
	}

	/* }}} */

	/* {{{ the $this-calls a PHP subclass may override: the slot when the
	 * object is exactly the class using the trait, the method through its
	 * class entry otherwise; UNDEF = pending exception */

	bool isExact() const { return self->ce == scope; }

	zv::Val thisCall(const char *lcname, size_t len, int slotIndex, const char *propertyName) const
	{
		if (EXPECTED(isExact())) {
			zval *slot = slotOrThrow(self, scope, slotIndex, propertyName);
			return slot == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(slot));
		}
		return pt_type_call(self, lcname, len, 0, NULL);
	}

	zv::Val thisGetName() const { return thisCall(PT_LC("getname"), PT_TT_PROP_NAME, "name"); }
	zv::Val thisGetScope() const { return thisCall(PT_LC("getscope"), PT_TT_PROP_SCOPE, "scope"); }
	zv::Val thisGetStrategy() const { return thisCall(PT_LC("getstrategy"), PT_TT_PROP_STRATEGY, "strategy"); }
	zv::Val thisGetVariance() const { return thisCall(PT_LC("getvariance"), PT_TT_PROP_VARIANCE, "variance"); }
	zv::Val thisGetDefault() const { return thisCall(PT_LC("getdefault"), PT_TT_PROP_DEFAULT, "default"); }

	/* $this->getBound(), checked to be a Type (the twin's return type) */
	zv::Val thisGetBound() const
	{
		zv::Val bound = thisCall(PT_LC("getbound"), PT_TT_PROP_BOUND, "bound");
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getBound() must return %s, %s returned", ZSTR_VAL(self->ce->name), ptcls::type, zend_zval_value_name(bound.raw()));
			return zv::Val();
		}
		return bound;
	}

	/* $this->isArgument(); false = pending exception */
	[[nodiscard]] bool thisIsArgument(bool &out) const
	{
		if (EXPECTED(isExact())) return isArgument(out);
		zv::Val result = pt_type_call(self, PT_LC("isargument"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $this->isSuperTypeOf($type) / $this->isSubTypeOf($type) /
	 * $this->isNull() / $this->getObjectClassNames() */
	zv::Val thisIsSuperTypeOf(zval *type) const
	{
		return isExact() ? isSuperTypeOf(type) : pt_type_op(self, PT_OP_IS_SUPER_TYPE_OF, 1, type);
	}

	zv::Val thisIsSubTypeOf(zval *type) const
	{
		return isExact() ? isSubTypeOf(type) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, type);
	}

	/* }}} */

	/* {{{ TemplateTypeFactory::create($this->getScope(), $this->getName(),
	 * $bound, $this->getVariance(), $this->getStrategy(), $default) — the
	 * rebuild every mutator ends in; UNDEF = pending exception */

	zv::Val recreate(zval *bound, zval *defaultType) const
	{
		zv::Val templateScope = thisGetScope();
		if (UNEXPECTED(templateScope.isUndef())) return zv::Val();
		zv::Val name = thisGetName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val variance = thisGetVariance();
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		zv::Val strategy = thisGetStrategy();
		if (UNEXPECTED(strategy.isUndef())) return zv::Val();
		return pt_template_type_factory_create(templateScope.raw(), name.raw(), bound, variance.raw(), strategy.raw(), defaultType);
	}

	/* the same over $this->getDefault() */
	zv::Val recreateKeepingDefault(zval *bound) const
	{
		zv::Val defaultType = thisGetDefault();
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		return recreate(bound, defaultType.raw());
	}

	/* }}} */

	/* {{{ the methods, in the trait's order */

	/* the `fn () => $this->default->describe($level)` of describe():
	 * state0 is $this->default, state1 the level */
	static void describeDefault(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		zv::Val described = pt_type_op(Z_OBJ_P(state0), PT_OP_DESCRIBE, 1, state1);
		if (UNEXPECTED(described.isUndef())) return;
		described.intoReturnValue(return_value);
	}

	/* the $basicDescription closure: the name, ` of <bound>` unless the
	 * bound is a plain mixed, ` = <default>` unless describing it recurses;
	 * an owned string, UNDEF = pending exception */
	zv::Val basicDescription(zval *level) const
	{
		zval *name = nameSlot();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *bound = boundSlot();
		if (UNEXPECTED(bound == NULL)) return zv::Val();
		zval *defaultType = defaultSlot();
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		smart_str description = {0, 0};
		smart_str_append(&description, Z_STR_P(name));

		/* $this->bound instanceof MixedType && $this->bound->getSubtractedType() === null && !$this->bound instanceof TemplateMixedType */
		bool plainMixed = false;
		if (zv::Ref(bound).instanceOf(pt_ce_mixed_type)) {
			zv::Val subtracted = pt_type_call(Z_OBJ_P(bound), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(subtracted.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			plainMixed = zv::Ref(subtracted.raw()).isNull() && !zv::Ref(bound).instanceOf(pt_ce_template_mixed_type);
		}
		if (!plainMixed) {
			zv::Val boundDescription = pt_type_op(Z_OBJ_P(bound), PT_OP_DESCRIBE, 1, level);
			if (UNEXPECTED(boundDescription.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (UNEXPECTED(!zv::Ref(boundDescription.raw()).isString())) {
				smart_str_free(&description);
				zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(bound)->name));
				return zv::Val();
			}
			smart_str_appendl(&description, " of ", 4);
			smart_str_append(&description, Z_STR_P(boundDescription.raw()));
		}
		if (Z_TYPE_P(defaultType) == IS_OBJECT) {
			zv::Val callback = pt_type_native_callback(describeDefault, defaultType, level);
			if (UNEXPECTED(callback.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			zv::Val guarded = pt_type_recursion_guard_run_on_object_identity(defaultType, callback.raw());
			if (UNEXPECTED(guarded.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (!zv::Ref(guarded.raw()).instanceOf(pt_ce_error_type)) {
				if (UNEXPECTED(!zv::Ref(guarded.raw()).isString())) {
					smart_str_free(&description);
					zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(defaultType)->name));
					return zv::Val();
				}
				smart_str_appendl(&description, " = ", 3);
				smart_str_append(&description, Z_STR_P(guarded.raw()));
			}
		}
		smart_str_0(&description);
		return zv::Val::adoptString(smart_str_extract(&description));
	}

	/* $level->handle($basic, $basic, fn () => '<basic> (<scope>, argument|parameter)') */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		zv::Val basic = basicDescription(level);
		if (UNEXPECTED(basic.isUndef())) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) return basic;
		/* the precise callback; handle() falls back to it for the cache
		 * level too, no cache callback being given */
		zval *templateScope = scopeSlot();
		if (UNEXPECTED(templateScope == NULL)) return zv::Val();
		zv::Val scopeDescription = pt_type_op(Z_OBJ_P(templateScope), PT_OP_DESCRIBE, 0, NULL);
		if (UNEXPECTED(scopeDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(scopeDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(templateScope)->name));
			return zv::Val();
		}
		bool argument;
		if (UNEXPECTED(!thisIsArgument(argument))) return zv::Val();
		smart_str description = {0, 0};
		smart_str_append(&description, Z_STR_P(basic.raw()));
		smart_str_appendl(&description, " (", 2);
		smart_str_append(&description, Z_STR_P(scopeDescription.raw()));
		smart_str_appendl(&description, ", ", 2);
		if (argument) {
			smart_str_appendl(&description, "argument", sizeof("argument") - 1);
		} else {
			smart_str_appendl(&description, "parameter", sizeof("parameter") - 1);
		}
		smart_str_appendc(&description, ')');
		smart_str_0(&description);
		return zv::Val::adoptString(smart_str_extract(&description));
	}

	/* $this->strategy->isArgument(); false = pending exception */
	[[nodiscard]] bool isArgument(bool &out) const
	{
		zval *strategy = strategySlot();
		if (UNEXPECTED(strategy == NULL)) return false;
		zend_class_entry *ce = Z_OBJCE_P(strategy);
		if (EXPECTED(ce == pt_ce_template_type_argument_strategy)) {
			out = true;
			return true;
		}
		if (EXPECTED(ce == pt_ce_template_type_parameter_strategy)) {
			out = false;
			return true;
		}
		zv::Val result = pt_type_call(Z_OBJ_P(strategy), PT_LC("isargument"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* TemplateTypeHelper::toArgument($type) */
	static zv::Val helperToArgument(zval *type)
	{
		return pt_type_template_type_helper_to_argument(type);
	}

	/* TemplateTypeFactory::create($this->scope, $this->name, TemplateTypeHelper::toArgument($this->getBound()), $this->variance, new TemplateTypeArgumentStrategy(), <default to argument>) */
	zv::Val toArgument() const
	{
		zval *templateScope = scopeSlot();
		if (UNEXPECTED(templateScope == NULL)) return zv::Val();
		zval *name = nameSlot();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val argumentBound = helperToArgument(bound.raw());
		if (UNEXPECTED(argumentBound.isUndef())) return zv::Val();
		zval *variance = varianceSlot();
		if (UNEXPECTED(variance == NULL)) return zv::Val();
		zv::Val strategy = pt_template_type_argument_strategy_create();
		if (UNEXPECTED(strategy.isUndef())) return zv::Val();
		zval *defaultType = defaultSlot();
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		zv::Val argumentDefault;
		if (Z_TYPE_P(defaultType) == IS_OBJECT) {
			argumentDefault = helperToArgument(defaultType);
			if (UNEXPECTED(argumentDefault.isUndef())) return zv::Val();
		}
		return pt_template_type_factory_create(templateScope, name, argumentBound.raw(), variance, strategy.raw(), argumentDefault.isUndef() ? NULL : argumentDefault.raw());
	}

	/* $this->variance->isValidVariance($this, $a, $b, $strict) */
	zv::Val isValidVariance(zval *a, zval *b, bool strict) const
	{
		zval *variance = varianceSlot();
		if (UNEXPECTED(variance == NULL)) return zv::Val();
		zv::Args args{self, a, b, strict};
		return pt_type_call(Z_OBJ_P(variance), PT_LC("isvalidvariance"), 4, args);
	}

	/* the rebuild over TypeCombinator::remove($this->getBound(), $typeToRemove) */
	zv::Val subtract(zval *typeToRemove) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val removedBound = pt_type_combinator_remove(bound.raw(), typeToRemove);
		if (UNEXPECTED(removedBound.isUndef())) return zv::Val();
		return recreateKeepingDefault(removedBound.raw());
	}

	/* $bound instanceof SubtractableType; false = pending exception */
	[[nodiscard]] static bool isSubtractable(zval *bound, bool &out)
	{
		return pt_type_instanceof(bound, PT_CLASS_SUBTRACTABLE_TYPE, out);
	}

	/* $this for a bound that is not subtractable, else the rebuild over
	 * $bound->getTypeWithoutSubtractedType() */
	zv::Val getTypeWithoutSubtractedType() const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		bool subtractable;
		if (UNEXPECTED(!isSubtractable(bound.raw(), subtractable))) return zv::Val();
		if (!subtractable) return thisObject();
		zv::Val withoutSubtracted = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("gettypewithoutsubtractedtype"), 0, NULL);
		if (UNEXPECTED(withoutSubtracted.isUndef())) return zv::Val();
		return recreateKeepingDefault(withoutSubtracted.raw());
	}

	/* $this for a bound that is not subtractable, else the rebuild over
	 * $bound->changeSubtractedType($subtractedType) */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		bool subtractable;
		if (UNEXPECTED(!isSubtractable(bound.raw(), subtractable))) return zv::Val();
		if (!subtractable) return thisObject();
		zv::Val changed = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("changesubtractedtype"), 1, subtractedType);
		if (UNEXPECTED(changed.isUndef())) return zv::Val();
		return recreateKeepingDefault(changed.raw());
	}

	/* null for a bound that is not subtractable, else $bound->getSubtractedType() */
	zv::Val getSubtractedType() const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		bool subtractable;
		if (UNEXPECTED(!isSubtractable(bound.raw(), subtractable))) return zv::Val();
		if (!subtractable) return zv::Val::null();
		return pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("getsubtractedtype"), 0, NULL);
	}

	/* $type instanceof self && $type->scope->equals($this->scope) && $type->name === $this->name && $this->bound->equals($type->bound) && <defaults both null or equal>; false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		out = false;
		if (!instanceof_function(Z_OBJCE_P(type), scope)) return true;
		zend_object *that = Z_OBJ_P(type);
		zval *thatScope = slotOrThrow(that, scope, PT_TT_PROP_SCOPE, "scope");
		if (UNEXPECTED(thatScope == NULL)) return false;
		zval *thisScope = scopeSlot();
		if (UNEXPECTED(thisScope == NULL)) return false;
		zv::Val scopesEqual = pt_type_op(Z_OBJ_P(thatScope), PT_OP_EQUALS, 1, thisScope);
		if (UNEXPECTED(scopesEqual.isUndef())) return false;
		if (!zend_is_true(scopesEqual.raw())) return true;
		zval *thatName = slotOrThrow(that, scope, PT_TT_PROP_NAME, "name");
		if (UNEXPECTED(thatName == NULL)) return false;
		zval *thisName = nameSlot();
		if (UNEXPECTED(thisName == NULL)) return false;
		if (!zend_string_equals(Z_STR_P(thatName), Z_STR_P(thisName))) return true;
		zval *thisBound = boundSlot();
		if (UNEXPECTED(thisBound == NULL)) return false;
		zval *thatBound = slotOrThrow(that, scope, PT_TT_PROP_BOUND, "bound");
		if (UNEXPECTED(thatBound == NULL)) return false;
		zv::Val boundsEqual = pt_type_op(Z_OBJ_P(thisBound), PT_OP_EQUALS, 1, thatBound);
		if (UNEXPECTED(boundsEqual.isUndef())) return false;
		if (!zend_is_true(boundsEqual.raw())) return true;
		zval *thisDefault = defaultSlot();
		if (UNEXPECTED(thisDefault == NULL)) return false;
		zval *thatDefault = slotOrThrow(that, scope, PT_TT_PROP_DEFAULT, "default");
		if (UNEXPECTED(thatDefault == NULL)) return false;
		if (Z_TYPE_P(thisDefault) != IS_OBJECT) {
			out = Z_TYPE_P(thatDefault) != IS_OBJECT;
			return true;
		}
		if (Z_TYPE_P(thatDefault) != IS_OBJECT) return true;
		return pt_type_op_bool(Z_OBJ_P(thisDefault), PT_OP_EQUALS, 1, thatDefault, out);
	}

	/* the shared head of isAcceptedBy() and isSubTypeOf(): whether the other
	 * type is a compound (union / intersection) that is neither an instance
	 * of the bound's class nor a class of $this nor a template type — the
	 * case delegated back to it; false = pending exception */
	[[nodiscard]] bool delegatesToCompound(zval *other, zval *bound, bool &out) const
	{
		out = false;
		zend_class_entry *otherCe = Z_OBJCE_P(other);
		if (instanceof_function(otherCe, Z_OBJCE_P(bound)) || instanceof_function(self->ce, otherCe)) return true;
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(other, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return false;
		if (isTemplate) return true;
		out = instanceof_function(otherCe, pt_ce_union_type) || instanceof_function(otherCe, pt_ce_intersection_type);
		return true;
	}

	/* $this->getScope()->equals($other->getScope()) && $this->getName() === $other->getName(); false = pending exception */
	[[nodiscard]] bool sameTemplate(zval *other, bool &out) const
	{
		out = false;
		zv::Val thisScope = thisGetScope();
		if (UNEXPECTED(thisScope.isUndef())) return false;
		zv::Val otherScope = pt_type_call(Z_OBJ_P(other), PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(otherScope.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(thisScope.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getScope() must return %s", ZSTR_VAL(self->ce->name), ptcls::templateTypeScope);
			return false;
		}
		zv::Val scopesEqual = pt_type_op(Z_OBJ_P(thisScope.raw()), PT_OP_EQUALS, 1, otherScope.raw());
		if (UNEXPECTED(scopesEqual.isUndef())) return false;
		if (!zend_is_true(scopesEqual.raw())) return true;
		zv::Val thisName = thisGetName();
		if (UNEXPECTED(thisName.isUndef())) return false;
		zv::Val otherName = pt_type_call(Z_OBJ_P(other), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(otherName.isUndef())) return false;
		out = zv::Ref(thisName.raw()).isString() && zv::Ref(otherName.raw()).isString() && zend_string_equals(Z_STR_P(thisName.raw()), Z_STR_P(otherName.raw()));
		return true;
	}

	/* $other->getBound(), checked to be a Type */
	static zv::Val boundOf(zval *other)
	{
		zv::Val bound = pt_type_call(Z_OBJ_P(other), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getBound() must return %s, %s returned", ZSTR_VAL(Z_OBJCE_P(other)->name), ptcls::type, zend_zval_value_name(bound.raw()));
			return zv::Val();
		}
		return bound;
	}

	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_BOOL(&args[1], strictTypes);
		bool delegate;
		if (UNEXPECTED(!delegatesToCompound(acceptingType, bound.raw(), delegate))) return zv::Val();
		if (delegate) {
			/* $acceptingType->accepts($this, $strictTypes) */
			ZVAL_OBJ(&args[0], self);
			return pt_type_op(Z_OBJ_P(acceptingType), PT_OP_ACCEPTS, 2, args);
		}
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(acceptingType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (!isTemplate) {
			/* $acceptingType->accepts($this->getBound(), $strictTypes) */
			zv::Val thisBound = thisGetBound();
			if (UNEXPECTED(thisBound.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], thisBound.raw());
			return pt_type_op(Z_OBJ_P(acceptingType), PT_OP_ACCEPTS, 2, args);
		}
		bool same;
		if (UNEXPECTED(!sameTemplate(acceptingType, same))) return zv::Val();
		/* $acceptingType->getBound()->accepts($this->getBound(), $strictTypes) */
		zv::Val acceptingBound = boundOf(acceptingType);
		if (UNEXPECTED(acceptingBound.isUndef())) return zv::Val();
		zv::Val thisBound = thisGetBound();
		if (UNEXPECTED(thisBound.isUndef())) return zv::Val();
		ZVAL_COPY_VALUE(&args[0], thisBound.raw());
		zv::Val accepts = pt_type_op(Z_OBJ_P(acceptingBound.raw()), PT_OP_ACCEPTS, 2, args);
		if (same || UNEXPECTED(accepts.isUndef())) return accepts;
		/* ->and(new AcceptsResult(TrinaryLogic::createMaybe(), [])) */
		zv::Val maybe = pt_type_new_accepts_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_result_and(std::move(accepts), maybe.raw());
	}

	/* $this->strategy->accepts($this, $type, $strictTypes) — the native
	 * strategies' bodies directly */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zval *strategy = strategySlot();
		if (UNEXPECTED(strategy == NULL)) return zv::Val();
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		zend_class_entry *ce = Z_OBJCE_P(strategy);
		if (EXPECTED(ce == pt_ce_template_type_argument_strategy)) return pt_template_type_argument_strategy_accepts(&thisValue, type, strictTypes);
		if (EXPECTED(ce == pt_ce_template_type_parameter_strategy)) return pt_template_type_parameter_strategy_accepts(&thisValue, type, strictTypes);
		zv::Args args{&thisValue, type, strictTypes};
		return pt_type_op(Z_OBJ_P(strategy), PT_OP_ACCEPTS, 3, args);
	}

	zv::Val isSuperTypeOf(zval *type) const
	{
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate || instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			/* $type->isSubTypeOf($this) */
			zval thisValue;
			ZVAL_OBJ(&thisValue, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &thisValue);
		}
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);
		/* $this->getBound()->isSuperTypeOf($type)->and(IsSuperTypeOfResult::createMaybe()) */
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val result = pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_result_and(std::move(result), maybe.raw());
	}

	zv::Val isSubTypeOf(zval *type) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		bool delegate;
		if (UNEXPECTED(!delegatesToCompound(type, bound.raw(), delegate))) return zv::Val();
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		if (delegate) {
			/* $type->isSuperTypeOf($this) */
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, &thisValue);
		}
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (!isTemplate) {
			/* $type->isSuperTypeOf($this->getBound()) */
			zv::Val thisBound = thisGetBound();
			if (UNEXPECTED(thisBound.isUndef())) return zv::Val();
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, thisBound.raw());
		}
		bool same;
		if (UNEXPECTED(!sameTemplate(type, same))) return zv::Val();
		/* $type->getBound()->isSuperTypeOf($this->getBound()) */
		zv::Val otherBound = boundOf(type);
		if (UNEXPECTED(otherBound.isUndef())) return zv::Val();
		zv::Val thisBound = thisGetBound();
		if (UNEXPECTED(thisBound.isUndef())) return zv::Val();
		zv::Val result = pt_type_op(Z_OBJ_P(otherBound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, thisBound.raw());
		if (same || UNEXPECTED(result.isUndef())) return result;
		/* ->and(IsSuperTypeOfResult::createMaybe()) */
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_result_and(std::move(result), maybe.raw());
	}

	/* $this (toArrayKey(), toCoercedArgumentType()) */
	zv::Val thisObject() const
	{
		zval thisValue;
		ZVAL_OBJ_COPY(&thisValue, self);
		return zv::Val::adopt(thisValue);
	}

	/* new NullType() for a null template, the literal class name for a
	 * bound that is one known final class, class-string<T>&literal-string
	 * otherwise */
	zv::Val toClassConstantType(zval *reflectionProvider) const
	{
		zend_long isNull = pt_type_op_trinary(self, PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		if (isNull == PT_TRI_YES) {
			return pt_val_of<pt_null_type_new>();
		}
		zv::Val classNames = pt_type_op(self, PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(classNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return array", ZSTR_VAL(self->ce->name));
			return zv::Val();
		}
		if (zv::ArrRef(classNames.raw()).size() == 1) {
			zv::Ref className = zv::ArrRef(classNames.raw()).findIndex(0);
			if (className.raw() != NULL && className.isString()) {
				zv::Val hasClass = pt_reflection_provider_has_class_zv(Z_OBJ_P(reflectionProvider), className.raw());
				if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
				if (zend_is_true(hasClass.raw())) {
					zv::Val reflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className.raw());
					if (UNEXPECTED(reflection.isUndef())) return zv::Val();
					if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
						zend_type_error("phpstan_turbo: ReflectionProvider::getClass() must return an object");
						return zv::Val();
					}
					zv::Val isFinal = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
					if (UNEXPECTED(isFinal.isUndef())) return zv::Val();
					if (zend_is_true(isFinal.raw())) {
						zv::Val reflectionName = pt_class_reflection_get_name(Z_OBJ_P(reflection.raw()));
						if (UNEXPECTED(reflectionName.isUndef())) return zv::Val();
						if (UNEXPECTED(!zv::Ref(reflectionName.raw()).isString())) {
							zend_type_error("phpstan_turbo: ClassReflection::getName() must return string");
							return zv::Val();
						}
						zval constantString;
						if (UNEXPECTED(!pt_constant_string_type_new(&constantString, Z_STR_P(reflectionName.raw()), true))) return zv::Val();
						return zv::Val::adopt(constantString);
					}
				}
			}
		}
		/* new IntersectionType([new GenericClassStringType($this), new AccessoryLiteralStringType()]) */
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		zv::Val classString = pt_type_new_generic_class_string(&thisValue);
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		zval literal;
		if (UNEXPECTED(!pt_accessory_literal_string_type_new(&literal))) return zv::Val();
		zv::Arr members = zv::Arr::create(2);
		members.push(std::move(classString));
		members.push(zv::Val::adopt(literal));
		return pt_intersection_of(std::move(members));
	}

	/* new TemplateTypeMap([$this->name => $type]) */
	zv::Val mapOf(zval *type) const
	{
		zval *name = nameSlot();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Arr types = zv::Arr::create(1);
		types.set(Z_STR_P(name), zv::Val::copyOf(zv::Ref(type)));
		zv::Val map; /* stays UNDEF when the constructor fails */
		pt_template_type_map_new(map.raw(), types.raw());
		return map;
	}

	zv::Val inferTemplateTypes(zval *receivedTypeIn) const
	{
		/* $receivedType = TemplateTypeHelper::removeFinalByKeywordOverrides($receivedType) */
		zv::Val received = pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("removefinalbykeywordoverrides"), 1, receivedTypeIn);
		if (UNEXPECTED(received.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(received.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TemplateTypeHelper::removeFinalByKeywordOverrides() must return %s", ptcls::type);
			return zv::Val();
		}
		zval *receivedType = received.raw();
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) {
			/* $this->getBound()->isSuperTypeOf($receivedType->getBound())->yes() */
			zv::Val receivedBound = boundOf(receivedType);
			if (UNEXPECTED(receivedBound.isUndef())) return zv::Val();
			zv::Val superOfBound = pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, receivedBound.raw());
			if (UNEXPECTED(superOfBound.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(superOfBound.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return mapOf(receivedType);
		}

		/* $map = $this->getBound()->inferTemplateTypes($receivedType) */
		zv::Val map = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("infertemplatetypes"), 1, receivedType);
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		/* TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->getBound(), $map, TemplateTypeVarianceMap::createEmpty(), TemplateTypeVariance::createStatic())) */
		zv::Val varianceMap = pt_callable_template_type_variance_map_empty();
		if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
		zval *staticVariance = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_STATIC);
		if (UNEXPECTED(staticVariance == NULL)) return zv::Val();
		zv::Val resolvedTemplates = pt_type_template_type_helper_resolve_template_types(bound.raw(), map.raw(), varianceMap.raw(), staticVariance, false);
		if (UNEXPECTED(resolvedTemplates.isUndef())) return zv::Val();
		zv::Val resolvedBound = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("resolvelateresolvabletypes"), 1, resolvedTemplates.raw());
		if (UNEXPECTED(resolvedBound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(resolvedBound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TypeUtils::resolveLateResolvableTypes() must return %s", ptcls::type);
			return zv::Val();
		}
		zv::Val boundMatches = pt_type_op(Z_OBJ_P(resolvedBound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, receivedType);
		if (UNEXPECTED(boundMatches.isUndef())) return zv::Val();
		zend_long matches = pt_type_result_trinary(boundMatches.raw());
		if (UNEXPECTED(matches < 0)) return zv::Val();
		if (matches == PT_TRI_YES) return unionWithMap(mapOf(receivedType), map.raw());

		if (matches == PT_TRI_MAYBE && instanceof_function(Z_OBJCE_P(receivedType), pt_ce_union_type)) {
			zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(receivedType));
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(innerTypes.raw()).isArray())) {
				zend_type_error("phpstan_turbo: %s::getTypes() must return array", ZSTR_VAL(Z_OBJCE_P(receivedType)->name));
				return zv::Val();
			}
			zv::Arr matchingTypes = zv::Arr::create(zv::ArrRef(innerTypes.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value().deref();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: %s::getTypes() must return a list of types", ZSTR_VAL(Z_OBJCE_P(receivedType)->name));
					return zv::Val();
				}
				zv::Val innerMatches = pt_type_op(Z_OBJ_P(resolvedBound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, innerType.raw());
				if (UNEXPECTED(innerMatches.isUndef())) return zv::Val();
				zend_long innerValue = pt_type_result_trinary(innerMatches.raw());
				if (UNEXPECTED(innerValue < 0)) return zv::Val();
				if (innerValue != PT_TRI_YES) continue;
				matchingTypes.push(innerType);
			}
			if (matchingTypes.arrRef().size() > 0) {
				/* TypeCombinator::union(...$matchingTypes) */
				zv::Val filteredType = pt_type_combinator_call_spread(PT_LC("union"), matchingTypes.table());
				if (UNEXPECTED(filteredType.isUndef())) return zv::Val();
				return unionWithMap(mapOf(filteredType.raw()), map.raw());
			}
		}

		return map;
	}

	/* $ownMap->union($map); UNDEF = pending exception (also for an UNDEF $ownMap) */
	static zv::Val unionWithMap(zv::Val ownMap, zval *map)
	{
		if (UNEXPECTED(ownMap.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(ownMap.raw()), PT_LC("union"), 1, map);
	}

	/* [new TemplateTypeReference($this, $positionVariance)] */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val reference;
		if (UNEXPECTED(!pt_template_type_reference_new(reference.raw(), &selfZv, positionVariance))) return zv::Val();
		zv::Arr references = zv::Arr::create(1);
		references.push(std::move(reference));
		return zv::Val(std::move(references));
	}

	/* $cb(...$args); UNDEF = pending exception */
	static zv::Val callback(zend_fcall_info *fci, zend_fcall_info_cache *fcc, uint32_t argc, zval *argv)
	{
		zval result;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, argc, argv, &result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* whether two values are the same object (`===` on the twin's Type
	 * operands) */
	static bool sameObject(zval *a, zval *b)
	{
		return Z_TYPE_P(a) == IS_OBJECT && Z_TYPE_P(b) == IS_OBJECT && Z_OBJ_P(a) == Z_OBJ_P(b);
	}

	/* $this when $cb left the bound and the default alone, the rebuild
	 * over the mapped ones otherwise */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val mappedBound = callback(fci, fcc, 1, bound.raw());
		if (UNEXPECTED(mappedBound.isUndef())) return zv::Val();
		zv::Val defaultType = thisGetDefault();
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		zv::Val mappedDefault = zv::Val::null();
		if (!zv::Ref(defaultType.raw()).isNull()) {
			mappedDefault = callback(fci, fcc, 1, defaultType.raw());
			if (UNEXPECTED(mappedDefault.isUndef())) return zv::Val();
		}
		return traversed(std::move(mappedBound), std::move(mappedDefault));
	}

	/* $this when $right is no template type or $cb left the bound and the
	 * default alone, the rebuild over the mapped ones otherwise */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(right, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (!isTemplate) return thisObject();
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val rightBound = pt_type_call(Z_OBJ_P(right), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(rightBound.isUndef())) return zv::Val();
		zv::Args args{bound.raw(), rightBound.raw()};
		zv::Val mappedBound = callback(fci, fcc, 2, args);
		if (UNEXPECTED(mappedBound.isUndef())) return zv::Val();
		zv::Val defaultType = thisGetDefault();
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		zv::Val mappedDefault = zv::Val::null();
		if (!zv::Ref(defaultType.raw()).isNull()) {
			zv::Val rightDefault = pt_type_call(Z_OBJ_P(right), PT_LC("getdefault"), 0, NULL);
			if (UNEXPECTED(rightDefault.isUndef())) return zv::Val();
			if (!zv::Ref(rightDefault.raw()).isNull()) {
				ZVAL_COPY_VALUE(&args[0], defaultType.raw());
				ZVAL_COPY_VALUE(&args[1], rightDefault.raw());
				mappedDefault = callback(fci, fcc, 2, args);
				if (UNEXPECTED(mappedDefault.isUndef())) return zv::Val();
			}
		}
		return traversed(std::move(mappedBound), std::move(mappedDefault));
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->getBound()
	 * === $bound && $this->getDefault() === $default ? $this : <rebuild>` */
	zv::Val traversed(zv::Val mappedBound, zv::Val mappedDefault) const
	{
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (sameObject(bound.raw(), mappedBound.raw())) {
			zv::Val defaultType = thisGetDefault();
			if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
			bool sameDefault = zv::Ref(defaultType.raw()).isNull() ? zv::Ref(mappedDefault.raw()).isNull() : sameObject(defaultType.raw(), mappedDefault.raw());
			if (sameDefault) return thisObject();
		}
		return recreate(mappedBound.raw(), mappedDefault.raw());
	}

	/* null for a template type to remove, null when removing changes the
	 * bound by value neither, the rebuild over the reduced bound otherwise */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(typeToRemove, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) return zv::Val::null();
		zv::Val bound = thisGetBound();
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val removed = pt_type_combinator_remove(bound.raw(), typeToRemove);
		if (UNEXPECTED(removed.isUndef())) return zv::Val();
		/* $this->getBound() === $bound || $this->getBound()->equals($bound) */
		zv::Val boundAgain = thisGetBound();
		if (UNEXPECTED(boundAgain.isUndef())) return zv::Val();
		if (sameObject(boundAgain.raw(), removed.raw())) return zv::Val::null();
		zv::Val boundOnceMore = thisGetBound();
		if (UNEXPECTED(boundOnceMore.isUndef())) return zv::Val();
		zv::Val equal = pt_type_op(Z_OBJ_P(boundOnceMore.raw()), PT_OP_EQUALS, 1, removed.raw());
		if (UNEXPECTED(equal.isUndef())) return zv::Val();
		if (zend_is_true(equal.raw())) return zv::Val::null();
		return recreateKeepingDefault(removed.raw());
	}

	/* new IdentifierTypeNode($this->name) */
	zv::Val toPhpDocNode() const
	{
		zval *name = nameSlot();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return pt_type_new_identifier_type_node(Z_STRVAL_P(name), Z_STRLEN_P(name));
	}

	/* }}} */

private:
	zend_object *self;
	zend_class_entry *scope;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeTrait;

void pt_template_type_init(zend_object *self, zend_class_entry *scope, zval *templateScope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	TemplateTypeTrait(self, scope).init(templateScope, strategy, variance, name, bound, defaultType);
}

bool pt_template_type_check_bound(zend_class_entry *scope, zval *bound, uint32_t argNo)
{
	if (UNEXPECTED(scope->parent == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s has no parent to type its bound by", ZSTR_VAL(scope->name));
		return false;
	}
	if (UNEXPECTED(Z_TYPE_P(bound) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(bound), scope->parent))) {
		zend_argument_type_error(argNo, "must be of type %s, %s given", ZSTR_VAL(scope->parent->name), zend_zval_value_name(bound));
		return false;
	}
	return true;
}

bool pt_template_type_parent_construct(zend_object *self, zend_class_entry *scope, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call_parent(scope, self, PT_LC("__construct"), argc, argv);
	return !result.isUndef();
}

zend_string *pt_template_type_name(zend_object *object, zend_class_entry *scope)
{
	zval *slot = TemplateTypeTrait(object, scope).nameSlot();
	return slot == NULL ? NULL : Z_STR_P(slot);
}

zval *pt_template_type_scope(zend_object *object, zend_class_entry *scope)
{
	return TemplateTypeTrait(object, scope).scopeSlot();
}

zval *pt_template_type_strategy(zend_object *object, zend_class_entry *scope)
{
	return TemplateTypeTrait(object, scope).strategySlot();
}

zval *pt_template_type_variance(zend_object *object, zend_class_entry *scope)
{
	return TemplateTypeTrait(object, scope).varianceSlot();
}

zval *pt_template_type_bound(zend_object *object, zend_class_entry *scope)
{
	return TemplateTypeTrait(object, scope).boundSlot();
}

zval *pt_template_type_default(zend_object *object, zend_class_entry *scope)
{
	return TemplateTypeTrait(object, scope).defaultSlot();
}

zv::Val pt_template_type_is_super_type_of(zend_object *self, zend_class_entry *scope, zval *type)
{
	return TemplateTypeTrait(self, scope).isSuperTypeOf(type);
}

zv::Val pt_template_type_is_sub_type_of(zend_object *self, zend_class_entry *scope, zval *type)
{
	return TemplateTypeTrait(self, scope).isSubTypeOf(type);
}

#define PT_TT_THIS TemplateTypeTrait(PT_THIS_OBJ, PT_SCOPE)

/* $this (toArrayKey(), toCoercedArgumentType()) */
static void ZEND_FASTCALL templateTypeThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_THIS();
}

void pt_type_trait_template_type(reg::Class &cls)
{
	namespace sigs = ptdecl::TemplateTypeTrait::sig;
	/* the trait's properties, in its order — the class's last slots */
	cls.privateTypedProperty("name", MAY_BE_STRING);
	cls.privateTypedClassProperty("scope", ptcls::templateTypeScope, false);
	cls.privateTypedClassProperty("strategy", ptcls::templateTypeStrategy, false);
	cls.privateTypedClassProperty("variance", ptcls::templateTypeVariance, false);
	cls.privateTypedClassProperty("bound", ptcls::type, false);
	cls.privateTypedClassProperty("default", ptcls::type, true);

	cls.traitMethod(sigs::getName, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *name = PT_TT_THIS.nameSlot();
		if (UNEXPECTED(name == NULL)) RETURN_THROWS();
		RETURN_COPY(name);
	});

	cls.traitMethod(sigs::getScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *templateScope = PT_TT_THIS.scopeSlot();
		if (UNEXPECTED(templateScope == NULL)) RETURN_THROWS();
		RETURN_COPY(templateScope);
	});

	cls.traitMethod(sigs::getBound, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *bound = PT_TT_THIS.boundSlot();
		if (UNEXPECTED(bound == NULL)) RETURN_THROWS();
		RETURN_COPY(bound);
	});

	cls.traitMethod(sigs::getDefault, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *defaultType = PT_TT_THIS.defaultSlot();
		if (UNEXPECTED(defaultType == NULL)) RETURN_THROWS();
		RETURN_COPY(defaultType);
	});

	cls.traitMethod(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.describe(level));
	});
	cls.traitOp(PT_OP_DESCRIBE, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).describe(argv); });

	cls.traitMethod(sigs::isArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		bool argument;
		if (UNEXPECTED(!PT_TT_THIS.isArgument(argument))) RETURN_THROWS();
		RETURN_BOOL(argument);
	});

	cls.traitMethod(sigs::toArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TT_THIS.toArgument());
	});

	cls.traitMethod(sigs::isValidVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *a, *b;
		bool strict = false;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Opt<zp::Bool>>(execute_data, a, b, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.isValidVariance(a, b, strict));
	});

	cls.traitMethod(sigs::subtract, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *typeToRemove;
		if (!zp::parse<zp::Obj>(execute_data, typeToRemove)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.subtract(typeToRemove));
	});

	cls.traitMethod(sigs::getTypeWithoutSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TT_THIS.getTypeWithoutSubtractedType());
	});
	cls.traitOp(PT_OP_GET_TYPE_WITHOUT_SUBTRACTED_TYPE, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).getTypeWithoutSubtractedType(); });

	cls.traitMethod(sigs::changeSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType;
		if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
		zval nullValue;
		ZVAL_NULL(&nullValue);
		PT_RETURN_VAL(PT_TT_THIS.changeSubtractedType(subtractedType != NULL ? subtractedType : &nullValue));
	});

	cls.traitMethod(sigs::getSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TT_THIS.getSubtractedType());
	});
	cls.traitOp(PT_OP_GET_SUBTRACTED_TYPE, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).getSubtractedType(); });

	cls.traitMethod(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		bool equal;
		if (UNEXPECTED(!PT_TT_THIS.equals(type, equal))) RETURN_THROWS();
		RETURN_BOOL(equal);
	});
	cls.traitOp(PT_OP_EQUALS, PT_OP_LAMBDA { bool equal; bool ok = TemplateTypeTrait(self, scope).equals(argv, equal); return pt_op_bool(ok, equal); });

	cls.traitMethod(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.isAcceptedBy(acceptingType, strictTypes));
	});

	cls.traitMethod(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.accepts(type, strictTypes));
	});
	cls.traitOp(PT_OP_ACCEPTS, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.traitMethod(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.isSuperTypeOf(type));
	});
	cls.traitOp(PT_OP_IS_SUPER_TYPE_OF, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).isSuperTypeOf(argv); });

	cls.traitMethod(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.isSubTypeOf(type));
	});
	cls.traitOp(PT_OP_IS_SUB_TYPE_OF, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).isSubTypeOf(argv); });

	cls.traitMethod(sigs::toArrayKey, templateTypeThis0);
	cls.traitOp(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_op_this(self); });

	cls.traitMethod(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_THIS();
	});

	cls.traitMethod(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		if (!zp::parse<zp::Obj>(execute_data, reflectionProvider)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.toClassConstantType(reflectionProvider));
	});

	cls.traitMethod(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *receivedType;
		if (!zp::parse<zp::Obj>(execute_data, receivedType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.inferTemplateTypes(receivedType));
	});

	cls.traitMethod(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *positionVariance;
		if (!zp::parse<zp::Obj>(execute_data, positionVariance)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.getReferencedTemplateTypes(positionVariance));
	});
	cls.traitOp(PT_OP_GET_REFERENCED_TEMPLATE_TYPES, PT_OP_LAMBDA { return TemplateTypeTrait(self, scope).getReferencedTemplateTypes(argv); });

	cls.traitMethod(sigs::getVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *variance = PT_TT_THIS.varianceSlot();
		if (UNEXPECTED(variance == NULL)) RETURN_THROWS();
		RETURN_COPY(variance);
	});

	cls.traitMethod(sigs::getStrategy, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *strategy = PT_TT_THIS.strategySlot();
		if (UNEXPECTED(strategy == NULL)) RETURN_THROWS();
		RETURN_COPY(strategy);
	});

	cls.traitMethod(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TT_THIS.traverse(&fci, &fcc));
	});
	cls.traitOp(PT_OP_TRAVERSE, PT_OP_LAMBDA { zend_fcall_info fci; zend_fcall_info_cache fcc; if (UNEXPECTED(!pt_op_parse_callable(argv, fci, fcc))) { return pt_type_call_engine(self, "traverse", sizeof("traverse") - 1, 1, argv); } return TemplateTypeTrait(self, scope).traverse(&fci, &fcc); });

	cls.traitMethod(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.traitMethod(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *typeToRemove;
		if (!zp::parse<zp::Obj>(execute_data, typeToRemove)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TT_THIS.tryRemove(typeToRemove));
	});

	cls.traitMethod(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TT_THIS.toPhpDocNode());
	});

	cls.traitMethod(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_TRUE;
	});
	cls.traitOp(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(true); });
}

/* }}} */

/* {{{ helpers of the unresolved prototype reflections
 * (CalledOnTypeUnresolved{Method,Property}PrototypeReflection.cpp,
 * CallbackUnresolved{Method,Property}PrototypeReflection.cpp) */

using phpstanturbo::PrototypeKind;
using phpstanturbo::PrototypeTransformer;

namespace {

/* what a reflection getter's declared return type allows */
enum ProtoReturn : uint8_t
{
	PROTO_ANY,
	PROTO_OBJECT,
	PROTO_OBJECT_OR_NULL,
	PROTO_ARRAY,
	PROTO_ARRAY_OR_NULL,
};

/* $object->method() with the twin's declared return type held (a PHP
 * implementation's is checked by the engine on return); UNDEF = pending
 * exception */
zv::Val protoCall(zend_object *object, const char *lcname, size_t len, ProtoReturn expected)
{
	zv::Val result = pt_type_call(object, lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return result;
	zend_uchar type = Z_TYPE_P(result.raw());
	bool ok;
	const char *expectedName;
	switch (expected) {
		case PROTO_OBJECT:
			ok = type == IS_OBJECT;
			expectedName = "an object";
			break;
		case PROTO_OBJECT_OR_NULL:
			ok = type == IS_OBJECT || type == IS_NULL;
			expectedName = "an object or null";
			break;
		case PROTO_ARRAY:
			ok = type == IS_ARRAY;
			expectedName = "an array";
			break;
		case PROTO_ARRAY_OR_NULL:
			ok = type == IS_ARRAY || type == IS_NULL;
			expectedName = "an array or null";
			break;
		default:
			ok = true;
			expectedName = "";
			break;
	}
	if (UNEXPECTED(!ok)) {
		zend_type_error("phpstan_turbo: %s::%s() must return %s, %s returned", ZSTR_VAL(object->ce->name), lcname, expectedName, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* $a->equals($b); -1 = pending exception */
int protoEquals(zval *a, zval *b)
{
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_EQUALS, 1, b);
	if (UNEXPECTED(result.isUndef())) return -1;
	return zend_is_true(result.raw()) ? 1 : 0;
}

/* $type !== null ? $this->transformStaticType($type) : null */
zv::Val protoTransformNullable(const PrototypeTransformer &transformer, zval *type)
{
	if (Z_TYPE_P(type) == IS_NULL) return zv::Val::null();
	return transformer.transform(type);
}

/* the Callback twins' `$original->equals($type) ? $transformedOriginal :
 * $this->transformStaticType($type)`; the CalledOnType twins transform
 * every type */
zv::Val protoTransformUnlessEqual(PrototypeKind kind, const PrototypeTransformer &transformer, zval *original, zval *transformedOriginal, zval *type)
{
	if (kind == phpstanturbo::PT_PROTOTYPE_CALLBACK) {
		int equal = protoEquals(original, type);
		if (UNEXPECTED(equal < 0)) return zv::Val();
		if (equal == 1) return zv::Val::copyOf(zv::Ref(transformedOriginal));
	}
	return transformer.transform(type);
}

/* array_map($fn, $array) over an array of objects, the keys kept as
 * array_map() keeps them for a single array; UNDEF = pending exception */
template <typename F>
zv::Val protoMap(zval *array, const char *what, F fn)
{
	zv::Arr result = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(array)));
	for (zv::ArrayEntry entry : zv::ArrRef(array)) {
		zv::Ref value = entry.value().deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_type_error("phpstan_turbo: %s must hold objects, %s given", what, zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		zv::Val mapped = fn(value.asObject());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		zval v = mapped.take();
		if (entry.hasStringKey()) {
			zend_hash_update(result.table(), entry.stringKey(), &v);
		} else {
			zend_hash_index_update(result.table(), entry.indexKey(), &v);
		}
	}
	return zv::Val(std::move(result));
}

/* the parameter callback of the twins' array_map(): new
 * ExtendedDummyParameter($parameter->getName(), transform($parameter->getType()),
 * ...) — each getter called once (the twins call getOutType() and
 * getClosureThisType() twice; pure getters); UNDEF = pending exception */
zv::Val protoParameter(PrototypeKind kind, const PrototypeTransformer &transformer, zend_object *parameter)
{
	zv::Val name = protoCall(parameter, PT_LC("getname"), PROTO_ANY);
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	zv::Val originalType = protoCall(parameter, PT_LC("gettype"), PROTO_OBJECT);
	if (UNEXPECTED(originalType.isUndef())) return zv::Val();
	zv::Val transformedType = transformer.transform(originalType.raw());
	if (UNEXPECTED(transformedType.isUndef())) return zv::Val();
	zv::Val optional = protoCall(parameter, PT_LC("isoptional"), PROTO_ANY);
	if (UNEXPECTED(optional.isUndef())) return zv::Val();
	zv::Val passedByReference = protoCall(parameter, PT_LC("passedbyreference"), PROTO_ANY);
	if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
	zv::Val variadic = protoCall(parameter, PT_LC("isvariadic"), PROTO_ANY);
	if (UNEXPECTED(variadic.isUndef())) return zv::Val();
	zv::Val defaultValue = protoCall(parameter, PT_LC("getdefaultvalue"), PROTO_ANY);
	if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
	zv::Val nativeType = protoCall(parameter, PT_LC("getnativetype"), PROTO_ANY);
	if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
	zv::Val phpDocType = protoCall(parameter, PT_LC("getphpdoctype"), PROTO_OBJECT);
	if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
	zv::Val transformedPhpDocType = protoTransformUnlessEqual(kind, transformer, originalType.raw(), transformedType.raw(), phpDocType.raw());
	if (UNEXPECTED(transformedPhpDocType.isUndef())) return zv::Val();
	zv::Val outType = protoCall(parameter, PT_LC("getouttype"), PROTO_OBJECT_OR_NULL);
	if (UNEXPECTED(outType.isUndef())) return zv::Val();
	zv::Val transformedOutType = protoTransformNullable(transformer, outType.raw());
	if (UNEXPECTED(transformedOutType.isUndef())) return zv::Val();
	zv::Val immediatelyInvokedCallable = protoCall(parameter, PT_LC("isimmediatelyinvokedcallable"), PROTO_ANY);
	if (UNEXPECTED(immediatelyInvokedCallable.isUndef())) return zv::Val();
	zv::Val closureThisType = protoCall(parameter, PT_LC("getclosurethistype"), PROTO_OBJECT_OR_NULL);
	if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
	zv::Val transformedClosureThisType = protoTransformNullable(transformer, closureThisType.raw());
	if (UNEXPECTED(transformedClosureThisType.isUndef())) return zv::Val();
	zv::Val attributes = protoCall(parameter, PT_LC("getattributes"), PROTO_ANY);
	if (UNEXPECTED(attributes.isUndef())) return zv::Val();
	zv::Val allowedConstants = protoCall(parameter, PT_LC("getallowedconstants"), PROTO_ANY);
	if (UNEXPECTED(allowedConstants.isUndef())) return zv::Val();
	zv::Val pureUnlessCallableIsImpure = protoCall(parameter, PT_LC("ispureunlesscallableisimpureparameter"), PROTO_ANY);
	if (UNEXPECTED(pureUnlessCallableIsImpure.isUndef())) return zv::Val();
	zval args[14];
	ZVAL_COPY_VALUE(&args[0], name.raw());
	ZVAL_COPY_VALUE(&args[1], transformedType.raw());
	ZVAL_COPY_VALUE(&args[2], optional.raw());
	ZVAL_COPY_VALUE(&args[3], passedByReference.raw());
	ZVAL_COPY_VALUE(&args[4], variadic.raw());
	ZVAL_COPY_VALUE(&args[5], defaultValue.raw());
	ZVAL_COPY_VALUE(&args[6], nativeType.raw());
	ZVAL_COPY_VALUE(&args[7], transformedPhpDocType.raw());
	ZVAL_COPY_VALUE(&args[8], transformedOutType.raw());
	ZVAL_COPY_VALUE(&args[9], immediatelyInvokedCallable.raw());
	ZVAL_COPY_VALUE(&args[10], transformedClosureThisType.raw());
	ZVAL_COPY_VALUE(&args[11], attributes.raw());
	ZVAL_COPY_VALUE(&args[12], allowedConstants.raw());
	ZVAL_COPY_VALUE(&args[13], pureUnlessCallableIsImpure.raw());
	return pt_type_new(PT_CLASS_EXTENDED_DUMMY_PARAMETER, 14, args);
}

/* the twins' $variantFn: new ExtendedFunctionVariant(...) over $acceptor;
 * $selfOutType is the transformed self-out type — the Callback twin's
 * `use (&$selfOutType)`, narrowed by each $this-returning variant;
 * UNDEF = pending exception */
zv::Val protoVariant(PrototypeKind kind, const PrototypeTransformer &transformer, zend_object *acceptor, zv::Val &selfOutType)
{
	zv::Val originalReturnType = protoCall(acceptor, PT_LC("getreturntype"), PROTO_OBJECT);
	if (UNEXPECTED(originalReturnType.isUndef())) return zv::Val();
	bool returnsThis;
	pt_type_instanceof_ce(originalReturnType.raw(), pt_ce_this_type, returnsThis);
	zv::Val returnType, transformedReturnType;
	if (kind == phpstanturbo::PT_PROTOTYPE_CALLED_ON_TYPE) {
		/* $originalReturnType instanceof ThisType && $selfOutType !== null ? $selfOutType : transform($originalReturnType) */
		if (returnsThis && !selfOutType.isNull()) {
			returnType = zv::Val::copyOf(zv::Ref(selfOutType.raw()));
		} else {
			returnType = transformer.transform(originalReturnType.raw());
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		}
	} else {
		transformedReturnType = transformer.transform(originalReturnType.raw());
		if (UNEXPECTED(transformedReturnType.isUndef())) return zv::Val();
		if (returnsThis && !selfOutType.isNull()) {
			/* $returnType = TypeCombinator::intersect($selfOutType, $transformedReturnType); $selfOutType = $returnType; */
			zv::Args args{selfOutType.raw(), transformedReturnType.raw()};
			returnType = pt_type_combinator_call(PT_LC("intersect"), 2, args);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			selfOutType = zv::Val::copyOf(zv::Ref(returnType.raw()));
		} else {
			returnType = zv::Val::copyOf(zv::Ref(transformedReturnType.raw()));
		}
	}
	zv::Val phpDocReturnType = protoCall(acceptor, PT_LC("getphpdocreturntype"), PROTO_OBJECT);
	if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
	zv::Val nativeReturnType = protoCall(acceptor, PT_LC("getnativereturntype"), PROTO_OBJECT);
	if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
	zv::Val templateTypeMap = protoCall(acceptor, PT_LC("gettemplatetypemap"), PROTO_ANY);
	if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
	zv::Val resolvedTemplateTypeMap = protoCall(acceptor, PT_LC("getresolvedtemplatetypemap"), PROTO_ANY);
	if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
	zv::Val parameters = protoCall(acceptor, PT_LC("getparameters"), PROTO_ARRAY);
	if (UNEXPECTED(parameters.isUndef())) return zv::Val();
	zv::Val mappedParameters = protoMap(parameters.raw(), "getParameters()", [&](zend_object *parameter) { return protoParameter(kind, transformer, parameter); });
	if (UNEXPECTED(mappedParameters.isUndef())) return zv::Val();
	zv::Val variadic = protoCall(acceptor, PT_LC("isvariadic"), PROTO_ANY);
	if (UNEXPECTED(variadic.isUndef())) return zv::Val();
	/* the Callback twin reuses the transformed return type for an equal
	 * PHPDoc / native return type; the CalledOnType twin transforms both */
	zval *transformedOriginal = kind == phpstanturbo::PT_PROTOTYPE_CALLBACK ? transformedReturnType.raw() : NULL;
	zv::Val transformedPhpDocReturnType = protoTransformUnlessEqual(kind, transformer, originalReturnType.raw(), transformedOriginal, phpDocReturnType.raw());
	if (UNEXPECTED(transformedPhpDocReturnType.isUndef())) return zv::Val();
	zv::Val transformedNativeReturnType = protoTransformUnlessEqual(kind, transformer, originalReturnType.raw(), transformedOriginal, nativeReturnType.raw());
	if (UNEXPECTED(transformedNativeReturnType.isUndef())) return zv::Val();
	zv::Val callSiteVarianceMap = protoCall(acceptor, PT_LC("getcallsitevariancemap"), PROTO_ANY);
	if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
	zval args[8];
	ZVAL_COPY_VALUE(&args[0], templateTypeMap.raw());
	ZVAL_COPY_VALUE(&args[1], resolvedTemplateTypeMap.raw());
	ZVAL_COPY_VALUE(&args[2], mappedParameters.raw());
	ZVAL_COPY_VALUE(&args[3], variadic.raw());
	ZVAL_COPY_VALUE(&args[4], returnType.raw());
	ZVAL_COPY_VALUE(&args[5], transformedPhpDocReturnType.raw());
	ZVAL_COPY_VALUE(&args[6], transformedNativeReturnType.raw());
	ZVAL_COPY_VALUE(&args[7], callSiteVarianceMap.raw());
	return pt_type_new(PT_CLASS_EXTENDED_FUNCTION_VARIANT, 8, args);
}

/* transformMethodWithStaticType($declaringClass, $method): new
 * ChangedTypeMethodReflection(...) over the transformed variants; UNDEF =
 * pending exception */
zv::Val protoTransformMethod(PrototypeKind kind, const PrototypeTransformer &transformer, zval *declaringClass, zend_object *method, zval *assertsCallback)
{
	/* $selfOutType = $method->getSelfOutType() !== null ? transform(...) : null */
	zv::Val selfOut = protoCall(method, PT_LC("getselfouttype"), PROTO_OBJECT_OR_NULL);
	if (UNEXPECTED(selfOut.isUndef())) return zv::Val();
	zv::Val selfOutType = protoTransformNullable(transformer, selfOut.raw());
	if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
	zv::Val variants = protoCall(method, PT_LC("getvariants"), PROTO_ARRAY);
	if (UNEXPECTED(variants.isUndef())) return zv::Val();
	zv::Val mappedVariants = protoMap(variants.raw(), "getVariants()", [&](zend_object *acceptor) { return protoVariant(kind, transformer, acceptor, selfOutType); });
	if (UNEXPECTED(mappedVariants.isUndef())) return zv::Val();
	zv::Val namedArgumentsVariants = protoCall(method, PT_LC("getnamedargumentsvariants"), PROTO_ARRAY_OR_NULL);
	if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
	zv::Val mappedNamedArgumentsVariants = zv::Val::null();
	if (!namedArgumentsVariants.isNull()) {
		mappedNamedArgumentsVariants = protoMap(namedArgumentsVariants.raw(), "getNamedArgumentsVariants()", [&](zend_object *acceptor) { return protoVariant(kind, transformer, acceptor, selfOutType); });
		if (UNEXPECTED(mappedNamedArgumentsVariants.isUndef())) return zv::Val();
	}
	zv::Val throwType = protoCall(method, PT_LC("getthrowtype"), PROTO_OBJECT_OR_NULL);
	if (UNEXPECTED(throwType.isUndef())) return zv::Val();
	zv::Val transformedThrowType = protoTransformNullable(transformer, throwType.raw());
	if (UNEXPECTED(transformedThrowType.isUndef())) return zv::Val();
	/* $method->getAsserts()->mapTypes($callback) */
	zv::Val asserts = protoCall(method, PT_LC("getasserts"), PROTO_OBJECT);
	if (UNEXPECTED(asserts.isUndef())) return zv::Val();
	zv::Val mappedAsserts = pt_type_call(Z_OBJ_P(asserts.raw()), PT_LC("maptypes"), 1, assertsCallback);
	if (UNEXPECTED(mappedAsserts.isUndef())) return zv::Val();
	zval methodZv;
	ZVAL_OBJ(&methodZv, method);
	return pt_changed_type_method_reflection_new(declaringClass, &methodZv, mappedVariants.raw(), mappedNamedArgumentsVariants.raw(), selfOutType.raw(), transformedThrowType.raw(), mappedAsserts.raw());
}

/* transformPropertyWithStaticType($declaringClass, $property): new
 * ChangedTypePropertyReflection(...) over the four transformed types;
 * UNDEF = pending exception */
zv::Val protoTransformProperty(PrototypeKind kind, const PrototypeTransformer &transformer, zval *declaringClass, zend_object *property)
{
	zv::Val readableType = protoCall(property, PT_LC("getreadabletype"), PROTO_OBJECT);
	if (UNEXPECTED(readableType.isUndef())) return zv::Val();
	zv::Val transformedReadableType = transformer.transform(readableType.raw());
	if (UNEXPECTED(transformedReadableType.isUndef())) return zv::Val();
	zv::Val writableType = protoCall(property, PT_LC("getwritabletype"), PROTO_OBJECT);
	if (UNEXPECTED(writableType.isUndef())) return zv::Val();
	/* the Callback twin: $readableType->equals($writableType) ? $transformedReadableType : transform($writableType) */
	zv::Val transformedWritableType = protoTransformUnlessEqual(kind, transformer, readableType.raw(), transformedReadableType.raw(), writableType.raw());
	if (UNEXPECTED(transformedWritableType.isUndef())) return zv::Val();
	zv::Val phpDocType = protoCall(property, PT_LC("getphpdoctype"), PROTO_OBJECT);
	if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
	zv::Val transformedPhpDocType = transformer.transform(phpDocType.raw());
	if (UNEXPECTED(transformedPhpDocType.isUndef())) return zv::Val();
	zv::Val nativeType = protoCall(property, PT_LC("getnativetype"), PROTO_OBJECT);
	if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
	/* the Callback twin: $phpDocType->equals($nativeType) ? $transformedPhpDocType : transform($nativeType) */
	zv::Val transformedNativeType = protoTransformUnlessEqual(kind, transformer, phpDocType.raw(), transformedPhpDocType.raw(), nativeType.raw());
	if (UNEXPECTED(transformedNativeType.isUndef())) return zv::Val();
	zval propertyZv;
	ZVAL_OBJ(&propertyZv, property);
	zval args[6];
	ZVAL_COPY_VALUE(&args[0], declaringClass);
	ZVAL_COPY_VALUE(&args[1], &propertyZv);
	ZVAL_COPY_VALUE(&args[2], transformedReadableType.raw());
	ZVAL_COPY_VALUE(&args[3], transformedWritableType.raw());
	ZVAL_COPY_VALUE(&args[4], transformedPhpDocType.raw());
	ZVAL_COPY_VALUE(&args[5], transformedNativeType.raw());
	return pt_type_new(PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION, 6, args);
}

/* the shared body of getTransformedMethod() / getTransformedProperty():
 * the declaring class's maps first (as the twins read them), then the
 * transformed member, then the resolved wrapper over the two */
zv::Val protoResolved(bool isMethod, PrototypeKind kind, const PrototypeTransformer &transformer, zend_object *resolvedDeclaringClass, zend_object *member, bool resolveTemplateTypeMapToBounds, zval *assertsCallback)
{
	zv::Val templateTypeMap = protoCall(resolvedDeclaringClass, PT_LC("getactivetemplatetypemap"), PROTO_OBJECT);
	if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
	zv::Val callSiteVarianceMap = protoCall(resolvedDeclaringClass, PT_LC("getcallsitevariancemap"), PROTO_ANY);
	if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
	zval declaringClassZv;
	ZVAL_OBJ(&declaringClassZv, resolvedDeclaringClass);
	zv::Val transformed = isMethod
		? protoTransformMethod(kind, transformer, &declaringClassZv, member, assertsCallback)
		: protoTransformProperty(kind, transformer, &declaringClassZv, member);
	if (UNEXPECTED(transformed.isUndef())) return zv::Val();
	/* $this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap */
	zv::Val map;
	if (resolveTemplateTypeMapToBounds) {
		map = pt_type_call(Z_OBJ_P(templateTypeMap.raw()), PT_LC("resolvetobounds"), 0, NULL);
		if (UNEXPECTED(map.isUndef())) return zv::Val();
	} else {
		map = zv::Val::copyOf(zv::Ref(templateTypeMap.raw()));
	}
	if (isMethod) return pt_resolved_method_reflection_new(transformed.raw(), map.raw(), callSiteVarianceMap.raw());
	zv::Args args{transformed.raw(), map.raw(), callSiteVarianceMap.raw()};
	return pt_type_new(PT_CLASS_RESOLVED_PROPERTY_REFLECTION, 3, args);
}

} // namespace

zv::Val pt_prototype_resolved_method(PrototypeKind kind, const PrototypeTransformer &transformer, zend_object *resolvedDeclaringClass, zend_object *method, bool resolveTemplateTypeMapToBounds, zval *assertsCallback)
{
	return protoResolved(true, kind, transformer, resolvedDeclaringClass, method, resolveTemplateTypeMapToBounds, assertsCallback);
}

zv::Val pt_prototype_resolved_property(PrototypeKind kind, const PrototypeTransformer &transformer, zend_object *resolvedDeclaringClass, zend_object *property, bool resolveTemplateTypeMapToBounds)
{
	return protoResolved(false, kind, transformer, resolvedDeclaringClass, property, resolveTemplateTypeMapToBounds, NULL);
}

/* }}} */
