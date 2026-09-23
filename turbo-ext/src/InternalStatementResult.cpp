/*
 * PHPStanTurbo\InternalStatementResult — native implementation of
 * PHPStan\Analyser\InternalStatementResult.
 *
 * What every statement handler returns (~200K per self-analysis) and
 * StatementsHandler reads back field by field. State lives in the twin's
 * slots, in its order: the explicit $endReachable, then the eight promoted
 * properties. The constructor joins the template argument constraints of
 * the exit points' and end statements' scopes into the result's scope
 * through MutatingScope's direct entries, reading the exit points and end
 * statements through the inline readers in AnalyserValues.h; the
 * exit-point walks are shared with StatementResult (StatementResults.h).
 * toPublic() builds the public StatementResult, StatementExitPoint,
 * ThrowPoint and EndStatementResult objects natively.
 */

#include "support.h"
#include "generated/InternalStatementResult.h"

namespace slots = ptdecl::InternalStatementResult::slot;
namespace sigs = ptdecl::InternalStatementResult::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AnalyserValues.h"
#include "StatementResults.h"

zend_class_entry *pt_ce_internal_statement_result = nullptr;

namespace {

const ptsr::ExitPointFlavour internalExitPoints = {
	pt_internal_statement_exit_point_statement,
	pt_internal_statement_exit_point_scope,
	pt_internal_statement_exit_point_new,
};

/* array_map(static fn ($item) => $item->toPublic(), $items): keys preserved,
 * the empty array for none; UNDEF = pending exception */
zv::Val mapToPublic(HashTable *items, zv::Val (*toPublic)(zval *item))
{
	if (zend_hash_num_elements(items) == 0) return zv::Val(zv::Arr::empty());
	zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(items));
	for (auto entry : zv::TableRef(items)) {
		zval *item = entry.value().deref().raw();
		if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toPublic() on %s", zend_zval_value_name(item));
			return zv::Val();
		}
		zv::Val value = toPublic(item);
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zval v = value.take();
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			zend_hash_add_new(mapped.table(), key, &v);
		} else {
			zend_hash_index_add_new(mapped.table(), entry.indexKey(), &v);
		}
	}
	return zv::Val(std::move(mapped));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\InternalStatementResult. */
class InternalStatementResult
{
public:
	explicit InternalStatementResult(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties, $endReachable, then the
	 * constraint joins; $endStatements NULL for [], $variableFlow NULL for
	 * null, $endReachable -1 for null. false = pending exception */
	[[nodiscard]] bool construct(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements, zval *variableFlow, int endReachable) const
	{
		zval value = {};
		pt_write_slot(self, slots::scope, scope);
		ZVAL_BOOL(&value, hasYield);
		pt_write_slot(self, slots::hasYield, &value);
		ZVAL_BOOL(&value, isAlwaysTerminating);
		pt_write_slot(self, slots::isAlwaysTerminating, &value);
		pt_write_slot(self, slots::exitPoints, exitPoints);
		pt_write_slot(self, slots::throwPoints, throwPoints);
		pt_write_slot(self, slots::impurePoints, impurePoints);
		if (endStatements != NULL) {
			pt_write_slot(self, slots::endStatements, endStatements);
		} else {
			ZVAL_EMPTY_ARRAY(&value);
			pt_write_slot(self, slots::endStatements, &value);
		}
		if (variableFlow != NULL) {
			pt_write_slot(self, slots::variableFlow, variableFlow);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::variableFlow, &value);
		}

		ZVAL_BOOL(&value, endReachable >= 0 ? endReachable != 0 : !isAlwaysTerminating);
		pt_write_slot(self, slots::endReachable, &value);

		for (auto entry : zv::TableRef(Z_ARRVAL_P(exitPoints))) {
			zval *exitPoint = entry.value().deref().raw();
			zv::Val hold;
			zval *exitScope = ptsr::scopeOf(internalExitPoints, exitPoint, hold);
			if (UNEXPECTED(exitScope == NULL || !joinConstraintsOf(exitScope))) return false;
		}
		if (endStatements != NULL) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(endStatements))) {
				zval *endStatement = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(endStatement) != IS_OBJECT)) {
					ptsr::memberCallOnNonObject("getResult", endStatement);
					return false;
				}
				zv::Val resultHold;
				zval *result = pt_internal_end_statement_result_result(endStatement, resultHold);
				if (UNEXPECTED(result == NULL)) return false;
				if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
					ptsr::memberCallOnNonObject("getScope", result);
					return false;
				}
				zv::Val scopeHold;
				zval *resultScope = pt_internal_statement_result_scope(result, scopeHold);
				if (UNEXPECTED(resultScope == NULL || !joinConstraintsOf(resultScope))) return false;
			}
		}
		return true;
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements, zval *variableFlow, int endReachable)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_internal_statement_result) != SUCCESS)) return zv::Val();
		zv::Val result = zv::Val::adopt(object);
		if (UNEXPECTED(!InternalStatementResult(Z_OBJ_P(result.raw())).construct(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements, variableFlow, endReachable))) return zv::Val();
		return result;
	}

	zv::Val getVariableFlow() const { return read(slots::variableFlow, "variableFlow"); }
	zv::Val isEndReachable() const { return read(slots::endReachable, "endReachable"); }

	/* Mirrors getLoopBackEdgeScope(). */
	zv::Val getLoopBackEdgeScope() const
	{
		zval *endReachable = pt_typed_slot(self, slots::endReachable, self->ce, "endReachable");
		if (UNEXPECTED(endReachable == NULL)) return zv::Val();
		zv::Val backEdge = zv::Val::null();
		if (Z_TYPE_P(endReachable) == IS_TRUE) {
			zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
			if (UNEXPECTED(scope == NULL)) return zv::Val();
			backEdge = zv::Val::copyOf(zv::Ref(scope));
		}
		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		zend_class_entry *continueCe = pt_class(PT_CLASS_CONTINUE_STMT);
		if (UNEXPECTED(continueCe == NULL)) return zv::Val();
		zv::Val continueExitPoints = ptsr::exitPointsByType(internalExitPoints, Z_ARRVAL_P(exitPoints), continueCe);
		if (UNEXPECTED(continueExitPoints.isUndef())) return zv::Val();
		for (auto entry : zv::TableRef(Z_ARRVAL_P(continueExitPoints.raw()))) {
			zval *continueExitPoint = entry.value().raw();
			if (Z_TYPE_P(backEdge.raw()) == IS_NULL) {
				zv::Val hold;
				zval *exitScope = ptsr::scopeOf(internalExitPoints, continueExitPoint, hold);
				if (UNEXPECTED(exitScope == NULL)) return zv::Val();
				backEdge = zv::Val::copyOf(zv::Ref(exitScope));
				continue;
			}
			if (UNEXPECTED(Z_TYPE_P(backEdge.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function mergeWith() on %s", zend_zval_value_name(backEdge.raw()));
				return zv::Val();
			}
			zv::Val hold;
			zval *exitScope = ptsr::scopeOf(internalExitPoints, continueExitPoint, hold);
			if (UNEXPECTED(exitScope == NULL)) return zv::Val();
			backEdge = pt_mutating_scope_merge_with(Z_OBJ_P(backEdge.raw()), exitScope);
			if (UNEXPECTED(backEdge.isUndef())) return zv::Val();
		}
		return backEdge;
	}

	/* Mirrors toPublic(). */
	zv::Val toPublic() const
	{
		zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
		zval *hasYield = scope != NULL ? pt_typed_slot(self, slots::hasYield, self->ce, "hasYield") : NULL;
		zval *isAlwaysTerminating = hasYield != NULL ? pt_typed_slot(self, slots::isAlwaysTerminating, self->ce, "isAlwaysTerminating") : NULL;
		zval *exitPoints = isAlwaysTerminating != NULL ? pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints") : NULL;
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		zv::Val publicExitPoints = mapToPublic(Z_ARRVAL_P(exitPoints), pt_internal_statement_exit_point_to_public);
		if (UNEXPECTED(publicExitPoints.isUndef())) return zv::Val();
		zval *throwPoints = pt_typed_slot(self, slots::throwPoints, self->ce, "throwPoints");
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zv::Val publicThrowPoints = mapToPublic(Z_ARRVAL_P(throwPoints), pt_internal_throw_point_to_public);
		if (UNEXPECTED(publicThrowPoints.isUndef())) return zv::Val();
		zval *impurePoints = pt_typed_slot(self, slots::impurePoints, self->ce, "impurePoints");
		zval *endStatements = impurePoints != NULL ? pt_typed_slot(self, slots::endStatements, self->ce, "endStatements") : NULL;
		if (UNEXPECTED(endStatements == NULL)) return zv::Val();
		zv::Val publicEndStatements = mapToPublic(Z_ARRVAL_P(endStatements), pt_internal_end_statement_result_to_public);
		if (UNEXPECTED(publicEndStatements.isUndef())) return zv::Val();

		return pt_statement_result_new(scope, Z_TYPE_P(hasYield) == IS_TRUE, Z_TYPE_P(isAlwaysTerminating) == IS_TRUE, publicExitPoints.raw(), publicThrowPoints.raw(), impurePoints, publicEndStatements.raw());
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val hasYield() const { return read(slots::hasYield, "hasYield"); }
	zv::Val isAlwaysTerminating() const { return read(slots::isAlwaysTerminating, "isAlwaysTerminating"); }

	/* Mirrors filterOutLoopExitPoints(). */
	zv::Val filterOutLoopExitPoints() const
	{
		zval *isAlwaysTerminating = pt_typed_slot(self, slots::isAlwaysTerminating, self->ce, "isAlwaysTerminating");
		if (UNEXPECTED(isAlwaysTerminating == NULL)) return zv::Val();
		if (Z_TYPE_P(isAlwaysTerminating) != IS_TRUE) return pt_this_value(self);

		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		int leaves = ptsr::leavesThisLoop(internalExitPoints, Z_ARRVAL_P(exitPoints));
		if (UNEXPECTED(leaves < 0)) return zv::Val();
		if (leaves == 0) return pt_this_value(self);

		/* new self($this->scope, $this->hasYield, false, $this->exitPoints,
		 * $this->throwPoints, $this->impurePoints, variableFlow:
		 * $this->variableFlow, endReachable: false) */
		zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
		zval *hasYield = scope != NULL ? pt_typed_slot(self, slots::hasYield, self->ce, "hasYield") : NULL;
		zval *throwPoints = hasYield != NULL ? pt_typed_slot(self, slots::throwPoints, self->ce, "throwPoints") : NULL;
		zval *impurePoints = throwPoints != NULL ? pt_typed_slot(self, slots::impurePoints, self->ce, "impurePoints") : NULL;
		zval *variableFlow = impurePoints != NULL ? pt_typed_slot(self, slots::variableFlow, self->ce, "variableFlow") : NULL;
		if (UNEXPECTED(variableFlow == NULL)) return zv::Val();
		return create(scope, Z_TYPE_P(hasYield) == IS_TRUE, false, exitPoints, throwPoints, impurePoints, NULL, Z_TYPE_P(variableFlow) == IS_NULL ? NULL : variableFlow, 0);
	}

	zv::Val getExitPoints() const { return read(slots::exitPoints, "exitPoints"); }

	/* Mirrors withVariableFlow(): clone, then the slot; $variableFlow NULL
	 * for null */
	zv::Val withVariableFlow(zval *variableFlow) const
	{
		zend_object *clone = self->handlers->clone_obj(self);
		if (UNEXPECTED(EG(exception))) {
			if (clone != NULL) {
				OBJ_RELEASE(clone);
			}
			return zv::Val();
		}
		zval cloneZv;
		ZVAL_OBJ(&cloneZv, clone);
		zv::Val result = zv::Val::adopt(cloneZv);
		zval value = {};
		if (variableFlow != NULL) {
			ZVAL_COPY_VALUE(&value, variableFlow);
		} else {
			ZVAL_NULL(&value);
		}
		pt_write_slot(clone, slots::variableFlow, &value);

		return result;
	}

	/* Mirrors getExitPointsByType(); $stmtClass NULL for a class that is not
	 * declared (matches nothing, as `instanceof` does) */
	zv::Val getExitPointsByType(zend_class_entry *stmtClass) const
	{
		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		return ptsr::exitPointsByType(internalExitPoints, Z_ARRVAL_P(exitPoints), stmtClass);
	}

	/* Mirrors getExitPointsForOuterLoop(). */
	zv::Val getExitPointsForOuterLoop() const
	{
		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		return ptsr::exitPointsForOuterLoop(internalExitPoints, Z_ARRVAL_P(exitPoints));
	}

	zv::Val getThrowPoints() const { return read(slots::throwPoints, "throwPoints"); }
	zv::Val getImpurePoints() const { return read(slots::impurePoints, "impurePoints"); }
	zv::Val getEndStatements() const { return read(slots::endStatements, "endStatements"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* $this->scope = $this->scope->addTemplateArgumentConstraints($otherScope->getTemplateArgumentConstraints());
	 * false = pending exception */
	[[nodiscard]] bool joinConstraintsOf(zval *otherScope) const
	{
		if (UNEXPECTED(Z_TYPE_P(otherScope) != IS_OBJECT)) {
			ptsr::memberCallOnNonObject("getTemplateArgumentConstraints", otherScope);
			return false;
		}
		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(otherScope));
		if (UNEXPECTED(constraints.isUndef())) return false;
		zval *scope = OBJ_PROP_NUM(self, slots::scope);
		zv::Val joined = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope), constraints.raw());
		if (UNEXPECTED(joined.isUndef())) return false;
		/* the typed property's assignment check (a MutatingScope subclass
		 * returns `self`; only a non-object can get here) */
		if (UNEXPECTED(Z_TYPE_P(joined.raw()) != IS_OBJECT)) {
			zend_type_error("Cannot assign %s to property %s::$scope of type PHPStan\\Analyser\\MutatingScope", zend_zval_value_name(joined.raw()), ZSTR_VAL(self->ce->name));
			return false;
		}
		scope = OBJ_PROP_NUM(self, slots::scope);
		if (Z_OBJ_P(joined.raw()) != Z_OBJ_P(scope)) {
			pt_write_slot(self, slots::scope, joined.raw());
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::InternalStatementResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_internal_statement_result_new(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements, zval *variableFlow, int endReachable)
{
	return InternalStatementResult::create(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements, variableFlow != NULL && Z_TYPE_P(variableFlow) == IS_NULL ? NULL : variableFlow, endReachable);
}

/* the twin is final: the native class entry answers natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method */
zv::Val pt_internal_statement_result_with_variable_flow(zval *result, zval *variableFlow)
{
	if (variableFlow != NULL && Z_TYPE_P(variableFlow) == IS_NULL) variableFlow = NULL;
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).withVariableFlow(variableFlow);
	zval null;
	ZVAL_NULL(&null);
	return pt_type_call(Z_OBJ_P(result), PT_LC("withvariableflow"), 1, variableFlow != NULL ? variableFlow : &null);
}

zv::Val pt_internal_statement_result_filter_out_loop_exit_points(zval *result)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).filterOutLoopExitPoints();
	return pt_type_call(Z_OBJ_P(result), PT_LC("filteroutloopexitpoints"), 0, NULL);
}

zv::Val pt_internal_statement_result_exit_points_by_type(zval *result, zend_class_entry *stmtClass)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).getExitPointsByType(stmtClass);
	zval className;
	ZVAL_STR(&className, stmtClass->name);
	return pt_type_call(Z_OBJ_P(result), PT_LC("getexitpointsbytype"), 1, &className);
}

zv::Val pt_internal_statement_result_exit_points_for_outer_loop(zval *result)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).getExitPointsForOuterLoop();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getexitpointsforouterloop"), 0, NULL);
}

zv::Val pt_internal_statement_result_loop_back_edge_scope(zval *result)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).getLoopBackEdgeScope();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getloopbackedgescope"), 0, NULL);
}

zv::Val pt_internal_statement_result_to_public(zval *result)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_internal_statement_result)) return InternalStatementResult(Z_OBJ_P(result)).toPublic();
	return pt_type_call(Z_OBJ_P(result), PT_LC("topublic"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_internal_statement_result()
{
	reg::Class cls("PHPStan\\Analyser\\InternalStatementResult");
	ptdecl::InternalStatementResult::declareClass(cls);
	ptdecl::InternalStatementResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *exitPoints, *throwPoints, *impurePoints, *endStatements = NULL, *variableFlow = NULL;
		bool hasYield, isAlwaysTerminating, endReachable = false, endReachableIsNull = true;
		ZEND_PARSE_PARAMETERS_START(6, 9)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_BOOL(hasYield)
			Z_PARAM_BOOL(isAlwaysTerminating)
			Z_PARAM_ARRAY(exitPoints)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(endStatements)
			Z_PARAM_OBJECT_OR_NULL(variableFlow)
			Z_PARAM_BOOL_OR_NULL(endReachable, endReachableIsNull)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!InternalStatementResult(Z_OBJ_P(ZEND_THIS)).construct(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements, variableFlow, endReachableIsNull ? -1 : (endReachable ? 1 : 0)))) RETURN_THROWS();
	});

	cls.method<&InternalStatementResult::getVariableFlow>(sigs::getVariableFlow);

	cls.method<&InternalStatementResult::withVariableFlow, zp::ObjOrNull>(sigs::withVariableFlow);

	cls.method<&InternalStatementResult::isEndReachable>(sigs::isEndReachable);

	cls.method<&InternalStatementResult::getLoopBackEdgeScope>(sigs::getLoopBackEdgeScope);

	cls.method<&InternalStatementResult::toPublic>(sigs::toPublic);

	cls.method<&InternalStatementResult::getScope>(sigs::getScope);

	cls.method<&InternalStatementResult::hasYield>(sigs::hasYield);

	cls.method<&InternalStatementResult::isAlwaysTerminating>(sigs::isAlwaysTerminating);

	cls.method<&InternalStatementResult::filterOutLoopExitPoints>(sigs::filterOutLoopExitPoints);

	cls.method<&InternalStatementResult::getExitPoints>(sigs::getExitPoints);

	cls.method(sigs::getExitPointsByType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *stmtClass;
		if (!zp::parse<zp::Str>(execute_data, stmtClass)) RETURN_THROWS();
		PT_RETURN_VAL(InternalStatementResult(Z_OBJ_P(ZEND_THIS)).getExitPointsByType(zend_lookup_class_ex(stmtClass, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD)));
	});

	cls.method<&InternalStatementResult::getExitPointsForOuterLoop>(sigs::getExitPointsForOuterLoop);

	cls.method<&InternalStatementResult::getThrowPoints>(sigs::getThrowPoints);

	cls.method<&InternalStatementResult::getImpurePoints>(sigs::getImpurePoints);

	cls.method<&InternalStatementResult::getEndStatements>(sigs::getEndStatements);

	cls.shadow(&pt_ce_internal_statement_result);
}

/* }}} */
