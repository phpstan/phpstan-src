/*
 * PHPStanTurbo\NodeScopeResolver — native implementation of
 * PHPStan\Analyser\NodeScopeResolver.
 *
 * The hub of the analysis walk. processExprNode() / processStmtNode() /
 * processStmtNodesInternal() reach the handlers through
 * pt_expr_handler_process() / pt_stmt_handler_process() and the native
 * registries, store results, apply NonNullabilityHelper's pending ensures and
 * emit the node callbacks without a PHP frame of their own; the on-demand
 * walks (processExprOnDemand(), processSyntheticOnDemand(),
 * readTypeOfMaybeStored(), findScopeStateType(), ...) likewise.
 *
 * The class is not final and a DI service (#[AutowiredService] with an
 * #[AutowiredExtensions] parameter): the constructor keeps the twin's
 * arginfo, the twin's public static properties ($guardNewWorld,
 * $guardRealExprIds, $guardProcessedExprIds) stay real static properties
 * (MutatingScope and PHP code read them), and every $this-call of a public
 * method takes the native body only for an instance of exactly this class —
 * a PHP subclass may override it — and the method through the object's
 * class entry otherwise. Other native classes call the direct entries
 * pt_node_scope_resolver_*() (support.h), which follow the same rule.
 *
 * The twin's try/finally blocks (storage push/pop, gatherer suspend/restore,
 * the walk-mode flags) are pt_finally() blocks: they run with a pending
 * exception set aside, as PHP runs them.
 *
 * Deep recursion: processExprNode(), processStmtNode() and
 * processStmtNodesInternal() continue on a fresh C stack segment when the
 * current one runs low (Engine.h) — the twin recursed on the VM stack, the
 * native walk re-enters execute_ex() for every PHP handler on its way down.
 *
 * InternalStatementResult, InternalThrowPoint, TemplateArgumentFrame and
 * RecordingNodeCallback are native (their entries and the AnalyserValues.h
 * readers); NodeFinder, the template-argument observer/constraints/stats and
 * the virtual call-expression nodes stay PHP and are reached through the
 * cached method sites in the block below, one helper each.
 */

#include "support.h"
#include "generated/NodeScopeResolver.h"

namespace slots = ptdecl::NodeScopeResolver::slot;
namespace sigs = ptdecl::NodeScopeResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "generated/RecordingNodeCallback.h"

#include <cstdlib>
#include <cstring>

zend_class_entry *pt_ce_node_scope_resolver = nullptr;

namespace {

/* {{{ the twin's static properties */

struct NsrStatics
{
	zend_class_entry *ce;
	uint32_t generation;
	zval *guardNewWorld;
	zval *guardRealExprIds;
	zval *guardProcessedExprIds;
};

NsrStatics pt_nsr_statics;

zval *staticSlotOf(zend_class_entry *ce, const char *name, size_t len)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, name, len);
	ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
	return CE_STATIC_MEMBERS(ce) + info->offset;
}

/* the slots of NodeScopeResolver::$guard* (the declaring class's, as the
 * twin's self:: addresses them) */
const NsrStatics &statics()
{
	zend_class_entry *ce = pt_ce_node_scope_resolver;
	if (UNEXPECTED(pt_nsr_statics.ce != ce || pt_nsr_statics.generation != pt_engine_generation)) {
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		pt_nsr_statics.ce = ce;
		pt_nsr_statics.generation = pt_engine_generation;
		pt_nsr_statics.guardNewWorld = staticSlotOf(ce, PT_LC("guardNewWorld"));
		pt_nsr_statics.guardRealExprIds = staticSlotOf(ce, PT_LC("guardRealExprIds"));
		pt_nsr_statics.guardProcessedExprIds = staticSlotOf(ce, PT_LC("guardProcessedExprIds"));
	}
	return pt_nsr_statics;
}

inline zval *staticValue(zval *slot)
{
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	return slot;
}

/* self::$guardNewWorld */
inline bool guardNewWorld()
{
	return Z_TYPE_P(staticValue(statics().guardNewWorld)) == IS_TRUE;
}

/* self::$<array> = [] */
void resetStaticArray(zval *slot)
{
	zval *value = staticValue(slot);
	zval old;
	ZVAL_COPY_VALUE(&old, value);
	ZVAL_EMPTY_ARRAY(value);
	zval_ptr_dtor(&old);
}

/* self::$<array>[$id] = true */
void setStaticArrayFlag(zval *slot, zend_ulong id)
{
	zval *value = staticValue(slot);
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		zval_ptr_dtor(value);
		ZVAL_EMPTY_ARRAY(value);
	}
	SEPARATE_ARRAY(value);
	zval flag;
	ZVAL_TRUE(&flag);
	zend_hash_index_update(Z_ARRVAL_P(value), id, &flag);
}

/* isset(self::$<array>[$id]) */
bool staticArrayHas(zval *slot, zend_ulong id)
{
	zval *value = staticValue(slot);
	if (Z_TYPE_P(value) != IS_ARRAY) return false;
	zval *found = zend_hash_index_find(Z_ARRVAL_P(value), id);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

/* }}} */

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_nsr_get_by_type_site;
pt_method_site pt_nsr_normalize_path_site;
pt_method_site pt_nsr_find_instance_of_site;
pt_method_site pt_nsr_collect_sites_site;
pt_method_site pt_nsr_collect_send_site;
pt_method_site pt_nsr_create_empty_site;
pt_method_site pt_nsr_enable_from_environment_site;
pt_method_site pt_nsr_function_get_return_type_site;
pt_method_site pt_nsr_get_sub_node_names_site;
pt_property_site pt_nsr_name_site;
pt_property_site pt_nsr_var_site;
pt_property_site pt_nsr_class_site;
pt_property_site pt_nsr_dim_site;
pt_property_site pt_nsr_items_site;
pt_property_site pt_nsr_item_value_site;
pt_property_site pt_nsr_stmt_name_site;
pt_property_site pt_nsr_hook_fetch_var_site;
pt_property_site pt_nsr_hook_fetch_name_site;
pt_property_site pt_nsr_hook_this_name_site;

/* $container->getByType($className) */
zv::Val containerGetByType(zval *container, const char *className, size_t len)
{
	zval name;
	ZVAL_STRINGL(&name, className, len);
	zv::Val service = pt_call_method_cached(pt_nsr_get_by_type_site, Z_OBJ_P(container), PT_LC("getbytype"), 1, &name);
	zval_ptr_dtor(&name);
	return service;
}

/* $extensionsCollection->getAll() */
zv::Val extensionsCollectionGetAll(zval *collection)
{
	return pt_extensions_collection_get_all(Z_OBJ_P(collection));
}

/* $fileHelper->normalizePath($path) */
zv::Val fileHelperNormalizePath(zval *fileHelper, zval *path)
{
	return pt_call_method_cached(pt_nsr_normalize_path_site, Z_OBJ_P(fileHelper), PT_LC("normalizepath"), 1, path);
}

/* (new NodeFinder())->findInstanceOf($nodes, Expr::class) */
zv::Val nodeFinderFindExprs(zval *nodes)
{
	zv::Val finder = pt_type_new(PT_CLASS_NODE_FINDER, 0, NULL);
	if (UNEXPECTED(finder.isUndef())) return zv::Val();
	zval exprClass;
	ZVAL_STRINGL(&exprClass, "PhpParser\\Node\\Expr", sizeof("PhpParser\\Node\\Expr") - 1);
	zv::Args argv{nodes, &exprClass};
	zv::Val found = pt_call_method_cached(pt_nsr_find_instance_of_site, Z_OBJ_P(finder.raw()), PT_LC("findinstanceof"), 2, argv);
	zval_ptr_dtor(&exprClass);
	return found;
}

/* an owned copy of a borrowed read of AnalyserValues.h (NULL = pending
 * exception) */
inline zv::Val owned(zval *value)
{
	return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
}

/* $expressionResult->getScope() / ->getBeforeScope() / ->getThrowPoints() /
 * ->getImpurePoints(), owned */
inline zv::Val expressionResultScope(zval *result)
{
	zv::Val hold;
	return owned(pt_expression_result_scope(result, hold));
}

inline zv::Val expressionResultBeforeScope(zval *result)
{
	zv::Val hold;
	return owned(pt_expression_result_before_scope(result, hold));
}

inline zv::Val expressionResultThrowPoints(zval *result)
{
	zv::Val hold;
	return owned(pt_expression_result_throw_points(result, hold));
}

inline zv::Val expressionResultImpurePoints(zval *result)
{
	zv::Val hold;
	return owned(pt_expression_result_impure_points(result, hold));
}

/* new InternalStatementResult($scope, false, false, [], $throwPoints, []) */
zv::Val emptyInternalStatementResult(zval *scope, zval *throwPoints)
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	return pt_internal_statement_result_new(scope, false, false, &empty, throwPoints != NULL ? throwPoints : &empty, &empty);
}

/* $frame->isObserving(); false = pending exception */
[[nodiscard]] bool templateArgumentFrameIsObserving(zval *frame, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(frame) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isObserving() on %s", zend_zval_value_name(frame));
		return false;
	}
	return pt_template_argument_frame_is_observing(frame, out);
}

/* $templateArgumentObserver->collectSites($type) */
zv::Val templateArgumentObserverCollectSites(zval *observer, zval *type)
{
	return pt_call_method_cached(pt_nsr_collect_sites_site, Z_OBJ_P(observer), PT_LC("collectsites"), 1, type);
}

/* $templateArgumentObserver->collectSend($declared, $actual) */
zv::Val templateArgumentObserverCollectSend(zval *observer, zval *declared, zval *actual)
{
	zv::Args argv{declared, actual};
	return pt_call_method_cached(pt_nsr_collect_send_site, Z_OBJ_P(observer), PT_LC("collectsend"), 2, argv);
}

/* TemplateArgumentConstraints::createEmpty() */
zv::Val templateArgumentConstraintsCreateEmpty()
{
	return pt_call_static_cached(pt_nsr_create_empty_site, PT_CLASS_TEMPLATE_ARGUMENT_CONSTRAINTS, PT_LC("createempty"), 0, NULL);
}

/* TemplateArgumentStats::enableFromEnvironment() */
zv::Val templateArgumentStatsEnableFromEnvironment()
{
	return pt_call_static_cached(pt_nsr_enable_from_environment_site, PT_CLASS_TEMPLATE_ARGUMENT_STATS, PT_LC("enablefromenvironment"), 0, NULL);
}

/* $function->getReturnType() */
zv::Val functionGetReturnType(zval *function)
{
	if (UNEXPECTED(Z_TYPE_P(function) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getReturnType() on %s", zend_zval_value_name(function));
		return zv::Val();
	}
	return pt_call_method_cached(pt_nsr_function_get_return_type_site, Z_OBJ_P(function), PT_LC("getreturntype"), 0, NULL);
}

/* {{{ RecordingNodeCallback (RecordingNodeCallback.cpp) */

/* $nodeCallback instanceof RecordingNodeCallback (a final class): the
 * native class, or — under the prefixed differential activation, where the
 * twin keeps the real name — the twin */
inline bool isRecordingNodeCallback(zval *nodeCallback)
{
	if (Z_TYPE_P(nodeCallback) != IS_OBJECT) return false;
	zend_class_entry *ce = Z_OBJCE_P(nodeCallback);
	return EXPECTED(ce == pt_ce_recording_node_callback) || UNEXPECTED(zend_string_equals_literal(ce->name, "PHPStan\\Analyser\\RecordingNodeCallback"));
}

/* $recording($node, $scope); false = pending exception */
[[nodiscard]] bool recordingNodeCallbackInvoke(zval *recording, zval *node, zval *scope)
{
	if (EXPECTED(Z_OBJCE_P(recording) == pt_ce_recording_node_callback)) return pt_recording_node_callback_record(Z_OBJ_P(recording), node, scope);
	zv::Args argv{node, scope};
	return !pt_type_call(Z_OBJ_P(recording), PT_LC("__invoke"), 2, argv).isUndef();
}

/* $recording->getPairs() */
zv::Val recordingNodeCallbackGetPairs(zval *recording)
{
	if (EXPECTED(Z_OBJCE_P(recording) == pt_ce_recording_node_callback)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(recording), ptdecl::RecordingNodeCallback::slot::pairs)));
	return pt_type_call(Z_OBJ_P(recording), PT_LC("getpairs"), 0, NULL);
}

/* $recording->count(); false = pending exception */
[[nodiscard]] bool recordingNodeCallbackCount(zval *recording, zend_long &out)
{
	if (EXPECTED(Z_OBJCE_P(recording) == pt_ce_recording_node_callback)) {
		zval *pairs = OBJ_PROP_NUM(Z_OBJ_P(recording), ptdecl::RecordingNodeCallback::slot::pairs);
		out = Z_TYPE_P(pairs) == IS_ARRAY ? (zend_long) zend_hash_num_elements(Z_ARRVAL_P(pairs)) : 0;
		return true;
	}
	zv::Val count = pt_type_call(Z_OBJ_P(recording), PT_LC("count"), 0, NULL);
	if (UNEXPECTED(count.isUndef())) return false;
	out = zval_get_long(count.raw());
	return true;
}

/* }}} */

/* $nodeCallback instanceof NoopNodeCallback (a final class; see above) */
inline bool isNoopNodeCallback(zval *nodeCallback)
{
	return Z_TYPE_P(nodeCallback) == IS_OBJECT && Z_OBJCE_P(nodeCallback) == pt_class(PT_CLASS_NOOP_NODE_CALLBACK);
}

/* $node->getSubNodeNames() */
zv::Val nodeGetSubNodeNames(zend_object *node)
{
	return pt_call_method_cached(pt_nsr_get_sub_node_names_site, node, PT_LC("getsubnodenames"), 0, NULL);
}

/* }}} */

/* {{{ node shapes */

/* what processStmtNode() asks about a statement's class, decided once per
 * class: the declarations its PHPDocs are not applied to, Foreach_ (no @var
 * before the walk), ClassMethod (the trait prelude) and the statements whose
 * node callback their handler emits after walking their expressions */
enum : uint32_t
{
	PT_NSR_STMT_DECLARATION = 1u << 0,
	PT_NSR_STMT_FOREACH = 1u << 1,
	PT_NSR_STMT_CLASS_METHOD = 1u << 2,
	PT_NSR_STMT_DEFERRED_CALLBACK = 1u << 3,
};

struct StmtShapeEntry
{
	zend_class_entry *ce;
	uint32_t generation;
	uint32_t shape;
};

#define PT_NSR_STMT_SHAPE_CACHE_BITS 8
StmtShapeEntry pt_nsr_stmt_shapes[1u << PT_NSR_STMT_SHAPE_CACHE_BITS];

/* the shape bits of a statement class; false = pending exception */
[[nodiscard]] bool computeStmtShape(zend_class_entry *ce, uint32_t &shape)
{
	shape = 0;
	static const int declarations[] = { PT_CLASS_STATIC_STMT, PT_CLASS_GLOBAL_STMT, PT_CLASS_PROPERTY_STMT, PT_CLASS_CLASS_CONST_STMT, PT_CLASS_CONST_STMT, PT_CLASS_CLASS_LIKE_STMT, PT_CLASS_FUNCTION_STMT, PT_CLASS_CLASS_METHOD_STMT };
	static const int deferred[] = { PT_CLASS_RETURN_STMT, PT_CLASS_EXPRESSION_STMT, PT_CLASS_ECHO_STMT, PT_CLASS_IF_STMT, PT_CLASS_SWITCH_STMT, PT_CLASS_FOREACH_STMT, PT_CLASS_UNSET_STMT, PT_CLASS_CLASS_CONST_STMT, PT_CLASS_CONST_STMT, PT_CLASS_WHILE_STMT, PT_CLASS_DO_STMT };
	for (int classIdx : declarations) {
		zend_class_entry *target = pt_class(classIdx);
		if (UNEXPECTED(target == NULL)) return false;
		if (instanceof_function(ce, target)) {
			shape |= PT_NSR_STMT_DECLARATION;
			break;
		}
	}
	zend_class_entry *foreachCe = pt_class(PT_CLASS_FOREACH_STMT);
	zend_class_entry *classMethodCe = pt_class(PT_CLASS_CLASS_METHOD_STMT);
	if (UNEXPECTED(foreachCe == NULL || classMethodCe == NULL)) return false;
	if (instanceof_function(ce, foreachCe)) shape |= PT_NSR_STMT_FOREACH;
	if (instanceof_function(ce, classMethodCe)) shape |= PT_NSR_STMT_CLASS_METHOD;
	for (int classIdx : deferred) {
		zend_class_entry *target = pt_class(classIdx);
		if (UNEXPECTED(target == NULL)) return false;
		if (instanceof_function(ce, target)) {
			shape |= PT_NSR_STMT_DEFERRED_CALLBACK;
			break;
		}
	}
	return true;
}

[[nodiscard]] inline bool stmtShapeOf(zend_class_entry *ce, uint32_t &shape)
{
	uintptr_t h = ((uintptr_t) ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	StmtShapeEntry &entry = pt_nsr_stmt_shapes[(size_t) (h >> (sizeof(uintptr_t) * 8 - PT_NSR_STMT_SHAPE_CACHE_BITS))];
	if (EXPECTED(entry.ce == ce && entry.generation == pt_engine_generation)) {
		shape = entry.shape;
		return true;
	}
	if (UNEXPECTED(!computeStmtShape(ce, shape))) return false;
	entry = { ce, pt_engine_generation, shape };
	return true;
}

/* $object instanceof <class-map class>; false = pending exception */
[[nodiscard]] inline bool isInstance(zend_object *object, int classIdx, bool &out)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(object->ce, ce);
	return true;
}

/* $zv instanceof <class-map class> for any value; false = pending exception */
[[nodiscard]] inline bool isInstanceValue(zval *value, int classIdx, bool &out)
{
	ZVAL_DEREF(value);
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	return isInstance(Z_OBJ_P(value), classIdx, out);
}

/* $node->$name of a declared property through a per-site offset; the
 * Error the engine raises for an undeclared or uninitialized one */
zval *nodeProperty(pt_property_site &site, zend_object *node, const char *name, size_t len)
{
	zval *slot = pt_property_cached(site, node, name, len);
	if (UNEXPECTED(slot == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s has no declared property $%s", ZSTR_VAL(node->ce->name), name);
		return NULL;
	}
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(node->ce->name), name);
		return NULL;
	}
	return slot;
}

/* get_class($node) . ' on line ' . $node->getStartLine() into a
 * ShouldNotHappenException message */
void throwWithNodeLine(zend_object *node, const char *format)
{
	zv::Val line = pt_type_call(node, PT_LC("getstartline"), 0, NULL);
	if (UNEXPECTED(line.isUndef())) return;
	zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
	if (UNEXPECTED(ce == NULL)) return;
	zend_throw_exception_ex(ce, 0, format, ZSTR_VAL(node->ce->name), (zend_long) zval_get_long(line.raw()));
}

/* the Error for reading an uninitialized typed property of the resolver */
zv::Val uninitialized(const char *name)
{
	zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\NodeScopeResolver::$%s must not be accessed before initialization", name);
	return zv::Val();
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\NodeScopeResolver; UNDEF / false = pending
 * exception. */
class NodeScopeResolver
{
public:
	explicit NodeScopeResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties, then the twin's body */
	[[nodiscard]] bool construct(zval *container, zval *templateArgumentObserver, zval *fileHelper, zval *perFileAnalysisResettables, zval *expressionResultFactory, zval *statementsHandler)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::container, zv::Val::copyOf(zv::Ref(container)));
		object.propAtWrite(slots::templateArgumentObserver, zv::Val::copyOf(zv::Ref(templateArgumentObserver)));
		object.propAtWrite(slots::fileHelper, zv::Val::copyOf(zv::Ref(fileHelper)));
		object.propAtWrite(slots::perFileAnalysisResettables, zv::Val::copyOf(zv::Ref(perFileAnalysisResettables)));
		object.propAtWrite(slots::expressionResultFactory, zv::Val::copyOf(zv::Ref(expressionResultFactory)));
		object.propAtWrite(slots::statementsHandler, zv::Val::copyOf(zv::Ref(statementsHandler)));

		// self::$guardNewWorld = getenv('PHPSTAN_GUARD_NW') === '1';
		const char *guard = getenv("PHPSTAN_GUARD_NW");
		zval *guardSlot = staticValue(statics().guardNewWorld);
		ZVAL_BOOL(guardSlot, guard != NULL && strcmp(guard, "1") == 0);
		return !templateArgumentStatsEnableFromEnvironment().isUndef();
	}

	/* Mirrors setAnalysedFiles(). */
	[[nodiscard]] bool setAnalysedFiles(HashTable *files)
	{
		zv::Ref fileHelper = slot(slots::fileHelper);
		zv::Arr analysedFiles = zv::Arr::empty();
		for (auto entry : zv::TableRef(files)) {
			if (UNEXPECTED(!fileHelper.isObject())) {
				(void) uninitialized("fileHelper");
				return false;
			}
			zv::Val normalized = fileHelperNormalizePath(fileHelper.raw(), entry.value().deref().raw());
			if (UNEXPECTED(normalized.isUndef())) return false;
			zval flag;
			ZVAL_TRUE(&flag);
			analysedFiles.separate();
			array_set_zval_key(analysedFiles.table(), normalized.raw(), &flag);
			if (UNEXPECTED(EG(exception))) return false;
		}
		zv::ObjRef(self).propAtWrite(slots::analysedFiles, std::move(analysedFiles));
		return true;
	}

	/* Mirrors resetPerFileAnalysisState(). */
	[[nodiscard]] bool resetPerFileAnalysisState()
	{
		zv::Ref collection = slot(slots::perFileAnalysisResettables);
		if (UNEXPECTED(!collection.isObject())) return !uninitialized("perFileAnalysisResettables").isUndef();
		zv::Val all = extensionsCollectionGetAll(collection.raw());
		if (UNEXPECTED(all.isUndef())) return false;
		if (UNEXPECTED(!all.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(all.raw()));
			return EG(exception) == NULL;
		}
		for (auto entry : zv::ArrRef(all.raw())) {
			zv::Ref service = entry.value().deref();
			if (UNEXPECTED(!service.isObject())) {
				zend_throw_error(NULL, "Call to a member function resetFileAnalysisState() on %s", zend_zval_value_name(service.raw()));
				return false;
			}
			if (UNEXPECTED(!pt_non_nullability_helper_reset_file_analysis_state(service.raw()))) return false;
		}
		return true;
	}

	/* Mirrors processNodes(). */
	[[nodiscard]] bool processNodes(zval *nodes, zval *scopeArg, zval *nodeCallback)
	{
		zv::Val scope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scopeArg));
		if (UNEXPECTED(scope.isUndef())) return false;
		if (UNEXPECTED(!requireObject(scope, "pushExpressionResultStorage"))) return false;
		if (guardNewWorld()) {
			const NsrStatics &s = statics();
			resetStaticArray(s.guardRealExprIds);
			resetStaticArray(s.guardProcessedExprIds);
			zv::Val realExprs = nodeFinderFindExprs(nodes);
			if (UNEXPECTED(realExprs.isUndef())) return false;
			if (realExprs.ref().isArray()) {
				for (auto entry : zv::ArrRef(realExprs.raw())) {
					zv::Ref realExpr = entry.value().deref();
					if (realExpr.isObject()) {
						setStaticArrayFlag(statics().guardRealExprIds, realExpr.asObject()->handle);
					}
				}
			}
		}

		zv::Val storage = pt_expression_result_storage_new();
		if (UNEXPECTED(storage.isUndef())) return false;
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return false;
		zv::Val gatherers = thisSuspendNodeGatherers();
		if (UNEXPECTED(gatherers.isUndef())) return false;
		zval thisZv;
		ZVAL_OBJ(&thisZv, self);
		zval *statementsHandler = requireStatementsHandler();
		if (EXPECTED(statementsHandler != NULL)) {
			(void) pt_statements_handler_process_nodes_with_storage(statementsHandler, &thisZv, nodes, scope.raw(), storage.raw(), nodeCallback);
		}
		pt_finally([&]() {
			if (thisRestoreNodeGatherers(gatherers.raw())) {
				(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw()));
			}
		});
		return EG(exception) == NULL;
	}

	/* Mirrors storeExpressionResult(). */
	void storeExpressionResult(zval *storage, zval *expr, zval *expressionResult)
	{
		if (UNEXPECTED(guardNewWorld())) {
			setStaticArrayFlag(statics().guardProcessedExprIds, Z_OBJ_HANDLE_P(expr));
		}
		pt_expression_result_storage_store(storage, expr, expressionResult);
	}

	/* Mirrors narrowScopeWithCondition(). */
	zv::Val narrowScopeWithCondition(zval *scope, zval *expr, zval *context)
	{
		zv::Val specifiedTypes = pt_mutating_scope_specify_types_of_new_world_handler_node(Z_OBJ_P(scope), Z_OBJ_P(expr), context);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
		return pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), specifiedTypes.raw());
	}

	/* Mirrors processStmtNodes(). */
	zv::Val processStmtNodes(zval *parentNode, zval *stmts, zval *scopeArg, zval *nodeCallback, zval *context)
	{
		zv::Val scope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scopeArg));
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireObject(scope, "pushExpressionResultStorage"))) return zv::Val();
		zv::Val storage = pt_expression_result_storage_new();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return zv::Val();
		zv::Val gatherers = thisSuspendNodeGatherers();
		if (UNEXPECTED(gatherers.isUndef())) return zv::Val();
		zv::Val result = thisProcessStmtNodesInternal(parentNode, stmts, scope.raw(), storage.raw(), nodeCallback, context);
		zv::Val publicResult;
		if (EXPECTED(!result.isUndef())) {
			if (UNEXPECTED(!result.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function toPublic() on %s", zend_zval_value_name(result.raw()));
			} else {
				publicResult = pt_internal_statement_result_to_public(result.raw());
			}
		}
		pt_finally([&]() {
			if (thisRestoreNodeGatherers(gatherers.raw())) {
				(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw()));
			}
		});
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return publicResult;
	}

	/* Mirrors processStmtNodesInternal(). */
	zv::Val processStmtNodesInternal(zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val result;
		pt_engine_with_stack([&]() { result = processStmtNodesInternalBody(parentNode, stmts, scope, storage, nodeCallback, context); });
		return result;
	}

	/* Mirrors processStmtNode(). */
	zv::Val processStmtNode(zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val result;
		pt_engine_with_stack([&]() { result = processStmtNodeBody(stmt, scope, storage, nodeCallback, context); });
		return result;
	}

	/* Mirrors isAnalysedFile(). */
	bool isAnalysedFile(zend_string *fileName) const
	{
		zval *analysedFiles = OBJ_PROP_NUM(self, slots::analysedFiles);
		ZVAL_DEREF(analysedFiles);
		if (UNEXPECTED(Z_TYPE_P(analysedFiles) != IS_ARRAY)) return false;
		zval *found = zend_symtable_find(Z_ARRVAL_P(analysedFiles), fileName);
		if (found == NULL) return false;
		ZVAL_DEREF(found);
		return Z_TYPE_P(found) != IS_NULL;
	}

	bool isReturningStoredExpressionResults() const { return flag(slots::returnStoredExpressionResults); }

	bool isConsumingStoredExpressionResults() const { return flag(slots::consumeStoredExpressionResults); }

	/* Mirrors lookForSetAllowedUndefinedExpressions() /
	 * lookForUnsetAllowedUndefinedExpressions() over the private
	 * lookForExpressionCallback() */
	zv::Val lookForSetAllowedUndefinedExpressions(zval *scope, zval *expr)
	{
		return lookForExpressionCallback(scope, expr, true);
	}

	zv::Val lookForUnsetAllowedUndefinedExpressions(zval *scope, zval *expr)
	{
		return lookForExpressionCallback(scope, expr, false);
	}

	/* Mirrors processExprNodeConsumingStored(). */
	zv::Val processExprNodeConsumingStored(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		bool previous = flag(slots::consumeStoredExpressionResults);
		setFlag(slots::consumeStoredExpressionResults, true);
		zv::Val result = thisProcessExprNode(stmt, expr, scope, storage, nodeCallback, context);
		pt_finally([&]() { setFlag(slots::consumeStoredExpressionResults, previous); });
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return result;
	}

	/* Mirrors processExprOnDemand(). */
	zv::Val processExprOnDemand(zval *expr, zval *scope, zval *storage)
	{
		zv::Ref container = slot(slots::container);
		if (UNEXPECTED(!container.isObject())) return uninitialized("container");
		zv::Val handler = pt_expr_handler_registry_resolve(Z_OBJ_P(expr), container.raw());
		if (UNEXPECTED(handler.isUndef())) return zv::Val();
		if (handler.isNull()) {
			bool firstClassCallable = false;
			bool isCallLike;
			if (UNEXPECTED(!isInstance(Z_OBJ_P(expr), PT_CLASS_CALL_LIKE, isCallLike))) return zv::Val();
			if (isCallLike && UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), firstClassCallable))) return zv::Val();
			if (!firstClassCallable) {
				zv::Val mixed = pt_type_new_mixed_type();
				if (UNEXPECTED(mixed.isUndef())) return zv::Val();
				zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
				if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
				zval null;
				ZVAL_NULL(&null);
				zv::Ref factory = slot(slots::expressionResultFactory);
				if (UNEXPECTED(!factory.isObject())) return uninitialized("expressionResultFactory");
				pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, &null, specifyTypesCallback.raw());
				args.withType(mixed.raw()).withNativeType(mixed.raw());
				return pt_expression_result_create(factory.raw(), args);
			}
		}

		bool previous = flag(slots::returnStoredExpressionResults);
		setFlag(slots::returnStoredExpressionResults, true);
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage))) return zv::Val();
		zv::Val result;
		zv::Val stmt = pt_type_new(PT_CLASS_EXPRESSION_STMT, 1, expr);
		if (EXPECTED(!stmt.isUndef())) {
			zv::Val noop = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
			if (EXPECTED(!noop.isUndef())) {
				zv::Val context = pt_expression_context_create_top_level(false);
				if (EXPECTED(!context.isUndef())) {
					result = thisProcessExprNode(stmt.raw(), expr, scope, storage, noop.raw(), context.raw());
				}
			}
		}
		pt_finally([&]() {
			if (pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope))) {
				setFlag(slots::returnStoredExpressionResults, previous);
			}
		});
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return result;
	}

	/* Mirrors readStoredResult(). */
	zv::Val readStoredResult(zval *expr, zval *storage)
	{
		zv::Val result = pt_expression_result_storage_find(storage, expr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (result.isNull()) {
			throwWithNodeLine(Z_OBJ_P(expr), "%s on line " ZEND_LONG_FMT " has no stored ExpressionResult - it was not processed by processExprNode().");
			return zv::Val();
		}
		return result;
	}

	/* Mirrors readTypeOfMaybeStored(). */
	zv::Val readTypeOfMaybeStored(zval *expr, zval *scope)
	{
		zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (!storage.isNull()) {
			zv::Val result = pt_expression_result_storage_find(storage.raw(), expr);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (!result.isNull()) {
				bool promoted;
				if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return zv::Val();
				return pt_expression_result_get_type_on_scope(result.raw(), scope, promoted);
			}
		}
		return thisReadScopeStateOrSyntheticType(expr, scope);
	}

	/* Mirrors findScopeStateType(). */
	zv::Val findScopeStateType(zval *expr, zval *scope)
	{
		zend_object *exprObject = Z_OBJ_P(expr);
		zend_object *scopeObject = Z_OBJ_P(scope);
		bool isVariable;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_VARIABLE, isVariable))) return zv::Val();
		if (isVariable) {
			zval *name = nodeProperty(pt_nsr_name_site, exprObject, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) == IS_STRING) {
				zv::Val has = pt_mutating_scope_has_variable_type(scopeObject, Z_STR_P(name));
				if (UNEXPECTED(has.isUndef())) return zv::Val();
				zend_long hasValue = pt_type_trinary_value(has.raw());
				if (UNEXPECTED(hasValue < 0)) return zv::Val();
				if (hasValue == PT_TRI_NO) return pt_type_new_error_type();
				return pt_mutating_scope_get_variable_type(scopeObject, Z_STR_P(name));
			}
		}

		bool isLiteral;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_SCALAR_STRING, isLiteral))) return zv::Val();
		if (!isLiteral && UNEXPECTED(!isInstance(exprObject, PT_CLASS_SCALAR_INT, isLiteral))) return zv::Val();
		if (!isLiteral && UNEXPECTED(!isInstance(exprObject, PT_CLASS_SCALAR_FLOAT, isLiteral))) return zv::Val();
		if (isLiteral) return pt_mutating_scope_get_state_type(scopeObject, exprObject);

		bool isClosure;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_CLOSURE_EXPR, isClosure))) return zv::Val();
		if (!isClosure && UNEXPECTED(!isInstance(exprObject, PT_CLASS_ARROW_FUNCTION, isClosure))) return zv::Val();
		if (!isClosure) {
			zend_long hasValue = pt_mutating_scope_has_expression_type(scopeObject, expr);
			if (UNEXPECTED(hasValue < 0)) return zv::Val();
			if (hasValue == PT_TRI_YES) {
				zv::Val tracked = pt_mutating_scope_get_tracked_expression_type(scopeObject, exprObject);
				if (UNEXPECTED(tracked.isUndef())) return zv::Val();
				return pt_type_utils_resolve_late_resolvable_types(tracked.raw());
			}
		}

		return zv::Val::null();
	}

	/* Mirrors readScopeStateOrSyntheticType(). */
	zv::Val readScopeStateOrSyntheticType(zval *expr, zval *scope)
	{
		zv::Val type = thisFindScopeStateType(expr, scope);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (!type.isNull()) return type;
		zv::Val result = thisProcessSyntheticOnDemand(expr, scope);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!result.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return zv::Val();
		return pt_expression_result_get_type_on_scope(result.raw(), scope, promoted);
	}

	/* Mirrors requireScopeStateType(). */
	zv::Val requireScopeStateType(zval *expr, zval *scope)
	{
		zv::Val type = thisFindScopeStateType(expr, scope);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) {
			throwWithNodeLine(Z_OBJ_P(expr), "%s on line " ZEND_LONG_FMT " is not tracked on the scope it was pinned as tracked on.");
			return zv::Val();
		}
		return type;
	}

	/* Mirrors processSyntheticOnDemand(). */
	zv::Val processSyntheticOnDemand(zval *expr, zval *scope)
	{
		if (UNEXPECTED(!guardAgainstUnprocessedRealNode(Z_OBJ_P(expr), "processSyntheticOnDemand"))) return zv::Val();
		zv::Val current = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
		if (UNEXPECTED(current.isUndef())) return zv::Val();
		if (current.isNull()) {
			current = pt_expression_result_storage_new();
			if (UNEXPECTED(current.isUndef())) return zv::Val();
		}
		zv::Val duplicate = pt_expression_result_storage_duplicate(current.raw());
		if (UNEXPECTED(duplicate.isUndef())) return zv::Val();
		return thisProcessExprOnDemand(expr, scope, duplicate.raw());
	}

	/* Mirrors processExprNode(). */
	zv::Val processExprNode(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val result;
		pt_engine_with_stack([&]() { result = processExprNodeBody(stmt, expr, scope, storage, nodeCallback, context); });
		return result;
	}

	/* public (twin 822): a property read defaults to pure - only a hook we
	 * are certain about and that is certainly side-effecting makes the read
	 * impure */
	zv::Val getImpurePointsFromPropertyHook(zval *scope, zval *propertyFetch, zval *propertyReflection, zend_string *hookName)
	{
		bool backingValueAccess;
		if (UNEXPECTED(!isPropertyHookBackingValueAccess(Z_OBJ_P(scope), Z_OBJ_P(propertyFetch), backingValueAccess))) return zv::Val();
		if (backingValueAccess) return zv::Val(zv::Arr::empty());

		zend_object *property = Z_OBJ_P(propertyReflection);
		zval hookNameZv;
		ZVAL_STR(&hookNameZv, hookName);
		zv::Val hasHook = pt_type_call(property, PT_LC("hashook"), 1, &hookNameZv);
		if (UNEXPECTED(hasHook.isUndef())) return zv::Val();
		if (!zend_is_true(hasHook.raw())) return zv::Val(zv::Arr::empty());

		zv::Val hook = pt_type_call(property, PT_LC("gethook"), 1, &hookNameZv);
		if (UNEXPECTED(hook.isUndef())) return zv::Val();
		if (UNEXPECTED(!hook.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function hasSideEffects() on %s", zend_zval_value_name(hook.raw()));
			return zv::Val();
		}
		zv::Val sideEffects = pt_type_call(hook.ref().asObject(), PT_LC("hassideeffects"), 0, NULL);
		if (UNEXPECTED(sideEffects.isUndef())) return zv::Val();
		if (pt_type_trinary_value(sideEffects.raw()) != PT_TRI_YES) return zv::Val(zv::Arr::empty());

		zv::Val declaringClass = pt_type_call(property, PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!declaringClass.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getDisplayName() on %s", zend_zval_value_name(declaringClass.raw()));
			return zv::Val();
		}
		zv::Val displayName = pt_type_call(declaringClass.ref().asObject(), PT_LC("getdisplayname"), 0, NULL);
		if (UNEXPECTED(displayName.isUndef())) return zv::Val();
		zv::Val propertyName = pt_type_call(property, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(propertyName.isUndef())) return zv::Val();

		zend_string *displayNameString = zval_get_string(displayName.raw());
		zend_string *propertyNameString = zval_get_string(propertyName.raw());
		zend_string *description = zend_strpprintf(0, "call to %s hook of property %s::$%s", ZSTR_VAL(hookName), ZSTR_VAL(displayNameString), ZSTR_VAL(propertyNameString));
		zend_string_release(displayNameString);
		zend_string_release(propertyNameString);
		zend_string *identifier = zend_string_init(PT_LC("propertyHookCall"), 0);
		zv::Val point = pt_impure_point_new(scope, propertyFetch, identifier, description, true);
		zend_string_release(identifier);
		zend_string_release(description);
		if (UNEXPECTED(point.isUndef())) return zv::Val();

		zv::Arr points = zv::Arr::create(1);
		points.push(std::move(point));
		return zv::Val(std::move(points));
	}

	/* private (twin 861): inside a hook of the same property, $this->prop is
	 * the backing value, not a re-entrant hook call; false = pending
	 * exception */
	[[nodiscard]] bool isPropertyHookBackingValueAccess(zend_object *scope, zend_object *propertyFetch, bool &out)
	{
		out = false;
		zv::Val function = pt_mutating_scope_get_function(scope);
		if (UNEXPECTED(function.isUndef())) return false;
		bool isParserNodeMethod;
		if (UNEXPECTED(!isInstanceValue(function.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, isParserNodeMethod))) return false;
		if (!isParserNodeMethod) return true;
		zend_object *functionObject = function.ref().asObject();
		zv::Val isPropertyHook = pt_type_call(functionObject, PT_LC("ispropertyhook"), 0, NULL);
		if (UNEXPECTED(isPropertyHook.isUndef())) return false;
		if (!zend_is_true(isPropertyHook.raw())) return true;

		zval *var = nodeProperty(pt_nsr_hook_fetch_var_site, propertyFetch, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		bool isVariable;
		if (UNEXPECTED(!isInstanceValue(var, PT_CLASS_VARIABLE, isVariable))) return false;
		if (!isVariable) return true;
		zval *varName = nodeProperty(pt_nsr_hook_this_name_site, Z_OBJ_P(var), PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (Z_TYPE_P(varName) != IS_STRING || !zend_string_equals_literal(Z_STR_P(varName), "this")) return true;

		zval *name = nodeProperty(pt_nsr_hook_fetch_name_site, propertyFetch, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		bool isIdentifier;
		if (UNEXPECTED(!isInstanceValue(name, PT_CLASS_IDENTIFIER, isIdentifier))) return false;
		if (!isIdentifier) return true;
		zv::Val nameString = pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(nameString.isUndef())) return false;
		zv::Val hookedPropertyName = pt_type_call(functionObject, PT_LC("gethookedpropertyname"), 0, NULL);
		if (UNEXPECTED(hookedPropertyName.isUndef())) return false;
		out = nameString.ref().isString()
			&& hookedPropertyName.ref().isString()
			&& zend_string_equals(nameString.ref().asString(), hookedPropertyName.ref().asString());
		return true;
	}

	/* Mirrors getAssignedVariables(). */
	zv::Val getAssignedVariables(zval *expr)
	{
		zend_object *exprObject = Z_OBJ_P(expr);
		bool is;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_VARIABLE, is))) return zv::Val();
		if (is) {
			zval *name = nodeProperty(pt_nsr_name_site, exprObject, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Arr names = zv::Arr::empty();
			if (Z_TYPE_P(name) == IS_STRING) {
				names = zv::Arr::create(1);
				names.push(zv::Ref(name));
			}
			return zv::Val(std::move(names));
		}

		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_LIST_EXPR, is))) return zv::Val();
		if (is) {
			zval *items = nodeProperty(pt_nsr_items_site, exprObject, PT_LC("items"));
			if (UNEXPECTED(items == NULL)) return zv::Val();
			zv::Arr names = zv::Arr::empty();
			if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(items));
				if (UNEXPECTED(EG(exception))) return zv::Val();
				return zv::Val(std::move(names));
			}
			zv::Val itemsHeld = zv::Val::copyOf(zv::Ref(items));
			for (auto entry : zv::ArrRef(itemsHeld.raw())) {
				zv::Ref item = entry.value().deref();
				if (item.isNull()) continue;
				if (UNEXPECTED(!item.isObject())) {
					zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(item.raw()));
					return zv::Val();
				}
				zval *value = nodeProperty(pt_nsr_item_value_site, item.asObject(), PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val itemNames;
				pt_engine_with_stack([&]() { itemNames = thisGetAssignedVariables(value); });
				if (UNEXPECTED(itemNames.isUndef())) return zv::Val();
				// $names = array_merge($names, ...): string lists renumbered
				if (itemNames.ref().isArray()) {
					for (auto nameEntry : zv::ArrRef(itemNames.raw())) {
						if (nameEntry.hasStringKey()) {
							names.separate();
							Z_TRY_ADDREF_P(nameEntry.value().raw());
							zend_hash_update(names.table(), nameEntry.stringKey(), nameEntry.value().raw());
						} else {
							names.push(nameEntry.value());
						}
					}
				}
			}
			return zv::Val(std::move(names));
		}

		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_ARRAY_DIM_FETCH, is))) return zv::Val();
		if (is) {
			zval *var = nodeProperty(pt_nsr_var_site, exprObject, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zv::Val result;
			pt_engine_with_stack([&]() { result = thisGetAssignedVariables(var); });
			return result;
		}

		zv::Arr empty = zv::Arr::empty();
		return zv::Val(std::move(empty));
	}

	/* Mirrors isReplayableConvergenceBody(). */
	[[nodiscard]] bool isReplayableConvergenceBody(zval *loopNode, HashTable *bodyStmts, bool &out)
	{
		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(loopNode), PT_LC("convergenceReplayableBody"));
		if (UNEXPECTED(cached.isUndef())) return false;
		if (!cached.isNull()) {
			if (UNEXPECTED(!cached.ref().isBool())) {
				zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::isReplayableConvergenceBody(): Return value must be of type bool, %s returned", zend_zval_value_name(cached.raw()));
				return false;
			}
			out = cached.ref().isTrue();
			return true;
		}

		bool replayable = true;
		for (auto entry : zv::TableRef(bodyStmts)) {
			zv::Ref bodyStmt = entry.value().deref();
			if (UNEXPECTED(!bodyStmt.isObject())) {
				zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::hasContextSensitiveConstruct(): Argument #1 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(bodyStmt.raw()));
				return false;
			}
			bool has;
			if (UNEXPECTED(!hasContextSensitiveConstruct(bodyStmt.asObject(), has))) return false;
			if (has) {
				replayable = false;
				break;
			}
		}
		zval value;
		ZVAL_BOOL(&value, replayable);
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(loopNode), PT_LC("convergenceReplayableBody"), &value))) return false;
		out = replayable;
		return true;
	}

	/* Mirrors pushNodeGatherer(). */
	void pushNodeGatherer(zval *gatherer)
	{
		zv::ArrRef(OBJ_PROP_NUM(self, slots::nodeGatherers)).push(zv::Ref(gatherer));
	}

	/* Mirrors popNodeGatherer(): array_pop() */
	void popNodeGatherer()
	{
		zval *gatherers = OBJ_PROP_NUM(self, slots::nodeGatherers);
		if (Z_TYPE_P(gatherers) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(gatherers)) == 0) return;
		SEPARATE_ARRAY(gatherers);
		HashTable *ht = Z_ARRVAL_P(gatherers);
		/* the gatherer list is packed by construction (push/pop/restore of
		 * lists); array_pop() removes the last element in order and pulls
		 * back the next free index */
		uint32_t idx = ht->nNumUsed;
		while (idx > 0) {
			idx--;
			if (HT_IS_PACKED(ht)) {
				if (Z_TYPE(ht->arPacked[idx]) == IS_UNDEF) continue;
				if ((zend_long) idx == ht->nNextFreeElement - 1) {
					ht->nNextFreeElement--;
				}
				zend_hash_index_del(ht, idx);
				return;
			}
			Bucket *p = ht->arData + idx;
			if (Z_TYPE(p->val) == IS_UNDEF) continue;
			if (p->key == NULL) {
				if ((zend_long) p->h == ht->nNextFreeElement - 1) {
					ht->nNextFreeElement--;
				}
				zend_hash_index_del(ht, p->h);
			} else {
				zend_hash_del(ht, p->key);
			}
			return;
		}
	}

	/* Mirrors suspendNodeGatherers(). */
	zv::Val suspendNodeGatherers()
	{
		zval *gatherers = OBJ_PROP_NUM(self, slots::nodeGatherers);
		zv::Val suspended = zv::Val::copyOf(zv::Ref(gatherers));
		zv::ObjRef(self).propAtWrite(slots::nodeGatherers, zv::Val(zv::Arr::empty()));
		return suspended;
	}

	/* Mirrors restoreNodeGatherers(). */
	void restoreNodeGatherers(zval *gatherers)
	{
		zv::ObjRef(self).propAtWrite(slots::nodeGatherers, zv::Val::copyOf(zv::Ref(gatherers)));
	}

	/* Mirrors replayRecording(). */
	[[nodiscard]] bool replayRecording(zval *recording, zval *nodeCallback, zval *storage, zval *scope)
	{
		zend_long count;
		if (UNEXPECTED(!recordingNodeCallbackCount(recording, count))) return false;
		return thisReplayRecordingRange(recording, 0, count, nodeCallback, storage, scope);
	}

	/* Mirrors replayRecordingRange(). */
	[[nodiscard]] bool replayRecordingRange(zval *recording, zend_long from, zend_long to, zval *nodeCallback, zval *storage, zval *scope)
	{
		zv::Val pairs = recordingNodeCallbackGetPairs(recording);
		if (UNEXPECTED(pairs.isUndef())) return false;
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage))) return false;
		for (zend_long i = from; i < to; i++) {
			zval *pair = pairs.ref().isArray() ? zend_hash_index_find(Z_ARRVAL_P(pairs.raw()), (zend_ulong) i) : NULL;
			zval *node = NULL;
			zval *pairScope = NULL;
			if (pair == NULL) {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, i);
				if (UNEXPECTED(EG(exception))) break;
			} else {
				ZVAL_DEREF(pair);
				if (Z_TYPE_P(pair) == IS_ARRAY) {
					node = zend_hash_index_find(Z_ARRVAL_P(pair), 0);
					pairScope = zend_hash_index_find(Z_ARRVAL_P(pair), 1);
				}
			}
			if (pairScope != NULL) {
				ZVAL_DEREF(pairScope);
			}
			if (pairScope == NULL || Z_TYPE_P(pairScope) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(pairScope), pt_ce_mutating_scope)) {
				pt_throw_should_not_happen();
				break;
			}
			zval nullNode;
			ZVAL_NULL(&nullNode);
			if (node == NULL) {
				node = &nullNode;
			}
			ZVAL_DEREF(node);
			if (UNEXPECTED(!thisCallNodeCallback(nodeCallback, node, pairScope, storage))) break;
		}
		pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
		return EG(exception) == NULL;
	}

	/* Mirrors callNodeCallbackWithExpression(). */
	[[nodiscard]] bool callNodeCallbackWithExpression(zval *nodeCallback, zval *expr, zval *scope, zval *storage, zval *context)
	{
		bool deep;
		if (UNEXPECTED(!pt_expression_context_is_deep(context, deep))) return false;
		if (!deep) return thisCallNodeCallback(nodeCallback, expr, scope, storage);
		zv::Val exited = pt_mutating_scope_exit_first_level_statements(Z_OBJ_P(scope));
		if (UNEXPECTED(exited.isUndef())) return false;
		if (UNEXPECTED(!requireObject(exited, "callNodeCallback"))) return false;
		return thisCallNodeCallback(nodeCallback, expr, exited.raw(), storage);
	}

	/* Mirrors callNodeCallback(). */
	[[nodiscard]] bool callNodeCallback(zval *nodeCallback, zval *node, zval *scope, zval *storage)
	{
		(void) storage;
		zval *gatherers = OBJ_PROP_NUM(self, slots::nodeGatherers);
		if (Z_TYPE_P(gatherers) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(gatherers)) > 0) {
			/* foreach iterates the array as it was when the loop started */
			zv::Val snapshot = zv::Val::copyOf(zv::Ref(gatherers));
			for (auto entry : zv::ArrRef(snapshot.raw())) {
				if (UNEXPECTED(!pt_engine_call_node_callback(entry.value().raw(), node, scope))) return false;
			}
		}

		zval *callback = nodeCallback;
		ZVAL_DEREF(callback);
		if (isNoopNodeCallback(callback)) return true;
		if (UNEXPECTED(EG(exception))) return false;
		if (isRecordingNodeCallback(callback)) return recordingNodeCallbackInvoke(callback, node, scope);
		if (UNEXPECTED(EG(exception))) return false;

		zv::Val callbackScope = pt_mutating_scope_to_node_callback_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(callbackScope.isUndef())) return false;
		return pt_engine_call_node_callback(nodeCallback, node, callbackScope.raw());
	}

	/* Mirrors observingTemplateArgumentFrame(). */
	zv::Val observingTemplateArgumentFrame(zval *scope)
	{
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		bool observing;
		if (UNEXPECTED(!templateArgumentFrameIsObserving(frame.raw(), observing))) return zv::Val();
		if (!observing) return zv::Val::null();
		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(scope));
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		if (constraints.isNull()) return zv::Val::null();
		return frame;
	}

	/* Mirrors collectReturnSend(). */
	zv::Val collectReturnSend(zval *scope, zval *returnedResult)
	{
		zv::Val returnedScope = expressionResultScope(returnedResult);
		if (UNEXPECTED(returnedScope.isUndef())) return zv::Val();
		zv::Val frame = thisObservingTemplateArgumentFrame(returnedScope.raw());
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (frame.isNull()) return templateArgumentConstraintsCreateEmpty();
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return zv::Val();
		zv::Val declaredReturnType;
		if (inAnonymousFunction) {
			declaredReturnType = pt_mutating_scope_get_anonymous_function_return_type(Z_OBJ_P(scope));
		} else {
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			declaredReturnType = function.isNull() ? zv::Val::null() : functionGetReturnType(function.raw());
		}
		if (UNEXPECTED(declaredReturnType.isUndef())) return zv::Val();
		if (declaredReturnType.isNull()) return templateArgumentConstraintsCreateEmpty();
		zv::Val returnedType = pt_expression_result_get_type(returnedResult);
		if (UNEXPECTED(returnedType.isUndef())) return zv::Val();
		zv::Ref observer = slot(slots::templateArgumentObserver);
		if (UNEXPECTED(!observer.isObject())) return uninitialized("templateArgumentObserver");
		return templateArgumentObserverCollectSend(observer.raw(), declaredReturnType.raw(), returnedType.raw());
	}

	/* {{{ $this-dispatch of the public methods the twin calls on itself: the
	 * native body for exactly this class, the method through the object's
	 * class entry otherwise */

	bool exact() const { return EXPECTED(self->ce == pt_ce_node_scope_resolver); }

	zv::Val thisProcessExprNode(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		if (exact()) return processExprNode(stmt, expr, scope, storage, nodeCallback, context);
		zv::Args argv{stmt, expr, scope, storage, nodeCallback, context};
		return pt_type_call(self, PT_LC("processexprnode"), 6, argv);
	}

	zv::Val thisProcessStmtNodesInternal(zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		if (exact()) return processStmtNodesInternal(parentNode, stmts, scope, storage, nodeCallback, context);
		zv::Args argv{parentNode, stmts, scope, storage, nodeCallback, context};
		return pt_type_call(self, PT_LC("processstmtnodesinternal"), 6, argv);
	}

	[[nodiscard]] bool thisStoreExpressionResult(zval *storage, zval *expr, zval *expressionResult)
	{
		if (exact()) {
			storeExpressionResult(storage, expr, expressionResult);
			return EG(exception) == NULL;
		}
		zv::Args argv{storage, expr, expressionResult};
		return !pt_type_call(self, PT_LC("storeexpressionresult"), 3, argv).isUndef();
	}

	[[nodiscard]] bool thisCallNodeCallbackWithExpression(zval *nodeCallback, zval *expr, zval *scope, zval *storage, zval *context)
	{
		if (exact()) return callNodeCallbackWithExpression(nodeCallback, expr, scope, storage, context);
		zv::Args argv{nodeCallback, expr, scope, storage, context};
		return !pt_type_call(self, PT_LC("callnodecallbackwithexpression"), 5, argv).isUndef();
	}

	[[nodiscard]] bool thisCallNodeCallback(zval *nodeCallback, zval *node, zval *scope, zval *storage)
	{
		if (exact()) return callNodeCallback(nodeCallback, node, scope, storage);
		zv::Args argv{nodeCallback, node, scope, storage};
		return !pt_type_call(self, PT_LC("callnodecallback"), 4, argv).isUndef();
	}

	zv::Val thisSuspendNodeGatherers()
	{
		if (exact()) return suspendNodeGatherers();
		return pt_type_call(self, PT_LC("suspendnodegatherers"), 0, NULL);
	}

	[[nodiscard]] bool thisRestoreNodeGatherers(zval *gatherers)
	{
		if (exact()) {
			restoreNodeGatherers(gatherers);
			return true;
		}
		return !pt_type_call(self, PT_LC("restorenodegatherers"), 1, gatherers).isUndef();
	}

	zv::Val thisFindScopeStateType(zval *expr, zval *scope)
	{
		if (exact()) return findScopeStateType(expr, scope);
		zv::Args argv{expr, scope};
		return pt_type_call(self, PT_LC("findscopestatetype"), 2, argv);
	}

	zv::Val thisReadScopeStateOrSyntheticType(zval *expr, zval *scope)
	{
		if (exact()) return readScopeStateOrSyntheticType(expr, scope);
		zv::Args argv{expr, scope};
		return pt_type_call(self, PT_LC("readscopestateorsynthetictype"), 2, argv);
	}

	zv::Val thisProcessSyntheticOnDemand(zval *expr, zval *scope)
	{
		if (exact()) return processSyntheticOnDemand(expr, scope);
		zv::Args argv{expr, scope};
		return pt_type_call(self, PT_LC("processsyntheticondemand"), 2, argv);
	}

	zv::Val thisProcessExprOnDemand(zval *expr, zval *scope, zval *storage)
	{
		if (exact()) return processExprOnDemand(expr, scope, storage);
		zv::Args argv{expr, scope, storage};
		return pt_type_call(self, PT_LC("processexprondemand"), 3, argv);
	}

	zv::Val thisGetAssignedVariables(zval *expr)
	{
		if (exact()) return getAssignedVariables(expr);
		return pt_type_call(self, PT_LC("getassignedvariables"), 1, expr);
	}

	zv::Val thisObservingTemplateArgumentFrame(zval *scope)
	{
		if (exact()) return observingTemplateArgumentFrame(scope);
		return pt_type_call(self, PT_LC("observingtemplateargumentframe"), 1, scope);
	}

	[[nodiscard]] bool thisReplayRecordingRange(zval *recording, zend_long from, zend_long to, zval *nodeCallback, zval *storage, zval *scope)
	{
		if (exact()) return replayRecordingRange(recording, from, to, nodeCallback, storage, scope);
		zv::Args argv{recording, from, to, nodeCallback, storage, scope};
		return !pt_type_call(self, PT_LC("replayrecordingrange"), 6, argv).isUndef();
	}

	/* }}} */

private:
	zend_object *self;

	zv::Ref slot(uint32_t index) const { return zv::Ref(OBJ_PROP_NUM(self, index)); }

	bool flag(uint32_t index) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		ZVAL_DEREF(value);
		return Z_TYPE_P(value) == IS_TRUE;
	}

	void setFlag(uint32_t index, bool value)
	{
		zval *target = OBJ_PROP_NUM(self, index);
		ZVAL_DEREF(target);
		ZVAL_BOOL(target, value);
	}

	/* the Error calling a method on a non-object walk scope raises */
	static bool requireObject(zv::Val &value, const char *method)
	{
		if (EXPECTED(value.ref().isObject())) return true;
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value.raw()));
		return false;
	}

	/* $this->statementsHandler, NULL = the uninitialized-property Error */
	zval *requireStatementsHandler()
	{
		zval *statementsHandler = OBJ_PROP_NUM(self, slots::statementsHandler);
		if (EXPECTED(Z_TYPE_P(statementsHandler) == IS_OBJECT)) return statementsHandler;
		(void) uninitialized("statementsHandler");
		return NULL;
	}

	/* the private getNonNullabilityHelper(): $this->nonNullabilityHelper ??=
	 * $this->container->getByType(NonNullabilityHelper::class); the slot,
	 * NULL = pending exception */
	zval *getNonNullabilityHelper()
	{
		zval *helper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		if (EXPECTED(Z_TYPE_P(helper) == IS_OBJECT)) return helper;
		zv::Ref container = slot(slots::container);
		if (UNEXPECTED(!container.isObject())) {
			(void) uninitialized("container");
			return NULL;
		}
		zv::Val service = containerGetByType(container.raw(), PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\NonNullabilityHelper"));
		if (UNEXPECTED(service.isUndef())) return NULL;
		/* the typed property assignment checks the service's type */
		zend_string *name = zend_string_init(PT_LC("nonNullabilityHelper"), 0);
		zend_update_property_ex(pt_ce_node_scope_resolver, self, name, service.raw());
		zend_string_release(name);
		if (UNEXPECTED(EG(exception))) return NULL;
		helper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		ZVAL_DEREF(helper);
		return helper;
	}

	/* the private lookForExpressionCallback() with the closure the public
	 * caller passes: $scope->setAllowedUndefinedExpression($expr) (set) or
	 * ->unsetAllowedUndefinedExpression($expr) */
	zv::Val lookForExpressionCallback(zval *scope, zval *expr, bool set)
	{
		zend_object *exprObject = Z_OBJ_P(expr);
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		zv::Val current = zv::Val::copyOf(zv::Ref(scope));
		bool apply = !isArrayDimFetch;
		if (isArrayDimFetch) {
			zval *dim = nodeProperty(pt_nsr_dim_site, exprObject, PT_LC("dim"));
			if (UNEXPECTED(dim == NULL)) return zv::Val();
			apply = Z_TYPE_P(dim) != IS_NULL;
		}
		if (apply) {
			if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(current.raw()), pt_ce_mutating_scope))) {
				zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::{closure}(): Argument #1 ($scope) must be of type PHPStan\\Analyser\\MutatingScope, %s given", zend_zval_value_name(current.raw()));
				return zv::Val();
			}
			current = set
				? pt_mutating_scope_set_allowed_undefined_expression(Z_OBJ_P(current.raw()), exprObject)
				: pt_mutating_scope_unset_allowed_undefined_expression(Z_OBJ_P(current.raw()), exprObject);
			if (UNEXPECTED(current.isUndef())) return zv::Val();
			if (UNEXPECTED(!current.ref().isObject() || !instanceof_function(Z_OBJCE_P(current.raw()), pt_ce_mutating_scope))) {
				zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::{closure}(): Return value must be of type PHPStan\\Analyser\\MutatingScope, %s returned", zend_zval_value_name(current.raw()));
				return zv::Val();
			}
		}

		zval *next = NULL;
		if (isArrayDimFetch) {
			next = nodeProperty(pt_nsr_var_site, exprObject, PT_LC("var"));
			if (UNEXPECTED(next == NULL)) return zv::Val();
		} else {
			bool is;
			if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_PROPERTY_FETCH, is))) return zv::Val();
			if (!is && UNEXPECTED(!isInstance(exprObject, PT_CLASS_NULLSAFE_PROPERTY_FETCH, is))) return zv::Val();
			if (!is && UNEXPECTED(!isInstance(exprObject, PT_CLASS_NULLSAFE_METHOD_CALL, is))) return zv::Val();
			if (is) {
				next = nodeProperty(pt_nsr_var_site, exprObject, PT_LC("var"));
				if (UNEXPECTED(next == NULL)) return zv::Val();
			} else {
				if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_STATIC_PROPERTY_FETCH, is))) return zv::Val();
				if (is) {
					zval *classNode = nodeProperty(pt_nsr_class_site, exprObject, PT_LC("class"));
					if (UNEXPECTED(classNode == NULL)) return zv::Val();
					bool isExpr;
					if (UNEXPECTED(!isInstanceValue(classNode, PT_CLASS_EXPR, isExpr))) return zv::Val();
					if (isExpr) {
						next = classNode;
					}
				} else {
					if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_LIST_EXPR, is))) return zv::Val();
					if (is) {
						zval *items = nodeProperty(pt_nsr_items_site, exprObject, PT_LC("items"));
						if (UNEXPECTED(items == NULL)) return zv::Val();
						if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
							zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(items));
							if (UNEXPECTED(EG(exception))) return zv::Val();
							return current;
						}
						zv::Val itemsHeld = zv::Val::copyOf(zv::Ref(items));
						for (auto entry : zv::ArrRef(itemsHeld.raw())) {
							zv::Ref item = entry.value().deref();
							if (item.isNull()) continue;
							if (UNEXPECTED(!item.isObject())) {
								zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(item.raw()));
								return zv::Val();
							}
							zval *value = nodeProperty(pt_nsr_item_value_site, item.asObject(), PT_LC("value"));
							if (UNEXPECTED(value == NULL)) return zv::Val();
							if (UNEXPECTED(!requireExpr(value))) return zv::Val();
							zv::Val itemScope;
							pt_engine_with_stack([&]() { itemScope = lookForExpressionCallback(current.raw(), value, set); });
							if (UNEXPECTED(itemScope.isUndef())) return zv::Val();
							current = std::move(itemScope);
						}
						return current;
					}
				}
			}
		}

		if (next != NULL) {
			if (UNEXPECTED(!requireExpr(next))) return zv::Val();
			zv::Val nextScope;
			pt_engine_with_stack([&]() { nextScope = lookForExpressionCallback(current.raw(), next, set); });
			return nextScope;
		}
		return current;
	}

	/* the Expr parameter check of the private recursive helpers */
	static bool requireExpr(zval *value)
	{
		bool isExpr;
		if (UNEXPECTED(!isInstanceValue(value, PT_CLASS_EXPR, isExpr))) return false;
		if (EXPECTED(isExpr)) return true;
		zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::lookForExpressionCallback(): Argument #2 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(value));
		return false;
	}

	/* the private guardAgainstUnprocessedRealNode(); false = pending
	 * exception (the guard's own included) */
	[[nodiscard]] bool guardAgainstUnprocessedRealNode(zend_object *expr, const char *caller)
	{
		if (EXPECTED(!guardNewWorld())) return true;
		const NsrStatics &s = statics();
		if (!staticArrayHas(s.guardRealExprIds, expr->handle) || staticArrayHas(s.guardProcessedExprIds, expr->handle)) return true;
		zv::Val line = pt_type_call(expr, PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(line.isUndef())) return false;
		zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (UNEXPECTED(ce == NULL)) return false;
		zend_throw_exception_ex(ce, 0, "%s() asked about non-synthetic %s on line " ZEND_LONG_FMT " before it was processed by processExprNode() - it should consume the node's ExpressionResult instead.", caller, ZSTR_VAL(expr->ce->name), (zend_long) zval_get_long(line.raw()));
		return false;
	}

	/* the private hasContextSensitiveConstruct(); false = pending exception */
	[[nodiscard]] bool hasContextSensitiveConstruct(zend_object *node, bool &out)
	{
		bool ok = true;
		pt_engine_with_stack([&]() { ok = hasContextSensitiveConstructBody(node, out); });
		return ok;
	}

	[[nodiscard]] bool hasContextSensitiveConstructBody(zend_object *node, bool &out)
	{
		out = false;
		bool is;
		if (UNEXPECTED(!isInstance(node, PT_CLASS_CLOSURE_EXPR, is))) return false;
		if (is) return true;
		static const int contextSensitive[] = { PT_CLASS_WHILE_STMT, PT_CLASS_DO_STMT, PT_CLASS_FOR_STMT, PT_CLASS_FOREACH_STMT, PT_CLASS_LABEL_STMT, PT_CLASS_CLASS_LIKE_STMT };
		for (int classIdx : contextSensitive) {
			if (UNEXPECTED(!isInstance(node, classIdx, is))) return false;
			if (is) {
				out = true;
				return true;
			}
		}

		zv::Val names = nodeGetSubNodeNames(node);
		if (UNEXPECTED(names.isUndef())) return false;
		if (UNEXPECTED(!names.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(names.raw()));
			return EG(exception) == NULL;
		}
		for (auto entry : zv::ArrRef(names.raw())) {
			zv::Ref name = entry.value().deref();
			zend_string *nameString = zval_get_string(name.raw());
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *subNode = zend_read_property_ex(node->ce, node, nameString, 0, &rv);
			zend_string_release(nameString);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&rv);
				return false;
			}
			zv::Val held = zv::Val::copyOf(zv::Ref(subNode).deref());
			zval_ptr_dtor(&rv);
			bool isNode;
			if (UNEXPECTED(!isInstanceValue(held.raw(), PT_CLASS_NODE, isNode))) return false;
			if (isNode) {
				bool has;
				if (UNEXPECTED(!hasContextSensitiveConstruct(Z_OBJ_P(held.raw()), has))) return false;
				if (has) {
					out = true;
					return true;
				}
			} else if (held.ref().isArray()) {
				for (auto itemEntry : zv::ArrRef(held.raw())) {
					zval *item = itemEntry.value().deref().raw();
					bool itemIsNode;
					if (UNEXPECTED(!isInstanceValue(item, PT_CLASS_NODE, itemIsNode))) return false;
					if (!itemIsNode) continue;
					bool has;
					if (UNEXPECTED(!hasContextSensitiveConstruct(Z_OBJ_P(item), has))) return false;
					if (has) {
						out = true;
						return true;
					}
				}
			}
		}
		return true;
	}

	/* {{{ processStmtNodesInternal() / processStmtNode() / processExprNode() bodies */

	zv::Val processStmtNodesInternalBody(zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val current = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
		if (UNEXPECTED(current.isUndef())) return zv::Val();
		bool pushStorage = !(current.ref().isObject() && Z_OBJ_P(current.raw()) == Z_OBJ_P(storage));
		if (pushStorage && UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage))) return zv::Val();
		zval thisZv;
		ZVAL_OBJ(&thisZv, self);
		zv::Val result;
		zval *statementsHandler = requireStatementsHandler();
		if (EXPECTED(statementsHandler != NULL)) {
			result = pt_statements_handler_do_process_stmt_nodes(statementsHandler, &thisZv, parentNode, stmts, scope, storage, nodeCallback, context);
		}
		if (pushStorage) {
			pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}
		return result;
	}

	zv::Val processStmtNodeBody(zval *stmt, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context)
	{
		zend_object *stmtObject = Z_OBJ_P(stmt);
		zval thisZv;
		ZVAL_OBJ(&thisZv, self);
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zv::Val overridingThrowPoints = zv::Val::null();

		uint32_t shape;
		if (UNEXPECTED(!stmtShapeOf(stmtObject->ce, shape))) return zv::Val();
		if ((shape & PT_NSR_STMT_DECLARATION) == 0) {
			zval *statementsHandler = requireStatementsHandler();
			if (UNEXPECTED(statementsHandler == NULL)) return zv::Val();
			if ((shape & PT_NSR_STMT_FOREACH) == 0) {
				scope = pt_statements_handler_process_stmt_var_annotation(statementsHandler, &thisZv, scope.raw(), storage, stmt, NULL, nodeCallback);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
				if (UNEXPECTED(!requireObject(scope, "getComments"))) return zv::Val();
			}
			overridingThrowPoints = pt_statements_handler_get_overriding_throw_points(statementsHandler, stmt, scope.raw());
			if (UNEXPECTED(overridingThrowPoints.isUndef())) return zv::Val();
		}

		if ((shape & PT_NSR_STMT_CLASS_METHOD) != 0) {
			zv::Val skipped = classMethodOfUsingClass(stmtObject, scope.raw());
			if (UNEXPECTED(skipped.isUndef()) || !skipped.isNull()) return skipped;
		}

		if ((shape & PT_NSR_STMT_DEFERRED_CALLBACK) == 0 && UNEXPECTED(!thisCallNodeCallback(nodeCallback, stmt, scope.raw(), storage))) return zv::Val();

		zv::Ref container = slot(slots::container);
		if (UNEXPECTED(!container.isObject())) return uninitialized("container");
		zv::Val stmtHandler = pt_stmt_handler_registry_resolve(stmtObject, container.raw());
		if (UNEXPECTED(stmtHandler.isUndef())) return zv::Val();
		if (!stmtHandler.isNull()) {
			zv::Val stmtResult = pt_stmt_handler_process(stmtHandler.raw(), &thisZv, stmt, scope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(stmtResult.isUndef())) return zv::Val();
			if (overridingThrowPoints.isNull()) return stmtResult;
			return withOverridingThrowPoints(stmtResult.raw(), overridingThrowPoints.raw());
		}

		// statements with no analysis of their own (e.g. HaltCompiler)
		return emptyInternalStatementResult(scope.raw(), overridingThrowPoints.isNull() ? NULL : overridingThrowPoints.raw());
	}

	/* the ClassMethod prelude of processStmtNode(): the empty result of a
	 * trait method the using class overrides, null to analyse it, UNDEF =
	 * pending exception */
	zv::Val classMethodOfUsingClass(zend_object *stmt, zval *scope)
	{
		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		if (!inClass) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		bool inTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), inTrait))) return zv::Val();
		if (!inTrait) return zv::Val::null();

		zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef() || !requireObject(classReflection, "hasNativeMethod"))) return zv::Val();
		zv::Val methodName = identifierToString(stmt);
		if (UNEXPECTED(methodName.isUndef())) return zv::Val();
		zv::Val hasNativeMethod = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("hasnativemethod"), 1, methodName.raw());
		if (UNEXPECTED(hasNativeMethod.isUndef())) return zv::Val();
		if (Z_TYPE_P(hasNativeMethod.raw()) != IS_TRUE) return zv::Val::null();

		zv::Val classReflectionAgain = pt_mutating_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflectionAgain.isUndef() || !requireObject(classReflectionAgain, "getNativeMethod"))) return zv::Val();
		zv::Val methodNameAgain = identifierToString(stmt);
		if (UNEXPECTED(methodNameAgain.isUndef())) return zv::Val();
		zv::Val methodReflection = pt_type_call(Z_OBJ_P(classReflectionAgain.raw()), PT_LC("getnativemethod"), 1, methodNameAgain.raw());
		if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
		bool isNative;
		if (UNEXPECTED(!isInstanceValue(methodReflection.raw(), PT_CLASS_NATIVE_METHOD_REFLECTION, isNative))) return zv::Val();
		if (isNative) return emptyInternalStatementResult(scope, NULL);
		bool isPhp;
		if (UNEXPECTED(!isInstanceValue(methodReflection.raw(), PT_CLASS_PHP_METHOD_REFLECTION, isPhp))) return zv::Val();
		if (!isPhp) return zv::Val::null();
		zv::Val declaringTrait = pt_type_call(Z_OBJ_P(methodReflection.raw()), PT_LC("getdeclaringtrait"), 0, NULL);
		if (UNEXPECTED(declaringTrait.isUndef())) return zv::Val();
		if (declaringTrait.isNull()) return emptyInternalStatementResult(scope, NULL);
		zv::Val declaringTraitName = requireObject(declaringTrait, "getName") ? pt_class_reflection_get_name(Z_OBJ_P(declaringTrait.raw())) : zv::Val();
		if (UNEXPECTED(declaringTraitName.isUndef())) return zv::Val();
		zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(traitReflection.isUndef() || !requireObject(traitReflection, "getName"))) return zv::Val();
		zv::Val traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection.raw()));
		if (UNEXPECTED(traitName.isUndef())) return zv::Val();
		if (!zend_is_identical(declaringTraitName.raw(), traitName.raw())) return emptyInternalStatementResult(scope, NULL);
		return zv::Val::null();
	}

	/* $stmt->name->toString() */
	static zv::Val identifierToString(zend_object *stmt)
	{
		zval *name = nodeProperty(pt_nsr_stmt_name_site, stmt, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
	}

	/* processStmtNode()'s statement result with the @throws override */
	static zv::Val withOverridingThrowPoints(zval *stmtResult, zval *overridingThrowPoints)
	{
		if (UNEXPECTED(Z_TYPE_P(stmtResult) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getScope() on %s", zend_zval_value_name(stmtResult));
			return zv::Val();
		}
		uint32_t count = Z_TYPE_P(overridingThrowPoints) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(overridingThrowPoints)) : 0;
		zval *flows = (zval *) safe_emalloc(count + 1, sizeof(zval), 0);
		uint32_t built = 0;
		bool failed = false;
		if (count > 0) {
			for (auto entry : zv::ArrRef(overridingThrowPoints)) {
				zval *throwPoint = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(throwPoint) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(throwPoint));
					failed = true;
					break;
				}
				zv::Val typeHold;
				zval *type = pt_internal_throw_point_type(throwPoint, typeHold);
				bool anyThrowable;
				if (UNEXPECTED(type == NULL || !pt_internal_throw_point_can_contain_any_throwable(throwPoint, anyThrowable))) {
					failed = true;
					break;
				}
				zv::Val flow = pt_variable_flow_throwing(type, true, anyThrowable);
				if (UNEXPECTED(flow.isUndef())) {
					failed = true;
					break;
				}
				flows[built++] = flow.take();
			}
		}

		zv::Val result;
		if (!failed) {
			zv::Val scopeHold, exitPointsHold, impurePointsHold, endStatementsHold, variableFlowHold;
			zval *scope = pt_internal_statement_result_scope(stmtResult, scopeHold);
			bool hasYield = false;
			bool terminating = false;
			bool ok = scope != NULL
				&& pt_internal_statement_result_has_yield(stmtResult, hasYield)
				&& pt_internal_statement_result_is_always_terminating(stmtResult, terminating);
			zval *exitPoints = ok ? pt_internal_statement_result_exit_points(stmtResult, exitPointsHold) : NULL;
			zval *impurePoints = exitPoints != NULL ? pt_internal_statement_result_impure_points(stmtResult, impurePointsHold) : NULL;
			zval *endStatements = impurePoints != NULL ? pt_internal_statement_result_end_statements(stmtResult, endStatementsHold) : NULL;
			zval *variableFlow = endStatements != NULL ? pt_internal_statement_result_variable_flow(stmtResult, variableFlowHold) : NULL;
			if (variableFlow != NULL) {
				ZVAL_COPY_VALUE(&flows[built], variableFlow);
				zv::Val sequence = pt_variable_flow_sequence(built + 1, flows);
				if (!sequence.isUndef()) {
					result = pt_internal_statement_result_new(scope, hasYield, terminating, exitPoints, overridingThrowPoints, impurePoints, endStatements, sequence.raw());
				}
			}
		}
		for (uint32_t i = 0; i < built; i++) {
			zval_ptr_dtor(&flows[i]);
		}
		efree(flows);
		return result;
	}

	zv::Val processExprNodeBody(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		if (flag(slots::returnStoredExpressionResults) || flag(slots::consumeStoredExpressionResults)) {
			zv::Val storedResult = pt_expression_result_storage_find(storage, expr);
			if (UNEXPECTED(storedResult.isUndef())) return zv::Val();
			if (!storedResult.isNull()) {
				bool answers = flag(slots::consumeStoredExpressionResults);
				if (!answers) {
					bool promoted;
					if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return zv::Val();
					if (UNEXPECTED(!pt_expression_result_ask_scope_variable_state_matches(storedResult.raw(), scope, promoted, answers))) return zv::Val();
				}
				if (answers) {
					zv::Val beforeScope = expressionResultBeforeScope(storedResult.raw());
					if (UNEXPECTED(beforeScope.isUndef())) return zv::Val();
					if (beforeScope.ref().isObject() && Z_OBJ_P(beforeScope.raw()) == Z_OBJ_P(scope)) return storedResult;

					zv::Val reanchored = pt_expression_result_at_ask_position(storedResult.raw(), scope);
					if (UNEXPECTED(reanchored.isUndef())) return zv::Val();
					if (flag(slots::consumeStoredExpressionResults) && UNEXPECTED(!thisStoreExpressionResult(storage, expr, reanchored.raw()))) return zv::Val();
					return reanchored;
				}
			}
		}

		return processExprNodeInternal(stmt, expr, scope, storage, nodeCallback, context);
	}

	/* the private processExprNodeInternal() */
	zv::Val processExprNodeInternal(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zend_object *exprObject = Z_OBJ_P(expr);
		bool isCallLike;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_CALL_LIKE, isCallLike))) return zv::Val();
		if (isCallLike) {
			bool firstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(exprObject, firstClassCallable))) return zv::Val();
			if (firstClassCallable) return processFirstClassCallable(stmt, expr, scope, storage, nodeCallback, context);
		}

		zv::Ref container = slot(slots::container);
		if (UNEXPECTED(!container.isObject())) return uninitialized("container");
		zv::Val exprHandler = pt_expr_handler_registry_resolve(exprObject, container.raw());
		if (UNEXPECTED(exprHandler.isUndef())) return zv::Val();
		if (UNEXPECTED(exprHandler.isNull())) {
			zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
			if (ce != NULL) {
				zend_throw_exception_ex(ce, 0, "Unhandled expr: %s", ZSTR_VAL(exprObject->ce->name));
			}
			return zv::Val();
		}

		zval thisZv;
		ZVAL_OBJ(&thisZv, self);
		zv::Val expressionResult = pt_expr_handler_process(exprHandler.raw(), &thisZv, stmt, expr, scope, storage, nodeCallback, context);
		if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();
		// a chain link an enclosing isset/empty/?? could not device ahead
		// of its walk (an untracked call) is deviced now, from the type the
		// walk produced
		zval *helper = getNonNullabilityHelper();
		if (UNEXPECTED(helper == NULL)) return zv::Val();
		expressionResult = pt_non_nullability_helper_apply_pending_ensure(helper, expr, expressionResult.raw());
		if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!thisStoreExpressionResult(storage, expr, expressionResult.raw()))) return zv::Val();

		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (!frame.isNull()) {
			bool observing;
			if (UNEXPECTED(!templateArgumentFrameIsObserving(frame.raw(), observing))) return zv::Val();
			if (observing && isCallLike) {
				zv::Val type = pt_expression_result_get_type(expressionResult.raw());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Ref observer = slot(slots::templateArgumentObserver);
				if (UNEXPECTED(!observer.isObject())) return uninitialized("templateArgumentObserver");
				zv::Val constraints = templateArgumentObserverCollectSites(observer.raw(), type.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				zv::Val resultScope = expressionResultScope(expressionResult.raw());
				if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
				zv::Val constrainedScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(resultScope.raw()), constraints.raw());
				if (UNEXPECTED(constrainedScope.isUndef())) return zv::Val();
				expressionResult = pt_expression_result_with_scope(expressionResult.raw(), constrainedScope.raw());
				if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();
				if (UNEXPECTED(!thisStoreExpressionResult(storage, expr, expressionResult.raw()))) return zv::Val();
			}
		}

		// the node's own callback fires AFTER its result is stored, with the
		// scope captured before processing
		if (UNEXPECTED(!thisCallNodeCallbackWithExpression(nodeCallback, expr, scope, storage, context))) return zv::Val();
		if (isCallLike) {
			static const int calls[][2] = {
				{ PT_CLASS_FUNC_CALL, PT_CLASS_FUNCTION_CALL_EXPRESSION_NODE },
				{ PT_CLASS_METHOD_CALL, PT_CLASS_METHOD_CALL_EXPRESSION_NODE },
				{ PT_CLASS_STATIC_CALL, PT_CLASS_STATIC_METHOD_CALL_EXPRESSION_NODE },
			};
			for (const auto &call : calls) {
				bool is;
				if (UNEXPECTED(!isInstance(exprObject, call[0], is))) return zv::Val();
				if (!is) continue;
				zv::Val argsResult = pt_expression_result_get_args_result(expressionResult.raw());
				if (UNEXPECTED(argsResult.isUndef())) return zv::Val();
				zv::Args argv{expr, expressionResult.raw(), argsResult.raw()};
				zv::Val virtualNode = pt_type_new(call[1], 3, argv);
				if (UNEXPECTED(virtualNode.isUndef())) return zv::Val();
				if (UNEXPECTED(!thisCallNodeCallbackWithExpression(nodeCallback, virtualNode.raw(), scope, storage, context))) return zv::Val();
				break;
			}
		}
		return expressionResult;
	}

	/* the first-class-callable branch of processExprNodeInternal() */
	zv::Val processFirstClassCallable(zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zend_object *exprObject = Z_OBJ_P(expr);
		zv::Val newExpr;
		bool is;
		if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_FUNC_CALL, is))) return zv::Val();
		if (is) {
			zval *name = nodeProperty(pt_nsr_name_site, exprObject, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Args argv{name, expr};
			newExpr = pt_type_new(PT_CLASS_FUNCTION_CALLABLE_NODE, 2, argv);
		} else {
			if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_METHOD_CALL, is))) return zv::Val();
			if (is) {
				zval *var = nodeProperty(pt_nsr_var_site, exprObject, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return zv::Val();
				zval *name = nodeProperty(pt_nsr_name_site, exprObject, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				zv::Args argv{var, name, expr};
				newExpr = pt_type_new(PT_CLASS_METHOD_CALLABLE_NODE, 3, argv);
			} else {
				if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_STATIC_CALL, is))) return zv::Val();
				if (is) {
					zval *classNode = nodeProperty(pt_nsr_class_site, exprObject, PT_LC("class"));
					if (UNEXPECTED(classNode == NULL)) return zv::Val();
					zval *name = nodeProperty(pt_nsr_name_site, exprObject, PT_LC("name"));
					if (UNEXPECTED(name == NULL)) return zv::Val();
					zv::Args argv{classNode, name, expr};
					newExpr = pt_type_new(PT_CLASS_STATIC_METHOD_CALLABLE_NODE, 3, argv);
				} else {
					if (UNEXPECTED(!isInstance(exprObject, PT_CLASS_NEW, is))) return zv::Val();
					bool anonymousClass = false;
					zval *classNode = NULL;
					if (is) {
						classNode = nodeProperty(pt_nsr_class_site, exprObject, PT_LC("class"));
						if (UNEXPECTED(classNode == NULL)) return zv::Val();
						if (UNEXPECTED(!isInstanceValue(classNode, PT_CLASS_CLASS_STMT, anonymousClass))) return zv::Val();
					}
					if (!is || anonymousClass) {
						pt_throw_should_not_happen();
						return zv::Val();
					}
					zv::Args argv{classNode, expr};
					newExpr = pt_type_new(PT_CLASS_INSTANTIATION_CALLABLE_NODE, 2, argv);
				}
			}
		}
		if (UNEXPECTED(newExpr.isUndef())) return zv::Val();

		zv::Val newExprResult = thisProcessExprNode(stmt, newExpr.raw(), scope, storage, nodeCallback, context);
		if (UNEXPECTED(newExprResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!newExprResult.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getScope() on %s", zend_zval_value_name(newExprResult.raw()));
			return zv::Val();
		}
		zv::Val resultScope = expressionResultScope(newExprResult.raw());
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(newExprResult.raw(), hasYield))) return zv::Val();
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(newExprResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val throwPoints = expressionResultThrowPoints(newExprResult.raw());
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zv::Val impurePoints = expressionResultImpurePoints(newExprResult.raw());
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();
		zv::Val variableFlow = pt_expression_result_variable_flow(newExprResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&firstClassCallableTypeCallbackBody, newExprResult.raw());
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		zv::Ref factory = slot(slots::expressionResultFactory);
		if (UNEXPECTED(!factory.isObject())) return uninitialized("expressionResultFactory");
		pt_expression_result_args args(resultScope.raw(), scope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		zv::Val expressionResult = pt_expression_result_create(factory.raw(), args);
		if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!thisStoreExpressionResult(storage, expr, expressionResult.raw()))) return zv::Val();
		return expressionResult;
	}

	/* static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ?
	 * $newExprResult->getNativeType() : $newExprResult->getType()) —
	 * captures: $newExprResult */
	static void firstClassCallableTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\NodeScopeResolver::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval *newExprResult = &captures[0];
		zv::Val type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(newExprResult) : pt_expression_result_get_type(newExprResult);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::NodeScopeResolver;

/* {{{ direct entries for native callers (support.h): the native body for
 * exactly this class, the method through the object's class entry
 * otherwise */

namespace {

inline bool isNativeResolver(zval *nodeScopeResolver)
{
	return EXPECTED(Z_OBJCE_P(nodeScopeResolver) == pt_ce_node_scope_resolver);
}

} // namespace

zv::Val pt_node_scope_resolver_process_expr_node(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisProcessExprNode(stmt, expr, scope, storage, nodeCallback, context);
}

zv::Val pt_node_scope_resolver_process_stmt_node(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).processStmtNode(stmt, scope, storage, nodeCallback, context);
	zv::Args argv{stmt, scope, storage, nodeCallback, context};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("processstmtnode"), 5, argv);
}

zv::Val pt_node_scope_resolver_process_stmt_nodes_internal(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisProcessStmtNodesInternal(parentNode, stmts, scope, storage, nodeCallback, context);
}

zv::Val pt_node_scope_resolver_process_expr_on_demand(zval *nodeScopeResolver, zval *expr, zval *scope, zval *storage)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisProcessExprOnDemand(expr, scope, storage);
}

zv::Val pt_node_scope_resolver_process_synthetic_on_demand(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisProcessSyntheticOnDemand(expr, scope);
}

zv::Val pt_node_scope_resolver_find_scope_state_type(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisFindScopeStateType(expr, scope);
}

zv::Val pt_node_scope_resolver_require_scope_state_type(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).requireScopeStateType(expr, scope);
	zv::Args argv{expr, scope};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("requirescopestatetype"), 2, argv);
}

zv::Val pt_node_scope_resolver_read_scope_state_or_synthetic_type(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisReadScopeStateOrSyntheticType(expr, scope);
}

zv::Val pt_node_scope_resolver_read_type_of_maybe_stored(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).readTypeOfMaybeStored(expr, scope);
	zv::Args argv{expr, scope};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("readtypeofmaybestored"), 2, argv);
}

bool pt_node_scope_resolver_store_expression_result(zval *nodeScopeResolver, zval *storage, zval *expr, zval *expressionResult)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisStoreExpressionResult(storage, expr, expressionResult);
}

bool pt_node_scope_resolver_call_node_callback(zval *nodeScopeResolver, zval *nodeCallback, zval *node, zval *scope, zval *storage)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisCallNodeCallback(nodeCallback, node, scope, storage);
}

bool pt_node_scope_resolver_call_node_callback_with_expression(zval *nodeScopeResolver, zval *nodeCallback, zval *expr, zval *scope, zval *storage, zval *context)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisCallNodeCallbackWithExpression(nodeCallback, expr, scope, storage, context);
}

zv::Val pt_node_scope_resolver_suspend_node_gatherers(zval *nodeScopeResolver)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisSuspendNodeGatherers();
}

bool pt_node_scope_resolver_restore_node_gatherers(zval *nodeScopeResolver, zval *gatherers)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisRestoreNodeGatherers(gatherers);
}

zv::Val pt_node_scope_resolver_observing_template_argument_frame(zval *nodeScopeResolver, zval *scope)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisObservingTemplateArgumentFrame(scope);
}

/* the assignment handlers' (AssignHandler.cpp) */
zv::Val pt_node_scope_resolver_get_assigned_variables(zval *nodeScopeResolver, zval *expr)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisGetAssignedVariables(expr);
}

zv::Val pt_node_scope_resolver_read_stored_result(zval *nodeScopeResolver, zval *expr, zval *storage)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).readStoredResult(expr, storage);
	zv::Args argv{expr, storage};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("readstoredresult"), 2, argv);
}

zv::Val pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(zval *nodeScopeResolver, zval *scope, zval *expr)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).lookForSetAllowedUndefinedExpressions(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("lookforsetallowedundefinedexpressions"), 2, argv);
}

bool pt_node_scope_resolver_replay_recording_range(zval *nodeScopeResolver, zval *recording, zend_long from, zend_long to, zval *nodeCallback, zval *storage, zval *scope)
{
	return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).thisReplayRecordingRange(recording, from, to, nodeCallback, storage, scope);
}

/* the statement handlers' (ExpressionHandler.cpp, ReturnHandler.cpp,
 * ClassMethodHandler.cpp, FunctionHandler.cpp) */
bool pt_node_scope_resolver_push_node_gatherer(zval *nodeScopeResolver, zval *gatherer)
{
	if (isNativeResolver(nodeScopeResolver)) {
		NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).pushNodeGatherer(gatherer);
		return true;
	}
	return !pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("pushnodegatherer"), 1, gatherer).isUndef();
}

bool pt_node_scope_resolver_pop_node_gatherer(zval *nodeScopeResolver)
{
	if (isNativeResolver(nodeScopeResolver)) {
		NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).popNodeGatherer();
		return true;
	}
	return !pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("popnodegatherer"), 0, NULL).isUndef();
}

zv::Val pt_node_scope_resolver_collect_return_send(zval *nodeScopeResolver, zval *scope, zval *returnedResult)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).collectReturnSend(scope, returnedResult);
	zv::Args argv{scope, returnedResult};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("collectreturnsend"), 2, argv);
}

/* the argument walk's (ArgumentsHandler.cpp) */
zv::Val pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(zval *nodeScopeResolver, zval *scope, zval *expr)
{
	if (isNativeResolver(nodeScopeResolver)) return NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).lookForUnsetAllowedUndefinedExpressions(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("lookforunsetallowedundefinedexpressions"), 2, argv);
}

bool pt_node_scope_resolver_is_returning_stored_expression_results(zval *nodeScopeResolver, bool &out)
{
	if (isNativeResolver(nodeScopeResolver)) {
		out = NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).isReturningStoredExpressionResults();
		return true;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("isreturningstoredexpressionresults"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_node_scope_resolver_is_consuming_stored_expression_results(zval *nodeScopeResolver, bool &out)
{
	if (isNativeResolver(nodeScopeResolver)) {
		out = NodeScopeResolver(Z_OBJ_P(nodeScopeResolver)).isConsumingStoredExpressionResults();
		return true;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("isconsumingstoredexpressionresults"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

namespace {

/* a `bool method(...)` body into return_value: null or the thrown exception */
#define PT_NSR_RETURN_VOID(expr) \
	do { \
		if (UNEXPECTED(!(expr))) { \
			RETURN_THROWS(); \
		} \
		return; \
	} while (0)

#define PT_NSR_THIS NodeScopeResolver(Z_OBJ_P(ZEND_THIS))

} // namespace

void pt_register_node_scope_resolver()
{
	reg::Class cls("PHPStan\\Analyser\\NodeScopeResolver");
	/* not final: every $this-call dispatches through the object's class
	 * entry unless it is exactly this class */
	ptdecl::NodeScopeResolver::declareClass(cls);
	cls.classConstantLong("LOOP_SCOPE_ITERATIONS", 3);
	cls.classConstantLong("GENERALIZE_AFTER_ITERATION", 1);
	cls.privateClassConstantString("REPLAYABLE_BODY_ATTRIBUTE", "convergenceReplayableBody");
	ptdecl::NodeScopeResolver::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *container, *templateArgumentObserver, *fileHelper, *perFileAnalysisResettables, *expressionResultFactory, *statementsHandler;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(container)
			Z_PARAM_OBJECT(templateArgumentObserver)
			Z_PARAM_OBJECT(fileHelper)
			Z_PARAM_OBJECT(perFileAnalysisResettables)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(statementsHandler)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.construct(container, templateArgumentObserver, fileHelper, perFileAnalysisResettables, expressionResultFactory, statementsHandler));
	});

	cls.method(sigs::setAnalysedFiles, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *files;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY_HT(files)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.setAnalysedFiles(files));
	});

	cls.method(sigs::resetPerFileAnalysisState, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.resetPerFileAnalysisState());
	});

	cls.method(sigs::processNodes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodes, *scope, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_ARRAY(nodes)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.processNodes(nodes, scope, nodeCallback));
	});

	cls.method(sigs::storeExpressionResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *storage, *expr, *expressionResult;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(expressionResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_THIS.storeExpressionResult(storage, expr, expressionResult);
		if (UNEXPECTED(EG(exception))) RETURN_THROWS();
	});

	cls.method(sigs::narrowScopeWithCondition, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *context;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.narrowScopeWithCondition(scope, expr, context));
	});

	cls.method(sigs::processStmtNodes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parentNode, *stmts, *scope, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(parentNode)
			Z_PARAM_ARRAY(stmts)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processStmtNodes(parentNode, stmts, scope, nodeCallback, context));
	});

	cls.method(sigs::processStmtNodesInternal, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parentNode, *stmts, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(parentNode)
			Z_PARAM_ARRAY(stmts)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processStmtNodesInternal(parentNode, stmts, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::processStmtNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processStmtNode(stmt, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::isAnalysedFile, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *fileName;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(fileName)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(PT_NSR_THIS.isAnalysedFile(fileName));
	});

	cls.method(sigs::isReturningStoredExpressionResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(PT_NSR_THIS.isReturningStoredExpressionResults());
	});

	cls.method(sigs::isConsumingStoredExpressionResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(PT_NSR_THIS.isConsumingStoredExpressionResults());
	});

	cls.method(sigs::lookForSetAllowedUndefinedExpressions, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.lookForSetAllowedUndefinedExpressions(scope, expr));
	});

	cls.method(sigs::lookForUnsetAllowedUndefinedExpressions, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.lookForUnsetAllowedUndefinedExpressions(scope, expr));
	});

	cls.method(sigs::processExprNodeConsumingStored, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processExprNodeConsumingStored(stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::processExprOnDemand, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope, *storage;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processExprOnDemand(expr, scope, storage));
	});

	cls.method(sigs::readStoredResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *storage;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(storage)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.readStoredResult(expr, storage));
	});

	cls.method(sigs::readTypeOfMaybeStored, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.readTypeOfMaybeStored(expr, scope));
	});

	cls.method(sigs::findScopeStateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.findScopeStateType(expr, scope));
	});

	cls.method(sigs::readScopeStateOrSyntheticType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.readScopeStateOrSyntheticType(expr, scope));
	});

	cls.method(sigs::requireScopeStateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.requireScopeStateType(expr, scope));
	});

	cls.method(sigs::processSyntheticOnDemand, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processSyntheticOnDemand(expr, scope));
	});

	cls.method(sigs::processExprNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.processExprNode(stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::getAssignedVariables, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.getAssignedVariables(expr));
	});

	cls.method(sigs::getImpurePointsFromPropertyHook, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *propertyFetch, *propertyReflection;
		zend_string *hookName;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(propertyFetch)
			Z_PARAM_OBJECT(propertyReflection)
			Z_PARAM_STR(hookName)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.getImpurePointsFromPropertyHook(scope, propertyFetch, propertyReflection, hookName));
	});

	cls.method(sigs::isReplayableConvergenceBody, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *loopNode;
		HashTable *bodyStmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(loopNode)
			Z_PARAM_ARRAY_HT(bodyStmts)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!PT_NSR_THIS.isReplayableConvergenceBody(loopNode, bodyStmts, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::pushNodeGatherer, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *gatherer;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ZVAL(gatherer)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_THIS.pushNodeGatherer(gatherer);
	});

	cls.method(sigs::popNodeGatherer, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_NSR_THIS.popNodeGatherer();
	});

	cls.method(sigs::suspendNodeGatherers, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_NSR_THIS.suspendNodeGatherers());
	});

	cls.method(sigs::restoreNodeGatherers, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *gatherers;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(gatherers)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_THIS.restoreNodeGatherers(gatherers);
	});

	cls.method(sigs::replayRecording, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *recording, *nodeCallback, *storage, *scope;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(recording)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.replayRecording(recording, nodeCallback, storage, scope));
	});

	cls.method(sigs::replayRecordingRange, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *recording, *nodeCallback, *storage, *scope;
		zend_long from, to;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(recording)
			Z_PARAM_LONG(from)
			Z_PARAM_LONG(to)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.replayRecordingRange(recording, from, to, nodeCallback, storage, scope));
	});

	cls.method(sigs::callNodeCallbackWithExpression, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeCallback, *expr, *scope, *storage, *context;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.callNodeCallbackWithExpression(nodeCallback, expr, scope, storage, context));
	});

	cls.method(sigs::callNodeCallback, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeCallback, *node, *scope, *storage;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
		ZEND_PARSE_PARAMETERS_END();
		PT_NSR_RETURN_VOID(PT_NSR_THIS.callNodeCallback(nodeCallback, node, scope, storage));
	});

	cls.method(sigs::observingTemplateArgumentFrame, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.observingTemplateArgumentFrame(scope));
	});

	cls.method(sigs::collectReturnSend, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *returnedResult;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(returnedResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_NSR_THIS.collectReturnSend(scope, returnedResult));
	});

	cls.shadow(&pt_ce_node_scope_resolver);
}

/* }}} */
