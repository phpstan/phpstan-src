/*
 * PHPStanTurbo\NullsafeOperatorHelper — native implementation of
 * PHPStan\Analyser\NullsafeOperatorHelper.
 *
 * The static helper rewriting a chained access below a nullsafe operator to
 * its plain form, asked for every call, fetch and offset the rules and the
 * type specifier look at (~65K per self-analysis, each walking the chain
 * with an attribute read and write per level). The chain walk, the
 * attribute memo and the checks are native; a rebuilt level constructs the
 * php-parser node through its constructor, as the twin does. Native callers
 * use pt_nullsafe_operator_helper_* (support.h).
 */

#include "support.h"
#include "generated/NullsafeOperatorHelper.h"

namespace sigs = ptdecl::NullsafeOperatorHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_nullsafe_operator_helper = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

constexpr const char *pt_noh_attribute = "phpstan_nullsafeShortcircuited";

/* the chained-access kinds, in the order the twin tests them */
enum ChainKind
{
	CHAIN_NONE = 0,
	CHAIN_NULLSAFE_METHOD_CALL,
	CHAIN_METHOD_CALL,
	CHAIN_ARRAY_DIM_FETCH,
	CHAIN_NULLSAFE_PROPERTY_FETCH,
	CHAIN_PROPERTY_FETCH,
	CHAIN_STATIC_CALL,
	CHAIN_STATIC_PROPERTY_FETCH,
};

NodeProp pt_noh_nullsafe_method_call_var = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "var");
NodeProp pt_noh_method_call_var = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "var");
NodeProp pt_noh_array_dim_fetch_var = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "var");
NodeProp pt_noh_nullsafe_property_fetch_var = PT_NODE_PROP(PT_CLASS_NULLSAFE_PROPERTY_FETCH, "var");
NodeProp pt_noh_property_fetch_var = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "var");
NodeProp pt_noh_static_call_class = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
NodeProp pt_noh_static_property_fetch_class = PT_NODE_PROP(PT_CLASS_STATIC_PROPERTY_FETCH, "class");

NodeProp pt_noh_nullsafe_method_call_name = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "name");
NodeProp pt_noh_nullsafe_method_call_args = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "args");
NodeProp pt_noh_nullsafe_property_fetch_name = PT_NODE_PROP(PT_CLASS_NULLSAFE_PROPERTY_FETCH, "name");
NodeProp pt_noh_method_call_name = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "name");
NodeProp pt_noh_array_dim_fetch_dim = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "dim");
NodeProp pt_noh_property_fetch_name = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "name");
NodeProp pt_noh_static_call_name = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
NodeProp pt_noh_static_property_fetch_name = PT_NODE_PROP(PT_CLASS_STATIC_PROPERTY_FETCH, "name");

pt_method_site pt_noh_method_call_get_args_site;
pt_method_site pt_noh_static_call_get_args_site;

inline bool isOf(zend_object *node, int classIdx)
{
	zend_class_entry *ce = pt_class_loaded(classIdx);
	return ce != NULL && instanceof_function(node->ce, ce);
}

/* the kind of a chained-access level (CHAIN_NONE for any other node) */
ChainKind kindOf(zend_object *node)
{
	if (isOf(node, PT_CLASS_NULLSAFE_METHOD_CALL)) return CHAIN_NULLSAFE_METHOD_CALL;
	if (isOf(node, PT_CLASS_METHOD_CALL)) return CHAIN_METHOD_CALL;
	if (isOf(node, PT_CLASS_ARRAY_DIM_FETCH)) return CHAIN_ARRAY_DIM_FETCH;
	if (isOf(node, PT_CLASS_NULLSAFE_PROPERTY_FETCH)) return CHAIN_NULLSAFE_PROPERTY_FETCH;
	if (isOf(node, PT_CLASS_PROPERTY_FETCH)) return CHAIN_PROPERTY_FETCH;
	if (isOf(node, PT_CLASS_STATIC_CALL)) return CHAIN_STATIC_CALL;
	if (isOf(node, PT_CLASS_STATIC_PROPERTY_FETCH)) return CHAIN_STATIC_PROPERTY_FETCH;
	return CHAIN_NONE;
}

/* $node->$property through the memoized offset, the engine's read (and its
 * Error for an uninitialized typed property) otherwise; the value is
 * borrowed from the node or kept in rv; NULL = pending exception */
zval *nodeProp(NodeProp &prop, zend_object *node, zval &rv)
{
	zval *slot = prop.of(node);
	if (EXPECTED(slot != NULL && Z_TYPE_P(slot) != IS_UNDEF)) return slot;
	if (UNEXPECTED(EG(exception))) return NULL;
	zval *value = zend_read_property(node->ce, node, prop.name, prop.nameLen, 0, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* the next level down (borrowed from the node, NULL at the root); failed =
 * pending exception */
zval *chainedInto(zend_object *node, ChainKind kind, zval &rv, bool &failed)
{
	failed = false;
	zval *next;
	switch (kind) {
		case CHAIN_NULLSAFE_METHOD_CALL: next = nodeProp(pt_noh_nullsafe_method_call_var, node, rv); break;
		case CHAIN_METHOD_CALL: next = nodeProp(pt_noh_method_call_var, node, rv); break;
		case CHAIN_ARRAY_DIM_FETCH: next = nodeProp(pt_noh_array_dim_fetch_var, node, rv); break;
		case CHAIN_NULLSAFE_PROPERTY_FETCH: next = nodeProp(pt_noh_nullsafe_property_fetch_var, node, rv); break;
		case CHAIN_PROPERTY_FETCH: next = nodeProp(pt_noh_property_fetch_var, node, rv); break;
		case CHAIN_STATIC_CALL:
		case CHAIN_STATIC_PROPERTY_FETCH: {
			next = nodeProp(kind == CHAIN_STATIC_CALL ? pt_noh_static_call_class : pt_noh_static_property_fetch_class, node, rv);
			if (next == NULL) break;
			zend_class_entry *exprCe = pt_class_loaded(PT_CLASS_EXPR);
			if (exprCe == NULL || Z_TYPE_P(next) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(next), exprCe)) return NULL;
			return next;
		}
		default:
			return NULL;
	}
	if (UNEXPECTED(next == NULL)) {
		failed = true;
		return NULL;
	}
	return next;
}

/* the chainedInto() method's `?Expr` return type */
[[nodiscard]] bool requireExprOrNull(zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_NULL)) return true;
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), exprCe))) return true;
	zend_type_error("PHPStan\\Analyser\\NullsafeOperatorHelper::chainedInto(): Return value must be of type ?PhpParser\\Node\\Expr, %s returned", zend_zval_value_name(value));
	return false;
}

/* $expr->getAttribute(SHORTCIRCUITED_ATTRIBUTE) === true; false = pending exception */
[[nodiscard]] bool isMarked(zend_object *node, bool &out)
{
	zv::Val marked = pt_engine_node_get_attribute(node, pt_noh_attribute, strlen(pt_noh_attribute));
	if (UNEXPECTED(marked.isUndef())) return false;
	out = Z_TYPE_P(marked.raw()) == IS_TRUE;
	return true;
}

[[nodiscard]] bool mark(zend_object *node)
{
	zval value;
	ZVAL_TRUE(&value);
	return pt_engine_node_set_attribute(node, pt_noh_attribute, strlen(pt_noh_attribute), &value);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\NullsafeOperatorHelper; UNDEF = pending exception. */
class NullsafeOperatorHelper
{
public:
	/* Mirrors getNullsafeShortcircuitedExprRespectingScope(). */
	static zv::Val getNullsafeShortcircuitedExprRespectingScope(zval *scope, zval *expr)
	{
		zv::Val shortcircuited = getNullsafeShortcircuitedExpr(expr);
		if (UNEXPECTED(shortcircuited.isUndef())) return zv::Val();
		if (Z_TYPE_P(shortcircuited.raw()) == IS_OBJECT && Z_OBJ_P(shortcircuited.raw()) == Z_OBJ_P(expr)) return shortcircuited;

		zv::Val type = pt_mutating_scope_get_type(Z_OBJ_P(scope), expr);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		bool containsNull;
		if (UNEXPECTED(!pt_type_combinator_contains_null(type.raw(), containsNull))) return zv::Val();
		if (!containsNull) return zv::Val::copyOf(zv::Ref(expr));
		return shortcircuited;
	}

	/* Mirrors getNullsafeShortcircuitedExpr(). */
	static zv::Val getNullsafeShortcircuitedExpr(zval *exprZv)
	{
		zend_object *expr = Z_OBJ_P(exprZv);
		bool marked;
		if (UNEXPECTED(!isMarked(expr, marked))) return zv::Val();
		if (marked) return zv::Val::copyOf(zv::Ref(exprZv));

		/* look for a nullsafe operator before building anything */
		zend_object *current = expr;
		zv::Val currentHold;
		for (;;) {
			ChainKind kind = kindOf(current);
			if (kind == CHAIN_NULLSAFE_METHOD_CALL || kind == CHAIN_NULLSAFE_PROPERTY_FETCH) break;
			zval rv;
			ZVAL_UNDEF(&rv);
			bool failed;
			zval *next = chainedInto(current, kind, rv, failed);
			zv::Val rvHold = zv::Val::adopt(rv);
			if (UNEXPECTED(failed)) return zv::Val();
			if (next != NULL && UNEXPECTED(!requireExprOrNull(next))) return zv::Val();
			if (next == NULL || Z_TYPE_P(next) == IS_NULL) {
				/* mark the levels on the way out */
				zend_object *level = expr;
				zv::Val levelHold;
				while (level != NULL) {
					if (UNEXPECTED(!mark(level))) return zv::Val();
					zval levelRv;
					ZVAL_UNDEF(&levelRv);
					bool levelFailed;
					zval *below = chainedInto(level, kindOf(level), levelRv, levelFailed);
					zv::Val levelRvHold = zv::Val::adopt(levelRv);
					if (UNEXPECTED(levelFailed)) return zv::Val();
					if (below != NULL && UNEXPECTED(!requireExprOrNull(below))) return zv::Val();
					if (below == NULL || Z_TYPE_P(below) != IS_OBJECT) break;
					levelHold = zv::Val::copyOf(zv::Ref(below));
					level = Z_OBJ_P(levelHold.raw());
				}
				return zv::Val::copyOf(zv::Ref(exprZv));
			}
			currentHold = zv::Val::copyOf(zv::Ref(next));
			current = Z_OBJ_P(currentHold.raw());
		}

		/* collect the chain of chained-access wrappers (outermost first) */
		zv::Arr chain = zv::Arr::create(4);
		zval exprCopy;
		ZVAL_OBJ(&exprCopy, expr);
		zv::Val walker = zv::Val::copyOf(zv::Ref(&exprCopy));
		for (;;) {
			zend_object *node = Z_OBJ_P(walker.raw());
			ChainKind kind = kindOf(node);
			if (kind == CHAIN_NONE) break;
			zval rv;
			ZVAL_UNDEF(&rv);
			bool failed;
			zval *next = chainedInto(node, kind, rv, failed);
			zv::Val rvHold = zv::Val::adopt(rv);
			if (UNEXPECTED(failed)) return zv::Val();
			if (next == NULL) break; /* a static access on a name */
			chain.push(zv::Ref(walker.raw()));
			if (UNEXPECTED(Z_TYPE_P(next) != IS_OBJECT)) {
				/* $current = $current->var holding a non-node: the loop's
				 * instanceof tests end the walk there */
				walker = zv::Val::copyOf(zv::Ref(next));
				break;
			}
			walker = zv::Val::copyOf(zv::Ref(next));
		}

		/* rebuild from innermost outward */
		zv::Val result = std::move(walker);
		bool changed = false;
		HashTable *table = chain.table();
		uint32_t count = zend_hash_num_elements(table);
		for (uint32_t i = count; i > 0; i--) {
			zval *nodeZv = zend_hash_index_find(table, i - 1);
			zend_object *node = Z_OBJ_P(nodeZv);
			ChainKind kind = kindOf(node);
			zv::Val rebuilt;
			if (kind == CHAIN_NULLSAFE_METHOD_CALL) {
				rebuilt = construct(PT_CLASS_METHOD_CALL, result.raw(), node, pt_noh_nullsafe_method_call_name, &pt_noh_nullsafe_method_call_args, NULL);
				changed = true;
			} else if (kind == CHAIN_NULLSAFE_PROPERTY_FETCH) {
				rebuilt = construct(PT_CLASS_PROPERTY_FETCH, result.raw(), node, pt_noh_nullsafe_property_fetch_name, NULL, NULL);
				changed = true;
			} else if (!changed) {
				rebuilt = zv::Val::copyOf(zv::Ref(nodeZv));
			} else if (kind == CHAIN_METHOD_CALL) {
				rebuilt = construct(PT_CLASS_METHOD_CALL, result.raw(), node, pt_noh_method_call_name, NULL, &pt_noh_method_call_get_args_site);
			} else if (kind == CHAIN_ARRAY_DIM_FETCH) {
				rebuilt = construct(PT_CLASS_ARRAY_DIM_FETCH, result.raw(), node, pt_noh_array_dim_fetch_dim, NULL, NULL);
			} else if (kind == CHAIN_PROPERTY_FETCH) {
				rebuilt = construct(PT_CLASS_PROPERTY_FETCH, result.raw(), node, pt_noh_property_fetch_name, NULL, NULL);
			} else if (kind == CHAIN_STATIC_CALL) {
				rebuilt = construct(PT_CLASS_STATIC_CALL, result.raw(), node, pt_noh_static_call_name, NULL, &pt_noh_static_call_get_args_site);
			} else if (kind == CHAIN_STATIC_PROPERTY_FETCH) {
				rebuilt = construct(PT_CLASS_STATIC_PROPERTY_FETCH, result.raw(), node, pt_noh_static_property_fetch_name, NULL, NULL);
			} else {
				rebuilt = std::move(result);
			}
			if (UNEXPECTED(rebuilt.isUndef())) return zv::Val();
			result = std::move(rebuilt);

			if (Z_TYPE_P(result.raw()) != IS_OBJECT || Z_OBJ_P(result.raw()) != node) continue;
			/* this level shortcircuits to itself */
			if (UNEXPECTED(!mark(node))) return zv::Val();
		}

		return result;
	}

private:
	/* new <class>($inner, $node-><second>, [$node->args | $node->getArgs()]) —
	 * the php-parser constructor; UNDEF = pending exception */
	static zv::Val construct(int classIdx, zval *inner, zend_object *node, NodeProp &second, NodeProp *argsProp, pt_method_site *getArgsSite)
	{
		zval secondRv;
		ZVAL_UNDEF(&secondRv);
		zval *secondValue = nodeProp(second, node, secondRv);
		zv::Val secondHold = zv::Val::adopt(secondRv);
		if (UNEXPECTED(secondValue == NULL)) return zv::Val();
		if (argsProp == NULL && getArgsSite == NULL) {
			zv::Args argv{inner, secondValue};
			return pt_type_new(classIdx, 2, argv);
		}
		zval argsRv;
		ZVAL_UNDEF(&argsRv);
		zv::Val argsHold;
		zval *args;
		if (argsProp != NULL) {
			args = nodeProp(*argsProp, node, argsRv);
			argsHold = zv::Val::adopt(argsRv);
			if (UNEXPECTED(args == NULL)) return zv::Val();
		} else {
			argsHold = pt_call_method_cached(*getArgsSite, node, PT_LC("getargs"), 0, NULL);
			if (UNEXPECTED(argsHold.isUndef())) return zv::Val();
			args = argsHold.raw();
		}
		zv::Args argv{inner, secondValue, args};
		return pt_type_new(classIdx, 3, argv);
	}
};

} // namespace phpstanturbo

using phpstanturbo::NullsafeOperatorHelper;

/* {{{ direct entries (support.h) */

zv::Val pt_nullsafe_operator_helper_get_nullsafe_shortcircuited_expr(zval *expr)
{
	return NullsafeOperatorHelper::getNullsafeShortcircuitedExpr(expr);
}

zv::Val pt_nullsafe_operator_helper_get_nullsafe_shortcircuited_expr_respecting_scope(zval *scope, zval *expr)
{
	return NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope(scope, expr);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_nullsafe_operator_helper()
{
	reg::Class cls("PHPStan\\Analyser\\NullsafeOperatorHelper");
	ptdecl::NullsafeOperatorHelper::declareClass(cls);
	cls.privateClassConstantString("SHORTCIRCUITED_ATTRIBUTE", pt_noh_attribute);
	ptdecl::NullsafeOperatorHelper::declareProperties(cls);

	cls.method(sigs::getNullsafeShortcircuitedExprRespectingScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_class(PT_CLASS_SCOPE))
			Z_PARAM_OBJECT_OF_CLASS(expr, pt_class(PT_CLASS_EXPR))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope(scope, expr));
	});

	cls.method(sigs::getNullsafeShortcircuitedExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(expr, pt_class(PT_CLASS_EXPR))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NullsafeOperatorHelper::getNullsafeShortcircuitedExpr(expr));
	});

	cls.shadow(&pt_ce_nullsafe_operator_helper);
}

/* }}} */
