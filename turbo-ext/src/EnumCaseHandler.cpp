/*
 * PHPStanTurbo\EnumCaseHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\EnumCaseHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * AttributesHandler, ExpressionResult, the contexts, the statement results
 * and NodeScopeResolver are called through their direct entries.
 */

#include "support.h"
#include "generated/EnumCaseHandler.h"

namespace slots = ptdecl::EnumCaseHandler::slot;
namespace sigs = ptdecl::EnumCaseHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_enum_case_handler = nullptr;

namespace {

pt_property_site pt_ech_attr_groups_site;
pt_property_site pt_ech_expr_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\EnumCaseHandler; UNDEF = pending
 * exception. */
class EnumCaseHandler
{
public:
	explicit EnumCaseHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *attributesHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_ENUM_CASE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *attrGroups = ptsh::readNodeProperty(pt_ech_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zval *expr = ptsh::readNodeProperty(pt_ech_expr_site, stmt, PT_LC("expr"));
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		if (Z_TYPE_P(expr) == IS_NULL) {
			return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
		}
		zv::Val exprHold = zv::Val::copyOf(zv::Ref(expr));
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
		if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, exprHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		zv::Val hold;
		zval *impurePoints = pt_expression_result_impure_points(exprResult.raw(), hold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, impurePoints);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return EnumCaseHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::EnumCaseHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_enum_case_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\EnumCaseHandler");
	ptdecl::EnumCaseHandler::declareClass(cls);
	ptdecl::EnumCaseHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *attributesHandler;
		if (!zp::parse<zp::Obj>(execute_data, attributesHandler)) RETURN_THROWS();
		EnumCaseHandler(Z_OBJ_P(ZEND_THIS)).construct(attributesHandler);
	});

	cls.method<&EnumCaseHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(EnumCaseHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_enum_case_handler);
	pt_stmt_handler_entry_register(&pt_ce_enum_case_handler, &EnumCaseHandler::processStmtEntry);
}

/* }}} */
