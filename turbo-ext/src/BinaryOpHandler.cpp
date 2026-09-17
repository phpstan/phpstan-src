/*
 * PHPStanTurbo\BinaryOpHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\BinaryOpHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $leftResult,
 * $rightResult, $nodeScopeResolver, $beforeScope), its operand reader
 * $getType ($expr, $leftResult, $rightResult, $nativeTypesPromoted,
 * $beforeScope, $nodeScopeResolver — a pt_ietr_get_type over the
 * typeCallback's frame where the twin hands it to InitializerExprTypeResolver,
 * which calls it synchronously; the typeCallback's own asks read the operands
 * directly), the specifyTypesCallback ($this, $expr, $leftResult,
 * $rightResult, $nodeScopeResolver, $beforeScope, $specifySubResults,
 * $leftArgResult, $rightArgResult, $typeCallback) and its identical-type
 * callback ($expr, $nativeTypesPromoted, $typeCallback). The specify
 * callback's own $getType is only called in place and is inlined.
 *
 * The operator is classified once per class entry (the twin's instanceof
 * chain, in its order, memoized per request). A concatenation's type is
 * InitializerExprTypeResolver::getConcatType() spelled out — its two operand
 * reads and resolveConcatType() — so a deep `.` chain recurses natively
 * (without an engine frame per level) and continues on a fresh C stack when
 * the current one runs low; every type callback does.
 *
 * NodeScopeResolver, ExpressionResult, ExpressionResultStorage,
 * ExpressionContext, MutatingScope, VariableFlow(Builder),
 * InternalThrowPoint, SpecifiedTypes, TypeSpecifierContext, ExprPrinter,
 * IdenticalNarrowingHelper, DefaultNarrowingHelper, TypeCombinator and the
 * Type kernel are called through their direct entries; the collaborators
 * that stay PHP for now (RicherScopeGetTypeHelper) through the cached method
 * sites in the block below, one helper each; InitializerExprTypeResolver,
 * ImplicitToStringCallHelper and CountNarrowingHelper through their direct
 * entries.
 */

#include "support.h"
#include "generated/BinaryOpHandler.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::BinaryOpHandler::slot;
namespace sigs = ptdecl::BinaryOpHandler::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_binary_op_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

/* {{{ the operator classes, in the order the twin's typeCallback tests them */

enum BinaryOpKind : uint8_t
{
	KIND_SMALLER,
	KIND_SMALLER_OR_EQUAL,
	KIND_GREATER,
	KIND_GREATER_OR_EQUAL,
	KIND_EQUAL,
	KIND_NOT_EQUAL,
	KIND_IDENTICAL,
	KIND_NOT_IDENTICAL,
	KIND_LOGICAL_XOR,
	KIND_SPACESHIP,
	KIND_CONCAT,
	KIND_BITWISE_AND,
	KIND_BITWISE_OR,
	KIND_BITWISE_XOR,
	KIND_DIV,
	KIND_MOD,
	KIND_PLUS,
	KIND_MINUS,
	KIND_MUL,
	KIND_POW,
	KIND_SHIFT_LEFT,
	KIND_SHIFT_RIGHT,
	KIND_OTHER,
};

constexpr int kindClasses[KIND_OTHER] = {
	PT_CLASS_SMALLER_EXPR,
	PT_CLASS_SMALLER_OR_EQUAL_EXPR,
	PT_CLASS_GREATER_EXPR,
	PT_CLASS_GREATER_OR_EQUAL_EXPR,
	PT_CLASS_EQUAL_EXPR,
	PT_CLASS_NOT_EQUAL_EXPR,
	PT_CLASS_IDENTICAL_EXPR,
	PT_CLASS_BINARY_OP_NOT_IDENTICAL,
	PT_CLASS_LOGICAL_XOR_EXPR,
	PT_CLASS_SPACESHIP_EXPR,
	PT_CLASS_CONCAT_EXPR,
	PT_CLASS_BITWISE_AND_EXPR,
	PT_CLASS_BITWISE_OR_EXPR,
	PT_CLASS_BITWISE_XOR_EXPR,
	PT_CLASS_DIV_EXPR,
	PT_CLASS_MOD_EXPR,
	PT_CLASS_BINARY_OP_PLUS,
	PT_CLASS_BINARY_OP_MINUS,
	PT_CLASS_MUL_EXPR,
	PT_CLASS_POW_EXPR,
	PT_CLASS_SHIFT_LEFT_EXPR,
	PT_CLASS_SHIFT_RIGHT_EXPR,
};

/* the class entry -> kind memo: direct-mapped, a collision recomputes */
#define PT_BOH_KIND_CACHE_BITS_LIMIT 6

struct KindSlot
{
	zend_class_entry *ce;
	uint32_t generation;
	uint8_t kind;
};

KindSlot kindCache[1u << PT_BOH_KIND_CACHE_BITS_LIMIT];

/* the first operator class $expr is an instance of (the php-parser classes
 * are siblings, so it answers every instanceof test of the twin); -1 =
 * pending exception */
int kindOf(zval *expr)
{
	zend_class_entry *ce = Z_OBJCE_P(expr);
	uintptr_t hash = ((uintptr_t) ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	KindSlot &slot = kindCache[hash >> (sizeof(uintptr_t) * 8 - PT_BOH_KIND_CACHE_BITS_LIMIT)];
	if (EXPECTED(slot.ce == ce && slot.generation == pt_engine_generation)) return slot.kind;
	uint8_t kind = KIND_OTHER;
	for (uint8_t i = 0; i < KIND_OTHER; i++) {
		zend_class_entry *kindCe = pt_class(kindClasses[i]);
		if (UNEXPECTED(kindCe == NULL)) return -1;
		if (instanceof_function(ce, kindCe)) {
			kind = i;
			break;
		}
	}
	slot.ce = ce;
	slot.generation = pt_engine_generation;
	slot.kind = kind;
	return kind;
}

/* }}} */

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_boh_get_identical_result_site;
pt_method_site pt_boh_get_not_identical_result_site;

/* $implicitToStringCallHelper->processImplicitToStringCall($expr, $scope, $exprResult) */
zv::Val processImplicitToStringCall(zval *helper, zval *expr, zval *scope, zval *exprResult)
{
	return pt_implicit_to_string_call_helper_process_implicit_to_string_call(helper, expr, scope, exprResult);
}

/* $initializerExprTypeResolver->resolveConcatType($left, $right) */
zv::Val resolveConcatType(zval *resolver, zval *left, zval *right)
{
	return pt_initializer_expr_type_resolver_resolve_concat_type(resolver, left, right);
}

/* $initializerExprTypeResolver->resolveEqualType($leftType, $rightType) */
zv::Val resolverEqualType(zval *resolver, zval *leftType, zval *rightType)
{
	return pt_initializer_expr_type_resolver_resolve_equal_type(resolver, leftType, rightType);
}

/* $initializerExprTypeResolver->get<Operator>Type($left, $right, $getType) of
 * the operators the twin delegates wholesale */
zv::Val operatorType(zval *resolver, uint8_t kind, zval *left, zval *right, const pt_ietr_get_type &getType)
{
	static constexpr pt_ietr_binary_operator operators[KIND_OTHER] = {
		PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT, PT_IETR_OP_CONCAT,
		PT_IETR_OP_SPACESHIP,
		PT_IETR_OP_CONCAT,
		PT_IETR_OP_BITWISE_AND,
		PT_IETR_OP_BITWISE_OR,
		PT_IETR_OP_BITWISE_XOR,
		PT_IETR_OP_DIV,
		PT_IETR_OP_MOD,
		PT_IETR_OP_PLUS,
		PT_IETR_OP_MINUS,
		PT_IETR_OP_MUL,
		PT_IETR_OP_POW,
		PT_IETR_OP_SHIFT_LEFT,
		PT_IETR_OP_SHIFT_RIGHT,
	};
	return pt_initializer_expr_type_resolver_get_binary_op_type(resolver, operators[kind], left, right, getType);
}

/* $richerScopeGetTypeHelper->getIdenticalResult($scope, $expr, $nodeScopeResolver,
 * $leftType, $rightType) / ->getNotIdenticalResult(...) */
zv::Val identicalResult(zval *helper, bool negated, zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType)
{
	zv::Args argv{scope, expr, nodeScopeResolver, leftType, rightType};
	return negated
		? pt_call_method_cached(pt_boh_get_not_identical_result_site, Z_OBJ_P(helper), PT_LC("getnotidenticalresult"), 5, argv)
		: pt_call_method_cached(pt_boh_get_identical_result_site, Z_OBJ_P(helper), PT_LC("getidenticalresult"), 5, argv);
}

/* $countNarrowingHelper->specifyCountSize($countFuncCall, $type, $sizeType,
 * $context, $scope, $rootExpr) */
zv::Val specifyCountSize(zval *helper, zval *argv)
{
	return pt_count_narrowing_helper_specify_count_size(helper, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5]);
}

/* $countNarrowingHelper->isNormalCountCall($countFuncCall, $typeToCount, $scope)->yes();
 * -1 = pending exception */
int isNormalCountCall(zval *helper, zval *countFuncCall, zval *typeToCount, zval *scope)
{
	zend_long value = pt_count_narrowing_helper_is_normal_count_call(helper, countFuncCall, typeToCount, scope);
	if (UNEXPECTED(value < 0)) return -1;
	return value == PT_TRI_YES ? 1 : 0;
}

/* $type->toNumber() */
zv::Val toNumber(zval *type)
{
	return pt_type_call(Z_OBJ_P(type), PT_LC("tonumber"), 0, NULL);
}

/* }}} */

/* {{{ the php-parser nodes' properties */

NodeProp pt_boh_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
NodeProp pt_boh_name_name = PT_NAME_PROP;
NodeProp pt_boh_arg_value = PT_NODE_PROP(PT_CLASS_ARG, "value");
NodeProp pt_boh_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");
NodeProp pt_boh_unary_minus_expr = PT_NODE_PROP(PT_CLASS_UNARY_MINUS, "expr");
NodeProp pt_boh_pre_inc_var = PT_NODE_PROP(PT_CLASS_PRE_INC, "var");
NodeProp pt_boh_pre_dec_var = PT_NODE_PROP(PT_CLASS_PRE_DEC, "var");
NodeProp pt_boh_post_inc_var = PT_NODE_PROP(PT_CLASS_POST_INC, "var");
NodeProp pt_boh_post_dec_var = PT_NODE_PROP(PT_CLASS_POST_DEC, "var");

/* a FuncCall whose name is a Name: $call->name's (string) cast, and the
 * call's getArgs() read on first use (callArgs()) */
struct NamedCall
{
	zv::Str name;
	zval *node = NULL;
	zval *args = NULL;
	zv::Val hold;
};

/* $node instanceof FuncCall && $node->name instanceof Name (and, when
 * requireCallable, && !$node->isFirstClassCallable()); 1 fills `out`, -1 =
 * pending exception */
int namedCall(zval *node, bool requireCallable, NamedCall &out)
{
	int isCall = ptoh::isInstance(node, PT_CLASS_FUNC_CALL);
	if (isCall <= 0) return isCall;
	zval *name = ptoh::operand(pt_boh_func_call_name, node);
	if (UNEXPECTED(name == NULL)) return -1;
	int isName = ptoh::isInstance(name, PT_CLASS_NAME);
	if (isName <= 0) return isName;
	if (requireCallable) {
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(node), firstClassCallable))) return -1;
		if (firstClassCallable) return 0;
	}
	/* (string) $node->name: Name::__toString() is its $name */
	zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
	if (UNEXPECTED(nameCe == NULL)) return -1;
	zend_function *toString = Z_OBJCE_P(name)->__tostring;
	zval *nameString = toString != NULL && toString->common.scope == nameCe ? pt_boh_name_name.of(Z_OBJ_P(name)) : NULL;
	if (nameString != NULL && Z_TYPE_P(nameString) == IS_STRING) {
		out.name = zv::Str::copyOf(Z_STR_P(nameString));
	} else {
		zend_string *cast = zval_try_get_string(name);
		if (UNEXPECTED(cast == NULL)) return -1;
		out.name = zv::Str::adopt(cast);
	}
	out.node = node;
	return 1;
}

/* in_array(strtolower($name), $names, true) */
bool lowerNameIn(zend_string *name, std::initializer_list<const char *> names)
{
	for (const char *candidate : names) {
		if (phpstanturbo::visitors::lowerEquals(name, candidate, strlen(candidate))) return true;
	}
	return false;
}

/* $call->getArgs() of a matched named call (borrowed, kept alive by the
 * call's hold); NULL = pending exception */
zval *callArgs(NamedCall &call)
{
	if (call.args == NULL) call.args = pt_call_like_args(Z_OBJ_P(call.node), call.hold);
	return call.args;
}

/* $matched && in_array(strtolower((string) $call->name), $names, true) &&
 * count($call->getArgs()) <op> $arity, in that order; -1 = pending
 * exception */
enum ArityCheck { ARITY_AT_LEAST, ARITY_EXACTLY };
int callMatches(int matched, NamedCall &call, std::initializer_list<const char *> names, ArityCheck check, uint32_t arity)
{
	if (matched <= 0) return matched;
	for (const char *candidate : names) {
		if (!phpstanturbo::visitors::lowerEquals(call.name.get(), candidate, strlen(candidate))) continue;
		zval *args = callArgs(call);
		if (UNEXPECTED(args == NULL)) return -1;
		uint32_t count = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(args)) : 0;
		return (check == ARITY_AT_LEAST ? count >= arity : count == arity) ? 1 : 0;
	}
	return 0;
}

/* $call->getArgs()[0]->value (dereferenced); NULL with the engine's Error
 * pending */
zval *firstArgValue(NamedCall &call)
{
	zval *args = callArgs(call);
	if (UNEXPECTED(args == NULL)) return NULL;
	zval *first = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), 0) : NULL;
	if (first != NULL) ZVAL_DEREF(first);
	if (UNEXPECTED(first == NULL || Z_TYPE_P(first) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"value\" on null");
		return NULL;
	}
	return ptoh::operand(pt_boh_arg_value, first);
}

/* $node instanceof Scalar || ($node instanceof UnaryMinus && $node->expr
 * instanceof Scalar); -1 = pending exception */
int isScalarOrNegatedScalar(zval *node)
{
	int isScalar = ptoh::isInstance(node, PT_CLASS_SCALAR);
	if (isScalar != 0) return isScalar;
	int isUnaryMinus = ptoh::isInstance(node, PT_CLASS_UNARY_MINUS);
	if (isUnaryMinus <= 0) return isUnaryMinus;
	zval *inner = ptoh::operand(pt_boh_unary_minus_expr, node);
	if (UNEXPECTED(inner == NULL)) return -1;
	return ptoh::isInstance(inner, PT_CLASS_SCALAR);
}

/* }}} */

/* {{{ small value helpers */

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_boh_division_by_zero_error = nullptr;
zend_string *pt_boh_countable = nullptr;

/* $result->type of a TypeResult (the slot of the native class, the property
 * otherwise); UNDEF = pending exception */
zv::Val typeResultType(zval *result)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_type_result)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(result), ptdecl::TypeResult::slot::type)));
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *type = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("type"), 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return zv::Val();
	}
	if (type == &rv) return zv::Val::adopt(rv);
	return zv::Val::copyOf(zv::Ref(type));
}

/* $a->isSuperTypeOf($b)->yes() / ->no(); -1 = pending exception */
int superTypeVerdict(zval *a, zval *b, zend_long wanted)
{
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
	if (UNEXPECTED(result.isUndef())) return -1;
	zend_long value = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(value < 0)) return -1;
	return value == wanted ? 1 : 0;
}

/* $type->is<Op>()->yes() of a TrinaryLogic op; -1 = pending exception */
int trinaryYes(zval *type, pt_type_op_id op)
{
	zend_long value = pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
	if (UNEXPECTED(value < 0)) return -1;
	return value == PT_TRI_YES ? 1 : 0;
}

/* $trinary->toBooleanType() */
zv::Val trinaryToBooleanType(zval *trinary)
{
	zend_long value = pt_type_trinary_value(trinary);
	if (UNEXPECTED(value < 0)) return zv::Val();
	if (value == PT_TRI_MAYBE) return ptoh::booleanType();
	return ptoh::constantBoolean(value == PT_TRI_YES);
}

/* IntegerRangeType::createAllGreaterThanOrEqualTo($value) / createAllGreaterThan($value) */
zv::Val greaterThan(zend_long value, bool orEqual)
{
	zval z;
	ZVAL_LONG(&z, value);
	return orEqual ? pt_integer_range_create_all_greater_than_or_equal_to(&z) : pt_integer_range_create_all_greater_than(&z);
}

/* $specifiedTypes->setRootExpr($rootExpr) of a value that must be an object */
zv::Val setRootExpr(zv::Val specifiedTypes, zval *rootExpr)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(specifiedTypes.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), rootExpr);
}

/* $result = $result->unionWith($other); false = pending exception */
[[nodiscard]] bool unionInto(zv::Val &result, zv::Val other)
{
	if (UNEXPECTED(other.isUndef())) return false;
	zv::Val united = pt_specified_types_union_with(Z_OBJ_P(result.raw()), other.raw());
	if (UNEXPECTED(united.isUndef())) return false;
	result = std::move(united);
	return true;
}

/* a TypeSpecifierContext singleton as a zval (borrowed) */
inline zval contextZval(zend_object *context)
{
	zval z;
	ZVAL_OBJ(&z, context);
	return z;
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\BinaryOpHandler; UNDEF = pending
 * exception. */
class BinaryOpHandler
{
public:
	explicit BinaryOpHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval **services) const
	{
		static const uint32_t serviceSlots[9] = {
			slots::initializerExprTypeResolver, slots::richerScopeGetTypeHelper, slots::phpVersion,
			slots::implicitToStringCallHelper, slots::exprPrinter, slots::identicalNarrowingHelper,
			slots::countNarrowingHelper, slots::expressionResultFactory, slots::defaultNarrowingHelper,
		};
		for (uint32_t i = 0; i < 9; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		out = false;
		int is = ptoh::isInstance(expr, PT_CLASS_BINARY_OP_EXPR);
		if (is <= 0) return is == 0;
		static constexpr int excluded[] = {
			PT_CLASS_BOOLEAN_AND_EXPR, PT_CLASS_LOGICAL_AND_EXPR, PT_CLASS_BOOLEAN_OR_EXPR,
			PT_CLASS_LOGICAL_OR_EXPR, PT_CLASS_COALESCE_EXPR, PT_CLASS_PIPE_EXPR,
		};
		for (int classIdx : excluded) {
			is = ptoh::isInstance(expr, classIdx);
			if (UNEXPECTED(is < 0)) return false;
			if (is) return true;
		}
		out = true;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val leftContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(leftContext.isUndef())) return zv::Val();
		zv::Val leftResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, left, scope, storage, nodeCallback, leftContext.raw());
		if (UNEXPECTED(leftResult.isUndef())) return zv::Val();
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val leftScopeHold;
		zval *leftScope = pt_expression_result_scope(leftResult.raw(), leftScopeHold);
		if (UNEXPECTED(leftScope == NULL)) return zv::Val();
		zv::Val rightContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(rightContext.isUndef())) return zv::Val();
		zv::Val rightResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, right, leftScope, storage, nodeCallback, rightContext.raw());
		if (UNEXPECTED(rightResult.isUndef())) return zv::Val();

		zv::Val hold, rightHold;
		zval *borrowed = pt_expression_result_throw_points(leftResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zval *rightBorrowed = pt_expression_result_throw_points(rightResult.raw(), rightHold);
		if (UNEXPECTED(rightBorrowed == NULL)) return zv::Val();
		zv::Val throwPoints = ptoh::arrayMerge(borrowed, rightBorrowed);
		borrowed = pt_expression_result_impure_points(leftResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		rightBorrowed = pt_expression_result_impure_points(rightResult.raw(), rightHold);
		if (UNEXPECTED(rightBorrowed == NULL)) return zv::Val();
		zv::Val impurePoints = ptoh::arrayMerge(borrowed, rightBorrowed);

		int kind = kindOf(expr);
		if (UNEXPECTED(kind < 0)) return zv::Val();
		if (kind == KIND_DIV || kind == KIND_MOD) {
			// the right operand was just processed on $leftResult's scope; read its
			// result instead of re-walking via Scope::getType().
			zv::Val rightType = pt_expression_result_get_type(rightResult.raw());
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			zv::Val rightNumber = toNumber(rightType.raw());
			if (UNEXPECTED(rightNumber.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(rightNumber.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(rightNumber.raw()));
				return zv::Val();
			}
			zv::Val zero = pt_type_new_constant_integer(0);
			if (UNEXPECTED(zero.isUndef())) return zv::Val();
			int no = superTypeVerdict(rightNumber.raw(), zero.raw(), PT_TRI_NO);
			if (UNEXPECTED(no < 0)) return zv::Val();
			if (!no) {
				zv::Val className = zv::Val::string(pt_boh_division_by_zero_error);
				zv::Val errorType = pt_type_new_object_type(className.raw());
				if (UNEXPECTED(errorType.isUndef())) return zv::Val();
				leftScope = pt_expression_result_scope(leftResult.raw(), leftScopeHold);
				if (UNEXPECTED(leftScope == NULL)) return zv::Val();
				zv::Val throwPoint = pt_internal_throw_point_create_explicit(leftScope, errorType.raw(), expr, false, false);
				if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
				zv::Arr points = zv::Arr::adoptVal(std::move(throwPoints));
				points.push(std::move(throwPoint));
				throwPoints = std::move(points);
			}
		}
		if (kind == KIND_CONCAT) {
			zval *helper = OBJ_PROP_NUM(self, slots::implicitToStringCallHelper);
			left = ptoh::binaryOpLeft(expr);
			if (UNEXPECTED(left == NULL)) return zv::Val();
			zv::Val leftToStringResult = processImplicitToStringCall(helper, left, scope, leftResult.raw());
			if (UNEXPECTED(leftToStringResult.isUndef())) return zv::Val();
			right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			leftScope = pt_expression_result_scope(leftResult.raw(), leftScopeHold);
			if (UNEXPECTED(leftScope == NULL)) return zv::Val();
			zv::Val rightToStringResult = processImplicitToStringCall(helper, right, leftScope, rightResult.raw());
			if (UNEXPECTED(rightToStringResult.isUndef())) return zv::Val();
			borrowed = pt_expression_result_throw_points(leftToStringResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			rightBorrowed = pt_expression_result_throw_points(rightToStringResult.raw(), rightHold);
			if (UNEXPECTED(rightBorrowed == NULL)) return zv::Val();
			throwPoints = ptoh::arrayMerge(throwPoints.raw(), borrowed);
			throwPoints = ptoh::arrayMerge(throwPoints.raw(), rightBorrowed);
			borrowed = pt_expression_result_impure_points(leftToStringResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			rightBorrowed = pt_expression_result_impure_points(rightToStringResult.raw(), rightHold);
			if (UNEXPECTED(rightBorrowed == NULL)) return zv::Val();
			impurePoints = ptoh::arrayMerge(impurePoints.raw(), borrowed);
			impurePoints = ptoh::arrayMerge(impurePoints.raw(), rightBorrowed);
		}
		zv::Val resultScopeHold;
		zval *resultScope = pt_expression_result_scope(rightResult.raw(), resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();

		zval *identicalNarrowingHelper = OBJ_PROP_NUM(self, slots::identicalNarrowingHelper);
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val leftArgResult = pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper, left, storage);
		if (UNEXPECTED(leftArgResult.isUndef())) return zv::Val();
		right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val rightArgResult = pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper, right, storage);
		if (UNEXPECTED(rightArgResult.isUndef())) return zv::Val();
		// the comparison specify logic reads these operand subexpressions (count()
		// arguments, subtraction operands) - capture their walk results now: the
		// callback must not capture the storage itself (the storage holds the
		// results and the results hold their callbacks - a cycle the disabled GC
		// never collects)
		zv::Val specifySubResults = zv::Val(zv::Arr::empty());
		if (UNEXPECTED(!captureSpecifySubResults(expr, storage, specifySubResults))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, leftResult.raw(), rightResult.raw(), nodeScopeResolver, beforeScope);

		zv::Val variableFlow;
		{
			zv::Val leftFlow = pt_expression_result_variable_flow(leftResult.raw());
			if (UNEXPECTED(leftFlow.isUndef())) return zv::Val();
			zv::Val rightFlow = pt_expression_result_variable_flow(rightResult.raw());
			if (UNEXPECTED(rightFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{leftFlow.raw(), rightFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(leftResult.raw(), hasYield))) return zv::Val();
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(rightResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(leftResult.raw(), isAlwaysTerminating))) return zv::Val();
		if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(rightResult.raw(), isAlwaysTerminating))) return zv::Val();

		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, leftResult.raw(), rightResult.raw(), nodeScopeResolver, beforeScope, specifySubResults.raw(), leftArgResult.raw(), rightArgResult.raw(), typeCallback.raw());
		pt_expression_result_args args(resultScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BinaryOpHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors resolveEqualType(), over the Equal node's operands (the twin's
	 * NotEqual ask builds an Equal node only to carry them) */
	zv::Val resolveEqualType(zval *scope, zval *left, zval *right, zval *leftResult, zval *rightResult) const
	{
		int leftIsVariable = ptoh::isInstance(left, PT_CLASS_VARIABLE);
		if (UNEXPECTED(leftIsVariable < 0)) return zv::Val();
		if (leftIsVariable) {
			zval *leftName = ptoh::operand(pt_boh_variable_name, left);
			if (UNEXPECTED(leftName == NULL)) return zv::Val();
			if (Z_TYPE_P(leftName) == IS_STRING) {
				int rightIsVariable = ptoh::isInstance(right, PT_CLASS_VARIABLE);
				if (UNEXPECTED(rightIsVariable < 0)) return zv::Val();
				if (rightIsVariable) {
					zval *rightName = ptoh::operand(pt_boh_variable_name, right);
					if (UNEXPECTED(rightName == NULL)) return zv::Val();
					if (Z_TYPE_P(rightName) == IS_STRING && zend_string_equals(Z_STR_P(leftName), Z_STR_P(rightName))) return ptoh::constantBoolean(true);
				}
			}
		}

		// the operands were processed during processExpr; use their results' types.
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		zv::Val leftType = pt_expression_result_get_type_on_scope(leftResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		zv::Val rightType = pt_expression_result_get_type_on_scope(rightResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();

		zv::Val typeResult = resolverEqualType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), leftType.raw(), rightType.raw());
		if (UNEXPECTED(typeResult.isUndef())) return zv::Val();
		return typeResultType(typeResult.raw());
	}

	/* Mirrors createRangeTypes(); $rootExpr NULL or IS_NULL for null */
	zv::Val createRangeTypes(zval *rootExpr, zval *expr, zval *type, zval *context) const
	{
		zv::Val sureNotTypes = zv::Val(zv::Arr::empty());
		bool isRange = Z_TYPE_P(type) == IS_OBJECT && (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_range_type) || instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type));
		if (isRange) {
			zend_string *exprString = pt_expr_printer_print(OBJ_PROP_NUM(self, slots::exprPrinter), Z_OBJ_P(expr));
			if (UNEXPECTED(exprString == NULL)) return zv::Val();
			zv::Str exprStringHold = zv::Str::adopt(exprString);
			bool isFalse;
			if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), isFalse))) return zv::Val();
			zv::Val entryType;
			if (isFalse) {
				entryType = zv::Val::copyOf(zv::Ref(type));
			} else {
				bool isTrue;
				if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), isTrue))) return zv::Val();
				if (isTrue) {
					zval integerType;
					if (UNEXPECTED(!pt_integer_type_new(&integerType))) return zv::Val();
					zv::Val integerTypeHold = zv::Val::adopt(integerType);
					entryType = pt_type_combinator_remove(integerTypeHold.raw(), type);
					if (UNEXPECTED(entryType.isUndef())) return zv::Val();
				}
			}
			if (!entryType.isUndef()) {
				zv::Arr entry = zv::Arr::create(2);
				entry.push(zv::Ref(expr));
				entry.push(std::move(entryType));
				zv::Arr table = zv::Arr::create(1);
				table.set(exprStringHold.get(), zv::Val(std::move(entry)));
				sureNotTypes = std::move(table);
			}
		}

		if (rootExpr != NULL && Z_TYPE_P(rootExpr) == IS_NULL) rootExpr = NULL;
		return pt_specified_types_new_with_root_expr(NULL, sureNotTypes.raw(), rootExpr);
	}

private:
	zend_object *self;

	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\BinaryOpHandler::{closure}";

	/* the $specifySubExprs / $specifySubResults block of processExpr();
	 * false = pending exception */
	[[nodiscard]] static bool captureSpecifySubResults(zval *expr, zval *storage, zv::Val &specifySubResults)
	{
		zval *specifySubExprs[2];
		zv::Val argsHolds[2];
		uint32_t count = 0;
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return false;
		int rightIsCall = ptoh::isInstance(right, PT_CLASS_FUNC_CALL);
		if (UNEXPECTED(rightIsCall < 0)) return false;
		bool captured = false;
		if (rightIsCall) {
			zval *arg = callableFirstArgValue(right, captured, argsHolds[0]);
			if (UNEXPECTED(arg == NULL && EG(exception) != NULL)) return false;
			if (arg != NULL) specifySubExprs[count++] = arg;
		}
		if (!captured) {
			int rightIsMinus = ptoh::isInstance(right, PT_CLASS_BINARY_OP_MINUS);
			if (UNEXPECTED(rightIsMinus < 0)) return false;
			if (rightIsMinus) {
				zval *minusRight = ptoh::binaryOpRight(right);
				if (UNEXPECTED(minusRight == NULL)) return false;
				specifySubExprs[count++] = minusRight;
				zval *minusLeft = ptoh::binaryOpLeft(right);
				if (UNEXPECTED(minusLeft == NULL)) return false;
				int minusLeftIsCall = ptoh::isInstance(minusLeft, PT_CLASS_FUNC_CALL);
				if (UNEXPECTED(minusLeftIsCall < 0)) return false;
				if (minusLeftIsCall) {
					bool minusLeftCaptured = false;
					zval *arg = callableFirstArgValue(minusLeft, minusLeftCaptured, argsHolds[1]);
					if (UNEXPECTED(arg == NULL && EG(exception) != NULL)) return false;
					if (arg != NULL) specifySubExprs[count++] = arg;
				}
			}
		}
		for (uint32_t i = 0; i < count; i++) {
			zval *specifySubExpr = specifySubExprs[i];
			zv::Val specifySubResult = pt_expression_result_storage_find(storage, specifySubExpr);
			if (UNEXPECTED(specifySubResult.isUndef())) return false;
			if (specifySubResult.isNull()) continue;
			zval *table = specifySubResults.raw();
			SEPARATE_ARRAY(table);
			zval value = specifySubResult.take();
			zend_hash_index_update(Z_ARRVAL_P(table), Z_OBJ_HANDLE_P(specifySubExpr), &value);
		}
		return true;
	}

	/* `!$call->isFirstClassCallable() && isset($call->getArgs()[0])` of a
	 * FuncCall: its first argument's value (the condition holding sets
	 * `matched`); NULL with an exception pending, or when it does not hold */
	static zval *callableFirstArgValue(zval *call, bool &matched, zv::Val &hold)
	{
		matched = false;
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable))) return NULL;
		if (firstClassCallable) return NULL;
		zval *args = pt_call_like_args(Z_OBJ_P(call), hold);
		if (UNEXPECTED(args == NULL)) return NULL;
		zval *first = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), 0) : NULL;
		if (first != NULL) ZVAL_DEREF(first);
		if (first == NULL || Z_TYPE_P(first) == IS_NULL) return NULL;
		matched = true;
		if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(first));
			return NULL;
		}
		return ptoh::operand(pt_boh_arg_value, first);
	}

	/* {{{ the typeCallback */

	/* the $getType closure's body: $e's type — the left / right operand's
	 * result re-priced on the result's own (flavoured) beforeScope, a
	 * synthetic node priced on demand */
	static zv::Val operandType(zval *expr, zval *leftResult, zval *rightResult, bool nativeTypesPromoted, zval *beforeScope, zval *nodeScopeResolver, zval *e)
	{
		zv::Val flavouredHold;
		zval *flavouredScope = beforeScope;
		if (nativeTypesPromoted) {
			flavouredHold = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(flavouredHold.isUndef())) return zv::Val();
			flavouredScope = flavouredHold.raw();
		}
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		if (Z_OBJ_P(e) == Z_OBJ_P(left)) return pt_expression_result_get_type_on_scope(leftResult, flavouredScope, nativeTypesPromoted);
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		if (Z_OBJ_P(e) == Z_OBJ_P(right)) return pt_expression_result_get_type_on_scope(rightResult, flavouredScope, nativeTypesPromoted);

		// InitializerExprTypeResolver also asks about synthetic composed
		// nodes (e.g. Mod($left, $right) for the int-division check) -
		// price those
		zv::Val synthetic = pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, e, flavouredScope);
		if (UNEXPECTED(synthetic.isUndef())) return zv::Val();
		bool flavouredPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(flavouredScope), flavouredPromoted))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(synthetic.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(synthetic.raw()));
			return zv::Val();
		}
		return pt_expression_result_get_type_on_scope(synthetic.raw(), flavouredScope, flavouredPromoted);
	}

	/* static function (Expr $e) use ($expr, $leftResult, $rightResult,
	 * $nativeTypesPromoted, $beforeScope, $nodeScopeResolver): Type — the
	 * $getType InitializerExprTypeResolver calls synchronously, over the
	 * typeCallback's frame */
	struct OperandTypeFrame
	{
		zval *expr;
		zval *leftResult;
		zval *rightResult;
		bool nativeTypesPromoted;
		zval *beforeScope;
		zval *nodeScopeResolver;
	};

	static zv::Val operandTypeCallback(void *data, zval *e)
	{
		OperandTypeFrame *frame = static_cast<OperandTypeFrame *>(data);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(e) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(e), exprCe))) {
			zend_type_error("%s(): Argument #1 ($e) must be of type PhpParser\\Node\\Expr, %s given", closureName, zend_zval_value_name(e));
			return zv::Val();
		}
		return operandType(frame->expr, frame->leftResult, frame->rightResult, frame->nativeTypesPromoted, frame->beforeScope, frame->nodeScopeResolver, e);
	}

	/* the $getType as a PHP callable that outlives the call (the resolver
	 * hands it to a collaborator that may keep it): a native closure over
	 * copies of the frame's values */
	static zv::Val operandTypeCallable(void *data)
	{
		OperandTypeFrame *frame = static_cast<OperandTypeFrame *>(data);
		zval getTypeCaptures[6];
		ZVAL_COPY_VALUE(&getTypeCaptures[0], frame->expr);
		ZVAL_COPY_VALUE(&getTypeCaptures[1], frame->leftResult);
		ZVAL_COPY_VALUE(&getTypeCaptures[2], frame->rightResult);
		ZVAL_BOOL(&getTypeCaptures[3], frame->nativeTypesPromoted);
		ZVAL_COPY_VALUE(&getTypeCaptures[4], frame->beforeScope);
		ZVAL_COPY_VALUE(&getTypeCaptures[5], frame->nodeScopeResolver);
		return pt_native_closure_new(&getTypeBody, 6, getTypeCaptures);
	}

	/* static function (Expr $e) use ($expr, $leftResult, $rightResult,
	 * $nativeTypesPromoted, $beforeScope, $nodeScopeResolver): Type —
	 * captures in that order */
	static void getTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		OperandTypeFrame frame{&captures[0], &captures[1], &captures[2], Z_TYPE(captures[3]) == IS_TRUE, &captures[4], &captures[5]};
		zval *e = &argv[0];
		ZVAL_DEREF(e);
		zv::Val type = operandTypeCallback(&frame, e);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $leftResult,
	 * $rightResult, $nodeScopeResolver, $beforeScope): Type — captures:
	 * $this, $expr, $leftResult, $rightResult, $nodeScopeResolver,
	 * $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = BinaryOpHandler(Z_OBJ(captures[0])).resolveType(captures, nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	zv::Val resolveType(zval *captures, bool nativeTypesPromoted) const
	{
		zval *expr = &captures[1];
		zval *leftResult = &captures[2];
		zval *rightResult = &captures[3];
		zval *nodeScopeResolver = &captures[4];
		zval *beforeScope = &captures[5];

		// the comparison helpers (resolveEqualType / RicherScopeGetTypeHelper)
		// read the operand types off the evaluation scope - native-promote it
		// here so the native flavour is honoured.
		zv::Val scopeHold;
		zval *scope = beforeScope;
		if (nativeTypesPromoted) {
			scopeHold = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(scopeHold.isUndef())) return zv::Val();
			scope = scopeHold.raw();
		}
		auto getType = [&](zval *e) { return operandType(expr, leftResult, rightResult, nativeTypesPromoted, beforeScope, nodeScopeResolver, e); };

		int kind = kindOf(expr);
		if (UNEXPECTED(kind < 0)) return zv::Val();
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		switch (kind) {
			case KIND_SMALLER:
			case KIND_SMALLER_OR_EQUAL:
			case KIND_GREATER:
			case KIND_GREATER_OR_EQUAL: {
				bool greater = kind == KIND_GREATER || kind == KIND_GREATER_OR_EQUAL;
				bool orEqual = kind == KIND_SMALLER_OR_EQUAL || kind == KIND_GREATER_OR_EQUAL;
				zv::Val smallerType = getType(greater ? right : left);
				if (UNEXPECTED(smallerType.isUndef())) return zv::Val();
				zv::Val largerType = getType(greater ? left : right);
				if (UNEXPECTED(largerType.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(smallerType.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function %s() on %s", orEqual ? "isSmallerThanOrEqual" : "isSmallerThan", zend_zval_value_name(smallerType.raw()));
					return zv::Val();
				}
				zv::Args argv{largerType.raw(), OBJ_PROP_NUM(self, slots::phpVersion)};
				zv::Val trinary = orEqual
					? pt_type_call(Z_OBJ_P(smallerType.raw()), PT_LC("issmallerthanorequal"), 2, argv)
					: pt_type_call(Z_OBJ_P(smallerType.raw()), PT_LC("issmallerthan"), 2, argv);
				if (UNEXPECTED(trinary.isUndef())) return zv::Val();
				return trinaryToBooleanType(trinary.raw());
			}
			case KIND_EQUAL:
				return resolveEqualType(scope, left, right, leftResult, rightResult);
			case KIND_NOT_EQUAL: {
				// negation of the Equal result - direct computation avoids
				// synthesizing a BooleanNot node (which would route through
				// on-demand re-processing once BooleanNot is migrated)
				zv::Val equalType = resolveEqualType(scope, left, right, leftResult, rightResult);
				if (UNEXPECTED(equalType.isUndef())) return zv::Val();
				ptoh::BooleanOf equalBoolean;
				if (UNEXPECTED(!equalBoolean.init(equalType.raw()))) return zv::Val();
				int isTrue = equalBoolean.isTrue();
				if (UNEXPECTED(isTrue < 0)) return zv::Val();
				if (isTrue) return ptoh::constantBoolean(false);
				int isFalse = equalBoolean.isFalse();
				if (UNEXPECTED(isFalse < 0)) return zv::Val();
				if (isFalse) return ptoh::constantBoolean(true);

				return ptoh::booleanType();
			}
			case KIND_IDENTICAL:
			case KIND_NOT_IDENTICAL: {
				zv::Val leftType = getType(left);
				if (UNEXPECTED(leftType.isUndef())) return zv::Val();
				right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				zv::Val rightType = getType(right);
				if (UNEXPECTED(rightType.isUndef())) return zv::Val();
				zv::Val typeResult = identicalResult(OBJ_PROP_NUM(self, slots::richerScopeGetTypeHelper), kind == KIND_NOT_IDENTICAL, scope, expr, nodeScopeResolver, leftType.raw(), rightType.raw());
				if (UNEXPECTED(typeResult.isUndef())) return zv::Val();
				return typeResultType(typeResult.raw());
			}
			case KIND_LOGICAL_XOR: {
				zv::Val leftType = getType(left);
				if (UNEXPECTED(leftType.isUndef())) return zv::Val();
				zv::Val leftBooleanType = toBoolean(leftType.raw());
				if (UNEXPECTED(leftBooleanType.isUndef())) return zv::Val();
				right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				zv::Val rightType = getType(right);
				if (UNEXPECTED(rightType.isUndef())) return zv::Val();
				zv::Val rightBooleanType = toBoolean(rightType.raw());
				if (UNEXPECTED(rightBooleanType.isUndef())) return zv::Val();
				if (isConstantBoolean(leftBooleanType.raw()) && isConstantBoolean(rightBooleanType.raw())) {
					bool leftValue, rightValue;
					if (UNEXPECTED(!constantBooleanValue(leftBooleanType.raw(), leftValue) || !constantBooleanValue(rightBooleanType.raw(), rightValue))) return zv::Val();
					return ptoh::constantBoolean(leftValue != rightValue);
				}

				return ptoh::booleanType();
			}
			case KIND_CONCAT: {
				// getConcatType($left, $right, $getType): the operand reads and
				// resolveConcatType(), without an engine frame between the levels
				// of a deep concatenation chain
				zv::Val leftType = getType(left);
				if (UNEXPECTED(leftType.isUndef())) return zv::Val();
				right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				zv::Val rightType = getType(right);
				if (UNEXPECTED(rightType.isUndef())) return zv::Val();
				return resolveConcatType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), leftType.raw(), rightType.raw());
			}
			case KIND_OTHER: {
				zend_string *className = Z_OBJCE_P(expr)->name;
				zend_class_entry *exceptionCe = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
				if (UNEXPECTED(exceptionCe == NULL)) return zv::Val();
				zend_throw_exception_ex(exceptionCe, 0, "Unhandled %s", ZSTR_VAL(className));
				return zv::Val();
			}
			default: {
				OperandTypeFrame frame{expr, leftResult, rightResult, nativeTypesPromoted, beforeScope, nodeScopeResolver};
				pt_ietr_get_type getTypeCallback{&operandTypeCallback, &frame, &operandTypeCallable};
				return operatorType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), (uint8_t) kind, left, right, getTypeCallback);
			}
		}
	}

	/* $value instanceof ConstantBooleanType */
	static bool isConstantBoolean(zval *value)
	{
		return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), pt_ce_constant_boolean_type);
	}

	/* $value->getValue() of a ConstantBooleanType; false = pending exception */
	[[nodiscard]] static bool constantBooleanValue(zval *value, bool &out)
	{
		if (EXPECTED(Z_OBJCE_P(value) == pt_ce_constant_boolean_type)) return pt_constant_boolean_type_value(Z_OBJ_P(value), out);
		zv::Val result = pt_type_op(Z_OBJ_P(value), PT_OP_GET_VALUE, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $type->toBoolean() (an exact ConstantBooleanType is its own) */
	static zv::Val toBoolean(zval *type)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type));
			return zv::Val();
		}
		if (Z_OBJCE_P(type) == pt_ce_constant_boolean_type) return zv::Val::copyOf(zv::Ref(type));
		return pt_type_call(Z_OBJ_P(type), PT_LC("toboolean"), 0, NULL);
	}

	/* }}} */

	/* {{{ the specifyTypesCallback */

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $leftResult, $rightResult, $nodeScopeResolver, $beforeScope,
	 * $specifySubResults, $leftArgResult, $rightArgResult, $typeCallback):
	 * SpecifiedTypes — captures: $this and those, in that order */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zv::Val specifiedTypes = BinaryOpHandler(Z_OBJ(captures[0])).specifyTypes(captures, &argv[0], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted) const
	{
		zval *expr = &captures[1];
		zval *leftResult = &captures[2];
		zval *rightResult = &captures[3];
		zval *nodeScopeResolver = &captures[4];
		zval *beforeScope = &captures[5];
		zval *leftArgResult = &captures[7];
		zval *rightArgResult = &captures[8];
		zval *typeCallback = &captures[9];
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);

		zv::Val scopeHold;
		zval *scope = beforeScope;
		if (nativeTypesPromoted) {
			scopeHold = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(scopeHold.isUndef())) return zv::Val();
			scope = scopeHold.raw();
		}
		int kind = kindOf(expr);
		if (UNEXPECTED(kind < 0)) return zv::Val();
		switch (kind) {
			case KIND_IDENTICAL:
			case KIND_NOT_IDENTICAL:
			case KIND_EQUAL:
			case KIND_NOT_EQUAL: {
				bool negated = kind == KIND_NOT_IDENTICAL || kind == KIND_NOT_EQUAL;
				// `!==` / `!=` narrowing is the `===` / `==` narrowing in the
				// negated context - no synthetic Identical node. A null context
				// never negates.
				bool contextNull;
				if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
				if (contextNull && negated) return pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);

				zval *left = ptoh::binaryOpLeft(expr);
				if (UNEXPECTED(left == NULL)) return zv::Val();
				zval *right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				zv::Val negatedContext;
				zval *narrowingContext = context;
				if (negated) {
					negatedContext = pt_type_specifier_context_negate(Z_OBJ_P(context));
					if (UNEXPECTED(negatedContext.isUndef())) return zv::Val();
					narrowingContext = negatedContext.raw();
				}
				zval *identicalNarrowingHelper = OBJ_PROP_NUM(self, slots::identicalNarrowingHelper);
				zv::Val newWorldTypes;
				if (kind == KIND_IDENTICAL || kind == KIND_NOT_IDENTICAL) {
					// the comparison's own verdict, in Identical semantics -
					// computed from the captured operand results (the walk's
					// evaluation point), only the flavour follows the ask
					zv::Val identicalTypeCallback = pt_native_closure(&identicalTypeCallbackBody, expr, nativeTypesPromoted, typeCallback);
					newWorldTypes = pt_identical_narrowing_helper_specify_identical(identicalNarrowingHelper, nodeScopeResolver, left, right, leftResult, rightResult, narrowingContext, scope, leftArgResult, rightArgResult, identicalTypeCallback.raw());
				} else {
					newWorldTypes = pt_identical_narrowing_helper_specify_equal(identicalNarrowingHelper, nodeScopeResolver, left, right, leftResult, rightResult, narrowingContext, scope, leftArgResult, rightArgResult);
				}
				if (UNEXPECTED(newWorldTypes.isUndef())) return zv::Val();
				// null = no shape-specific narrowing (unknown-class ::class,
				// null-context asks) - the default is all that remains
				if (newWorldTypes.isNull()) {
					newWorldTypes = pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);
				}
				return setRootExpr(std::move(newWorldTypes), expr);
			}
			case KIND_SMALLER:
			case KIND_SMALLER_OR_EQUAL:
				return specifySmaller(captures, scope, context, nativeTypesPromoted, kind == KIND_SMALLER_OR_EQUAL);
			case KIND_GREATER:
			case KIND_GREATER_OR_EQUAL: {
				zval *right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				zval *left = ptoh::binaryOpLeft(expr);
				if (UNEXPECTED(left == NULL)) return zv::Val();
				zv::Args nodeArgv{right, left};
				zv::Val swapped = pt_type_new(kind == KIND_GREATER ? PT_CLASS_SMALLER_EXPR : PT_CLASS_SMALLER_OR_EQUAL_EXPR, 2, nodeArgv);
				if (UNEXPECTED(swapped.isUndef())) return zv::Val();
				return setRootExpr(pt_default_narrowing_helper_specify_types_for_node(defaultNarrowingHelper, scope, swapped.raw(), context), expr);
			}
			default:
				return pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);
		}
	}

	/* static function () use ($expr, $nativeTypesPromoted, $typeCallback):
	 * Type — captures in that order */
	static void identicalTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Args promoted{Z_TYPE(captures[1]) == IS_TRUE};
		zv::Val ownType = pt_type_call_callable(&captures[2], 1, promoted);
		if (UNEXPECTED(ownType.isUndef())) return;
		int kind = kindOf(&captures[0]);
		if (UNEXPECTED(kind < 0)) return;
		if (kind == KIND_NOT_IDENTICAL) {
			int isTrue = ptoh::typeVerdict(ownType.raw(), true);
			if (UNEXPECTED(isTrue < 0)) return;
			if (isTrue) {
				zv::Val verdict = ptoh::constantBoolean(false);
				if (UNEXPECTED(verdict.isUndef())) return;
				verdict.intoReturnValue(return_value);
				return;
			}
			int isFalse = ptoh::typeVerdict(ownType.raw(), false);
			if (UNEXPECTED(isFalse < 0)) return;
			if (isFalse) {
				zv::Val verdict = ptoh::constantBoolean(true);
				if (UNEXPECTED(verdict.isUndef())) return;
				verdict.intoReturnValue(return_value);
				return;
			}
		}

		ownType.intoReturnValue(return_value);
	}

	/* the specify callback's $getType: the operands' own types (in the asked
	 * flavour), a captured operand subexpression's result on the asking
	 * scope */
	static zv::Val specifyOperandType(zval *captures, zval *scope, bool nativeTypesPromoted, zval *e)
	{
		zval *expr = &captures[1];
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		if (Z_TYPE_P(e) == IS_OBJECT && Z_OBJ_P(e) == Z_OBJ_P(left)) return nativeTypesPromoted ? pt_expression_result_get_native_type(&captures[2]) : pt_expression_result_get_type(&captures[2]);
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		if (Z_TYPE_P(e) == IS_OBJECT && Z_OBJ_P(e) == Z_OBJ_P(right)) return nativeTypesPromoted ? pt_expression_result_get_native_type(&captures[3]) : pt_expression_result_get_type(&captures[3]);

		// the remaining asks are operand subexpressions whose walk
		// results were captured at creation
		zval *specifySubResults = &captures[6];
		zval *result = Z_TYPE_P(e) == IS_OBJECT && Z_TYPE_P(specifySubResults) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(specifySubResults), Z_OBJ_HANDLE_P(e)) : NULL;
		if (result == NULL || Z_TYPE_P(result) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		bool scopePromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), scopePromoted))) return zv::Val();
		return pt_expression_result_get_type_on_scope(result, scope, scopePromoted);
	}

	/* `$context->true() && IntegerRangeType::createAllGreaterThanOrEqualTo(1 -
	 * $offset)->isSuperTypeOf($leftType)->yes() || ($context->false() && (new
	 * ConstantIntegerType(1 - $offset))->isSuperTypeOf($leftType)->yes())`;
	 * -1 = pending exception */
	static int atLeastOneMatches(zval *context, zend_long offset, zval *leftType)
	{
		bool contextTrue;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return -1;
		if (contextTrue) {
			zv::Val atLeast = greaterThan(1 - offset, true);
			if (UNEXPECTED(atLeast.isUndef())) return -1;
			int yes = superTypeVerdict(atLeast.raw(), leftType, PT_TRI_YES);
			if (yes != 0) return yes;
		}
		bool contextFalse;
		if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), contextFalse))) return -1;
		if (!contextFalse) return 0;
		zv::Val exactly = pt_type_new_constant_integer(1 - offset);
		if (UNEXPECTED(exactly.isUndef())) return -1;
		return superTypeVerdict(exactly.raw(), leftType, PT_TRI_YES);
	}

	/* IntegerRangeType::fromInterval($min, $max)->isSuperTypeOf($type)->yes();
	 * -1 = pending exception */
	static int rangeContains(phpstanturbo::NullableLong min, phpstanturbo::NullableLong max, zval *type)
	{
		zv::Val range = pt_integer_range_from_interval(min, max, 0);
		if (UNEXPECTED(range.isUndef())) return -1;
		return superTypeVerdict(range.raw(), type, PT_TRI_YES);
	}

	/* $leftType instanceof ConstantIntegerType, and its value */
	[[nodiscard]] static bool constantIntegerValue(zval *type, bool &is, zend_long &value)
	{
		is = Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type);
		if (!is) return true;
		return pt_constant_integer_get_value(Z_OBJ_P(type), value);
	}

	/* $type->get<Comparison>Type($this->phpVersion) */
	zv::Val comparisonType(zval *type, const char *lcname, size_t len) const
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(type));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(type), lcname, len, 1, OBJ_PROP_NUM(self, slots::phpVersion));
	}

	/* $this->defaultNarrowingHelper->createForSubject($subject, $type,
	 * $context, $scope)->setRootExpr($expr) */
	zv::Val subjectTypes(zval *subject, zval *type, zval *context, zval *scope, zval *expr) const
	{
		return setRootExpr(pt_default_narrowing_helper_create_for_subject(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), subject, type, context, scope), expr);
	}

	/* the Smaller / SmallerOrEqual branch of the specifyTypesCallback */
	zv::Val specifySmaller(zval *captures, zval *scope, zval *context, bool nativeTypesPromoted, bool orEqual) const
	{
		zval *expr = &captures[1];
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		zval *countNarrowingHelper = OBJ_PROP_NUM(self, slots::countNarrowingHelper);

		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		{
			NamedCall leftCall;
			int leftIsCall = namedCall(left, true, leftCall);
			if (UNEXPECTED(leftIsCall < 0)) return zv::Val();
			int leftMatches = callMatches(leftIsCall, leftCall, { "count", "sizeof", "strlen", "mb_strlen", "preg_match" }, ARITY_AT_LEAST, 1);
			if (UNEXPECTED(leftMatches < 0)) return zv::Val();
			if (leftMatches) {
				zval *right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				NamedCall rightCall;
				int rightIsCall = namedCall(right, false, rightCall);
				if (UNEXPECTED(rightIsCall < 0)) return zv::Val();
				if (!rightIsCall || !lowerNameIn(rightCall.name.get(), { "count", "sizeof", "strlen", "mb_strlen", "preg_match" })) {
					zv::Args inverseArgv{right, left};
					zv::Val inverseOperator = pt_type_new(orEqual ? PT_CLASS_SMALLER_EXPR : PT_CLASS_SMALLER_OR_EQUAL_EXPR, 2, inverseArgv);
					if (UNEXPECTED(inverseOperator.isUndef())) return zv::Val();

					// negating the context is exactly what a BooleanNot around the
					// inverse operator would do - direct computation avoids
					// synthesizing a BooleanNot node. A null context never negates
					// (BooleanNot defaults on it too).
					bool contextNull;
					if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
					if (contextNull) return pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);

					zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(context));
					if (UNEXPECTED(negated.isUndef())) return zv::Val();
					return setRootExpr(pt_default_narrowing_helper_specify_types_for_node(defaultNarrowingHelper, scope, inverseOperator.raw(), negated.raw()), expr);
				}
			}
		}

		zend_long offset = orEqual ? 0 : 1;
		// the operands were processed during processExpr; read their
		// already computed results instead of re-walking via
		// Scope::getType(). Their subexpressions (e.g. count() arguments)
		// were also processed and are read from the stored result.
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val leftType = specifyOperandType(captures, scope, nativeTypesPromoted, left);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(leftType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isInteger() on %s", zend_zval_value_name(leftType.raw()));
			return zv::Val();
		}
		zv::Val result = pt_specified_types_new_with_root_expr(NULL, NULL, expr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		bool contextNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
		zend_object *trueContext = pt_type_specifier_context_create_true();
		if (UNEXPECTED(trueContext == NULL)) return zv::Val();
		zval trueContextZval = contextZval(trueContext);
		zend_object *truthyContext = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthyContext == NULL)) return zv::Val();
		zval truthyContextZval = contextZval(truthyContext);

		if (!contextNull) {
			zval *right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			NamedCall rightCall;
			int rightIsCall = namedCall(right, true, rightCall);
			if (UNEXPECTED(rightIsCall < 0)) return zv::Val();
			int leftIsInteger = callMatches(rightIsCall, rightCall, { "count", "sizeof" }, ARITY_AT_LEAST, 1);
			if (UNEXPECTED(leftIsInteger < 0)) return zv::Val();
			if (leftIsInteger) {
				leftIsInteger = trinaryYes(leftType.raw(), PT_OP_IS_INTEGER);
				if (UNEXPECTED(leftIsInteger < 0)) return zv::Val();
			}
			if (leftIsInteger) {
				zval *argValue = firstArgValue(rightCall);
				if (UNEXPECTED(argValue == NULL)) return zv::Val();
				zv::Val argType = specifyOperandType(captures, scope, nativeTypesPromoted, argValue);
				if (UNEXPECTED(argType.isUndef())) return zv::Val();

				zv::Val sizeType;
				bool leftIsConstant;
				zend_long leftValue = 0;
				if (UNEXPECTED(!constantIntegerValue(leftType.raw(), leftIsConstant, leftValue))) return zv::Val();
				if (leftIsConstant) {
					sizeType = greaterThan(leftValue, orEqual);
					if (UNEXPECTED(sizeType.isUndef())) return zv::Val();
				} else if (instanceof_function(Z_OBJCE_P(leftType.raw()), pt_ce_integer_range_type)) {
					phpstanturbo::NullableLong min = phpstanturbo::NullableLong::null();
					phpstanturbo::NullableLong max = phpstanturbo::NullableLong::null();
					bool falsey;
					if (UNEXPECTED(!pt_type_specifier_context_falsey(Z_OBJ_P(context), falsey))) return zv::Val();
					if (falsey && UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(leftType.raw()), min, max))) return zv::Val();
					if (falsey && !max.isNull) {
						sizeType = greaterThan(max.value, orEqual);
						if (UNEXPECTED(sizeType.isUndef())) return zv::Val();
					} else {
						bool truthy;
						if (UNEXPECTED(!pt_type_specifier_context_truthy(Z_OBJ_P(context), truthy))) return zv::Val();
						if (truthy && UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(leftType.raw()), min, max))) return zv::Val();
						if (truthy && !min.isNull) {
							sizeType = greaterThan(min.value, orEqual);
							if (UNEXPECTED(sizeType.isUndef())) return zv::Val();
						}
					}
				} else {
					sizeType = zv::Val::copyOf(leftType.ref());
				}

				if (!sizeType.isUndef()) {
					right = ptoh::binaryOpRight(expr);
					if (UNEXPECTED(right == NULL)) return zv::Val();
					zv::Args countArgv{right, argType.raw(), sizeType.raw(), context, scope, expr};
					zv::Val specifiedTypes = specifyCountSize(countNarrowingHelper, countArgv);
					if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
					if (!specifiedTypes.isNull() && UNEXPECTED(!unionInto(result, std::move(specifiedTypes)))) return zv::Val();
				}

				int atLeastOne = atLeastOneMatches(context, offset, leftType.raw());
				if (UNEXPECTED(atLeastOne < 0)) return zv::Val();
				if (atLeastOne) {
					bool truthy;
					if (UNEXPECTED(!pt_type_specifier_context_truthy(Z_OBJ_P(context), truthy))) return zv::Val();
					zend_long argIsArray = PT_TRI_NO;
					if (truthy) {
						argIsArray = pt_type_op_trinary(Z_OBJ_P(argType.raw()), PT_OP_IS_ARRAY, 0, NULL);
						if (UNEXPECTED(argIsArray < 0)) return zv::Val();
					}
					if (truthy && argIsArray == PT_TRI_MAYBE) {
						zv::Arr countables = zv::Arr::empty();
						if (instanceof_function(Z_OBJCE_P(argType.raw()), pt_ce_union_type)) {
							zv::Val className = zv::Val::string(pt_boh_countable);
							zv::Val countableInterface = pt_type_new_object_type(className.raw());
							if (UNEXPECTED(countableInterface.isUndef())) return zv::Val();
							zv::Val innerTypes = pt_type_op(Z_OBJ_P(argType.raw()), PT_OP_GET_TYPES, 0, NULL);
							if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
							if (UNEXPECTED(Z_TYPE_P(innerTypes.raw()) != IS_ARRAY)) {
								zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(innerTypes.raw()));
								if (UNEXPECTED(EG(exception))) return zv::Val();
							} else {
								for (auto entry : zv::TableRef(Z_ARRVAL_P(innerTypes.raw()))) {
									zv::Val innerType = zv::Val::copyOf(entry.value().deref());
									if (UNEXPECTED(!innerType.ref().isObject())) {
										zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(innerType.raw()));
										return zv::Val();
									}
									int innerIsArray = trinaryYes(innerType.raw(), PT_OP_IS_ARRAY);
									if (UNEXPECTED(innerIsArray < 0)) return zv::Val();
									if (innerIsArray) {
										zv::Val nonEmptyArray = pt_type_new_shadowed(&pt_non_empty_array_type_new);
										if (UNEXPECTED(nonEmptyArray.isUndef())) return zv::Val();
										zv::Args intersectArgv{nonEmptyArray.raw(), innerType.raw()};
										innerType = pt_type_combinator_intersect(2, intersectArgv);
										if (UNEXPECTED(innerType.isUndef())) return zv::Val();
										countables.push(innerType.ref());
									}

									int countable = superTypeVerdict(countableInterface.raw(), innerType.raw(), PT_TRI_YES);
									if (UNEXPECTED(countable < 0)) return zv::Val();
									if (!countable) continue;

									countables.push(std::move(innerType));
								}
							}
						}

						HashTable *countablesTable = countables.table();
						if (zend_hash_num_elements(countablesTable) > 0) {
							zv::Val countableType = pt_type_combinator_union(zend_hash_num_elements(countablesTable), countablesTable->arPacked);
							if (UNEXPECTED(countableType.isUndef())) return zv::Val();
							return subjectTypes(argValue, countableType.raw(), context, scope, expr);
						}
					}

					int argIsArrayYes = trinaryYes(argType.raw(), PT_OP_IS_ARRAY);
					if (UNEXPECTED(argIsArrayYes < 0)) return zv::Val();
					if (argIsArrayYes) {
						zv::Val newType = pt_type_new_shadowed(&pt_non_empty_array_type_new);
						if (UNEXPECTED(newType.isUndef())) return zv::Val();
						bool contextTrue;
						if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
						int argIsList = 0;
						if (contextTrue) {
							argIsList = trinaryYes(argType.raw(), PT_OP_IS_LIST);
							if (UNEXPECTED(argIsList < 0)) return zv::Val();
						}
						if (argIsList) {
							zv::Val listType = pt_type_new_shadowed(&pt_accessory_array_list_type_new);
							if (UNEXPECTED(listType.isUndef())) return zv::Val();
							zv::Args intersectArgv{newType.raw(), listType.raw()};
							newType = pt_type_combinator_intersect(2, intersectArgv);
							if (UNEXPECTED(newType.isUndef())) return zv::Val();
						}

						if (UNEXPECTED(!unionInto(result, subjectTypes(argValue, newType.raw(), context, scope, expr)))) return zv::Val();
					}
				}

				// infer $list[$index] after $index < count($list)
				bool contextTrue;
				if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
				if (contextTrue && !orEqual && !leftIsConstant) {
					int argIsList = trinaryYes(argType.raw(), PT_OP_IS_LIST);
					if (UNEXPECTED(argIsList < 0)) return zv::Val();
					int nonNegative = 0;
					if (argIsList) {
						nonNegative = rangeContains(phpstanturbo::NullableLong::of(0), phpstanturbo::NullableLong::null(), leftType.raw());
						if (UNEXPECTED(nonNegative < 0)) return zv::Val();
					}
					if (nonNegative) {
						zv::Val dimFetch = newArrayDimFetch(argValue, expr);
						if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
						zv::Val valueType = pt_type_op(Z_OBJ_P(argType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
						if (UNEXPECTED(valueType.isUndef())) return zv::Val();
						if (UNEXPECTED(!unionInto(result, subjectTypes(dimFetch.raw(), valueType.raw(), &trueContextZval, scope, expr)))) return zv::Val();
					}
				}
			}
		}

		// infer $list[$index] after $zeroOrMore < count($list) - N
		// infer $list[$index] after $zeroOrMore <= count($list) - N
		bool contextTrue;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
		if (contextTrue) {
			zval *right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			int rightIsMinus = ptoh::isInstance(right, PT_CLASS_BINARY_OP_MINUS);
			if (UNEXPECTED(rightIsMinus < 0)) return zv::Val();
			if (rightIsMinus) {
				zval *minuend = ptoh::binaryOpLeft(right);
				if (UNEXPECTED(minuend == NULL)) return zv::Val();
				NamedCall countCall;
				int minuendIsCall = namedCall(minuend, true, countCall);
				if (UNEXPECTED(minuendIsCall < 0)) return zv::Val();
				int matches = callMatches(minuendIsCall, countCall, { "count", "sizeof" }, ARITY_AT_LEAST, 1);
				if (UNEXPECTED(matches < 0)) return zv::Val();
				if (matches) {
					bool leftIsConstant;
					zend_long leftValue;
					if (UNEXPECTED(!constantIntegerValue(leftType.raw(), leftIsConstant, leftValue))) return zv::Val();
					matches = leftIsConstant ? 0 : trinaryYes(leftType.raw(), PT_OP_IS_INTEGER);
					if (UNEXPECTED(matches < 0)) return zv::Val();
				}
				if (matches) {
					matches = rangeContains(phpstanturbo::NullableLong::of(0), phpstanturbo::NullableLong::null(), leftType.raw());
					if (UNEXPECTED(matches < 0)) return zv::Val();
				}
				if (matches) {
					zval *countArg = firstArgValue(countCall);
					if (UNEXPECTED(countArg == NULL)) return zv::Val();
					zv::Val countArgType = specifyOperandType(captures, scope, nativeTypesPromoted, countArg);
					if (UNEXPECTED(countArgType.isUndef())) return zv::Val();
					zval *subtrahend = ptoh::binaryOpRight(right);
					if (UNEXPECTED(subtrahend == NULL)) return zv::Val();
					zv::Val subtractedType = specifyOperandType(captures, scope, nativeTypesPromoted, subtrahend);
					if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
					if (UNEXPECTED(!countArgType.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function isList() on %s", zend_zval_value_name(countArgType.raw()));
						return zv::Val();
					}
					int holds = trinaryYes(countArgType.raw(), PT_OP_IS_LIST);
					if (UNEXPECTED(holds < 0)) return zv::Val();
					if (holds) {
						holds = isNormalCountCall(countNarrowingHelper, minuend, countArgType.raw(), scope);
						if (UNEXPECTED(holds < 0)) return zv::Val();
					}
					if (holds) {
						holds = rangeContains(phpstanturbo::NullableLong::of(1), phpstanturbo::NullableLong::null(), subtractedType.raw());
						if (UNEXPECTED(holds < 0)) return zv::Val();
					}
					if (holds) {
						countArg = firstArgValue(countCall);
						if (UNEXPECTED(countArg == NULL)) return zv::Val();
						zv::Val dimFetch = newArrayDimFetch(countArg, expr);
						if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
						zv::Val valueType = pt_type_op(Z_OBJ_P(countArgType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
						if (UNEXPECTED(valueType.isUndef())) return zv::Val();
						if (UNEXPECTED(!unionInto(result, subjectTypes(dimFetch.raw(), valueType.raw(), &trueContextZval, scope, expr)))) return zv::Val();
					}
				}
			}
		}

		if (!contextNull) {
			zval *right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			NamedCall rightCall;
			int rightIsCall = namedCall(right, true, rightCall);
			if (UNEXPECTED(rightIsCall < 0)) return zv::Val();
			int pregMatch = callMatches(rightIsCall, rightCall, { "preg_match" }, ARITY_AT_LEAST, 3);
			if (UNEXPECTED(pregMatch < 0)) return zv::Val();
			if (pregMatch) {
				int matches = rangeContains(phpstanturbo::NullableLong::of(1), phpstanturbo::NullableLong::null(), leftType.raw());
				if (UNEXPECTED(matches < 0)) return zv::Val();
				if (!matches && !orEqual) {
					matches = rangeContains(phpstanturbo::NullableLong::of(0), phpstanturbo::NullableLong::null(), leftType.raw());
					if (UNEXPECTED(matches < 0)) return zv::Val();
				}
				if (matches) {
					// 0 < preg_match or 1 <= preg_match becomes 1 === preg_match
					zval one;
					ZVAL_LONG(&one, 1);
					zv::Val oneNode = pt_type_new(PT_CLASS_SCALAR_INT, 1, &one);
					if (UNEXPECTED(oneNode.isUndef())) return zv::Val();
					zv::Args identicalArgv{right, oneNode.raw()};
					zv::Val newExpr = pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
					if (UNEXPECTED(newExpr.isUndef())) return zv::Val();
					return setRootExpr(pt_default_narrowing_helper_specify_types_for_node(defaultNarrowingHelper, scope, newExpr.raw(), context), expr);
				}
			}
		}

		if (!contextNull) {
			zval *right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			NamedCall rightCall;
			int rightIsCall = namedCall(right, true, rightCall);
			if (UNEXPECTED(rightIsCall < 0)) return zv::Val();
			int matches = callMatches(rightIsCall, rightCall, { "strlen", "mb_strlen" }, ARITY_EXACTLY, 1);
			if (UNEXPECTED(matches < 0)) return zv::Val();
			if (matches) {
				matches = trinaryYes(leftType.raw(), PT_OP_IS_INTEGER);
				if (UNEXPECTED(matches < 0)) return zv::Val();
			}
			if (matches) {
				matches = atLeastOneMatches(context, offset, leftType.raw());
				if (UNEXPECTED(matches < 0)) return zv::Val();
			}
			if (matches) {
				zval *argValue = firstArgValue(rightCall);
				if (UNEXPECTED(argValue == NULL)) return zv::Val();
				zv::Val argType = specifyOperandType(captures, scope, nativeTypesPromoted, argValue);
				if (UNEXPECTED(argType.isUndef())) return zv::Val();
				if (UNEXPECTED(!argType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function isString() on %s", zend_zval_value_name(argType.raw()));
					return zv::Val();
				}
				int argIsString = trinaryYes(argType.raw(), PT_OP_IS_STRING);
				if (UNEXPECTED(argIsString < 0)) return zv::Val();
				if (argIsString) {
					zv::Val accessory = pt_type_new_shadowed(&pt_accessory_non_empty_string_type_new);
					if (UNEXPECTED(accessory.isUndef())) return zv::Val();
					zv::Val atLeastTwo = greaterThan(2 - offset, true);
					if (UNEXPECTED(atLeastTwo.isUndef())) return zv::Val();
					int nonFalsy = superTypeVerdict(atLeastTwo.raw(), leftType.raw(), PT_TRI_YES);
					if (UNEXPECTED(nonFalsy < 0)) return zv::Val();
					if (nonFalsy) {
						accessory = pt_type_new_shadowed(&pt_accessory_non_falsy_string_type_new);
						if (UNEXPECTED(accessory.isUndef())) return zv::Val();
					}

					if (UNEXPECTED(!unionInto(result, subjectTypes(argValue, accessory.raw(), context, scope, expr)))) return zv::Val();
				}
			}
		}

		{
			bool leftIsConstant;
			zend_long leftValue = 0;
			if (UNEXPECTED(!constantIntegerValue(leftType.raw(), leftIsConstant, leftValue))) return zv::Val();
			if (leftIsConstant) {
				zval *right = ptoh::binaryOpRight(expr);
				if (UNEXPECTED(right == NULL)) return zv::Val();
				if (UNEXPECTED(!unionIncDecRange(result, expr, right, phpstanturbo::NullableLong::of(leftValue), phpstanturbo::NullableLong::null(), offset + 1, offset - 1, offset, context))) return zv::Val();
			}
		}

		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val rightType = specifyOperandType(captures, scope, nativeTypesPromoted, right);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();
		{
			bool rightIsConstant;
			zend_long rightValue = 0;
			if (UNEXPECTED(!constantIntegerValue(rightType.raw(), rightIsConstant, rightValue))) return zv::Val();
			if (rightIsConstant) {
				left = ptoh::binaryOpLeft(expr);
				if (UNEXPECTED(left == NULL)) return zv::Val();
				if (UNEXPECTED(!unionIncDecRange(result, expr, left, phpstanturbo::NullableLong::null(), phpstanturbo::NullableLong::of(rightValue), -offset + 1, -offset - 1, -offset, context))) return zv::Val();
			}
		}

		bool contextFalse = false;
		if (!contextTrue && UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), contextFalse))) return zv::Val();
		if (contextTrue || contextFalse) {
			left = ptoh::binaryOpLeft(expr);
			if (UNEXPECTED(left == NULL)) return zv::Val();
			int leftIsScalar = isScalarOrNegatedScalar(left);
			if (UNEXPECTED(leftIsScalar < 0)) return zv::Val();
			if (!leftIsScalar) {
				zv::Val narrowed = contextTrue
					? (orEqual ? comparisonType(rightType.raw(), PT_LC("getsmallerorequaltype")) : comparisonType(rightType.raw(), PT_LC("getsmallertype")))
					: (orEqual ? comparisonType(rightType.raw(), PT_LC("getgreatertype")) : comparisonType(rightType.raw(), PT_LC("getgreaterorequaltype")));
				if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
				if (UNEXPECTED(!unionInto(result, subjectTypes(left, narrowed.raw(), &truthyContextZval, scope, expr)))) return zv::Val();
			}
			right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			int rightIsScalar = isScalarOrNegatedScalar(right);
			if (UNEXPECTED(rightIsScalar < 0)) return zv::Val();
			if (!rightIsScalar) {
				zv::Val narrowed = contextTrue
					? (orEqual ? comparisonType(leftType.raw(), PT_LC("getgreaterorequaltype")) : comparisonType(leftType.raw(), PT_LC("getgreatertype")))
					: (orEqual ? comparisonType(leftType.raw(), PT_LC("getsmallertype")) : comparisonType(leftType.raw(), PT_LC("getsmallerorequaltype")));
				if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
				if (UNEXPECTED(!unionInto(result, subjectTypes(right, narrowed.raw(), &truthyContextZval, scope, expr)))) return zv::Val();
			}
		}

		return result;
	}

	/* new ArrayDimFetch($arrayArg, $expr->left) */
	static zv::Val newArrayDimFetch(zval *arrayArg, zval *expr)
	{
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Args argv{arrayArg, left};
		return pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, argv);
	}

	/* the Post/Pre Inc/Dec block over one side: $result =
	 * $result->unionWith($this->createRangeTypes($expr, $side->var,
	 * IntegerRangeType::fromInterval($min, $max, <shift>), $context)) with the
	 * PostInc / PostDec / PreInc|PreDec shift; false = pending exception */
	[[nodiscard]] bool unionIncDecRange(zv::Val &result, zval *expr, zval *side, phpstanturbo::NullableLong min, phpstanturbo::NullableLong max, zend_long postIncShift, zend_long postDecShift, zend_long preShift, zval *context) const
	{
		static constexpr int classes[4] = { PT_CLASS_POST_INC, PT_CLASS_POST_DEC, PT_CLASS_PRE_INC, PT_CLASS_PRE_DEC };
		NodeProp *vars[4] = { &pt_boh_post_inc_var, &pt_boh_post_dec_var, &pt_boh_pre_inc_var, &pt_boh_pre_dec_var };
		for (int i = 0; i < 4; i++) {
			int is = ptoh::isInstance(side, classes[i]);
			if (UNEXPECTED(is < 0)) return false;
			if (!is) continue;
			zval *var = ptoh::operand(*vars[i], side);
			if (UNEXPECTED(var == NULL)) return false;
			zend_long shift = i == 0 ? postIncShift : (i == 1 ? postDecShift : preShift);
			zv::Val range = pt_integer_range_from_interval(min, max, shift);
			if (UNEXPECTED(range.isUndef())) return false;
			return unionInto(result, createRangeTypes(expr, var, range.raw(), context));
		}
		return true;
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::BinaryOpHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_binary_op_handler()
{
	pt_boh_division_by_zero_error = zend_string_init_interned(PT_LC("DivisionByZeroError"), 1);
	pt_boh_countable = zend_string_init_interned(PT_LC("Countable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\BinaryOpHandler");
	ptdecl::BinaryOpHandler::declareClass(cls);
	ptdecl::BinaryOpHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *richerScopeGetTypeHelper, *phpVersion, *implicitToStringCallHelper, *exprPrinter, *identicalNarrowingHelper, *countNarrowingHelper, *expressionResultFactory, *defaultNarrowingHelper;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(initializerExprTypeResolver)
			Z_PARAM_OBJECT(richerScopeGetTypeHelper)
			Z_PARAM_OBJECT(phpVersion)
			Z_PARAM_OBJECT(implicitToStringCallHelper)
			Z_PARAM_OBJECT(exprPrinter)
			Z_PARAM_OBJECT(identicalNarrowingHelper)
			Z_PARAM_OBJECT(countNarrowingHelper)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
		ZEND_PARSE_PARAMETERS_END();
		zval *services[9] = {initializerExprTypeResolver, richerScopeGetTypeHelper, phpVersion, implicitToStringCallHelper, exprPrinter, identicalNarrowingHelper, countNarrowingHelper, expressionResultFactory, defaultNarrowingHelper};
		BinaryOpHandler(Z_OBJ_P(ZEND_THIS)).construct(services);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!BinaryOpHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(BinaryOpHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::resolveEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *leftResult, *rightResult;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, leftResult, rightResult)) RETURN_THROWS();
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) RETURN_THROWS();
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) RETURN_THROWS();
		PT_RETURN_VAL(BinaryOpHandler(Z_OBJ_P(ZEND_THIS)).resolveEqualType(scope, left, right, leftResult, rightResult));
	});

	cls.method(sigs::createRangeTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *rootExpr, *expr, *type, *context;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT_OR_NULL(rootExpr)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(BinaryOpHandler(Z_OBJ_P(ZEND_THIS)).createRangeTypes(rootExpr, expr, type, context));
	});

	cls.shadow(&pt_ce_binary_op_handler);
	pt_expr_handler_entry_register(&pt_ce_binary_op_handler, &BinaryOpHandler::processExprEntry);
}

/* }}} */
