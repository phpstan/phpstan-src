/*
 * PHPStanTurbo\GotoHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\GotoHandler.
 *
 * A DI service (#[AutowiredService]) without a constructor. processStmt()
 * is registered as the class's statement-handler entry (Engine.h); the
 * exit point, the opaque flow and the result through their direct entries.
 */

#include "support.h"
#include "generated/GotoHandler.h"

namespace sigs = ptdecl::GotoHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_goto_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\GotoHandler; UNDEF = pending
 * exception. */
class GotoHandler
{
public:
	explicit GotoHandler(zend_object *self) : self(self) {}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_GOTO_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) self;
		(void) nodeScopeResolver;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		// a jump defeats reaching-write tracking for the whole body
		zv::Val exitPoint = pt_internal_statement_exit_point_new(stmt, scope);
		if (UNEXPECTED(exitPoint.isUndef())) return zv::Val();
		zv::Arr exitPoints = zv::Arr::create(1);
		exitPoints.push(std::move(exitPoint));
		zv::Val variableFlow = pt_variable_flow_all_opaque();
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, true, exitPoints.raw(), &emptyArray, &emptyArray, NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return GotoHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::GotoHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_goto_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\GotoHandler");
	ptdecl::GotoHandler::declareClass(cls);
	ptdecl::GotoHandler::declareProperties(cls);

	cls.method<&GotoHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(GotoHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_goto_handler);
	pt_stmt_handler_entry_register(&pt_ce_goto_handler, &GotoHandler::processStmtEntry);
}

/* }}} */
