/*
 * PHPStanTurbo\RicherScopeGetTypeHelper — native implementation of
 * PHPStan\Analyser\RicherScopeGetTypeHelper.
 *
 * The DI service pricing `===` / `!==` (~30K per self-analysis, from the
 * binary-op, match and identical-narrowing handlers' type callbacks and from
 * rules): the same-variable shortcut, the operand types through the walk hub
 * or the scope's direct entries, the untyped-native-property guard and the
 * InitializerExprTypeResolver's comparison (its direct entry).
 * PropertyReflectionFinder and the found property reflections stay PHP behind
 * cached sites.
 * getNotIdenticalResult() prices the operands of the NotIdentical directly —
 * the twin's `new Identical($expr->left, $expr->right)` is read for nothing
 * else and never escapes. Native callers use the
 * pt_richer_scope_get_type_helper_* entries (support.h).
 */

#include "support.h"
#include "generated/RicherScopeGetTypeHelper.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::RicherScopeGetTypeHelper::slot;
namespace sigs = ptdecl::RicherScopeGetTypeHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_richer_scope_get_type_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_rsgth_find_property_reflections_site;
pt_method_site pt_rsgth_is_native_site;
pt_method_site pt_rsgth_has_native_type_site;

/* }}} */

using phpstanturbo::visitors::NodeProp;

NodeProp pt_rsgth_left = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "left");
NodeProp pt_rsgth_right = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "right");
NodeProp pt_rsgth_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");

/* the declared operand slot ($expr->left / $expr->right) of a BinaryOp, the
 * engine's read (and its Error for an uninitialized one) otherwise; NULL =
 * pending exception */
zval *operand(NodeProp &prop, zval *expr, const char *name, size_t len, zval &rv)
{
	zval *slot = prop.of(Z_OBJ_P(expr));
	if (EXPECTED(slot != NULL && Z_TYPE_P(slot) != IS_UNDEF)) return slot;
	if (UNEXPECTED(EG(exception))) return NULL;
	zval *value = zend_read_property(Z_OBJCE_P(expr), Z_OBJ_P(expr), name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* $node instanceof <class-map class>; false = pending exception */
[[nodiscard]] bool isA(zval *node, int classIdx, bool &out)
{
	zend_class_entry *ce = pt_class_loaded(classIdx);
	if (UNEXPECTED(EG(exception))) return false;
	out = ce != NULL && Z_TYPE_P(node) == IS_OBJECT && instanceof_function(Z_OBJCE_P(node), ce);
	return true;
}

/* new TypeResult(new ConstantBooleanType($value) / new BooleanType(), []) */
zv::Val booleanResult(int constant)
{
	zval type;
	bool created = constant < 0 ? pt_boolean_type_new(&type) : pt_constant_boolean_type_new(&type, constant != 0);
	if (UNEXPECTED(!created)) return zv::Val();
	zv::Val typeHold = zv::Val::adopt(type);
	zval reasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	return pt_type_result_new(typeHold.raw(), &reasons);
}

inline bool isNullOrAbsent(zval *value)
{
	return value == NULL || Z_TYPE_P(value) == IS_NULL;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\RicherScopeGetTypeHelper; UNDEF = pending exception. */
class RicherScopeGetTypeHelper
{
public:
	explicit RicherScopeGetTypeHelper(zend_object *self) : self(self) {}

	void construct(zval *initializerExprTypeResolver, zval *propertyReflectionFinder) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::propertyReflectionFinder, propertyReflectionFinder);
	}

	/* Mirrors getIdenticalResult() ($nodeScopeResolver / $leftType /
	 * $rightType NULL or IS_NULL for null). */
	zv::Val getIdenticalResult(zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType) const
	{
		zval leftRv, rightRv;
		ZVAL_UNDEF(&leftRv);
		ZVAL_UNDEF(&rightRv);
		zval *left = operand(pt_rsgth_left, expr, PT_LC("left"), leftRv);
		zv::Val leftHold = zv::Val::adopt(leftRv);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zval *right = operand(pt_rsgth_right, expr, PT_LC("right"), rightRv);
		zv::Val rightHold = zv::Val::adopt(rightRv);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		return identicalResult(scope, left, right, nodeScopeResolver, leftType, rightType);
	}

	/* Mirrors getNotIdenticalResult(). */
	zv::Val getNotIdenticalResult(zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType) const
	{
		zval leftRv, rightRv;
		ZVAL_UNDEF(&leftRv);
		ZVAL_UNDEF(&rightRv);
		zval *left = operand(pt_rsgth_left, expr, PT_LC("left"), leftRv);
		zv::Val leftHold = zv::Val::adopt(leftRv);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zval *right = operand(pt_rsgth_right, expr, PT_LC("right"), rightRv);
		zv::Val rightHold = zv::Val::adopt(rightRv);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		/* new Identical($expr->left, $expr->right): the constructor's `Expr` parameters */
		if (UNEXPECTED(!requireExpr(left, 1, "left") || !requireExpr(right, 2, "right"))) return zv::Val();

		zv::Val identical = identicalResult(scope, left, right, nodeScopeResolver, leftType, rightType);
		if (UNEXPECTED(identical.isUndef())) return zv::Val();
		zval typeRv, reasonsRv;
		ZVAL_UNDEF(&typeRv);
		ZVAL_UNDEF(&reasonsRv);
		zval *identicalType = resultSlot(identical.raw(), ptdecl::TypeResult::slot::type, "type", typeRv);
		zv::Val typeRvHold = zv::Val::adopt(typeRv);
		if (UNEXPECTED(identicalType == NULL)) return zv::Val();
		if (Z_TYPE_P(identicalType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(identicalType), pt_ce_constant_boolean_type)) {
			bool value;
			if (UNEXPECTED(!pt_constant_boolean_type_value(Z_OBJ_P(identicalType), value))) return zv::Val();
			zval type;
			if (UNEXPECTED(!pt_constant_boolean_type_new(&type, !value))) return zv::Val();
			zv::Val typeHold = zv::Val::adopt(type);
			zval *reasons = resultSlot(identical.raw(), ptdecl::TypeResult::slot::reasons, "reasons", reasonsRv);
			zv::Val reasonsRvHold = zv::Val::adopt(reasonsRv);
			if (UNEXPECTED(reasons == NULL)) return zv::Val();
			return pt_type_result_new(typeHold.raw(), reasons);
		}
		return booleanResult(-1);
	}

private:
	zend_object *self;

	/* the body over the operands (borrowed) */
	zv::Val identicalResult(zval *scope, zval *left, zval *right, zval *nodeScopeResolver, zval *leftTypeArg, zval *rightTypeArg) const
	{
		bool leftVariable, rightVariable;
		if (UNEXPECTED(!isA(left, PT_CLASS_VARIABLE, leftVariable))) return zv::Val();
		if (leftVariable) {
			zval *leftName = pt_rsgth_variable_name.of(Z_OBJ_P(left));
			if (leftName != NULL && Z_TYPE_P(leftName) == IS_STRING) {
				if (UNEXPECTED(!isA(right, PT_CLASS_VARIABLE, rightVariable))) return zv::Val();
				if (rightVariable) {
					zval *rightName = pt_rsgth_variable_name.of(Z_OBJ_P(right));
					if (rightName != NULL && Z_TYPE_P(rightName) == IS_STRING && zend_string_equals(Z_STR_P(leftName), Z_STR_P(rightName))) {
						return booleanResult(1);
					}
				}
			}
		}

		zv::Val leftType = operandType(scope, left, nodeScopeResolver, leftTypeArg);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		zv::Val rightType = operandType(scope, right, nodeScopeResolver, rightTypeArg);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();

		bool untypedGuard;
		if (UNEXPECTED(!untypedNativePropertyComparedToNull(scope, left, rightType.raw(), untypedGuard))) return zv::Val();
		if (untypedGuard) return booleanResult(-1);
		if (UNEXPECTED(!untypedNativePropertyComparedToNull(scope, right, leftType.raw(), untypedGuard))) return zv::Val();
		if (untypedGuard) return booleanResult(-1);

		zval *resolver = pt_typed_slot(self, slots::initializerExprTypeResolver, self->ce, "initializerExprTypeResolver");
		if (UNEXPECTED(resolver == NULL)) return zv::Val();
		return pt_initializer_expr_type_resolver_resolve_identical_type(resolver, leftType.raw(), rightType.raw());
	}

	/* $type ??= $nodeScopeResolver !== null
	 *     ? $nodeScopeResolver->readTypeOfMaybeStored($operand, $scope->toWalkScope())
	 *     : $scope->getType($operand) */
	static zv::Val operandType(zval *scope, zval *operandExpr, zval *nodeScopeResolver, zval *given)
	{
		if (!isNullOrAbsent(given)) return zv::Val::copyOf(zv::Ref(given));
		if (!isNullOrAbsent(nodeScopeResolver)) {
			zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
			if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
			return pt_node_scope_resolver_read_type_of_maybe_stored(nodeScopeResolver, operandExpr, walkScope.raw());
		}
		return pt_mutating_scope_get_type(Z_OBJ_P(scope), operandExpr);
	}

	/* ($operand instanceof PropertyFetch || $operand instanceof StaticPropertyFetch)
	 * && $otherType->isNull()->yes() and a found native property reflection
	 * without a native type; false = pending exception */
	[[nodiscard]] bool untypedNativePropertyComparedToNull(zval *scope, zval *operandExpr, zval *otherType, bool &out) const
	{
		out = false;
		bool fetch;
		if (UNEXPECTED(!isA(operandExpr, PT_CLASS_PROPERTY_FETCH, fetch))) return false;
		if (!fetch) {
			if (UNEXPECTED(!isA(operandExpr, PT_CLASS_STATIC_PROPERTY_FETCH, fetch))) return false;
		}
		if (!fetch) return true;
		if (UNEXPECTED(Z_TYPE_P(otherType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(otherType));
			return false;
		}
		zend_long isNull = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return false;
		if (isNull != PT_TRI_YES) return true;

		zval *finder = pt_typed_slot(self, slots::propertyReflectionFinder, self->ce, "propertyReflectionFinder");
		if (UNEXPECTED(finder == NULL)) return false;
		zv::Args argv{operandExpr, scope};
		zv::Val found = pt_call_method_cached(pt_rsgth_find_property_reflections_site, Z_OBJ_P(finder), PT_LC("findpropertyreflectionsfromnode"), 2, argv);
		if (UNEXPECTED(found.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(found.raw()) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(found.raw()));
			return false;
		}
		for (auto entry : zv::ArrRef(found.raw())) {
			zval *reflection = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(reflection) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isNative() on %s", zend_zval_value_name(reflection));
				return false;
			}
			zv::Val isNative = pt_call_method_cached(pt_rsgth_is_native_site, Z_OBJ_P(reflection), PT_LC("isnative"), 0, NULL);
			if (UNEXPECTED(isNative.isUndef())) return false;
			if (!zend_is_true(isNative.raw())) continue;
			zv::Val hasNativeType = pt_call_method_cached(pt_rsgth_has_native_type_site, Z_OBJ_P(reflection), PT_LC("hasnativetype"), 0, NULL);
			if (UNEXPECTED(hasNativeType.isUndef())) return false;
			if (zend_is_true(hasNativeType.raw())) continue;
			out = true;
			return true;
		}
		return true;
	}

	/* the Identical constructor's `Expr $left` / `Expr $right` check */
	[[nodiscard]] static bool requireExpr(zval *value, uint32_t argNum, const char *name)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return false;
		if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), exprCe))) return true;
		zend_type_error("PhpParser\\Node\\Expr\\BinaryOp::__construct(): Argument #%u ($%s) must be of type PhpParser\\Node\\Expr, %s given", argNum, name, zend_zval_value_name(value));
		return false;
	}

	/* $typeResult->type / ->reasons of the native TypeResult, the engine's
	 * read otherwise; NULL = pending exception */
	static zval *resultSlot(zval *result, uint32_t index, const char *name, zval &rv)
	{
		if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_type_result)) {
			zval *value = OBJ_PROP_NUM(Z_OBJ_P(result), index);
			if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
		}
		if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(result));
			return NULL;
		}
		zval *value = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), name, strlen(name), 0, &rv);
		if (UNEXPECTED(EG(exception))) return NULL;
		return value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::RicherScopeGetTypeHelper;

/* {{{ direct entries (support.h): the native body for the native service,
 * the method otherwise */

namespace {

[[nodiscard]] bool helperReceiver(zval *helper, const char *method)
{
	if (EXPECTED(Z_TYPE_P(helper) == IS_OBJECT)) return true;
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(helper));
	return false;
}

inline bool nativeArguments(zval *scope, zval *expr, int exprClassIdx)
{
	zend_class_entry *exprCe = pt_class_loaded(exprClassIdx);
	return Z_TYPE_P(scope) == IS_OBJECT && Z_TYPE_P(expr) == IS_OBJECT && exprCe != NULL && instanceof_function(Z_OBJCE_P(expr), exprCe);
}

zv::Val callByName(zval *helper, const char *lcname, size_t len, zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType)
{
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, nodeScopeResolver != NULL ? nodeScopeResolver : &null, leftType != NULL ? leftType : &null, rightType != NULL ? rightType : &null};
	return pt_type_call(Z_OBJ_P(helper), lcname, len, 5, argv);
}

} // namespace

zv::Val pt_richer_scope_get_type_helper_get_identical_result(zval *helper, zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType)
{
	if (UNEXPECTED(!helperReceiver(helper, "getIdenticalResult"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_richer_scope_get_type_helper && nativeArguments(scope, expr, PT_CLASS_IDENTICAL_EXPR))) {
		return RicherScopeGetTypeHelper(Z_OBJ_P(helper)).getIdenticalResult(scope, expr, nodeScopeResolver, leftType, rightType);
	}
	if (UNEXPECTED(EG(exception))) return zv::Val();
	return callByName(helper, PT_LC("getidenticalresult"), scope, expr, nodeScopeResolver, leftType, rightType);
}

zv::Val pt_richer_scope_get_type_helper_get_not_identical_result(zval *helper, zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType)
{
	if (UNEXPECTED(!helperReceiver(helper, "getNotIdenticalResult"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_richer_scope_get_type_helper && nativeArguments(scope, expr, PT_CLASS_BINARY_OP_NOT_IDENTICAL))) {
		return RicherScopeGetTypeHelper(Z_OBJ_P(helper)).getNotIdenticalResult(scope, expr, nodeScopeResolver, leftType, rightType);
	}
	if (UNEXPECTED(EG(exception))) return zv::Val();
	return callByName(helper, PT_LC("getnotidenticalresult"), scope, expr, nodeScopeResolver, leftType, rightType);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_RSGTH_THIS RicherScopeGetTypeHelper(Z_OBJ_P(ZEND_THIS))

namespace {

/* getIdenticalResult() / getNotIdenticalResult()'s parameters; false =
 * pending exception */
[[nodiscard]] bool parseResultParameters(zend_execute_data *execute_data, int exprClassIdx, zval *&scope, zval *&expr, zval *&nodeScopeResolver, zval *&leftType, zval *&rightType)
{
	nodeScopeResolver = NULL;
	leftType = NULL;
	rightType = NULL;
	zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
	if (UNEXPECTED(typeCe == NULL)) return false;
	ZEND_PARSE_PARAMETERS_START(2, 5)
		Z_PARAM_OBJECT_OF_CLASS(scope, pt_class(PT_CLASS_SCOPE))
		Z_PARAM_OBJECT_OF_CLASS(expr, pt_class(exprClassIdx))
		Z_PARAM_OPTIONAL
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(nodeScopeResolver, pt_ce_node_scope_resolver)
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(leftType, typeCe)
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(rightType, typeCe)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

} // namespace

void pt_register_richer_scope_get_type_helper()
{
	reg::Class cls("PHPStan\\Analyser\\RicherScopeGetTypeHelper");
	ptdecl::RicherScopeGetTypeHelper::declareClass(cls);
	ptdecl::RicherScopeGetTypeHelper::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *propertyReflectionFinder;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, propertyReflectionFinder)) RETURN_THROWS();
		PT_RSGTH_THIS.construct(initializerExprTypeResolver, propertyReflectionFinder);
	});

	cls.method(sigs::getIdenticalResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *nodeScopeResolver, *leftType, *rightType;
		if (UNEXPECTED(!parseResultParameters(execute_data, PT_CLASS_IDENTICAL_EXPR, scope, expr, nodeScopeResolver, leftType, rightType))) RETURN_THROWS();
		PT_RETURN_VAL(PT_RSGTH_THIS.getIdenticalResult(scope, expr, nodeScopeResolver, leftType, rightType));
	});

	cls.method(sigs::getNotIdenticalResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *nodeScopeResolver, *leftType, *rightType;
		if (UNEXPECTED(!parseResultParameters(execute_data, PT_CLASS_BINARY_OP_NOT_IDENTICAL, scope, expr, nodeScopeResolver, leftType, rightType))) RETURN_THROWS();
		PT_RETURN_VAL(PT_RSGTH_THIS.getNotIdenticalResult(scope, expr, nodeScopeResolver, leftType, rightType));
	});

	cls.shadow(&pt_ce_richer_scope_get_type_helper);
}

/* }}} */
