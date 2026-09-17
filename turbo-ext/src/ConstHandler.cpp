/*
 * PHPStanTurbo\ConstHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ConstHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h).
 *
 * ExpressionResult, the contexts, MutatingScope, the statement results and
 * NodeScopeResolver are called through their direct entries; the php-parser
 * Name and ConstFetch nodes are instantiated through the class map.
 */

#include "support.h"
#include "generated/ConstHandler.h"

namespace sigs = ptdecl::ConstHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_const_handler = nullptr;

namespace {

pt_method_site pt_ch_name_to_string_site;
pt_method_site pt_ch_identifier_to_string_site;

pt_property_site pt_ch_consts_site;
pt_property_site pt_ch_const_value_site;
pt_property_site pt_ch_const_namespaced_name_site;
pt_property_site pt_ch_const_name_site;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* $name->toString() */
zv::Val nodeToString(pt_method_site &, zval *name)
{
	if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
		memberCallOnNonObject("toString", name);
		return zv::Val();
	}
	return pt_name_node_to_string(name);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ConstHandler; UNDEF = pending
 * exception. */
class ConstHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_CONST_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zval *entryScope = scope;
		zv::Val scopeHold;
		zv::Arr impurePoints = zv::Arr::empty();
		zval *consts = ptsh::readNodeProperty(pt_ch_consts_site, stmt, PT_LC("consts"));
		if (UNEXPECTED(consts == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(consts) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(consts));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(consts));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				zval *constNode = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(constNode) != IS_OBJECT)) {
					zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(constNode));
					if (UNEXPECTED(EG(exception))) return zv::Val();
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processExprNode(): Argument #2 ($expr) must be of type PhpParser\\Node\\Expr, null given");
					return zv::Val();
				}
				zval *value = ptsh::readNodeProperty(pt_ch_const_value_site, constNode, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
				bool resolveTemplateArguments;
				if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val constResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, valueHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(constResult.isUndef())) return zv::Val();
				// the constant's callback fires after its value was processed, so
				// rule-side asks about the value answer from the storage
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, constNode, scope, storage))) return zv::Val();
				{
					zv::Val hold;
					zval *resultImpurePoints = pt_expression_result_impure_points(constResult.raw(), hold);
					if (UNEXPECTED(resultImpurePoints == NULL || !pt_callable_array_merge_into(impurePoints, resultImpurePoints))) return zv::Val();
				}
				zv::Val constantName = fullyQualifiedConstantName(constNode);
				if (UNEXPECTED(constantName.isUndef())) return zv::Val();
				zv::Val fetch = pt_type_new(PT_CLASS_CONST_FETCH, 1, constantName.raw());
				if (UNEXPECTED(fetch.isUndef())) return zv::Val();
				zv::Val type = pt_expression_result_get_type(constResult.raw());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val nativeType = pt_expression_result_get_native_type(constResult.raw());
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(scope), Z_OBJ_P(fetch.raw()), type.raw(), nativeType.raw());
				if (UNEXPECTED(assigned.isUndef())) return zv::Val();
				scopeHold = std::move(assigned);
				scope = scopeHold.raw();
			}
		}

		// deferred from processStmtNode() - fires after the values were processed
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, impurePoints.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) handler;
		return processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	/* new Name\FullyQualified($const->namespacedName !== null ?
	 * $const->namespacedName->toString() : $const->name->toString()) */
	static zv::Val fullyQualifiedConstantName(zval *constNode)
	{
		zval *namespacedName = ptsh::readNodeProperty(pt_ch_const_namespaced_name_site, constNode, PT_LC("namespacedName"));
		if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(namespacedName) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$namespacedName must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(constNode)->name));
			return zv::Val();
		}
		zv::Val nameString;
		if (Z_TYPE_P(namespacedName) != IS_NULL) {
			zv::Val hold = zv::Val::copyOf(zv::Ref(namespacedName));
			nameString = nodeToString(pt_ch_name_to_string_site, hold.raw());
		} else {
			zval *name = ptsh::readNodeProperty(pt_ch_const_name_site, constNode, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Val hold = zv::Val::copyOf(zv::Ref(name));
			nameString = nodeToString(pt_ch_identifier_to_string_site, hold.raw());
		}
		if (UNEXPECTED(nameString.isUndef())) return zv::Val();
		return pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, nameString.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConstHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_const_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ConstHandler");
	ptdecl::ConstHandler::declareClass(cls);
	ptdecl::ConstHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ConstHandler::supports(stmt, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ConstHandler::processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_const_handler);
	pt_stmt_handler_entry_register(&pt_ce_const_handler, &ConstHandler::processStmtEntry);
}

/* }}} */
