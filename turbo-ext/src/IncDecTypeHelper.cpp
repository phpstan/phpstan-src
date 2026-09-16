/*
 * PHPStanTurbo\IncDecTypeHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\IncDecTypeHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getTypeCallback() is exported as
 * pt_inc_dec_type_helper_get_type_callback() for the inc/dec handlers, which
 * take the native closure holder itself (they only hand it on as a
 * typeCallback); the PHP method returns the \Closure over it its declared
 * return type demands. The typeCallback captures what the twin's closure
 * captures ($this, $varExpr, $varResult, $increment), its $getType operand
 * reader the same ($nativeTypesPromoted, $varExpr, $varResult, $one).
 *
 * The constant-scalar arm steps each value with the engine's own
 * increment_function() / decrement_function() (what `++$v` / `--$v` run for
 * a non-integer) and calls str_increment() / str_decrement() through the
 * function table, catching their ValueError like the twin.
 *
 * ExpressionResult, ConstantTypeHelper, TypeCombinator and the Type kernel
 * are called through their direct entries; InitializerExprTypeResolver's
 * getPlusType() / getMinusType() through cached sites.
 */

#include "support.h"
#include "generated/IncDecTypeHelper.h"

namespace slots = ptdecl::IncDecTypeHelper::slot;
namespace sigs = ptdecl::IncDecTypeHelper::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_inc_dec_type_helper = nullptr;

namespace {

pt_method_site pt_idth_get_plus_type_site;
pt_method_site pt_idth_get_minus_type_site;

/* $varValue = str_increment($varValue) / str_decrement($varValue) through
 * the function table (the internal function, or a polyfill); 1 = stepped
 * (value replaced), 0 = the ValueError the twin catches (cleared), -1 =
 * pending exception */
int stepString(zval *value, bool increment)
{
	const char *name = increment ? "str_increment" : "str_decrement";
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(EG(function_table), name, strlen(name));
	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "Call to undefined function %s()", name);
		return -1;
	}
	zval result;
	ZVAL_UNDEF(&result);
	zend_call_known_function(fn, NULL, NULL, &result, 1, value, NULL);
	if (UNEXPECTED(EG(exception) != NULL)) {
		zval_ptr_dtor(&result);
		if (instanceof_function(EG(exception)->ce, zend_ce_value_error)) {
			zend_clear_exception();
			return 0;
		}
		return -1;
	}
	zval_ptr_dtor(value);
	ZVAL_COPY_VALUE(value, &result);
	return 1;
}

/* is_numeric($value) */
bool isNumeric(zval *value)
{
	switch (Z_TYPE_P(value)) {
		case IS_LONG:
		case IS_DOUBLE:
			return true;
		case IS_STRING:
			return is_numeric_string(Z_STRVAL_P(value), Z_STRLEN_P(value), NULL, NULL, 0) != 0;
		default:
			return false;
	}
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\IncDecTypeHelper; UNDEF =
 * pending exception. */
class IncDecTypeHelper
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\Helper\\IncDecTypeHelper::{closure}";

	explicit IncDecTypeHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *initializerExprTypeResolver) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
	}

	/* Mirrors getTypeCallback(): the native closure holder */
	zv::Val getTypeCallback(zval *varExpr, zval *varResult, bool increment) const
	{
		return pt_native_closure(&typeCallbackBody, self, varExpr, varResult, increment);
	}

private:
	zend_object *self;

	/* function (bool $nativeTypesPromoted) use ($varExpr, $varResult,
	 * $increment): Type — captures: $this, $varExpr, $varResult, $increment */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(captures, nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *varExpr = &captures[1];
		zval *varResult = &captures[2];
		bool increment = Z_TYPE(captures[3]) == IS_TRUE;

		zv::Val varType = ptse::typeOf(varResult, nativeTypesPromoted);
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getConstantScalarValues() on %s", zend_zval_value_name(varType.raw()));
			return zv::Val();
		}
		zend_object *varTypeObject = Z_OBJ_P(varType.raw());
		zv::Val varScalars = pt_type_op(varTypeObject, PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(varScalars.isUndef())) return zv::Val();

		if (Z_TYPE_P(varScalars.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(varScalars.raw())) > 0) {
			zv::Arr newTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(varScalars.raw())));
			for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(varScalars.raw()))) {
				zval varValue;
				ZVAL_COPY_DEREF(&varValue, entry.value().raw());
				bool isString = Z_TYPE(varValue) == IS_STRING;
				if (increment) {
					if (isString && Z_STRLEN(varValue) == 0) {
						zval_ptr_dtor(&varValue);
						ZVAL_STRINGL(&varValue, "1", 1);
					} else if (isString && !isNumeric(&varValue)) {
						int stepped = stepString(&varValue, true);
						if (stepped <= 0) {
							zval_ptr_dtor(&varValue);
							if (stepped < 0) return zv::Val();
							return neverType();
						}
					} else if (Z_TYPE(varValue) != IS_TRUE && Z_TYPE(varValue) != IS_FALSE) {
						if (UNEXPECTED(increment_function(&varValue) == FAILURE)) {
							zval_ptr_dtor(&varValue);
							return zv::Val();
						}
					}
				} else {
					if (isString && Z_STRLEN(varValue) == 0) {
						zval_ptr_dtor(&varValue);
						ZVAL_LONG(&varValue, -1);
					} else if (isString && !isNumeric(&varValue)) {
						int stepped = stepString(&varValue, false);
						if (stepped <= 0) {
							zval_ptr_dtor(&varValue);
							if (stepped < 0) return zv::Val();
							return neverType();
						}
					} else if (isNumeric(&varValue)) {
						if (UNEXPECTED(decrement_function(&varValue) == FAILURE)) {
							zval_ptr_dtor(&varValue);
							return zv::Val();
						}
					}
				}
				if (UNEXPECTED(EG(exception) != NULL)) {
					zval_ptr_dtor(&varValue);
					return zv::Val();
				}

				zv::Val newType = pt_constant_type_helper_get_type_from_value(&varValue);
				zval_ptr_dtor(&varValue);
				if (UNEXPECTED(newType.isUndef())) return zv::Val();
				newTypes.push(std::move(newType));
			}
			HashTable *newTypesTable = newTypes.table();
			return pt_type_combinator_union(zend_hash_num_elements(newTypesTable), newTypesTable->arPacked);
		}

		zend_long isString = pt_type_op_trinary(varTypeObject, PT_OP_IS_STRING, 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString == PT_TRI_YES) {
			zend_long isLiteralString = pt_type_call_trinary(varTypeObject, PT_LC("isliteralstring"), 0, NULL);
			if (UNEXPECTED(isLiteralString < 0)) return zv::Val();
			if (isLiteralString == PT_TRI_YES) {
				zval stringType, literalType;
				if (UNEXPECTED(!pt_string_type_new(&stringType))) return zv::Val();
				zv::Val stringValue = zv::Val::adopt(stringType);
				if (UNEXPECTED(!pt_accessory_literal_string_type_new(&literalType))) return zv::Val();
				zv::Arr types = zv::Arr::create(2);
				types.push(std::move(stringValue));
				types.push(zv::Val::adopt(literalType));
				zval intersection;
				if (UNEXPECTED(!pt_intersection_type_new(&intersection, types.raw()))) return zv::Val();
				return zv::Val::adopt(intersection);
			}

			zend_long isNumericString = pt_type_call_trinary(varTypeObject, PT_LC("isnumericstring"), 0, NULL);
			if (UNEXPECTED(isNumericString < 0)) return zv::Val();
			zv::Arr types = zv::Arr::create(3);
			if (isNumericString != PT_TRI_YES) {
				zval stringType;
				if (UNEXPECTED(!pt_string_type_new(&stringType))) return zv::Val();
				types.push(zv::Val::adopt(stringType));
			}
			zval integerType, floatType;
			if (UNEXPECTED(!pt_integer_type_new(&integerType))) return zv::Val();
			types.push(zv::Val::adopt(integerType));
			if (UNEXPECTED(!pt_float_type_new(&floatType))) return zv::Val();
			types.push(zv::Val::adopt(floatType));
			zval benevolentUnion;
			if (UNEXPECTED(!pt_benevolent_union_type_new(&benevolentUnion, types.raw()))) return zv::Val();
			return zv::Val::adopt(benevolentUnion);
		}

		zval oneZv;
		ZVAL_LONG(&oneZv, 1);
		zv::Val one = pt_type_new(PT_CLASS_SCALAR_INT, 1, &oneZv);
		if (UNEXPECTED(one.isUndef())) return zv::Val();
		zval nativeFlag = {};
		ZVAL_BOOL(&nativeFlag, nativeTypesPromoted);
		zv::Val getType = pt_native_closure(&getTypeCallbackBody, &nativeFlag, varExpr, varResult, one.raw());

		zval *resolver = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver);
		zv::Args callArgs{varExpr, one.raw(), getType.raw()};
		return increment
			? pt_call_method_cached(pt_idth_get_plus_type_site, Z_OBJ_P(resolver), PT_LC("getplustype"), 3, callArgs)
			: pt_call_method_cached(pt_idth_get_minus_type_site, Z_OBJ_P(resolver), PT_LC("getminustype"), 3, callArgs);
	}

	/* new NeverType() */
	static zv::Val neverType()
	{
		zval never;
		if (UNEXPECTED(!pt_never_type_new(&never))) return zv::Val();
		return zv::Val::adopt(never);
	}

	/* static function (Expr $e) use ($nativeTypesPromoted, $varExpr,
	 * $varResult, $one): Type — captures: $nativeTypesPromoted, $varExpr,
	 * $varResult, $one */
	static void getTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval *e = &argv[0];
		if (Z_TYPE_P(e) == IS_OBJECT && Z_TYPE(captures[1]) == IS_OBJECT && Z_OBJ_P(e) == Z_OBJ(captures[1])) {
			zv::Val type;
			pt_engine_with_stack([&]() { type = ptse::typeOf(&captures[2], Z_TYPE(captures[0]) == IS_TRUE); });
			if (UNEXPECTED(type.isUndef())) return;
			type.intoReturnValue(return_value);
			return;
		}
		if (Z_TYPE_P(e) == IS_OBJECT && Z_TYPE(captures[3]) == IS_OBJECT && Z_OBJ_P(e) == Z_OBJ(captures[3])) {
			if (UNEXPECTED(!pt_constant_integer_type_new(return_value, 1))) {
				ZVAL_NULL(return_value);
			}
			return;
		}

		pt_throw_should_not_happen();
	}
};

} // namespace phpstanturbo

using phpstanturbo::IncDecTypeHelper;

zv::Val pt_inc_dec_type_helper_get_type_callback(zval *helper, zval *varExpr, zval *varResult, bool increment)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_inc_dec_type_helper)) return IncDecTypeHelper(Z_OBJ_P(helper)).getTypeCallback(varExpr, varResult, increment);
	zv::Args argv{varExpr, varResult, increment};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("gettypecallback"), 3, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_inc_dec_type_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\IncDecTypeHelper");
	ptdecl::IncDecTypeHelper::declareClass(cls);
	ptdecl::IncDecTypeHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj>(execute_data, initializerExprTypeResolver)) RETURN_THROWS();
		IncDecTypeHelper(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver);
	});

	cls.method(sigs::getTypeCallback, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *varExpr, *varResult;
		bool increment;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(varExpr)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_BOOL(increment)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val holder = IncDecTypeHelper(Z_OBJ_P(ZEND_THIS)).getTypeCallback(varExpr, varResult, increment);
		if (UNEXPECTED(holder.isUndef())) RETURN_THROWS();
		PT_RETURN_VAL(pt_native_closure_to_closure(holder.raw()));
	});

	cls.shadow(&pt_ce_inc_dec_type_helper);
}

/* }}} */
