/*
 * PHPStanTurbo\StatementsHandler — native implementation of
 * PHPStan\Analyser\StatementsHandler.
 *
 * Walks statement lists for NodeScopeResolver: goto convergence,
 * unreachable statements, the two-pass function-like body walk, and the
 * statement-level PHPDocs (@var, @throws). A final DI service
 * (#[AutowiredService] with an #[AutowiredParameter] bool): the constructor
 * keeps the twin's arginfo. Native callers use the
 * pt_statements_handler_*() direct entries (support.h).
 *
 * The twin's closures never escape: the goto-name matchers handed to the
 * private resolveBackwardGotoScope() are a C++ matcher, and
 * collectMentionedVariables()'s by-reference accumulators are C++ out
 * parameters. The statement list state is the native StatementListWalkState,
 * read and written through its generated slots.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResultStorage, the contexts,
 * VariableFlow, the statement results and throw points, TemplateArgumentFrame
 * and RecordingNodeCallback are called through their direct entries and
 * slot readers; the collaborators that stay PHP for now (FileTypeMapper and
 * the PHPDoc tags, the template argument resolver/observer/stats) through the
 * cached method sites in the block below, one helper each.
 */

#include "support.h"
#include "generated/StatementsHandler.h"
#include "generated/StatementListWalkState.h"

namespace slots = ptdecl::StatementsHandler::slot;
namespace sigs = ptdecl::StatementsHandler::sig;
namespace stateSlots = ptdecl::StatementListWalkState::slot;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "generated/RecordingNodeCallback.h"

#include "zend_smart_str.h"

#include <cstdlib>
#include <cstring>
#include <initializer_list>

zend_class_entry *pt_ce_statements_handler = nullptr;

/* NodeScopeResolver::LOOP_SCOPE_ITERATIONS / ::GENERALIZE_AFTER_ITERATION */
#define PT_SH_LOOP_SCOPE_ITERATIONS_LIMIT 3
#define PT_SH_GENERALIZE_AFTER_ITERATION_LIMIT 1
/* the twin's STATIC_WALK_* kinds of the last static-variable walk's log */
#define PT_SH_STATIC_WALK_WALKED 0
#define PT_SH_STATIC_WALK_CARRIED 1
#define PT_SH_STATIC_WALK_REST 2

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_sh_get_return_type_site;
pt_method_site pt_sh_get_by_type_site;
pt_method_site pt_sh_get_attributes_site;
pt_method_site pt_sh_get_start_token_pos_site;
pt_method_site pt_sh_get_start_line_site;
pt_method_site pt_sh_get_sub_node_names_site;
pt_method_site pt_sh_get_resolved_php_doc_site;
pt_method_site pt_sh_tag_get_type_site;
pt_method_site pt_sh_comment_get_text_site;
pt_method_site pt_sh_function_get_name_site;
pt_method_site pt_sh_stats_increment_site;
pt_property_site pt_sh_name_site;
pt_property_site pt_sh_expr_site;
pt_property_site pt_sh_var_site;
pt_property_site pt_sh_variable_name_site;
pt_property_site pt_sh_static_site;
pt_property_site pt_sh_uses_site;
pt_property_site pt_sh_use_var_site;
pt_property_site pt_sh_func_call_name_site;

/* $object->method() without arguments through a per-site cache; the Error
 * the engine raises for a non-object receiver */
zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *displayName)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", displayName, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* the Error calling a getter on a non-object raises; false = thrown */
inline bool requireReceiver(zval *object, const char *displayName)
{
	if (EXPECTED(Z_TYPE_P(object) == IS_OBJECT)) return true;
	zend_throw_error(NULL, "Call to a member function %s() on %s", displayName, zend_zval_value_name(object));
	return false;
}

/* an owned copy of a borrowed read of AnalyserValues.h (NULL = pending
 * exception) */
inline zv::Val owned(zval *value)
{
	return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
}

/* the getters of InternalStatementResult, owned (the native readers) */
#define PT_SH_ISR_READER(fn, reader, displayName) \
	zv::Val fn(zval *result) \
	{ \
		if (UNEXPECTED(!requireReceiver(result, displayName))) return zv::Val(); \
		zv::Val hold; \
		return owned(reader(result, hold)); \
	}
PT_SH_ISR_READER(isrGetScope, pt_internal_statement_result_scope, "getScope")
PT_SH_ISR_READER(isrGetVariableFlow, pt_internal_statement_result_variable_flow, "getVariableFlow")
PT_SH_ISR_READER(isrGetExitPoints, pt_internal_statement_result_exit_points, "getExitPoints")
PT_SH_ISR_READER(isrGetThrowPoints, pt_internal_statement_result_throw_points, "getThrowPoints")
PT_SH_ISR_READER(isrGetImpurePoints, pt_internal_statement_result_impure_points, "getImpurePoints")
PT_SH_ISR_READER(isrGetEndStatements, pt_internal_statement_result_end_statements, "getEndStatements")
PT_SH_ISR_READER(exitPointGetStatement, pt_internal_statement_exit_point_statement, "getStatement")
PT_SH_ISR_READER(exitPointGetScope, pt_internal_statement_exit_point_scope, "getScope")
PT_SH_ISR_READER(endStatementGetResult, pt_internal_end_statement_result_result, "getResult")
PT_SH_ISR_READER(endStatementGetStatement, pt_internal_end_statement_result_statement, "getStatement")
#undef PT_SH_ISR_READER

/* $result->hasYield() / ->isAlwaysTerminating(), as bool values */
zv::Val isrHasYield(zval *result)
{
	bool out;
	if (UNEXPECTED(!requireReceiver(result, "hasYield") || !pt_internal_statement_result_has_yield(result, out))) return zv::Val();
	return zv::Val::boolean(out);
}

zv::Val isrIsAlwaysTerminating(zval *result)
{
	bool out;
	if (UNEXPECTED(!requireReceiver(result, "isAlwaysTerminating") || !pt_internal_statement_result_is_always_terminating(result, out))) return zv::Val();
	return zv::Val::boolean(out);
}

/* $result->toPublic() */
zv::Val isrToPublic(zval *result)
{
	if (UNEXPECTED(!requireReceiver(result, "toPublic"))) return zv::Val();
	return pt_internal_statement_result_to_public(result);
}

/* $identifier->toString() / $name->toLowerString() */
zv::Val nameToString(zval *name) { return pt_name_node_to_string(name); }
zv::Val nameToLowerString(zval *name) { return pt_name_node_to_lower_string(name); }

/* $node->getReturnType() / getAttributes() / getStartTokenPos() /
 * getStartLine() / getSubNodeNames() */
zv::Val nodeGetReturnType(zval *node) { return callOn(pt_sh_get_return_type_site, node, PT_LC("getreturntype"), "getReturnType"); }
zv::Val nodeGetAttributes(zval *node) { return callOn(pt_sh_get_attributes_site, node, PT_LC("getattributes"), "getAttributes"); }
zv::Val nodeGetStartTokenPos(zval *node) { return callOn(pt_sh_get_start_token_pos_site, node, PT_LC("getstarttokenpos"), "getStartTokenPos"); }
zv::Val nodeGetStartLine(zval *node) { return callOn(pt_sh_get_start_line_site, node, PT_LC("getstartline"), "getStartLine"); }
zv::Val nodeGetSubNodeNames(zval *node) { return callOn(pt_sh_get_sub_node_names_site, node, PT_LC("getsubnodenames"), "getSubNodeNames"); }

/* $fileTypeMapper->getResolvedPhpDoc($fileName, $className, $traitName,
 * $functionName, $docComment) */
zv::Val fileTypeMapperGetResolvedPhpDoc(zval *fileTypeMapper, zval *argv)
{
	return pt_call_method_cached(pt_sh_get_resolved_php_doc_site, Z_OBJ_P(fileTypeMapper), PT_LC("getresolvedphpdoc"), 5, argv);
}

/* $resolvedPhpDoc->getThrowsTag() / getVarTags(); $tag->getType() */
zv::Val resolvedPhpDocGetThrowsTag(zval *resolvedPhpDoc) { return pt_resolved_php_doc_block_call(resolvedPhpDoc, PT_RPD_GET_THROWS_TAG); }
zv::Val resolvedPhpDocGetVarTags(zval *resolvedPhpDoc) { return pt_resolved_php_doc_block_call(resolvedPhpDoc, PT_RPD_GET_VAR_TAGS); }
zv::Val tagGetType(zval *tag) { return callOn(pt_sh_tag_get_type_site, tag, PT_LC("gettype"), "getType"); }

/* $comment->getText() / $function->getName() */
zv::Val commentGetText(zval *comment) { return callOn(pt_sh_comment_get_text_site, comment, PT_LC("gettext"), "getText"); }
zv::Val functionGetName(zval *function) { return callOn(pt_sh_function_get_name_site, function, PT_LC("getname"), "getName"); }

/* InternalThrowPoint::createExplicit($scope, $type, $node, false) */
zv::Val internalThrowPointCreateExplicit(zval *scope, zval *type, zval *node)
{
	return pt_internal_throw_point_create_explicit(scope, type, node, false);
}

/* $templateArgumentObserver->collectSend($declared, $actual) */
zv::Val templateArgumentObserverCollectSend(zval *observer, zval *declared, zval *actual)
{
	return pt_template_argument_observer_collect_send(observer, declared, actual);
}


/* $templateArgumentResolver->resolve($constraints, $parentFrame,
 * $statementStartTokenPositions, $parentNode, $stmts) */
zv::Val templateArgumentResolverResolve(zval *resolver, zval *constraints, zval *parentFrame, zval *positions, zval *parentNode, zval *stmts)
{
	return pt_template_argument_resolver_resolve(resolver, constraints, parentFrame, positions, parentNode, stmts);
}

/* $frame->firstSiteStatementIndex() / hasSiteAtOrAfter($i) /
 * ownsSiteInStatement($i): the native frame's $siteStatementIndexes slot
 * (integer keys by construction; anything else takes the method) */
zval *frameSiteStatementIndexes(zval *frame)
{
	if (EXPECTED(Z_TYPE_P(frame) == IS_OBJECT && Z_OBJCE_P(frame) == pt_ce_template_argument_frame)) {
		zval *indexes = OBJ_PROP_NUM(Z_OBJ_P(frame), ptdecl::TemplateArgumentFrame::slot::siteStatementIndexes);
		if (EXPECTED(Z_TYPE_P(indexes) == IS_ARRAY)) {
			for (auto entry : zv::ArrRef(indexes)) {
				if (UNEXPECTED(entry.hasStringKey())) return NULL;
			}
			return indexes;
		}
	}
	return NULL;
}

zv::Val frameFirstSiteStatementIndex(zval *frame)
{
	zval *indexes = frameSiteStatementIndexes(frame);
	if (EXPECTED(indexes != NULL)) {
		bool found = false;
		zend_long first = 0;
		for (auto entry : zv::ArrRef(indexes)) {
			zend_long index = (zend_long) entry.indexKey();
			if (found && index >= first) continue;
			first = index;
			found = true;
		}
		return found ? zv::Val::integer(first) : zv::Val::null();
	}
	if (UNEXPECTED(!requireReceiver(frame, "firstSiteStatementIndex"))) return zv::Val();
	return pt_type_call(Z_OBJ_P(frame), PT_LC("firstsitestatementindex"), 0, NULL);
}

[[nodiscard]] bool frameHasSiteAtOrAfter(zval *frame, zend_long statementIndex, bool &out)
{
	zval *indexes = frameSiteStatementIndexes(frame);
	if (EXPECTED(indexes != NULL)) {
		out = false;
		for (auto entry : zv::ArrRef(indexes)) {
			if ((zend_long) entry.indexKey() >= statementIndex) {
				out = true;
				break;
			}
		}
		return true;
	}
	if (UNEXPECTED(!requireReceiver(frame, "hasSiteAtOrAfter"))) return false;
	zval indexZv;
	ZVAL_LONG(&indexZv, statementIndex);
	zv::Val result = pt_type_call(Z_OBJ_P(frame), PT_LC("hassiteatorafter"), 1, &indexZv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

[[nodiscard]] bool frameOwnsSiteInStatement(zval *frame, zend_long statementIndex, bool &out)
{
	zval *indexes = frameSiteStatementIndexes(frame);
	if (EXPECTED(indexes != NULL)) {
		zval *found = zend_hash_index_find(Z_ARRVAL_P(indexes), (zend_ulong) statementIndex);
		if (found != NULL) {
			ZVAL_DEREF(found);
		}
		out = found != NULL && Z_TYPE_P(found) != IS_NULL;
		return true;
	}
	if (UNEXPECTED(!requireReceiver(frame, "ownsSiteInStatement"))) return false;
	zval indexZv;
	ZVAL_LONG(&indexZv, statementIndex);
	zv::Val result = pt_type_call(Z_OBJ_P(frame), PT_LC("ownssiteinstatement"), 1, &indexZv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

/* TemplateArgumentConstraints::createEmpty() */
zv::Val templateArgumentConstraintsCreateEmpty()
{
	return pt_template_argument_constraints_create_empty();
}

/* TemplateArgumentStats::$enabled; false = pending exception */
[[nodiscard]] bool templateArgumentStatsEnabled(bool &out)
{
	zend_class_entry *ce = pt_class(PT_CLASS_TEMPLATE_ARGUMENT_STATS);
	if (UNEXPECTED(ce == NULL)) return false;
	zval *enabled = zend_read_static_property(ce, PT_LC("enabled"), 0);
	if (UNEXPECTED(enabled == NULL)) return false;
	ZVAL_DEREF(enabled);
	out = Z_TYPE_P(enabled) == IS_TRUE;
	return true;
}

/* TemplateArgumentStats::increment($counter, $by); false = pending exception */
[[nodiscard]] bool templateArgumentStatsIncrement(const char *counter, size_t len, zend_long by)
{
	zval counterZv;
	ZVAL_STRINGL(&counterZv, counter, len);
	zv::Args argv{&counterZv, by};
	zv::Val result = pt_call_static_cached(pt_sh_stats_increment_site, PT_CLASS_TEMPLATE_ARGUMENT_STATS, PT_LC("increment"), 2, argv);
	zval_ptr_dtor(&counterZv);
	return !result.isUndef();
}

/* {{{ RecordingNodeCallback (RecordingNodeCallback.cpp) */

zv::Val newRecordingNodeCallback()
{
	return pt_type_new_ce(pt_ce_recording_node_callback, 0, NULL);
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

/* }}} */

/* {{{ node shapes and arrays */

/* $object instanceof <class-map class> for any value; false = pending
 * exception */
[[nodiscard]] inline bool isInstance(zval *value, int classIdx, bool &out)
{
	ZVAL_DEREF(value);
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* $node->$name of a declared property through a per-site offset (the
 * value dereferenced); NULL = pending exception */
zval *nodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	ZVAL_DEREF(node);
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(node));
		if (UNEXPECTED(EG(exception))) return NULL;
		static zval null;
		ZVAL_NULL(&null);
		return &null;
	}
	zval *slot = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(slot == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s has no declared property $%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	return slot;
}

/* a value copy as array_merge()/array_slice() copy it: a reference nobody
 * else holds is dereferenced */
inline void copyArrayValue(zval *target, zval *value)
{
	if (Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1) {
		value = Z_REFVAL_P(value);
	}
	ZVAL_COPY(target, value);
}

/* array_merge($a, $b) of two arrays */
zv::Val arrayMerge(zval *a, zval *b)
{
	HashTable *left = Z_ARRVAL_P(a);
	HashTable *right = Z_ARRVAL_P(b);
	zv::Arr result = zv::Arr::create(zend_hash_num_elements(left) + zend_hash_num_elements(right));
	for (HashTable *source : { left, right }) {
		for (auto entry : zv::TableRef(source)) {
			zval value;
			copyArrayValue(&value, entry.value().raw());
			if (entry.hasStringKey()) {
				zend_hash_update(result.table(), entry.stringKey(), &value);
			} else {
				zend_hash_next_index_insert_new(result.table(), &value);
			}
		}
	}
	return zv::Val(std::move(result));
}

/* $a = array_merge($a, $b) into a property slot, the untouched slot kept
 * when $b is empty and $a already is the list array_merge() would build */
void arrayMergeInto(zval *slot, zval *b)
{
	zval *a = slot;
	ZVAL_DEREF(a);
	if (Z_TYPE_P(a) != IS_ARRAY || Z_TYPE_P(b) != IS_ARRAY) return;
	HashTable *left = Z_ARRVAL_P(a);
	if (zend_hash_num_elements(Z_ARRVAL_P(b)) == 0 && HT_IS_PACKED(left) && HT_IS_WITHOUT_HOLES(left)) return;
	zv::Val merged = arrayMerge(a, b);
	zv::Ref(a).assign(std::move(merged));
}

/* array_slice($array, $offset) (no length, keys not preserved) */
zv::Val arraySlice(zval *array, zend_long offset)
{
	HashTable *source = Z_ARRVAL_P(array);
	zend_long count = zend_hash_num_elements(source);
	if (offset > count) offset = count;
	if (offset < 0) {
		offset = count + offset;
		if (offset < 0) offset = 0;
	}
	zv::Arr result = zv::Arr::create((uint32_t) (count - offset));
	zend_long position = 0;
	for (auto entry : zv::TableRef(source)) {
		if (position++ < offset) continue;
		zval value;
		copyArrayValue(&value, entry.value().raw());
		if (entry.hasStringKey()) {
			zend_hash_update(result.table(), entry.stringKey(), &value);
		} else {
			zend_hash_next_index_insert_new(result.table(), &value);
		}
	}
	return zv::Val(std::move(result));
}

/* the goto-name matcher closures the twin hands to resolveBackwardGotoScope():
 * static fn (string $name): bool => isset($nestedLabelNames[$name]) and
 * static fn (string $name): bool => $name === $labelName */
struct GotoNameMatcher
{
	zval *nestedLabelNames; /* the first form when set */
	zend_string *labelName; /* the second form otherwise */

	bool matches(zend_string *name) const
	{
		if (nestedLabelNames != NULL) {
			zval *labels = nestedLabelNames;
			ZVAL_DEREF(labels);
			if (Z_TYPE_P(labels) != IS_ARRAY) return false;
			zval *found = zend_symtable_find(Z_ARRVAL_P(labels), name);
			if (found == NULL) return false;
			ZVAL_DEREF(found);
			return Z_TYPE_P(found) != IS_NULL;
		}
		return zend_string_equals(name, labelName);
	}
};

/* the TypeError of the `int $i` parameter of processStatementStep() for a
 * string statement key */
void throwStatementKeyError()
{
	zend_type_error("PHPStan\\Analyser\\StatementsHandler::processStatementStep(): Argument #4 ($i) must be of type int, string given");
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StatementsHandler; UNDEF / false = pending
 * exception. */
class StatementsHandler
{
public:
	explicit StatementsHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties, then the twin's body */
	void construct(zval *fileTypeMapper, zval *templateArgumentObserver, zval *templateArgumentResolver, zval *container, zval *staticVariableInference, bool unresolvedTemplateArguments)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::fileTypeMapper, zv::Val::copyOf(zv::Ref(fileTypeMapper)));
		object.propAtWrite(slots::templateArgumentObserver, zv::Val::copyOf(zv::Ref(templateArgumentObserver)));
		object.propAtWrite(slots::templateArgumentResolver, zv::Val::copyOf(zv::Ref(templateArgumentResolver)));
		object.propAtWrite(slots::container, zv::Val::copyOf(zv::Ref(container)));
		object.propAtWrite(slots::staticVariableInference, zv::Val::copyOf(zv::Ref(staticVariableInference)));
		object.propAtWrite(slots::unresolvedTemplateArguments, zv::Val::boolean(unresolvedTemplateArguments));
		const char *debug = getenv("PHPSTAN_TEMPLATE_ARGUMENTS_DEBUG");
		object.propAtWrite(slots::debugTemplateArguments, zv::Val::boolean(debug != NULL && strcmp(debug, "1") == 0));
	}

	/* Mirrors processNodesWithStorage(). */
	[[nodiscard]] bool processNodesWithStorage(zval *nodeScopeResolver, zval *nodes, zval *scopeArg, zval *storage, zval *nodeCallback)
	{
		bool alreadyTerminated = false;
		zv::Val exitPoints = zv::Val(zv::Arr::empty());
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));

		zv::Arr stmts = zv::Arr::empty();
		zv::Arr stmtToNodeIndex = zv::Arr::empty();
		zv::Val nodesHeld = zv::Val::copyOf(zv::Ref(nodes));
		for (auto entry : zv::ArrRef(nodesHeld.raw())) {
			zval *node = entry.value().deref().raw();
			bool isStmt;
			if (UNEXPECTED(!isInstance(node, PT_CLASS_STMT, isStmt))) return false;
			if (!isStmt) continue;
			zval key;
			if (entry.hasStringKey()) {
				ZVAL_STR_COPY(&key, entry.stringKey());
			} else {
				ZVAL_LONG(&key, (zend_long) entry.indexKey());
			}
			stmtToNodeIndex.separate();
			zend_hash_index_update(stmtToNodeIndex.table(), zend_hash_num_elements(stmts.table()), &key);
			stmts.push(zv::Ref(node));
		}

		zv::Val dummyParent = pt_type_new(PT_CLASS_NOP_STMT, 0, NULL);
		if (UNEXPECTED(dummyParent.isUndef())) return false;
		for (auto entry : zv::ArrRef(stmts.raw())) {
			zend_ulong si = entry.indexKey();
			zval *node = entry.value().raw();
			if (alreadyTerminated) {
				bool keeps;
				if (UNEXPECTED(!isEarlyBound(node, keeps))) return false;
				if (!keeps) continue;
			}

			zv::Val nestedLabelNames = pt_engine_node_get_attribute(Z_OBJ_P(node), PT_LC("nestedBackwardGotoLabels"));
			if (UNEXPECTED(nestedLabelNames.isUndef())) return false;
			if (!nestedLabelNames.isNull()) {
				zv::Arr bodyStmts = zv::Arr::create(1);
				bodyStmts.push(zv::Ref(node));
				zv::Val deep = pt_statement_context_create_deep();
				if (UNEXPECTED(deep.isUndef())) return false;
				GotoNameMatcher matcher = { nestedLabelNames.raw(), NULL };
				scope = resolveBackwardGotoScope(nodeScopeResolver, dummyParent.raw(), bodyStmts.raw(), scope.raw(), storage, deep.raw(), matcher, false);
				if (UNEXPECTED(scope.isUndef())) return false;
			}

			zv::Val topLevel = pt_statement_context_create_top_level();
			if (UNEXPECTED(topLevel.isUndef())) return false;
			zv::Val statementResult = pt_node_scope_resolver_process_stmt_node(nodeScopeResolver, node, scope.raw(), storage, nodeCallback, topLevel.raw());
			if (UNEXPECTED(statementResult.isUndef())) return false;
			scope = isrGetScope(statementResult.raw());
			if (UNEXPECTED(scope.isUndef())) return false;

			bool isLabel;
			if (UNEXPECTED(!isInstance(node, PT_CLASS_LABEL_STMT, isLabel))) return false;
			if (isLabel) {
				zval *nameNode = nodeProperty(pt_sh_name_site, node, PT_LC("name"));
				if (UNEXPECTED(nameNode == NULL)) return false;
				zv::Val labelName = nameToString(nameNode);
				if (UNEXPECTED(labelName.isUndef())) return false;
				if (UNEXPECTED(!mergeForwardGotoExitPoints(labelName.raw(), scope, alreadyTerminated, exitPoints))) return false;
				if (alreadyTerminated) continue;

				zv::Val hasBackwardGoto = pt_engine_node_get_attribute(Z_OBJ_P(node), PT_LC("hasBackwardGoto"));
				if (UNEXPECTED(hasBackwardGoto.isUndef())) return false;
				if (hasBackwardGoto.ref().isTrue()) {
					zv::Val bodyStmts = arraySlice(stmts.raw(), (zend_long) si + 1);
					zv::Val deep = pt_statement_context_create_deep();
					if (UNEXPECTED(deep.isUndef())) return false;
					GotoNameMatcher matcher = { NULL, Z_TYPE_P(labelName.raw()) == IS_STRING ? Z_STR_P(labelName.raw()) : ZSTR_EMPTY_ALLOC() };
					scope = resolveBackwardGotoScope(nodeScopeResolver, dummyParent.raw(), bodyStmts.raw(), scope.raw(), storage, deep.raw(), matcher, true);
					if (UNEXPECTED(scope.isUndef())) return false;
				}
			}

			zv::Val statementExitPoints = isrGetExitPoints(statementResult.raw());
			if (UNEXPECTED(statementExitPoints.isUndef())) return false;
			if (UNEXPECTED(!requireArray(statementExitPoints.raw(), "array_merge", 2))) return false;
			exitPoints = arrayMerge(exitPoints.raw(), statementExitPoints.raw());

			if (alreadyTerminated) continue;
			bool terminating;
			if (UNEXPECTED(!isAlwaysTerminating(statementResult.raw(), terminating))) return false;
			if (!terminating) continue;

			alreadyTerminated = true;
			zval *nodeIndex = zend_hash_index_find(stmtToNodeIndex.table(), si);
			zend_long nodeOffset;
			if (UNEXPECTED(!offsetAfter(nodeIndex, nodeOffset))) return false;
			zv::Val rest = arraySlice(nodesHeld.raw(), nodeOffset);
			zv::Val nextStmts = getNextUnreachableStatements(rest.raw(), true);
			if (UNEXPECTED(nextStmts.isUndef())) return false;
			if (UNEXPECTED(!processUnreachableStatement(nodeScopeResolver, nextStmts.raw(), scope.raw(), storage, nodeCallback))) return false;
		}
		return true;
	}

	/* Mirrors doProcessStmtNodes(). */
	zv::Val doProcessStmtNodes(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zend_long stmtCount = zend_hash_num_elements(Z_ARRVAL_P(stmts));
		bool shouldCheckLastStatement;
		if (UNEXPECTED(!isBodyParent(parentNode, shouldCheckLastStatement))) return zv::Val();

		if (shouldCheckLastStatement && stmtCount > 0 && Z_TYPE_P(OBJ_PROP_NUM(self, slots::unresolvedTemplateArguments)) == IS_TRUE) {
			bool resolve;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolve))) return zv::Val();
			if (resolve) return processBodyStmtNodesTwoPass(nodeScopeResolver, parentNode, stmts, scope, storage, nodeCallback, context);
		}

		zv::Val state = pt_statement_list_walk_state_new(scope);
		if (UNEXPECTED(state.isUndef())) return zv::Val();
		zv::Val stmtsHeld = zv::Val::copyOf(zv::Ref(stmts));
		for (auto entry : zv::ArrRef(stmtsHeld.raw())) {
			if (UNEXPECTED(entry.hasStringKey())) {
				throwStatementKeyError();
				return zv::Val();
			}
			if (UNEXPECTED(!processStatementStep(nodeScopeResolver, parentNode, stmts, (zend_long) entry.indexKey(), entry.value().deref().raw(), state.raw(), storage, nodeCallback, context, shouldCheckLastStatement))) return zv::Val();
		}

		zv::Val statementResult = pt_statement_list_walk_state_to_result(state.raw());
		if (UNEXPECTED(statementResult.isUndef())) return zv::Val();
		if (stmtCount == 0 && shouldCheckLastStatement) {
			zv::Val returnTypeNode = nodeGetReturnType(parentNode);
			if (UNEXPECTED(returnTypeNode.isUndef())) return zv::Val();
			zv::Val endParent = zv::Val::copyOf(zv::Ref(parentNode));
			bool isClosure;
			if (UNEXPECTED(!isInstance(parentNode, PT_CLASS_CLOSURE_EXPR, isClosure))) return zv::Val();
			if (isClosure) {
				zv::Val attributes = nodeGetAttributes(parentNode);
				if (UNEXPECTED(attributes.isUndef())) return zv::Val();
				zv::Args argv{parentNode, attributes.raw()};
				endParent = pt_type_new(PT_CLASS_EXPRESSION_STMT, 2, argv);
				if (UNEXPECTED(endParent.isUndef())) return zv::Val();
			}
			zv::Val publicResult = isrToPublic(statementResult.raw());
			if (UNEXPECTED(publicResult.isUndef())) return zv::Val();
			zv::Args argv{endParent.raw(), publicResult.raw(), !returnTypeNode.isNull()};
			zv::Val endNode = pt_type_new(PT_CLASS_EXECUTION_END_NODE, 3, argv);
			if (UNEXPECTED(endNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, endNode.raw(), scope, storage))) return zv::Val();
		}

		return statementResult;
	}

	/* Mirrors getVariableMentionFlow(). */
	zv::Val getVariableMentionFlow(zval *stmt)
	{
		zv::Arr names = zv::Arr::empty();
		bool mentionsEverything = false;
		if (UNEXPECTED(!collectMentionedVariables(stmt, names, mentionsEverything))) return zv::Val();
		if (mentionsEverything) return pt_variable_flow_all_mention_all();

		uint32_t count = zend_hash_num_elements(names.table());
		zval *flows = (zval *) safe_emalloc(count > 0 ? count : 1, sizeof(zval), 0);
		uint32_t built = 0;
		bool failed = false;
		for (auto entry : zv::TableRef(names.table())) {
			zv::Val flow;
			if (entry.hasStringKey()) {
				flow = pt_variable_flow_mention(entry.stringKey());
			} else {
				zend_type_error("PHPStan\\Analyser\\VariableFlow::mention(): Argument #1 ($name) must be of type string, int given");
			}
			if (UNEXPECTED(flow.isUndef())) {
				failed = true;
				break;
			}
			flows[built++] = flow.take();
		}
		zv::Val result;
		if (!failed) {
			result = pt_variable_flow_sequence(built, flows);
		}
		for (uint32_t i = 0; i < built; i++) {
			zval_ptr_dtor(&flows[i]);
		}
		efree(flows);
		return result;
	}

	/* Mirrors getOverridingThrowPoints(). */
	zv::Val getOverridingThrowPoints(zval *statement, zval *scope)
	{
		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(statement));
		if (UNEXPECTED(comments.isUndef())) return zv::Val();
		if (EXPECTED(!comments.ref().isArray() || zend_hash_num_elements(Z_ARRVAL_P(comments.raw())) == 0)) return zv::Val::null();
		for (auto entry : zv::ArrRef(comments.raw())) {
			zval *comment = entry.value().deref().raw();
			bool isDoc;
			if (UNEXPECTED(!isInstance(comment, PT_CLASS_DOC_COMMENT, isDoc))) return zv::Val();
			if (!isDoc) continue;

			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			zv::Val resolvedPhpDoc = resolvedPhpDocOf(scope, function.raw(), comment);
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
			zv::Val throwsTag = resolvedPhpDocGetThrowsTag(resolvedPhpDoc.raw());
			if (UNEXPECTED(throwsTag.isUndef())) return zv::Val();
			if (throwsTag.isNull()) continue;

			zv::Val throwsType = tagGetType(throwsTag.raw());
			if (UNEXPECTED(throwsType.isUndef())) return zv::Val();
			if (UNEXPECTED(!throwsType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwsType.raw()));
				return zv::Val();
			}
			zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(throwsType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid < 0)) return zv::Val();
			if (isVoid == PT_TRI_YES) {
				zv::Arr empty = zv::Arr::empty();
				return zv::Val(std::move(empty));
			}
			zv::Val throwPoint = internalThrowPointCreateExplicit(scope, throwsType.raw(), statement);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			zv::Arr throwPoints = zv::Arr::create(1);
			throwPoints.push(std::move(throwPoint));
			return zv::Val(std::move(throwPoints));
		}
		return zv::Val::null();
	}

	/* Mirrors processStmtVarAnnotation(). */
	zv::Val processStmtVarAnnotation(zval *nodeScopeResolver, zval *scopeArg, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scopeArg));
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(stmt));
		if (UNEXPECTED(comments.isUndef())) return zv::Val();
		if (EXPECTED(!comments.ref().isArray() || zend_hash_num_elements(Z_ARRVAL_P(comments.raw())) == 0)) return scope;

		zv::Arr variableLessTags = zv::Arr::empty();
		for (auto entry : zv::ArrRef(comments.raw())) {
			zval *comment = entry.value().deref().raw();
			bool isDoc;
			if (UNEXPECTED(!isInstance(comment, PT_CLASS_DOC_COMMENT, isDoc))) return zv::Val();
			if (!isDoc) continue;

			zv::Val resolvedPhpDoc = resolvedPhpDocOf(scope.raw(), function.raw(), comment);
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();

			zend_string *assignedVariable = NULL;
			if (UNEXPECTED(!assignedVariableOf(stmt, assignedVariable))) return zv::Val();

			zv::Val varTags = resolvedPhpDocGetVarTags(resolvedPhpDoc.raw());
			if (UNEXPECTED(varTags.isUndef())) return zv::Val();
			if (UNEXPECTED(!varTags.ref().isArray())) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(varTags.raw()));
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue;
			}
			for (auto tagEntry : zv::ArrRef(varTags.raw())) {
				zval *varTag = tagEntry.value().deref().raw();
				if (!tagEntry.hasStringKey()) {
					variableLessTags.push(zv::Ref(varTag));
					continue;
				}
				zend_string *name = tagEntry.stringKey();
				if (assignedVariable != NULL && zend_string_equals(name, assignedVariable)) continue;

				zend_object *scopeObject = Z_OBJ_P(scope.raw());
				zv::Val certainty = pt_mutating_scope_has_variable_type(scopeObject, name);
				if (UNEXPECTED(certainty.isUndef())) return zv::Val();
				zend_long certaintyValue = pt_type_trinary_value(certainty.raw());
				if (UNEXPECTED(certaintyValue < 0)) return zv::Val();
				if (certaintyValue == PT_TRI_NO) continue;

				bool inClass;
				if (UNEXPECTED(!pt_mutating_scope_is_in_class(scopeObject, inClass))) return zv::Val();
				if (inClass) {
					zv::Val currentFunction = pt_mutating_scope_get_function(scopeObject);
					if (UNEXPECTED(currentFunction.isUndef())) return zv::Val();
					if (currentFunction.isNull()) continue;
				}

				bool anyVariableExists;
				if (UNEXPECTED(!pt_mutating_scope_can_any_variable_exist(scopeObject, anyVariableExists))) return zv::Val();
				if (anyVariableExists) {
					certainty = zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
				}

				zv::Val attributes = nodeGetAttributes(stmt);
				if (UNEXPECTED(attributes.isUndef())) return zv::Val();
				zval nameZv;
				ZVAL_STR(&nameZv, name);
				zv::Args variableArgv{&nameZv, attributes.raw()};
				zv::Val variableNode = pt_type_new(PT_CLASS_VARIABLE, 2, variableArgv);
				if (UNEXPECTED(variableNode.isUndef())) return zv::Val();
				zv::Val originalType = pt_mutating_scope_get_variable_type(scopeObject, name);
				if (UNEXPECTED(originalType.isUndef())) return zv::Val();
				zv::Val tagType = tagGetType(varTag);
				if (UNEXPECTED(tagType.isUndef())) return zv::Val();
				if (UNEXPECTED(!originalType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(originalType.raw()));
					return zv::Val();
				}
				zv::Val equals = pt_type_op(Z_OBJ_P(originalType.raw()), PT_OP_EQUALS, 1, tagType.raw());
				if (UNEXPECTED(equals.isUndef())) return zv::Val();
				if (Z_TYPE_P(equals.raw()) != IS_TRUE) {
					zv::Args nodeArgv{varTag, variableNode.raw()};
					zv::Val changedNode = pt_type_new(PT_CLASS_VAR_TAG_CHANGED_EXPRESSION_TYPE_NODE, 2, nodeArgv);
					if (UNEXPECTED(changedNode.isUndef())) return zv::Val();
					if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, changedNode.raw(), scope.raw(), storage))) return zv::Val();
				}
				zv::Val templateArgumentFrame = pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, scope.raw());
				if (UNEXPECTED(templateArgumentFrame.isUndef())) return zv::Val();
				if (!templateArgumentFrame.isNull()) {
					zv::Val sendType = tagGetType(varTag);
					if (UNEXPECTED(sendType.isUndef())) return zv::Val();
					zv::Val constraints = templateArgumentObserverCollectSend(OBJ_PROP_NUM(self, slots::templateArgumentObserver), sendType.raw(), originalType.raw());
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					scope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), constraints.raw());
					if (UNEXPECTED(scope.isUndef())) return zv::Val();
				}

				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw()));
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				zv::Val assignedType = tagGetType(varTag);
				if (UNEXPECTED(assignedType.isUndef())) return zv::Val();
				zv::Val nativeHas = pt_mutating_scope_has_variable_type(Z_OBJ_P(nativeScope.raw()), name);
				if (UNEXPECTED(nativeHas.isUndef())) return zv::Val();
				zend_long nativeHasValue = pt_type_trinary_value(nativeHas.raw());
				if (UNEXPECTED(nativeHasValue < 0)) return zv::Val();
				zv::Val nativeType = nativeHasValue == PT_TRI_NO ? pt_type_new_error_type() : pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), name);
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				scope = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), name, assignedType.raw(), nativeType.raw(), certainty.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		if (zend_hash_num_elements(variableLessTags.table()) == 1 && defaultExpr != NULL && Z_TYPE_P(defaultExpr) != IS_NULL) {
			zval *tag = zend_hash_index_find(variableLessTags.table(), 0);
			zv::Val type = tagGetType(tag);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val mixed = pt_type_new_mixed_type();
			if (UNEXPECTED(mixed.isUndef())) return zv::Val();
			scope = pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(defaultExpr), type.raw(), mixed.raw());
		}

		return scope;
	}

	/* Mirrors emitVarTagChangedNode(). */
	zv::Val emitVarTagChangedNode(zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback)
	{
		zv::Val varTag = findSingleVariableLessVarTag(scope, stmt);
		if (UNEXPECTED(varTag.isUndef())) return zv::Val();
		if (varTag.isNull()) return templateArgumentConstraintsCreateEmpty();

		zv::Args nodeArgv{varTag.raw(), defaultExpr};
		zv::Val changedNode = pt_type_new(PT_CLASS_VAR_TAG_CHANGED_EXPRESSION_TYPE_NODE, 2, nodeArgv);
		if (UNEXPECTED(changedNode.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, changedNode.raw(), scope, storage))) return zv::Val();
		zv::Val defaultExprResult = pt_expression_result_storage_find(storage, defaultExpr);
		if (UNEXPECTED(defaultExprResult.isUndef())) return zv::Val();
		if (defaultExprResult.isNull()) return templateArgumentConstraintsCreateEmpty();
		zv::Val resultScopeHold;
		zval *resultScope = pt_expression_result_scope(defaultExprResult.raw(), resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		zv::Val frame = pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, resultScope);
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (frame.isNull()) return templateArgumentConstraintsCreateEmpty();
		zv::Val declared = tagGetType(varTag.raw());
		if (UNEXPECTED(declared.isUndef())) return zv::Val();
		zv::Val actual = pt_expression_result_get_type(defaultExprResult.raw());
		if (UNEXPECTED(actual.isUndef())) return zv::Val();
		return templateArgumentObserverCollectSend(OBJ_PROP_NUM(self, slots::templateArgumentObserver), declared.raw(), actual.raw());
	}

private:
	zend_object *self;

	/* {{{ statement lists */

	/* $node instanceof Function_ || ClassLike || Label */
	[[nodiscard]] static bool isEarlyBound(zval *node, bool &out)
	{
		for (int classIdx : { PT_CLASS_FUNCTION_STMT, PT_CLASS_CLASS_LIKE_STMT, PT_CLASS_LABEL_STMT }) {
			if (UNEXPECTED(!isInstance(node, classIdx, out))) return false;
			if (out) return true;
		}
		return true;
	}

	/* $parentNode instanceof Function_ || ClassMethod ||
	 * PropertyHookStatementNode || Expr\Closure */
	[[nodiscard]] static bool isBodyParent(zval *parentNode, bool &out)
	{
		for (int classIdx : { PT_CLASS_FUNCTION_STMT, PT_CLASS_CLASS_METHOD_STMT, PT_CLASS_PROPERTY_HOOK_STATEMENT_NODE, PT_CLASS_CLOSURE_EXPR }) {
			if (UNEXPECTED(!isInstance(parentNode, classIdx, out))) return false;
			if (out) return true;
		}
		return true;
	}

	/* $statementResult->isAlwaysTerminating() */
	[[nodiscard]] static bool isAlwaysTerminating(zval *statementResult, bool &out)
	{
		zv::Val terminating = isrIsAlwaysTerminating(statementResult);
		if (UNEXPECTED(terminating.isUndef())) return false;
		out = Z_TYPE_P(terminating.raw()) == IS_TRUE;
		return true;
	}

	/* the TypeError array_merge() raises for a non-array argument */
	static bool requireArray(zval *value, const char *function, int argument)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
		zend_type_error("%s(): Argument #%d must be of type array, %s given", function, argument, zend_zval_value_name(value));
		return false;
	}

	/* $stmtToNodeIndex[$si] + 1 */
	[[nodiscard]] static bool offsetAfter(zval *nodeIndex, zend_long &out)
	{
		if (EXPECTED(nodeIndex != NULL && Z_TYPE_P(nodeIndex) == IS_LONG)) {
			out = Z_LVAL_P(nodeIndex) + 1;
			return true;
		}
		zval one, sum;
		ZVAL_LONG(&one, 1);
		zval null;
		ZVAL_NULL(&null);
		if (UNEXPECTED(add_function(&sum, nodeIndex != NULL ? nodeIndex : &null, &one) != SUCCESS)) return false;
		out = zval_get_long(&sum);
		zval_ptr_dtor(&sum);
		return EG(exception) == NULL;
	}

	/* the private resolveBackwardGotoScope() */
	zv::Val resolveBackwardGotoScope(zval *nodeScopeResolver, zval *parentNode, zval *bodyStmts, zval *scope, zval *storage, zval *context, const GotoNameMatcher &matcher, bool mergeBodyScopeEachIteration)
	{
		zv::Val bodyScope = zv::Val::copyOf(zv::Ref(scope));
		zend_long count = 0;
		zv::Val prevEntryScope = zv::Val::null();
		do {
			zv::Val prevScope = zv::Val::copyOf(bodyScope.ref());
			if (mergeBodyScopeEachIteration) {
				bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), scope);
				if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
			}
			if (!prevEntryScope.isNull()) {
				bool equal;
				if (UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(bodyScope.raw()), Z_OBJ_P(prevEntryScope.raw()), equal))) return zv::Val();
				if (equal) {
					// walking is deterministic in the entry scope - an unchanged entry
					// reproduces the previous pass's exit, so the verification walk is skipped
					bodyScope = std::move(prevScope);
					break;
				}
			}
			prevEntryScope = zv::Val::copyOf(bodyScope.ref());
			zv::Val tempStorage = pt_expression_result_storage_duplicate(storage);
			if (UNEXPECTED(tempStorage.isUndef())) return zv::Val();
			zv::Val noop = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
			if (UNEXPECTED(noop.isUndef())) return zv::Val();
			zv::Val bodyContext = pt_statement_context_without_template_argument_resolution(context);
			if (UNEXPECTED(bodyContext.isUndef())) return zv::Val();
			zv::Val bodyScopeResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, parentNode, bodyStmts, bodyScope.raw(), tempStorage.raw(), noop.raw(), bodyContext.raw());
			if (UNEXPECTED(bodyScopeResult.isUndef())) return zv::Val();

			zv::Val gotoScope = zv::Val::null();
			zv::Val exitPoints = isrGetExitPoints(bodyScopeResult.raw());
			if (UNEXPECTED(exitPoints.isUndef())) return zv::Val();
			if (exitPoints.ref().isArray()) {
				for (auto entry : zv::ArrRef(exitPoints.raw())) {
					zval *exitPoint = entry.value().deref().raw();
					zv::Val epStmt = exitPointGetStatement(exitPoint);
					if (UNEXPECTED(epStmt.isUndef())) return zv::Val();
					bool isGoto;
					if (UNEXPECTED(!isInstance(epStmt.raw(), PT_CLASS_GOTO_STMT, isGoto))) return zv::Val();
					if (!isGoto) continue;
					zval *nameNode = nodeProperty(pt_sh_name_site, epStmt.raw(), PT_LC("name"));
					if (UNEXPECTED(nameNode == NULL)) return zv::Val();
					zv::Val name = nameToString(nameNode);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
					if (UNEXPECTED(!name.ref().isString())) {
						zend_type_error("PHPStan\\Analyser\\StatementsHandler::{closure}(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(name.raw()));
						return zv::Val();
					}
					if (!matcher.matches(Z_STR_P(name.raw()))) continue;

					zv::Val epScope = exitPointGetScope(exitPoint);
					if (UNEXPECTED(epScope.isUndef())) return zv::Val();
					if (gotoScope.isNull()) {
						gotoScope = std::move(epScope);
					} else {
						gotoScope = pt_mutating_scope_merge_with(Z_OBJ_P(gotoScope.raw()), epScope.raw());
						if (UNEXPECTED(gotoScope.isUndef())) return zv::Val();
					}
				}
			}

			if (!gotoScope.isNull()) {
				bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(scope), gotoScope.raw());
				if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
			}

			bool equal;
			if (UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(bodyScope.raw()), Z_OBJ_P(prevScope.raw()), equal))) return zv::Val();
			if (equal) break;

			if (count >= PT_SH_GENERALIZE_AFTER_ITERATION_LIMIT) {
				bodyScope = pt_mutating_scope_generalize_with(Z_OBJ_P(prevScope.raw()), Z_OBJ_P(bodyScope.raw()));
				if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
			}
			count++;
		} while (count < PT_SH_LOOP_SCOPE_ITERATIONS_LIMIT);

		return bodyScope;
	}

	/* the private mergeForwardGotoExitPoints(): the three results written
	 * through the out parameters */
	[[nodiscard]] static bool mergeForwardGotoExitPoints(zval *labelName, zv::Val &scope, bool &alreadyTerminated, zv::Val &exitPoints)
	{
		zv::Arr newExitPoints = zv::Arr::empty();
		zv::Val held = zv::Val::copyOf(exitPoints.ref());
		if (held.ref().isArray()) {
			for (auto entry : zv::ArrRef(held.raw())) {
				zval *exitPoint = entry.value().deref().raw();
				zv::Val exitStmt = exitPointGetStatement(exitPoint);
				if (UNEXPECTED(exitStmt.isUndef())) return false;
				bool isGoto;
				if (UNEXPECTED(!isInstance(exitStmt.raw(), PT_CLASS_GOTO_STMT, isGoto))) return false;
				bool sameLabel = false;
				if (isGoto) {
					zval *nameNode = nodeProperty(pt_sh_name_site, exitStmt.raw(), PT_LC("name"));
					if (UNEXPECTED(nameNode == NULL)) return false;
					zv::Val name = nameToString(nameNode);
					if (UNEXPECTED(name.isUndef())) return false;
					sameLabel = zend_is_identical(name.raw(), labelName);
				}
				if (sameLabel) {
					zv::Val exitScope = exitPointGetScope(exitPoint);
					if (UNEXPECTED(exitScope.isUndef())) return false;
					if (alreadyTerminated) {
						scope = std::move(exitScope);
						alreadyTerminated = false;
					} else {
						scope = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), exitScope.raw());
						if (UNEXPECTED(scope.isUndef())) return false;
					}
				} else {
					newExitPoints.push(zv::Ref(exitPoint));
				}
			}
		}
		exitPoints = zv::Val(std::move(newExitPoints));
		return true;
	}

	/* the private processUnreachableStatement() */
	[[nodiscard]] static bool processUnreachableStatement(zval *nodeScopeResolver, zval *nextStmts, zval *scope, zval *storage, zval *nodeCallback)
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(nextStmts)) == 0) return true;

		zval *unreachableStatement = NULL;
		zv::Arr nextStatements = zv::Arr::empty();
		for (auto entry : zv::ArrRef(nextStmts)) {
			if (!entry.hasStringKey() && entry.indexKey() == 0) {
				unreachableStatement = entry.value().deref().raw();
				continue;
			}
			nextStatements.push(entry.value().deref());
		}

		bool isStmt = false;
		if (unreachableStatement != NULL && UNEXPECTED(!isInstance(unreachableStatement, PT_CLASS_STMT, isStmt))) return false;
		if (!isStmt) return true;

		zv::Args argv{unreachableStatement, nextStatements.raw()};
		zv::Val node = pt_type_new(PT_CLASS_UNREACHABLE_STATEMENT_NODE, 2, argv);
		if (UNEXPECTED(node.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, node.raw(), scope, storage);
	}

	/* the private getNextUnreachableStatements() */
	static zv::Val getNextUnreachableStatements(zval *nodes, bool earlyBinding)
	{
		zv::Arr stmts = zv::Arr::empty();
		bool isPassedUnreachableStatement = false;
		for (auto entry : zv::ArrRef(nodes)) {
			zval *node = entry.value().deref().raw();
			bool is;
			if (UNEXPECTED(!isInstance(node, PT_CLASS_LABEL_STMT, is))) return zv::Val();
			if (is) break;
			if (earlyBinding) {
				bool early = false;
				for (int classIdx : { PT_CLASS_FUNCTION_STMT, PT_CLASS_CLASS_LIKE_STMT, PT_CLASS_HALT_COMPILER }) {
					if (UNEXPECTED(!isInstance(node, classIdx, early))) return zv::Val();
					if (early) break;
				}
				if (early) continue;
			}
			bool isStmt;
			if (UNEXPECTED(!isInstance(node, PT_CLASS_STMT, isStmt))) return zv::Val();
			if (isPassedUnreachableStatement && isStmt) {
				stmts.push(zv::Ref(node));
				continue;
			}
			bool skip;
			if (UNEXPECTED(!isInstance(node, PT_CLASS_NOP_STMT, skip))) return zv::Val();
			if (!skip && UNEXPECTED(!isInstance(node, PT_CLASS_INLINE_HTML_STMT, skip))) return zv::Val();
			if (skip) continue;
			if (!isStmt) continue;
			stmts.push(zv::Ref(node));
			isPassedUnreachableStatement = true;
		}
		return zv::Val(std::move(stmts));
	}

	/* the private processStatementStep() */
	[[nodiscard]] bool processStatementStep(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zend_long i, zval *stmt, zval *state, zval *storage, zval *nodeCallback, zval *context, bool shouldCheckLastStatement)
	{
		/* the twin's `Node\Stmt $stmt` parameter: the caller's list may hold anything */
		zend_class_entry *stmtCe = pt_class(PT_CLASS_STMT);
		if (UNEXPECTED(stmtCe == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(stmt) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(stmt), stmtCe))) {
			zend_type_error("PHPStan\\Analyser\\StatementsHandler::processStatementStep(): Argument #5 ($stmt) must be of type PhpParser\\Node\\Stmt, %s given", zend_zval_value_name(stmt));
			return false;
		}
		zend_object *stateObject = Z_OBJ_P(state);
		zval *stateScope = OBJ_PROP_NUM(stateObject, stateSlots::scope);
		if (Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE) {
			bool keeps;
			if (UNEXPECTED(!isEarlyBound(stmt, keeps))) return false;
			if (!keeps) return true;
		}

		bool isLast = i == (zend_long) zend_hash_num_elements(Z_ARRVAL_P(stmts)) - 1;

		zv::Val nestedLabelNames = pt_engine_node_get_attribute(Z_OBJ_P(stmt), PT_LC("nestedBackwardGotoLabels"));
		if (UNEXPECTED(nestedLabelNames.isUndef())) return false;
		if (!nestedLabelNames.isNull()) {
			bool topLevel;
			if (UNEXPECTED(!pt_statement_context_is_top_level(context, topLevel))) return false;
			if (topLevel) {
				zv::Arr bodyStmts = zv::Arr::create(1);
				bodyStmts.push(zv::Ref(stmt));
				zv::Val deep = pt_statement_context_enter_deep(context);
				if (UNEXPECTED(deep.isUndef())) return false;
				GotoNameMatcher matcher = { nestedLabelNames.raw(), NULL };
				zv::Val resolved = resolveBackwardGotoScope(nodeScopeResolver, parentNode, bodyStmts.raw(), stateScope, storage, deep.raw(), matcher, false);
				if (UNEXPECTED(resolved.isUndef())) return false;
				zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(resolved));
			}
		}

		zv::Val statementResult = pt_node_scope_resolver_process_stmt_node(nodeScopeResolver, stmt, OBJ_PROP_NUM(stateObject, stateSlots::scope), storage, nodeCallback, context);
		if (UNEXPECTED(statementResult.isUndef())) return false;
		zv::Val variableFlow = isrGetVariableFlow(statementResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return false;
		zval *variableFlows = OBJ_PROP_NUM(stateObject, stateSlots::variableFlows);
		SEPARATE_ARRAY(variableFlows);
		zend_hash_index_update(Z_ARRVAL_P(variableFlows), (zend_ulong) i, variableFlow.raw());
		ZVAL_UNDEF(variableFlow.raw());
		zv::Val resultScope = isrGetScope(statementResult.raw());
		if (UNEXPECTED(resultScope.isUndef())) return false;
		zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(resultScope));
		if (Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::hasYield)) != IS_TRUE) {
			zv::Val hasYield = isrHasYield(statementResult.raw());
			if (UNEXPECTED(hasYield.isUndef())) return false;
			ZVAL_BOOL(OBJ_PROP_NUM(stateObject, stateSlots::hasYield), Z_TYPE_P(hasYield.raw()) == IS_TRUE);
		}

		bool isLabel;
		if (UNEXPECTED(!isInstance(stmt, PT_CLASS_LABEL_STMT, isLabel))) return false;
		if (isLabel) {
			zval *nameNode = nodeProperty(pt_sh_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(nameNode == NULL)) return false;
			zv::Val labelName = nameToString(nameNode);
			if (UNEXPECTED(labelName.isUndef())) return false;
			zv::Val scope = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)));
			bool alreadyTerminated = Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			zv::Val exitPoints = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::exitPoints)));
			if (UNEXPECTED(!mergeForwardGotoExitPoints(labelName.raw(), scope, alreadyTerminated, exitPoints))) return false;
			zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(scope));
			ZVAL_BOOL(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated), alreadyTerminated);
			zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::exitPoints)).assign(std::move(exitPoints));

			if (alreadyTerminated) return true;

			zv::Val hasBackwardGoto = pt_engine_node_get_attribute(Z_OBJ_P(stmt), PT_LC("hasBackwardGoto"));
			if (UNEXPECTED(hasBackwardGoto.isUndef())) return false;
			if (hasBackwardGoto.ref().isTrue()) {
				bool topLevel;
				if (UNEXPECTED(!pt_statement_context_is_top_level(context, topLevel))) return false;
				if (topLevel) {
					zv::Val bodyStmts = arraySlice(stmts, i + 1);
					zv::Val deep = pt_statement_context_enter_deep(context);
					if (UNEXPECTED(deep.isUndef())) return false;
					GotoNameMatcher matcher = { NULL, Z_TYPE_P(labelName.raw()) == IS_STRING ? Z_STR_P(labelName.raw()) : ZSTR_EMPTY_ALLOC() };
					zv::Val resolved = resolveBackwardGotoScope(nodeScopeResolver, parentNode, bodyStmts.raw(), OBJ_PROP_NUM(stateObject, stateSlots::scope), storage, deep.raw(), matcher, true);
					if (UNEXPECTED(resolved.isUndef())) return false;
					zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(resolved));
				}
			}
		}

		if (shouldCheckLastStatement && isLast && UNEXPECTED(!emitExecutionEnds(nodeScopeResolver, parentNode, stmt, stateObject, statementResult.raw(), storage, nodeCallback))) return false;

		zv::Val exitPoints = isrGetExitPoints(statementResult.raw());
		if (UNEXPECTED(exitPoints.isUndef() || !requireArray(exitPoints.raw(), "array_merge", 2))) return false;
		arrayMergeInto(OBJ_PROP_NUM(stateObject, stateSlots::exitPoints), exitPoints.raw());
		zv::Val throwPoints = isrGetThrowPoints(statementResult.raw());
		if (UNEXPECTED(throwPoints.isUndef() || !requireArray(throwPoints.raw(), "array_merge", 2))) return false;
		arrayMergeInto(OBJ_PROP_NUM(stateObject, stateSlots::throwPoints), throwPoints.raw());
		zv::Val impurePoints = isrGetImpurePoints(statementResult.raw());
		if (UNEXPECTED(impurePoints.isUndef() || !requireArray(impurePoints.raw(), "array_merge", 2))) return false;
		arrayMergeInto(OBJ_PROP_NUM(stateObject, stateSlots::impurePoints), impurePoints.raw());

		if (Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE) return true;
		bool terminating;
		if (UNEXPECTED(!isAlwaysTerminating(statementResult.raw(), terminating))) return false;
		if (!terminating) return true;

		ZVAL_TRUE(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated));
		zv::Val rest = arraySlice(stmts, i + 1);
		bool earlyBinding;
		if (UNEXPECTED(!isInstance(parentNode, PT_CLASS_NAMESPACE_STMT, earlyBinding))) return false;
		zv::Val nextStmts = getNextUnreachableStatements(rest.raw(), earlyBinding);
		if (UNEXPECTED(nextStmts.isUndef())) return false;
		return processUnreachableStatement(nodeScopeResolver, nextStmts.raw(), OBJ_PROP_NUM(stateObject, stateSlots::scope), storage, nodeCallback);
	}

	/* the `$shouldCheckLastStatement && $isLast` block of
	 * processStatementStep(): the ExecutionEndNode emissions */
	[[nodiscard]] static bool emitExecutionEnds(zval *nodeScopeResolver, zval *parentNode, zval *stmt, zend_object *stateObject, zval *statementResult, zval *storage, zval *nodeCallback)
	{
		bool hasDeclaredReturnType = false;
		bool returnTyped;
		if (UNEXPECTED(!isInstance(parentNode, PT_CLASS_FUNCTION_LIKE, returnTyped))) return false;
		if (!returnTyped && UNEXPECTED(!isInstance(parentNode, PT_CLASS_PROPERTY_HOOK_STATEMENT_NODE, returnTyped))) return false;
		if (returnTyped) {
			zv::Val returnType = nodeGetReturnType(parentNode);
			if (UNEXPECTED(returnType.isUndef())) return false;
			hasDeclaredReturnType = !returnType.isNull();
		}

		zv::Val endStatements = isrGetEndStatements(statementResult);
		if (UNEXPECTED(endStatements.isUndef())) return false;
		if (UNEXPECTED(!endStatements.ref().isArray())) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(endStatements.raw()));
			return false;
		}
		bool hasYield = Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::hasYield)) == IS_TRUE;
		if (zend_hash_num_elements(Z_ARRVAL_P(endStatements.raw())) > 0) {
			for (auto entry : zv::ArrRef(endStatements.raw())) {
				zval *endStatement = entry.value().deref().raw();
				zv::Val endStatementResult = endStatementGetResult(endStatement);
				if (UNEXPECTED(endStatementResult.isUndef())) return false;
				zv::Val endStmt = endStatementGetStatement(endStatement);
				if (UNEXPECTED(endStmt.isUndef())) return false;
				zv::Val endScope = isrGetScope(endStatementResult.raw());
				if (UNEXPECTED(endScope.isUndef())) return false;
				zv::Val terminating = isrIsAlwaysTerminating(endStatementResult.raw());
				if (UNEXPECTED(terminating.isUndef())) return false;
				zv::Val exitPoints = isrGetExitPoints(endStatementResult.raw());
				if (UNEXPECTED(exitPoints.isUndef())) return false;
				zv::Val throwPoints = isrGetThrowPoints(endStatementResult.raw());
				if (UNEXPECTED(throwPoints.isUndef())) return false;
				zv::Val impurePoints = isrGetImpurePoints(endStatementResult.raw());
				if (UNEXPECTED(impurePoints.isUndef())) return false;
				zv::Val result = pt_internal_statement_result_new(endScope.raw(), hasYield, Z_TYPE_P(terminating.raw()) == IS_TRUE, exitPoints.raw(), throwPoints.raw(), impurePoints.raw());
				if (UNEXPECTED(result.isUndef())) return false;
				zv::Val publicResult = isrToPublic(result.raw());
				if (UNEXPECTED(publicResult.isUndef())) return false;
				zv::Val endStmtForResult = endStatementGetStatement(endStatement);
				if (UNEXPECTED(endStmtForResult.isUndef())) return false;
				zv::Val exprResult = readEndStatementExprResult(endStmtForResult.raw(), storage);
				if (UNEXPECTED(exprResult.isUndef())) return false;
				zv::Args nodeArgv{endStmt.raw(), publicResult.raw(), hasDeclaredReturnType, exprResult.raw()};
				zv::Val endNode = pt_type_new(PT_CLASS_EXECUTION_END_NODE, 4, nodeArgv);
				if (UNEXPECTED(endNode.isUndef())) return false;
				zv::Val callbackScope = isrGetScope(endStatementResult.raw());
				if (UNEXPECTED(callbackScope.isUndef())) return false;
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, endNode.raw(), callbackScope.raw(), storage))) return false;
			}
			return true;
		}

		zval *stateScope = OBJ_PROP_NUM(stateObject, stateSlots::scope);
		zv::Val terminating = isrIsAlwaysTerminating(statementResult);
		if (UNEXPECTED(terminating.isUndef())) return false;
		zv::Val exitPoints = isrGetExitPoints(statementResult);
		if (UNEXPECTED(exitPoints.isUndef())) return false;
		zv::Val throwPoints = isrGetThrowPoints(statementResult);
		if (UNEXPECTED(throwPoints.isUndef())) return false;
		zv::Val impurePoints = isrGetImpurePoints(statementResult);
		if (UNEXPECTED(impurePoints.isUndef())) return false;
		zv::Val result = pt_internal_statement_result_new(stateScope, hasYield, Z_TYPE_P(terminating.raw()) == IS_TRUE, exitPoints.raw(), throwPoints.raw(), impurePoints.raw());
		if (UNEXPECTED(result.isUndef())) return false;
		zv::Val publicResult = isrToPublic(result.raw());
		if (UNEXPECTED(publicResult.isUndef())) return false;
		zv::Val exprResult = readEndStatementExprResult(stmt, storage);
		if (UNEXPECTED(exprResult.isUndef())) return false;
		zv::Args nodeArgv{stmt, publicResult.raw(), hasDeclaredReturnType, exprResult.raw()};
		zv::Val endNode = pt_type_new(PT_CLASS_EXECUTION_END_NODE, 4, nodeArgv);
		if (UNEXPECTED(endNode.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, endNode.raw(), OBJ_PROP_NUM(stateObject, stateSlots::scope), storage);
	}

	/* the private readEndStatementExprResult() */
	static zv::Val readEndStatementExprResult(zval *stmt, zval *storage)
	{
		bool isExpression;
		if (UNEXPECTED(!isInstance(stmt, PT_CLASS_EXPRESSION_STMT, isExpression))) return zv::Val();
		if (!isExpression) return zv::Val::null();
		zval *expr = nodeProperty(pt_sh_expr_site, stmt, PT_LC("expr"));
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExpressionResultStorage::findExpressionResult(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Val();
		}
		return pt_expression_result_storage_find(storage, expr);
	}

	/* }}} */

	/* {{{ the two-pass function-like body walk */

	/* the private processBodyStmtNodesTwoPass() */
	zv::Val processBodyStmtNodesTwoPass(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context)
	{
		HashTable *stmtsTable = Z_ARRVAL_P(stmts);
		zend_long stmtCount = zend_hash_num_elements(stmtsTable);
		zv::Arr statementStartTokenPositions = zv::Arr::create((uint32_t) stmtCount);
		zv::Val stmtsHeld = zv::Val::copyOf(zv::Ref(stmts));
		for (auto entry : zv::ArrRef(stmtsHeld.raw())) {
			zv::Val position = nodeGetStartTokenPos(entry.value().deref().raw());
			if (UNEXPECTED(position.isUndef())) return zv::Val();
			statementStartTokenPositions.push(std::move(position));
		}
		zv::Val parentFrame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scopeArg));
		if (UNEXPECTED(parentFrame.isUndef())) return zv::Val();
		zv::Val parentConstraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(scopeArg));
		if (UNEXPECTED(parentConstraints.isUndef())) return zv::Val();
		zv::Val observationFrame = pt_template_argument_frame_new(parentFrame.raw(), NULL, NULL, parentNode, stmts);
		if (UNEXPECTED(observationFrame.isUndef())) return zv::Val();
		bool statsEnabled;
		if (UNEXPECTED(!templateArgumentStatsEnabled(statsEnabled))) return zv::Val();
		if (statsEnabled) {
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("bodiesWalked"), 1))) return zv::Val();
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("statementsTotal"), stmtCount))) return zv::Val();
		}
		zv::Val framed = pt_mutating_scope_with_template_argument_frame(Z_OBJ_P(scopeArg), observationFrame.raw());
		if (UNEXPECTED(framed.isUndef())) return zv::Val();
		zval null;
		ZVAL_NULL(&null);
		zv::Val scope = pt_mutating_scope_with_template_argument_constraints(Z_OBJ_P(framed.raw()), &null);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val recording = newRecordingNodeCallback();
		if (UNEXPECTED(recording.isUndef())) return zv::Val();
		zv::Val state = pt_statement_list_walk_state_new(scope.raw());
		if (UNEXPECTED(state.isUndef())) return zv::Val();
		zv::Arr entries = zv::Arr::empty();
		zv::Val observationContext = pt_statement_context_without_template_argument_resolution(context);
		if (UNEXPECTED(observationContext.isUndef())) return zv::Val();
		zv::Val suspendedGatherers = pt_node_scope_resolver_suspend_node_gatherers(nodeScopeResolver);
		if (UNEXPECTED(suspendedGatherers.isUndef())) return zv::Val();
		for (auto entry : zv::ArrRef(stmtsHeld.raw())) {
			if (UNEXPECTED(entry.hasStringKey())) {
				throwStatementKeyError();
				break;
			}
			zend_long i = (zend_long) entry.indexKey();
			zv::Val snapshot = snapshotEntry(state.raw(), recording.raw());
			if (UNEXPECTED(snapshot.isUndef())) break;
			entries.separate();
			zend_hash_index_update(entries.table(), (zend_ulong) i, snapshot.raw());
			ZVAL_UNDEF(snapshot.raw());
			if (UNEXPECTED(!processStatementStep(nodeScopeResolver, parentNode, stmts, i, entry.value().deref().raw(), state.raw(), storage, recording.raw(), observationContext.raw(), true))) break;
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_restore_node_gatherers(nodeScopeResolver, suspendedGatherers.raw()); });
		if (UNEXPECTED(EG(exception))) return zv::Val();

		{
			zv::Val snapshot = snapshotEntry(state.raw(), recording.raw());
			if (UNEXPECTED(snapshot.isUndef())) return zv::Val();
			entries.separate();
			zend_hash_index_update(entries.table(), (zend_ulong) stmtCount, snapshot.raw());
			ZVAL_UNDEF(snapshot.raw());
		}
		zval *stateScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
		zv::Val finalConstraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(stateScope));
		if (UNEXPECTED(finalConstraints.isUndef())) return zv::Val();
		if (finalConstraints.isNull()) {
			finalConstraints = templateArgumentConstraintsCreateEmpty();
			if (UNEXPECTED(finalConstraints.isUndef())) return zv::Val();
		}
		zv::Val staticSites = pt_static_variable_inference_get_sites(OBJ_PROP_NUM(self, slots::staticVariableInference), parentNode, stmts);
		if (UNEXPECTED(staticSites.isUndef())) return zv::Val();
		zv::Val staticVariableTypes = zv::Val(zv::Arr::empty());
		zv::Val staticVariableConditionalExpressions = zv::Val(zv::Arr::empty());
		zv::Arr staticStatementIndexes = zv::Arr::empty();
		zv::Val staticWalk = zv::Val::null();
		if (staticSites.ref().isArray() && zend_hash_num_elements(Z_ARRVAL_P(staticSites.raw())) > 0) {
			for (auto entry : zv::ArrRef(staticSites.raw())) {
				zval *index = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 1);
				if (UNEXPECTED(index == NULL)) continue;
				staticStatementIndexes.separate();
				zval marked;
				ZVAL_TRUE(&marked);
				zend_hash_index_update(staticStatementIndexes.table(), (zend_ulong) zval_get_long(index), &marked);
			}
			zv::Val inferred = inferStaticVariableTypes(nodeScopeResolver, parentNode, stmts, staticSites.raw(), staticStatementIndexes.raw(), parentFrame.raw(), entries.raw(), recording.raw(), storage, observationContext.raw());
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			staticVariableTypes = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(inferred.raw()), 0)));
			staticVariableConditionalExpressions = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(inferred.raw()), 1)));
			finalConstraints = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(inferred.raw()), 2)));
			staticWalk = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(inferred.raw()), 3)));
		}
		zv::Val frame = templateArgumentResolverResolve(OBJ_PROP_NUM(self, slots::templateArgumentResolver), finalConstraints.raw(), parentFrame.raw(), statementStartTokenPositions.raw(), parentNode, stmts);
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(staticVariableTypes.raw())) > 0) {
			frame = pt_template_argument_frame_with_static_variable_types(frame.raw(), staticVariableTypes.raw(), staticStatementIndexes.raw(), staticVariableConditionalExpressions.raw());
			if (UNEXPECTED(frame.isUndef())) return zv::Val();
		}
		if (staticWalk.ref().isArray()) {
			bool constraintsEmpty;
			if (UNEXPECTED(!pt_template_argument_constraints_is_empty(finalConstraints.raw(), constraintsEmpty))) return zv::Val();
			if (constraintsEmpty) {
				bool hasFunctionLike;
				zend_long start = zval_get_long(zend_hash_index_find(Z_ARRVAL_P(staticWalk.raw()), 0));
				if (UNEXPECTED(!pt_static_variable_inference_has_function_like_from(OBJ_PROP_NUM(self, slots::staticVariableInference), parentNode, stmts, start, hasFunctionLike))) return zv::Val();
				if (!hasFunctionLike) {
					return replayStaticVariableWalk(nodeScopeResolver, stmts, entries.raw(), recording.raw(), staticWalk.raw(), frame.raw(), parentFrame.raw(), parentConstraints.raw(), storage, nodeCallback, scope.raw(), statsEnabled);
				}
			}
		}
		{
			bool observingClosures;
			if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observingClosures))) return zv::Val();
			if (observingClosures) {
				frame = observeClosureSignatures(nodeScopeResolver, parentNode, stmts, frame.raw(), entries.raw(), storage, observationContext.raw(), statementStartTokenPositions.raw());
				if (UNEXPECTED(frame.isUndef())) return zv::Val();
			}
		}

		zv::Val firstSiteStatementIndex = frameFirstSiteStatementIndex(frame.raw());
		if (UNEXPECTED(firstSiteStatementIndex.isUndef())) return zv::Val();
		if (firstSiteStatementIndex.isNull()) {
			zend_long recorded;
			if (UNEXPECTED(!recordingNodeCallbackCount(recording.raw(), recorded))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording.raw(), 0, recorded, nodeCallback, storage, scope.raw()))) return zv::Val();
			if (UNEXPECTED(!restoreParentFrame(state.raw(), parentFrame.raw(), parentConstraints.raw()))) return zv::Val();
			return pt_statement_list_walk_state_to_result(state.raw());
		}

		zend_long firstSite = zval_get_long(firstSiteStatementIndex.raw());
		if (statsEnabled) {
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("bodiesWithSites"), 1))) return zv::Val();
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("statementsReplayed"), firstSite))) return zv::Val();
		}
		// the second pass: the statements before the first site stand as
		// recorded; from there on a statement is re-walked only when it
		// created a site or mentions a variable whose tracked state the
		// resolutions changed - the rest replay their recording and carry
		// their recorded effect onto the re-walked scope
		zval *firstEntry = entryAt(entries.raw(), firstSite);
		if (UNEXPECTED(firstEntry == NULL)) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording.raw(), 0, zval_get_long(entryOffset(firstEntry)), nodeCallback, storage, scope.raw()))) return zv::Val();
		bool hasLabels;
		if (UNEXPECTED(!containsLabels(stmtsHeld.raw(), hasLabels))) return zv::Val();
		state = cloneObject(entryState(firstEntry));
		if (UNEXPECTED(state.isUndef())) return zv::Val();
		{
			zval *clonedScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
			zv::Val reframed = pt_mutating_scope_with_template_argument_frame(Z_OBJ_P(clonedScope), frame.raw());
			if (UNEXPECTED(reframed.isUndef())) return zv::Val();
			zv::Val unconstrained = pt_mutating_scope_with_template_argument_constraints(Z_OBJ_P(reframed.raw()), &null);
			if (UNEXPECTED(unconstrained.isUndef())) return zv::Val();
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)).assign(std::move(unconstrained));
		}
		zv::Val finalEntryHeld = zv::Val::copyOf(zv::Ref(entryAt(entries.raw(), stmtCount)));
		for (zend_long i = firstSite; i < stmtCount; i++) {
			zval *recordedEntryPair = entryAt(entries.raw(), i);
			zval *recordedExitPair = recordedEntryPair != NULL ? entryAt(entries.raw(), i + 1) : NULL;
			if (UNEXPECTED(recordedExitPair == NULL)) return zv::Val();
			zval *recordedEntry = entryState(recordedEntryPair);
			zend_long offset = zval_get_long(entryOffset(recordedEntryPair));
			zval *recordedExit = entryState(recordedExitPair);
			zend_long nextOffset = zval_get_long(entryOffset(recordedExitPair));
			zend_object *stateObject = Z_OBJ_P(state.raw());
			zend_object *recordedEntryObject = Z_OBJ_P(recordedEntry);

			zv::Val differingRoots = zv::Val::null();
			bool stateTerminated = Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			bool entryTerminated = Z_TYPE_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			if (stateTerminated == entryTerminated) {
				differingRoots = pt_mutating_scope_get_differing_variable_roots(Z_OBJ_P(OBJ_PROP_NUM(stateObject, stateSlots::scope)), Z_OBJ_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope)));
				if (UNEXPECTED(differingRoots.isUndef())) return zv::Val();
			}
			bool noDifference = differingRoots.ref().isArray() && zend_hash_num_elements(Z_ARRVAL_P(differingRoots.raw())) == 0;
			if (noDifference) {
				bool hasSite;
				if (UNEXPECTED(!frameHasSiteAtOrAfter(frame.raw(), i, hasSite))) return zv::Val();
				if (!hasSite) {
					// converged with the observation pass: the rest of its recording stands
					if (statsEnabled) {
						if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("earlyExits"), 1))) return zv::Val();
						if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("statementsReplayed"), stmtCount - i))) return zv::Val();
					}
					zend_long recorded;
					if (UNEXPECTED(!recordingNodeCallbackCount(recording.raw(), recorded))) return zv::Val();
					if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording.raw(), offset, recorded, nodeCallback, storage, scope.raw()))) return zv::Val();
					zval *finalState = entryState(finalEntryHeld.raw());
					if (UNEXPECTED(!appendRecordedStatementResults(state.raw(), recordedEntry, finalState))) return zv::Val();
					// the recorded end scope, with what this pass collected
					{
						zval *stateScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
						zv::Val collected = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(stateScope));
						if (UNEXPECTED(collected.isUndef())) return zv::Val();
						zv::Val endScope = pt_mutating_scope_with_template_argument_constraints(Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ_P(finalState), stateSlots::scope)), collected.raw());
						if (UNEXPECTED(endScope.isUndef())) return zv::Val();
						zv::Ref(stateScope).assign(std::move(endScope));
					}
					if (UNEXPECTED(!processDeferredByRefClosureBodies(nodeScopeResolver, frame.raw(), state.raw(), storage, nodeCallback))) return zv::Val();
					if (UNEXPECTED(!restoreParentFrame(state.raw(), parentFrame.raw(), parentConstraints.raw()))) return zv::Val();
					return pt_statement_list_walk_state_to_result(state.raw());
				}
			}

			zval *stmt = zend_hash_index_find(stmtsTable, (zend_ulong) i);
			if (UNEXPECTED(stmt == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: the function-like body's statements are not a list");
				return zv::Val();
			}
			ZVAL_DEREF(stmt);
			bool reWalk = differingRoots.isNull() || hasLabels;
			if (!reWalk && UNEXPECTED(!frameOwnsSiteInStatement(frame.raw(), i, reWalk))) return zv::Val();
			if (!reWalk && UNEXPECTED(!statementMentionsAnyVariable(stmt, differingRoots.raw(), reWalk))) return zv::Val();
			if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::debugTemplateArguments)) == IS_TRUE && UNEXPECTED(!printDebugDecision(scope.raw(), stmt, i, reWalk, differingRoots.raw()))) return zv::Val();
			if (reWalk) {
				if (statsEnabled && UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("statementsReWalked"), 1))) return zv::Val();
				if (UNEXPECTED(!processStatementStep(nodeScopeResolver, parentNode, stmts, i, stmt, state.raw(), storage, nodeCallback, context, true))) return zv::Val();
				continue;
			}

			if (statsEnabled && UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("statementsReplayed"), 1))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording.raw(), offset, nextOffset, nodeCallback, storage, scope.raw()))) return zv::Val();
			if (UNEXPECTED(!appendRecordedStatementResults(state.raw(), recordedEntry, recordedExit))) return zv::Val();
			zval *currentScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
			zv::Val delta = pt_mutating_scope_with_recorded_statement_delta(Z_OBJ_P(currentScope), Z_OBJ_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope)), Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ_P(recordedExit), stateSlots::scope)));
			if (UNEXPECTED(delta.isUndef())) return zv::Val();
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)).assign(std::move(delta));
		}
		if (UNEXPECTED(!processDeferredByRefClosureBodies(nodeScopeResolver, frame.raw(), state.raw(), storage, nodeCallback))) return zv::Val();

		if (UNEXPECTED(!restoreParentFrame(state.raw(), parentFrame.raw(), parentConstraints.raw()))) return zv::Val();
		return pt_statement_list_walk_state_to_result(state.raw());
	}

	/* Mirrors the private getClosureProcessor(): looked up instead of
	 * injected; UNDEF = pending exception */
	zv::Val getClosureProcessor() const
	{
		zval *container = OBJ_PROP_NUM(self, slots::container);
		if (UNEXPECTED(Z_TYPE_P(container) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getByType() on %s", zend_zval_value_name(container));
			return zv::Val();
		}
		zval className;
		ZVAL_STRINGL(&className, "PHPStan\\Analyser\\ClosureProcessor", sizeof("PHPStan\\Analyser\\ClosureProcessor") - 1);
		zv::Val service = pt_call_method_cached(pt_sh_get_by_type_site, Z_OBJ_P(container), PT_LC("getbytype"), 1, &className);
		zval_ptr_dtor(&className);
		return service;
	}

	/* Mirrors the private processDeferredByRefClosureBodies(); false =
	 * pending exception */
	[[nodiscard]] bool processDeferredByRefClosureBodies(zval *nodeScopeResolver, zval *frame, zval *state, zval *storage, zval *nodeCallback) const
	{
		zv::Val sites = pt_template_argument_frame_get_local_by_ref_sites(frame);
		if (UNEXPECTED(sites.isUndef())) return false;
		if (!sites.ref().isArray() || zend_hash_num_elements(Z_ARRVAL_P(sites.raw())) == 0) return true;

		// the invocations are collected from the end of the body joined with
		// where it returned; the body is entered from where the closure was
		// created, which knows what its uses were narrowed to
		zv::Val result = pt_statement_list_walk_state_to_result(state);
		if (UNEXPECTED(result.isUndef())) return false;
		zv::Val endScopeHold;
		zval *endScope = pt_internal_statement_result_scope(result.raw(), endScopeHold);
		if (UNEXPECTED(endScope == NULL)) return false;
		zv::Val endScopeKept = zv::Val::copyOf(zv::Ref(endScope));
		zv::Val entryTypes = pt_closure_signature_inference_collect_by_ref_entry_types(endScopeKept.raw());
		if (UNEXPECTED(entryTypes.isUndef())) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(sites.raw())) {
			zval *site = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 0);
			if (UNEXPECTED(site == NULL)) continue;
			zv::Val creationResult = pt_expression_result_storage_find(storage, site);
			if (UNEXPECTED(creationResult.isUndef())) return false;
			zv::Val creationScopeHold;
			zval *creationScope = endScopeKept.raw();
			if (!creationResult.isNull()) {
				creationScope = pt_expression_result_before_scope(creationResult.raw(), creationScopeHold);
				if (UNEXPECTED(creationScope == NULL)) return false;
			}
			zv::Val processor = getClosureProcessor();
			if (UNEXPECTED(processor.isUndef())) return false;
			zval *siteEntryTypes = zend_hash_index_find(Z_ARRVAL_P(entryTypes.raw()), Z_OBJ_HANDLE_P(site));
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			if (UNEXPECTED(!pt_closure_processor_process_deferred_by_ref_closure_body(processor.raw(), nodeScopeResolver, site, creationScope, storage, nodeCallback, siteEntryTypes != NULL ? siteEntryTypes : &empty))) return false;
		}
		return true;
	}

	/* the private inferStaticVariableTypes(): [the site types, the
	 * conditional expressions of the runs, the constraints]; UNDEF = pending
	 * exception */
	zv::Val inferStaticVariableTypes(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *staticSites, zval *staticStatementIndexes, zval *parentFrame, zval *entries, zval *recording, zval *storageArg, zval *context)
	{
		HashTable *stmtsTable = Z_ARRVAL_P(stmts);
		zend_long stmtCount = zend_hash_num_elements(stmtsTable);
		zval *firstEntry = entryAt(entries, 0);
		zval *finalEntry = entryAt(entries, stmtCount);
		if (UNEXPECTED(firstEntry == NULL || finalEntry == NULL)) return zv::Val();
		zv::Val bodyScope = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(entryState(firstEntry)), stateSlots::scope)));
		zv::Arr names = zv::Arr::empty();
		zv::ScratchTable seenNames(8);
		zend_long start = stmtCount;
		for (auto entry : zv::ArrRef(staticSites)) {
			HashTable *site = Z_ARRVAL_P(entry.value().deref().raw());
			zval *index = zend_hash_index_find(site, 1);
			zval *name = zend_hash_index_find(site, 2);
			if (UNEXPECTED(index == NULL || name == NULL || Z_TYPE_P(name) != IS_STRING)) continue;
			if (zval_get_long(index) < start) start = zval_get_long(index);
			if (zend_hash_exists(seenNames.table(), Z_STR_P(name))) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_add(seenNames.table(), Z_STR_P(name), &marked);
			names.push(zv::Ref(name));
		}
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zv::Val runs = pt_static_variable_inference_get_runs(OBJ_PROP_NUM(self, slots::staticVariableInference), parentNode, stmts);
		if (UNEXPECTED(runs.isUndef())) return zv::Val();
		zv::Val types = collectStaticVariableTypes(bodyScope.raw(), names.raw(), recording, entryState(finalEntry), &emptyArray);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val conditionalExpressions = zv::Val(zv::Arr::empty());
		if (zend_hash_num_elements(Z_ARRVAL_P(runs.raw())) > 0) {
			zv::Val stateScopes = collectStaticVariableStateScopes(bodyScope.raw(), recording, entryState(finalEntry), &emptyArray);
			if (UNEXPECTED(stateScopes.isUndef())) return zv::Val();
			conditionalExpressions = collectStaticVariableConditionalExpressions(runs.raw(), stateScopes.raw(), storageArg, NULL, &emptyArray, types.raw());
			if (UNEXPECTED(conditionalExpressions.isUndef())) return zv::Val();
		}
		bool hasLabels;
		if (UNEXPECTED(!containsLabels(stmts, hasLabels))) return zv::Val();
		zend_long count = 0;
		zend_long conditionalExpressionsCount = 0;
		zv::Val constraints;
		zv::Val lastWalk;
		for (;;) {
			zv::Val siteTypes = staticSiteTypes(staticSites, types.raw());
			if (UNEXPECTED(siteTypes.isUndef())) return zv::Val();
			zv::Val observingFrame = pt_template_argument_frame_new(parentFrame, NULL, NULL, parentNode, stmts);
			if (UNEXPECTED(observingFrame.isUndef())) return zv::Val();
			zv::Val frame = pt_template_argument_frame_with_static_variable_types(observingFrame.raw(), siteTypes.raw(), staticStatementIndexes, conditionalExpressions.raw());
			if (UNEXPECTED(frame.isUndef())) return zv::Val();

			zval *startEntry = entryAt(entries, start);
			if (UNEXPECTED(startEntry == NULL)) return zv::Val();
			zv::Val state = cloneObject(entryState(startEntry));
			if (UNEXPECTED(state.isUndef())) return zv::Val();
			{
				zval *clonedScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
				zv::Val reframed = pt_mutating_scope_with_template_argument_frame(Z_OBJ_P(clonedScope), frame.raw());
				if (UNEXPECTED(reframed.isUndef())) return zv::Val();
				zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)).assign(std::move(reframed));
			}
			zv::Val walkStorage = pt_expression_result_storage_duplicate(storageArg);
			if (UNEXPECTED(walkStorage.isUndef())) return zv::Val();
			zv::Val walkRecording = newRecordingNodeCallback();
			if (UNEXPECTED(walkRecording.isUndef())) return zv::Val();
			zv::Arr replayedScopes = zv::Arr::empty();
			zv::Arr carriedOverScopes = zv::Arr::empty();
			zv::Arr walkLog = zv::Arr::empty();
			zv::Val suspendedGatherers = pt_node_scope_resolver_suspend_node_gatherers(nodeScopeResolver);
			if (UNEXPECTED(suspendedGatherers.isUndef())) return zv::Val();
			zv::Val pushedScope = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)));
			if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(pushedScope.raw()), walkStorage.raw()))) {
				(void) pt_node_scope_resolver_restore_node_gatherers(nodeScopeResolver, suspendedGatherers.raw());
				return zv::Val();
			}
			(void) observeStaticVariablesFrom(nodeScopeResolver, parentNode, stmts, stmtsTable, frame.raw(), entries, state.raw(), walkStorage.raw(), walkRecording.raw(), context, start, stmtCount, hasLabels, replayedScopes, carriedOverScopes, walkLog);
			pt_finally([&]() {
				(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(pushedScope.raw()));
				(void) pt_node_scope_resolver_restore_node_gatherers(nodeScopeResolver, suspendedGatherers.raw());
			});
			if (UNEXPECTED(EG(exception))) return zv::Val();

			zval *stateScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
			constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(stateScope));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			if (constraints.isNull()) {
				constraints = templateArgumentConstraintsCreateEmpty();
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			{
				zv::Arr walk = zv::Arr::create(5);
				walk.push(zv::Val::integer(start));
				walk.push(zv::Val::copyOf(zv::Ref(state.raw())));
				walk.push(zv::Val::copyOf(zv::Ref(walkRecording.raw())));
				walk.push(zv::Val::copyOf(zv::Ref(walkStorage.raw())));
				walk.push(std::move(walkLog));
				lastWalk = zv::Val(std::move(walk));
			}

			zv::Val walkTypes = collectStaticVariableTypes(bodyScope.raw(), names.raw(), walkRecording.raw(), state.raw(), replayedScopes.raw());
			if (UNEXPECTED(walkTypes.isUndef())) return zv::Val();
			bool hasRuns = zend_hash_num_elements(Z_ARRVAL_P(runs.raw())) > 0;
			zv::Val stateScopes = zv::Val(zv::Arr::empty());
			zv::Val walkConditionalExpressions = zv::Val(zv::Arr::empty());
			if (hasRuns) {
				stateScopes = collectStaticVariableStateScopes(bodyScope.raw(), walkRecording.raw(), state.raw(), carriedOverScopes.raw());
				if (UNEXPECTED(stateScopes.isUndef())) return zv::Val();
				walkConditionalExpressions = collectStaticVariableConditionalExpressions(runs.raw(), stateScopes.raw(), storageArg, types.raw(), conditionalExpressions.raw(), types.raw());
				if (UNEXPECTED(walkConditionalExpressions.isUndef())) return zv::Val();
			}
			bool converged = true;
			for (auto entry : zv::ArrRef(names.raw())) {
				zend_string *name = Z_STR_P(entry.value().raw());
				HashTable *known = Z_ARRVAL_P(zend_hash_find(Z_ARRVAL_P(types.raw()), name));
				HashTable *walked = Z_ARRVAL_P(zend_hash_find(Z_ARRVAL_P(walkTypes.raw()), name));
				bool holds = true;
				for (zend_ulong k = 0; k < 2 && holds; k++) {
					zval *knownType = zend_hash_index_find(known, k);
					zv::Val isSuperType = pt_type_op(Z_OBJ_P(knownType), PT_OP_IS_SUPER_TYPE_OF, 1, zend_hash_index_find(walked, k));
					if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
					holds = pt_type_result_trinary(isSuperType.raw()) == PT_TRI_YES;
				}
				if (!holds) {
					converged = false;
					break;
				}
			}
			bool conditionalExpressionsConverged;
			if (UNEXPECTED(!equalStaticVariableConditionalExpressions(conditionalExpressions.raw(), walkConditionalExpressions.raw(), conditionalExpressionsConverged))) return zv::Val();
			count++;
			if (converged && conditionalExpressionsConverged) break;
			if (converged) {
				// the conditional expressions follow the types
				conditionalExpressionsCount++;
				if (conditionalExpressionsCount >= PT_SH_LOOP_SCOPE_ITERATIONS_LIMIT) {
					runs = zv::Val(zv::Arr::empty());
					walkConditionalExpressions = zv::Val(zv::Arr::empty());
				}
			} else if (count >= PT_SH_LOOP_SCOPE_ITERATIONS_LIMIT) {
				if (!hasRuns) break;
				// the conditional expressions keep changing: the walks go on
				// without them - what they narrowed the types to is joined with
				// what the variables take without them
				runs = zv::Val(zv::Arr::empty());
				walkConditionalExpressions = zv::Val(zv::Arr::empty());
				count = 0;
			}

			zv::Val joinedTypes = joinStaticVariableTypes(bodyScope.raw(), names.raw(), types.raw(), walkTypes.raw(), count > PT_SH_GENERALIZE_AFTER_ITERATION_LIMIT);
			if (UNEXPECTED(joinedTypes.isUndef())) return zv::Val();
			if (zend_hash_num_elements(Z_ARRVAL_P(runs.raw())) > 0 && !converged) {
				// the next walk enters with the joined types - the conditions
				// cover them
				walkConditionalExpressions = collectStaticVariableConditionalExpressions(runs.raw(), stateScopes.raw(), storageArg, types.raw(), conditionalExpressions.raw(), joinedTypes.raw());
				if (UNEXPECTED(walkConditionalExpressions.isUndef())) return zv::Val();
			}
			types = std::move(joinedTypes);
			conditionalExpressions = std::move(walkConditionalExpressions);
		}

		zv::Val siteTypes = staticSiteTypes(staticSites, types.raw());
		if (UNEXPECTED(siteTypes.isUndef())) return zv::Val();
		// every way out of the loop leaves the last walk made with the types
		// and conditional expressions returned
		zv::Arr result = zv::Arr::create(4);
		result.push(std::move(siteTypes));
		result.push(std::move(conditionalExpressions));
		result.push(std::move(constraints));
		result.push(std::move(lastWalk));
		return zv::Val(std::move(result));
	}

	/* [kind, a, b] of the last static-variable walk's log */
	static zv::Val staticWalkLogEntry(zend_long kind, zend_long a, zend_long b)
	{
		zv::Arr entry = zv::Arr::create(3);
		entry.push(zv::Val::integer(kind));
		entry.push(zv::Val::integer(a));
		entry.push(zv::Val::integer(b));
		return zv::Val(std::move(entry));
	}

	/* Mirrors the private replayStaticVariableWalk() */
	zv::Val replayStaticVariableWalk(zval *nodeScopeResolver, zval *stmts, zval *entries, zval *recording, zval *staticWalk, zval *frame, zval *parentFrame, zval *parentConstraints, zval *storage, zval *nodeCallback, zval *scope, bool statsEnabled)
	{
		HashTable *walk = Z_ARRVAL_P(staticWalk);
		zend_long start = zval_get_long(zend_hash_index_find(walk, 0));
		zval *state = zend_hash_index_find(walk, 1);
		zval *walkRecording = zend_hash_index_find(walk, 2);
		zval *walkStorage = zend_hash_index_find(walk, 3);
		zval *walkLog = zend_hash_index_find(walk, 4);
		if (UNEXPECTED(state == NULL || walkRecording == NULL || walkStorage == NULL || walkLog == NULL)) return zv::Val();
		zend_long stmtCount = zend_hash_num_elements(Z_ARRVAL_P(stmts));
		if (statsEnabled) {
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("bodiesWithSites"), 1))) return zv::Val();
			if (UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("staticVariableWalksReplayed"), 1))) return zv::Val();
		}
		zval *startEntry = entryAt(entries, start);
		if (UNEXPECTED(startEntry == NULL)) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording, 0, zval_get_long(entryOffset(startEntry)), nodeCallback, storage, scope))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_storage_merge_results(storage, walkStorage))) return zv::Val();
		zv::Val stateHold = zv::Val::copyOf(zv::Ref(state));
		zv::Val logHold = zv::Val::copyOf(zv::Ref(walkLog));
		for (zv::ArrayEntry entry : zv::ArrRef(logHold.raw())) {
			HashTable *op = Z_ARRVAL_P(entry.value().deref().raw());
			zend_long kind = zval_get_long(zend_hash_index_find(op, 0));
			zend_long a = zval_get_long(zend_hash_index_find(op, 1));
			zend_long b = zval_get_long(zend_hash_index_find(op, 2));
			if (kind == PT_SH_STATIC_WALK_WALKED) {
				if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, walkRecording, a, b, nodeCallback, storage, scope))) return zv::Val();
				continue;
			}
			zval *entryPair = entryAt(entries, a);
			if (UNEXPECTED(entryPair == NULL)) return zv::Val();
			if (kind == PT_SH_STATIC_WALK_CARRIED) {
				zval *exitPair = entryAt(entries, a + 1);
				if (UNEXPECTED(exitPair == NULL)) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording, zval_get_long(entryOffset(entryPair)), zval_get_long(entryOffset(exitPair)), nodeCallback, storage, scope))) return zv::Val();
				continue;
			}
			zend_long recorded;
			if (UNEXPECTED(!recordingNodeCallbackCount(recording, recorded))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording_range(nodeScopeResolver, recording, zval_get_long(entryOffset(entryPair)), recorded, nodeCallback, storage, scope))) return zv::Val();
			// the recorded end scope, as the second pass takes it
			zval *finalPair = entryAt(entries, stmtCount);
			if (UNEXPECTED(finalPair == NULL)) return zv::Val();
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(stateHold.raw()), stateSlots::scope)).assign(zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(entryState(finalPair)), stateSlots::scope))));
		}
		if (UNEXPECTED(!processDeferredByRefClosureBodies(nodeScopeResolver, frame, stateHold.raw(), storage, nodeCallback))) return zv::Val();
		if (UNEXPECTED(!restoreParentFrame(stateHold.raw(), parentFrame, parentConstraints))) return zv::Val();
		return pt_statement_list_walk_state_to_result(stateHold.raw());
	}

	/* inferStaticVariableTypes()'s try block: the statements from $start on,
	 * walked or carried over, each walked exit scope and carried-over entry
	 * scope into $replayedScopes, each carried-over entry scope into
	 * $carriedOverScopes; false = pending exception */
	bool observeStaticVariablesFrom(zval *nodeScopeResolver, zval *parentNode, zval *stmts, HashTable *stmtsTable, zval *frame, zval *entries, zval *state, zval *storage, zval *nodeCallback, zval *context, zend_long start, zend_long stmtCount, bool hasLabels, zv::Arr &replayedScopes, zv::Arr &carriedOverScopes, zv::Arr &walkLog)
	{
		for (zend_long i = start; i < stmtCount; i++) {
			zval *recordedEntryPair = entryAt(entries, i);
			if (UNEXPECTED(recordedEntryPair == NULL)) return false;
			zval *recordedEntry = entryState(recordedEntryPair);
			zend_object *stateObject = Z_OBJ_P(state);
			zend_object *recordedEntryObject = Z_OBJ_P(recordedEntry);
			zv::Val differingRoots = zv::Val::null();
			bool stateTerminated = Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			bool entryTerminated = Z_TYPE_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			if (stateTerminated == entryTerminated) {
				differingRoots = pt_mutating_scope_get_differing_variable_roots(Z_OBJ_P(OBJ_PROP_NUM(stateObject, stateSlots::scope)), Z_OBJ_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope)));
				if (UNEXPECTED(differingRoots.isUndef())) return false;
			}
			if (differingRoots.ref().isArray() && zend_hash_num_elements(Z_ARRVAL_P(differingRoots.raw())) == 0) {
				bool hasSite;
				if (UNEXPECTED(!frameHasSiteAtOrAfter(frame, i, hasSite))) return false;
				if (!hasSite) {
					// converged with the observation pass: the rest of it stands
					zval *finalEntryPair = entryAt(entries, stmtCount);
					if (UNEXPECTED(finalEntryPair == NULL)) return false;
					zval *finalState = entryState(finalEntryPair);
					zv::Val carried = withRecordedConstraints(OBJ_PROP_NUM(stateObject, stateSlots::scope), OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope), OBJ_PROP_NUM(Z_OBJ_P(finalState), stateSlots::scope));
					if (UNEXPECTED(carried.isUndef())) return false;
					zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(carried));
					if (UNEXPECTED(!appendRecordedStatementResults(state, recordedEntry, finalState))) return false;
					walkLog.push(staticWalkLogEntry(PT_SH_STATIC_WALK_REST, i, 0));
					return true;
				}
			}

			zval *stmt = zend_hash_index_find(stmtsTable, (zend_ulong) i);
			if (UNEXPECTED(stmt == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: the function-like body's statements are not a list");
				return false;
			}
			ZVAL_DEREF(stmt);
			bool reWalk = differingRoots.isNull() || hasLabels;
			if (!reWalk && UNEXPECTED(!frameOwnsSiteInStatement(frame, i, reWalk))) return false;
			if (!reWalk && UNEXPECTED(!statementMentionsAnyVariable(stmt, differingRoots.raw(), reWalk))) return false;
			if (reWalk) {
				zend_long from;
				if (UNEXPECTED(!recordingNodeCallbackCount(nodeCallback, from))) return false;
				if (UNEXPECTED(!processStatementStep(nodeScopeResolver, parentNode, stmts, i, stmt, state, storage, nodeCallback, context, true))) return false;
				zend_long to;
				if (UNEXPECTED(!recordingNodeCallbackCount(nodeCallback, to))) return false;
				walkLog.push(staticWalkLogEntry(PT_SH_STATIC_WALK_WALKED, from, to));
				replayedScopes.push(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope)));
				continue;
			}

			// the statement does not read what changed: the variables keep
			// their types through it
			walkLog.push(staticWalkLogEntry(PT_SH_STATIC_WALK_CARRIED, i, 0));
			replayedScopes.push(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope)));
			carriedOverScopes.push(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope)));
			zval *recordedExitPair = entryAt(entries, i + 1);
			if (UNEXPECTED(recordedExitPair == NULL)) return false;
			zval *recordedExit = entryState(recordedExitPair);
			if (UNEXPECTED(!appendRecordedStatementResults(state, recordedEntry, recordedExit))) return false;
			zval *currentScope = OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope);
			zval *recordedEntryScope = OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope);
			zval *recordedExitScope = OBJ_PROP_NUM(Z_OBJ_P(recordedExit), stateSlots::scope);
			zv::Val delta = pt_mutating_scope_with_recorded_statement_delta(Z_OBJ_P(currentScope), Z_OBJ_P(recordedEntryScope), Z_OBJ_P(recordedExitScope));
			if (UNEXPECTED(delta.isUndef())) return false;
			zv::Val carried = withRecordedConstraints(delta.raw(), recordedEntryScope, recordedExitScope);
			if (UNEXPECTED(carried.isUndef())) return false;
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope)).assign(std::move(carried));
		}
		return true;
	}

	/* spl_object_id($var) => [$var, $type, $nativeType] of every site;
	 * UNDEF = pending exception */
	static zv::Val staticSiteTypes(zval *staticSites, zval *types)
	{
		zv::Arr siteTypes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(staticSites)) {
			HashTable *site = Z_ARRVAL_P(entry.value().deref().raw());
			zval *var = zend_hash_index_find(site, 0);
			zval *name = zend_hash_index_find(site, 2);
			if (UNEXPECTED(var == NULL || name == NULL || Z_TYPE_P(name) != IS_STRING)) continue;
			zval *pair = zend_hash_find(Z_ARRVAL_P(types), Z_STR_P(name));
			if (UNEXPECTED(pair == NULL)) {
				zend_error(E_WARNING, "Undefined array key \"%s\"", Z_STRVAL_P(name));
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue;
			}
			zv::Arr siteType = zv::Arr::create(3);
			siteType.push(zv::Ref(var));
			siteType.push(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(pair), 0)));
			siteType.push(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(pair), 1)));
			siteTypes.separate();
			zval value;
			ZVAL_COPY_VALUE(&value, siteType.raw());
			ZVAL_UNDEF(siteType.raw());
			zend_hash_index_update(siteTypes.table(), Z_OBJ_HANDLE_P(var), &value);
		}
		return zv::Val(std::move(siteTypes));
	}

	/* one scope's contribution to collectStaticVariableTypes(); false =
	 * pending exception */
	[[nodiscard]] static bool collectStaticVariableScope(zval *scope, zval *bodyFunction, zval *bodyReflection, zval *names, zv::ScratchTable &seen, zv::Arr &phpDocTypes, zv::Arr &nativeTypes)
	{
		if (Z_TYPE_P(scope) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(scope), pt_ce_mutating_scope)) return true;
		zend_ulong id = Z_OBJ_HANDLE_P(scope);
		if (zend_hash_index_exists(seen.table(), id)) return true;
		zval marked;
		ZVAL_TRUE(&marked);
		zend_hash_index_add(seen.table(), id, &marked);
		zv::Val reflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(reflection.isUndef())) return false;
		if (!zend_is_identical(reflection.raw(), bodyReflection)) return true;
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return false;
		if (!zend_is_identical(function.raw(), bodyFunction)) return true;
		for (auto entry : zv::ArrRef(names)) {
			zend_string *name = Z_STR_P(entry.value().raw());
			zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
			if (UNEXPECTED(has.isUndef())) return false;
			if (pt_type_trinary_value(has.raw()) == PT_TRI_NO) continue;
			zv::Val type = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
			if (UNEXPECTED(type.isUndef())) return false;
			zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
			if (UNEXPECTED(nativeScope.isUndef())) return false;
			zv::Val nativeType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), name);
			if (UNEXPECTED(nativeType.isUndef())) return false;
			zval *phpDocList = zend_hash_find(phpDocTypes.table(), name);
			zval *nativeList = zend_hash_find(nativeTypes.table(), name);
			SEPARATE_ARRAY(phpDocList);
			SEPARATE_ARRAY(nativeList);
			zval typeZv = type.take();
			zend_hash_next_index_insert(Z_ARRVAL_P(phpDocList), &typeZv);
			zval nativeTypeZv = nativeType.take();
			zend_hash_next_index_insert(Z_ARRVAL_P(nativeList), &nativeTypeZv);
		}
		return true;
	}

	/* the private collectStaticVariableTypes(): name => [phpdoc, native];
	 * UNDEF = pending exception */
	static zv::Val collectStaticVariableTypes(zval *bodyScope, zval *names, zval *recording, zval *endState, zval *moreScopes)
	{
		zv::Arr scopes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(moreScopes)) {
			scopes.push(zv::Ref(entry.value().deref().raw()));
		}
		zend_object *endStateObject = Z_OBJ_P(endState);
		scopes.push(zv::Ref(OBJ_PROP_NUM(endStateObject, stateSlots::scope)));
		zval *exitPoints = OBJ_PROP_NUM(endStateObject, stateSlots::exitPoints);
		if (Z_TYPE_P(exitPoints) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(exitPoints)) {
				zv::Val hold;
				zval *scope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(scope == NULL)) return zv::Val();
				scopes.push(zv::Ref(scope));
			}
		}
		zval *throwPoints = OBJ_PROP_NUM(endStateObject, stateSlots::throwPoints);
		if (Z_TYPE_P(throwPoints) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(throwPoints)) {
				zv::Val hold;
				zval *scope = pt_internal_throw_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(scope == NULL)) return zv::Val();
				scopes.push(zv::Ref(scope));
			}
		}
		zval *pairs = OBJ_PROP_NUM(Z_OBJ_P(recording), ptdecl::RecordingNodeCallback::slot::pairs);
		if (Z_TYPE_P(pairs) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(pairs)) {
				zval *scope = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 1);
				if (scope != NULL) scopes.push(zv::Ref(scope));
			}
		}

		zv::Val bodyFunction = pt_mutating_scope_get_function(Z_OBJ_P(bodyScope));
		if (UNEXPECTED(bodyFunction.isUndef())) return zv::Val();
		zv::Val bodyReflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(bodyScope));
		if (UNEXPECTED(bodyReflection.isUndef())) return zv::Val();
		zv::Arr phpDocTypes = zv::Arr::create(0);
		zv::Arr nativeTypes = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(names)) {
			phpDocTypes.set(Z_STR_P(entry.value().raw()), zv::Val(zv::Arr::empty()));
			nativeTypes.set(Z_STR_P(entry.value().raw()), zv::Val(zv::Arr::empty()));
		}
		zv::ScratchTable seen(64);
		for (auto entry : zv::ArrRef(scopes.raw())) {
			if (UNEXPECTED(!collectStaticVariableScope(entry.value().raw(), bodyFunction.raw(), bodyReflection.raw(), names, seen, phpDocTypes, nativeTypes))) return zv::Val();
		}

		zv::Arr types = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(names)) {
			zend_string *name = Z_STR_P(entry.value().raw());
			HashTable *phpDocList = Z_ARRVAL_P(zend_hash_find(phpDocTypes.table(), name));
			HashTable *nativeList = Z_ARRVAL_P(zend_hash_find(nativeTypes.table(), name));
			zv::Arr pair = zv::Arr::create(2);
			if (zend_hash_num_elements(phpDocList) == 0) {
				zv::Val never = pt_type_new_never_type();
				if (UNEXPECTED(never.isUndef())) return zv::Val();
				zv::Val nativeNever = pt_type_new_never_type();
				if (UNEXPECTED(nativeNever.isUndef())) return zv::Val();
				pair.push(std::move(never));
				pair.push(std::move(nativeNever));
			} else {
				zv::Val phpDocUnion = unionOfList(phpDocList);
				if (UNEXPECTED(phpDocUnion.isUndef())) return zv::Val();
				zv::Val nativeUnion = unionOfList(nativeList);
				if (UNEXPECTED(nativeUnion.isUndef())) return zv::Val();
				pair.push(std::move(phpDocUnion));
				pair.push(std::move(nativeUnion));
			}
			types.set(name, zv::Val(std::move(pair)));
		}
		return zv::Val(std::move(types));
	}

	/* the private collectStaticVariableStateScopes(): the scopes of the body
	 * where a call can leave its `static` variables for the next one; UNDEF =
	 * pending exception */
	zv::Val collectStaticVariableStateScopes(zval *bodyScope, zval *recording, zval *endState, zval *moreScopes)
	{
		zv::Arr scopes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(moreScopes)) {
			scopes.push(zv::Ref(entry.value().deref().raw()));
		}
		zend_object *endStateObject = Z_OBJ_P(endState);
		scopes.push(zv::Ref(OBJ_PROP_NUM(endStateObject, stateSlots::scope)));
		zval *exitPoints = OBJ_PROP_NUM(endStateObject, stateSlots::exitPoints);
		if (Z_TYPE_P(exitPoints) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(exitPoints)) {
				zv::Val hold;
				zval *scope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(scope == NULL)) return zv::Val();
				scopes.push(zv::Ref(scope));
			}
		}
		zval *throwPoints = OBJ_PROP_NUM(endStateObject, stateSlots::throwPoints);
		if (Z_TYPE_P(throwPoints) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(throwPoints)) {
				zval *throwPoint = entry.value().deref().raw();
				bool isExplicit;
				if (UNEXPECTED(!pt_internal_throw_point_is_explicit(throwPoint, isExplicit))) return zv::Val();
				if (!isExplicit) continue;
				zv::Val hold;
				zval *scope = pt_internal_throw_point_scope(throwPoint, hold);
				if (UNEXPECTED(scope == NULL)) return zv::Val();
				scopes.push(zv::Ref(scope));
			}
		}
		zval *pairs = OBJ_PROP_NUM(Z_OBJ_P(recording), ptdecl::RecordingNodeCallback::slot::pairs);
		if (Z_TYPE_P(pairs) == IS_ARRAY) {
			zval *inference = OBJ_PROP_NUM(self, slots::staticVariableInference);
			for (auto entry : zv::ArrRef(pairs)) {
				HashTable *pair = Z_ARRVAL_P(entry.value().deref().raw());
				zval *node = zend_hash_index_find(pair, 0);
				zval *scope = zend_hash_index_find(pair, 1);
				if (UNEXPECTED(node == NULL || scope == NULL)) continue;
				if (Z_TYPE_P(scope) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(scope), pt_ce_mutating_scope)) continue;
				bool canRunUserCode;
				if (UNEXPECTED(!pt_static_variable_inference_can_run_user_code(inference, node, scope, canRunUserCode))) return zv::Val();
				if (!canRunUserCode) continue;
				scopes.push(zv::Ref(scope));
			}
		}

		zv::Val bodyFunction = pt_mutating_scope_get_function(Z_OBJ_P(bodyScope));
		if (UNEXPECTED(bodyFunction.isUndef())) return zv::Val();
		zv::Val bodyReflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(bodyScope));
		if (UNEXPECTED(bodyReflection.isUndef())) return zv::Val();
		zv::Arr bodyScopes = zv::Arr::empty();
		zv::ScratchTable seen(64);
		for (auto entry : zv::ArrRef(scopes.raw())) {
			zval *scope = entry.value().raw();
			zend_ulong id = Z_OBJ_HANDLE_P(scope);
			if (zend_hash_index_exists(seen.table(), id)) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_add(seen.table(), id, &marked);
			zv::Val reflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(reflection.isUndef())) return zv::Val();
			if (!zend_is_identical(reflection.raw(), bodyReflection.raw())) continue;
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			if (!zend_is_identical(function.raw(), bodyFunction.raw())) continue;
			bodyScopes.push(zv::Ref(scope));
		}
		return zv::Val(std::move(bodyScopes));
	}

	/* $a->equals($b) of two types; false = pending exception */
	[[nodiscard]] static bool typeEquals(zval *a, zval *b, bool &out)
	{
		zv::Val equals = pt_type_op(Z_OBJ_P(a), PT_OP_EQUALS, 1, b);
		if (UNEXPECTED(equals.isUndef())) return false;
		out = Z_TYPE_P(equals.raw()) == IS_TRUE;
		return true;
	}

	/* $a->isSuperTypeOf($b) of two types as a trinary value; false = pending
	 * exception */
	[[nodiscard]] static bool typeIsSuperTypeOf(zval *a, zval *b, zend_long &out)
	{
		zv::Val isSuperType = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
		if (UNEXPECTED(isSuperType.isUndef())) return false;
		out = pt_type_result_trinary(isSuperType.raw());
		return true;
	}

	/* TypeCombinator::union($a, $b); UNDEF = pending exception */
	static zv::Val unionOf(zval *a, zval *b)
	{
		zval argv[2];
		ZVAL_COPY_VALUE(&argv[0], a);
		ZVAL_COPY_VALUE(&argv[1], b);
		return pt_type_combinator_union(2, argv);
	}

	/* $state[$name] of a state list entry (borrowed) */
	static zval *stateType(zval *state, zend_string *name)
	{
		return zend_hash_find(Z_ARRVAL_P(state), name);
	}

	/* the private static equalConditionalExpressionHolders(); false =
	 * pending exception */
	[[nodiscard]] static bool equalConditionalExpressionHolders(zval *a, zval *b, bool &out)
	{
		out = false;
		zend_object *aObject = Z_OBJ_P(a);
		zend_object *bObject = Z_OBJ_P(b);
		bool equal;
		if (UNEXPECTED(!pt_holder_equals(OBJ_PROP_NUM(aObject, PT_CEH_PROP_TYPEHOLDER), OBJ_PROP_NUM(bObject, PT_CEH_PROP_TYPEHOLDER), &equal))) return false;
		if (!equal) return true;
		HashTable *aConditions = Z_ARRVAL_P(OBJ_PROP_NUM(aObject, PT_CEH_PROP_CONDS));
		HashTable *bConditions = Z_ARRVAL_P(OBJ_PROP_NUM(bObject, PT_CEH_PROP_CONDS));
		if (zend_hash_num_elements(aConditions) != zend_hash_num_elements(bConditions)) return true;
		for (auto entry : zv::TableRef(aConditions)) {
			zend_string *key = entry.stringKeyOrNull();
			zval *bCondition = key != NULL ? zend_hash_find(bConditions, key) : zend_hash_index_find(bConditions, entry.indexKey());
			if (bCondition == NULL) return true;
			if (UNEXPECTED(!pt_holder_equals(entry.value().raw(), bCondition, &equal))) return false;
			if (!equal) return true;
		}
		out = true;
		return true;
	}

	/* the private static equalStaticVariableConditionalExpressions(); false
	 * = pending exception */
	[[nodiscard]] static bool equalStaticVariableConditionalExpressions(zval *a, zval *b, bool &out)
	{
		out = false;
		if (zend_hash_num_elements(Z_ARRVAL_P(a)) != zend_hash_num_elements(Z_ARRVAL_P(b))) return true;
		for (auto entry : zv::ArrRef(a)) {
			zval *bRun = zend_hash_index_find(Z_ARRVAL_P(b), entry.indexKey());
			if (bRun == NULL) return true;
			HashTable *aConditionalExpressions = Z_ARRVAL_P(zend_hash_index_find(Z_ARRVAL_P(entry.value().raw()), 1));
			HashTable *bConditionalExpressions = Z_ARRVAL_P(zend_hash_index_find(Z_ARRVAL_P(bRun), 1));
			if (zend_hash_num_elements(aConditionalExpressions) != zend_hash_num_elements(bConditionalExpressions)) return true;
			for (auto targetEntry : zv::TableRef(aConditionalExpressions)) {
				zval *bHolders = zend_hash_find(bConditionalExpressions, targetEntry.stringKeyOrNull());
				if (bHolders == NULL) return true;
				HashTable *aHolders = Z_ARRVAL_P(targetEntry.value().raw());
				if (zend_hash_num_elements(aHolders) != zend_hash_num_elements(Z_ARRVAL_P(bHolders))) return true;
				for (auto aHolder : zv::TableRef(aHolders)) {
					bool found = false;
					for (auto bHolder : zv::ArrRef(bHolders)) {
						bool equal;
						if (UNEXPECTED(!equalConditionalExpressionHolders(aHolder.value().raw(), bHolder.value().raw(), equal))) return false;
						if (equal) {
							found = true;
							break;
						}
					}
					if (!found) return true;
				}
			}
		}
		out = true;
		return true;
	}

	/* the private static isWalkedStaticVariableState(); false = pending
	 * exception */
	[[nodiscard]] static bool isWalkedStaticVariableState(zval *state, zval *types, zval *scope, zval *walkedConditionalExpressions, bool &out)
	{
		out = false;
		for (auto entry : zv::ArrRef(state)) {
			zval *known = zend_hash_find(Z_ARRVAL_P(types), entry.stringKeyOrNull());
			if (UNEXPECTED(known == NULL)) return true;
			bool equal;
			if (UNEXPECTED(!typeEquals(entry.value().raw(), zend_hash_index_find(Z_ARRVAL_P(known), 0), equal))) return false;
			if (!equal) return true;
		}
		if (walkedConditionalExpressions == NULL) {
			out = true;
			return true;
		}
		zv::Val scopeConditionalExpressions = pt_mutating_scope_get_conditional_expressions(Z_OBJ_P(scope));
		if (UNEXPECTED(scopeConditionalExpressions.isUndef())) return false;
		for (auto targetEntry : zv::ArrRef(walkedConditionalExpressions)) {
			zval *scopeHolders = zend_hash_find(Z_ARRVAL_P(scopeConditionalExpressions.raw()), targetEntry.stringKeyOrNull());
			for (auto holder : zv::ArrRef(targetEntry.value().raw())) {
				bool found = false;
				if (scopeHolders != NULL) {
					for (auto scopeHolder : zv::ArrRef(scopeHolders)) {
						bool equal;
						if (UNEXPECTED(!equalConditionalExpressionHolders(holder.value().raw(), scopeHolder.value().raw(), equal))) return false;
						if (equal) {
							found = true;
							break;
						}
					}
				}
				if (!found) return true;
			}
		}
		out = true;
		return true;
	}

	/* new ConditionalExpressionHolder(['$' . $guardName =>
	 * ExpressionTypeHolder::createYes(new Variable($guardName), $guardType)],
	 * ExpressionTypeHolder::createYes(new Variable($targetName), $targetType));
	 * UNDEF = pending exception */
	static zv::Val newStaticVariableConditionalExpressionHolder(zend_string *guardName, zval *guardType, zend_string *targetName, zval *targetType)
	{
		zval guardNameZv;
		ZVAL_STR(&guardNameZv, guardName);
		zv::Val guardVariable = pt_type_new(PT_CLASS_VARIABLE, 1, &guardNameZv);
		if (UNEXPECTED(guardVariable.isUndef())) return zv::Val();
		zval targetNameZv;
		ZVAL_STR(&targetNameZv, targetName);
		zv::Val targetVariable = pt_type_new(PT_CLASS_VARIABLE, 1, &targetNameZv);
		if (UNEXPECTED(targetVariable.isUndef())) return zv::Val();
		zval guardHolder;
		pt_holder_create(&guardHolder, guardVariable.raw(), guardType, PT_TRI_YES);
		zv::Val guardHolderHold = zv::Val::adopt(guardHolder);
		zval typeHolder;
		pt_holder_create(&typeHolder, targetVariable.raw(), targetType, PT_TRI_YES);
		zv::Val typeHolderHold = zv::Val::adopt(typeHolder);
		zv::Arr conditions = zv::Arr::create(1);
		zend_string *guardKey = zend_string_concat2(PT_LC("$"), ZSTR_VAL(guardName), ZSTR_LEN(guardName));
		conditions.set(guardKey, std::move(guardHolderHold));
		zend_string_release(guardKey);
		zval holderRaw;
		object_init_ex(&holderRaw, pt_ce_cond_expr_holder);
		zv::Val holder = zv::Val::adopt(holderRaw);
		zv::ObjRef holderObject(holder.ref().asObject());
		holderObject.propAtWrite(PT_CEH_PROP_CONDS, std::move(conditions));
		holderObject.propAtWrite(PT_CEH_PROP_TYPEHOLDER, std::move(typeHolderHold));
		return holder;
	}

	/* the private collectStaticVariableConditionalExpressions(); $types NULL
	 * while observing; UNDEF = pending exception */
	static zv::Val collectStaticVariableConditionalExpressions(zval *runs, zval *scopes, zval *storage, zval *types, zval *walkedConditionalExpressions, zval *enteredTypes)
	{
		zv::Arr conditionalExpressions = zv::Arr::empty();
		for (auto runEntry : zv::ArrRef(runs)) {
			HashTable *run = Z_ARRVAL_P(runEntry.value().deref().raw());
			zval *stmt = zend_hash_index_find(run, 0);
			zval *defaults = zend_hash_index_find(run, 1);
			if (UNEXPECTED(stmt == NULL || defaults == NULL)) continue;
			ZVAL_DEREF(defaults);

			zv::Arr defaultState = zv::Arr::create(0);
			for (auto defaultEntry : zv::ArrRef(defaults)) {
				zval *defaultExpr = defaultEntry.value().deref().raw();
				zv::Val defaultType;
				if (Z_TYPE_P(defaultExpr) == IS_OBJECT) {
					zv::Val defaultResult = pt_expression_result_storage_find(storage, defaultExpr);
					if (UNEXPECTED(defaultResult.isUndef())) return zv::Val();
					if (!defaultResult.isNull()) {
						defaultType = pt_expression_result_get_type(defaultResult.raw());
						if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
					}
				}
				if (defaultType.isUndef()) {
					zval nullType;
					if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
					defaultType = zv::Val::adopt(nullType);
				}
				defaultState.set(defaultEntry.stringKeyOrNull(), std::move(defaultType));
			}
			zval *walkedRun = walkedConditionalExpressions != NULL ? zend_hash_index_find(Z_ARRVAL_P(walkedConditionalExpressions), Z_OBJ_HANDLE_P(stmt)) : NULL;
			zval *walkedRunConditionalExpressions = walkedRun != NULL ? zend_hash_index_find(Z_ARRVAL_P(walkedRun), 1) : NULL;
			zval emptyArray;
			ZVAL_EMPTY_ARRAY(&emptyArray);

			zv::Arr states = zv::Arr::empty();
			states.push(std::move(defaultState));
			zv::Val never = pt_type_new_never_type();
			if (UNEXPECTED(never.isUndef())) return zv::Val();
			for (auto scopeEntry : zv::ArrRef(scopes)) {
				zval *scope = scopeEntry.value().raw();
				zv::Arr state = zv::Arr::create(0);
				bool complete = true;
				for (auto defaultEntry : zv::ArrRef(defaults)) {
					zend_string *name = defaultEntry.stringKeyOrNull();
					zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
					if (UNEXPECTED(has.isUndef())) return zv::Val();
					if (pt_type_trinary_value(has.raw()) != PT_TRI_YES) {
						complete = false;
						break;
					}
					zv::Val type = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
					if (UNEXPECTED(type.isUndef())) return zv::Val();
					zend_long isNever;
					if (UNEXPECTED(!typeIsSuperTypeOf(never.raw(), type.raw(), isNever))) return zv::Val();
					if (isNever == PT_TRI_YES) {
						// an unreachable scope
						complete = false;
						break;
					}
					state.set(name, std::move(type));
				}
				if (!complete) continue;
				if (types != NULL) {
					bool walked;
					if (UNEXPECTED(!isWalkedStaticVariableState(state.raw(), types, scope, walkedRunConditionalExpressions != NULL ? walkedRunConditionalExpressions : &emptyArray, walked))) return zv::Val();
					if (walked) continue;
				}
				bool seen = false;
				for (auto seenEntry : zv::ArrRef(states.raw())) {
					bool equal = true;
					for (auto typeEntry : zv::ArrRef(seenEntry.value().raw())) {
						if (UNEXPECTED(!typeEquals(typeEntry.value().raw(), stateType(state.raw(), typeEntry.stringKeyOrNull()), equal))) return zv::Val();
						if (!equal) break;
					}
					if (equal) {
						seen = true;
						break;
					}
				}
				if (seen) continue;
				states.push(std::move(state));
			}
			if (zend_hash_num_elements(states.table()) < 2) continue;

			zv::Arr joinedTypes = zv::Arr::create(0);
			for (auto defaultEntry : zv::ArrRef(defaults)) {
				zend_string *name = defaultEntry.stringKeyOrNull();
				zv::Arr list = zv::Arr::create(0);
				for (auto stateEntry : zv::ArrRef(states.raw())) {
					list.push(zv::Ref(stateType(stateEntry.value().raw(), name)));
				}
				zv::Val joined = unionOfList(list.table());
				if (UNEXPECTED(joined.isUndef())) return zv::Val();
				joinedTypes.set(name, std::move(joined));
			}

			zv::Arr runConditionalExpressions = zv::Arr::create(0);
			for (auto targetEntry : zv::ArrRef(defaults)) {
				zend_string *targetName = targetEntry.stringKeyOrNull();
				zv::Arr targetTypes = zv::Arr::empty();
				for (auto stateEntry : zv::ArrRef(states.raw())) {
					zval *stateTargetType = stateType(stateEntry.value().raw(), targetName);
					bool seen = false;
					for (auto seenEntry : zv::ArrRef(targetTypes.raw())) {
						if (UNEXPECTED(!typeEquals(seenEntry.value().raw(), stateTargetType, seen))) return zv::Val();
						if (seen) break;
					}
					if (!seen) targetTypes.push(zv::Ref(stateTargetType));
				}
				zval *joinedTarget = zend_hash_find(joinedTypes.table(), targetName);
				zval *enteredTarget = zend_hash_find(Z_ARRVAL_P(enteredTypes), targetName);
				if (UNEXPECTED(enteredTarget == NULL)) continue;
				for (auto targetTypeEntry : zv::ArrRef(targetTypes.raw())) {
					zv::Val targetType = zv::Val::copyOf(zv::Ref(targetTypeEntry.value().raw()));
					bool isJoined;
					if (UNEXPECTED(!typeEquals(targetType.raw(), joinedTarget, isJoined))) return zv::Val();
					if (isJoined) continue;
					zv::Arr otherStates = zv::Arr::empty();
					for (auto stateEntry : zv::ArrRef(states.raw())) {
						zend_long isSuperType;
						if (UNEXPECTED(!typeIsSuperTypeOf(targetType.raw(), stateType(stateEntry.value().raw(), targetName), isSuperType))) return zv::Val();
						if (isSuperType == PT_TRI_YES) continue;
						otherStates.push(zv::Ref(stateEntry.value().raw()));
					}
					// what the target holds in none of the other states - of
					// everything it can hold - covers the target type in a form
					// that does not change with every walk
					zv::Val remainingTargetType = unionOf(zend_hash_index_find(Z_ARRVAL_P(enteredTarget), 0), joinedTarget);
					if (UNEXPECTED(remainingTargetType.isUndef())) return zv::Val();
					for (auto otherEntry : zv::ArrRef(otherStates.raw())) {
						zv::Val removed = pt_type_combinator_remove(remainingTargetType.raw(), stateType(otherEntry.value().raw(), targetName));
						if (UNEXPECTED(removed.isUndef())) return zv::Val();
						remainingTargetType = std::move(removed);
					}
					zend_long coversTarget;
					if (UNEXPECTED(!typeIsSuperTypeOf(remainingTargetType.raw(), targetType.raw(), coversTarget))) return zv::Val();
					if (coversTarget == PT_TRI_YES) targetType = std::move(remainingTargetType);

					for (auto guardEntry : zv::ArrRef(defaults)) {
						zend_string *guardName = guardEntry.stringKeyOrNull();
						if (zend_string_equals(guardName, targetName)) continue;
						// what the guard can hold in no state the target type
						// leaves out - of everything it can hold, the walked
						// type included, so a guard narrowed from that still
						// matches
						zval *enteredGuard = zend_hash_find(Z_ARRVAL_P(enteredTypes), guardName);
						if (UNEXPECTED(enteredGuard == NULL)) continue;
						zv::Val guardType = unionOf(zend_hash_index_find(Z_ARRVAL_P(enteredGuard), 0), zend_hash_find(joinedTypes.table(), guardName));
						if (UNEXPECTED(guardType.isUndef())) return zv::Val();
						for (auto otherEntry : zv::ArrRef(otherStates.raw())) {
							zv::Val removed = pt_type_combinator_remove(guardType.raw(), stateType(otherEntry.value().raw(), guardName));
							if (UNEXPECTED(removed.isUndef())) return zv::Val();
							guardType = std::move(removed);
						}
						zend_long isNever;
						if (UNEXPECTED(!typeIsSuperTypeOf(never.raw(), guardType.raw(), isNever))) return zv::Val();
						if (isNever == PT_TRI_YES) continue;
						bool disjoint = true;
						for (auto otherEntry : zv::ArrRef(otherStates.raw())) {
							// remove() keeps what it cannot subtract
							zend_long overlaps;
							if (UNEXPECTED(!typeIsSuperTypeOf(guardType.raw(), stateType(otherEntry.value().raw(), guardName), overlaps))) return zv::Val();
							if (overlaps != PT_TRI_NO) {
								disjoint = false;
								break;
							}
						}
						if (!disjoint) continue;
						zv::Val holder = newStaticVariableConditionalExpressionHolder(guardName, guardType.raw(), targetName, targetType.raw());
						if (UNEXPECTED(holder.isUndef())) return zv::Val();
						zend_string *targetKey = zend_string_concat2(PT_LC("$"), ZSTR_VAL(targetName), ZSTR_LEN(targetName));
						runConditionalExpressions.separate();
						zval *list = zend_hash_find(runConditionalExpressions.table(), targetKey);
						if (list == NULL) {
							zval empty;
							array_init(&empty);
							list = zend_hash_add_new(runConditionalExpressions.table(), targetKey, &empty);
						}
						zend_string_release(targetKey);
						SEPARATE_ARRAY(list);
						zval holderZv = holder.take();
						zend_hash_next_index_insert(Z_ARRVAL_P(list), &holderZv);
					}
				}
			}
			if (zend_hash_num_elements(runConditionalExpressions.table()) == 0) continue;
			zv::Arr entry = zv::Arr::create(2);
			entry.push(zv::Ref(stmt));
			entry.push(std::move(runConditionalExpressions));
			conditionalExpressions.separate();
			zval entryZv = zv::Val(std::move(entry)).take();
			zend_hash_index_update(conditionalExpressions.table(), Z_OBJ_HANDLE_P(stmt), &entryZv);
		}
		return zv::Val(std::move(conditionalExpressions));
	}

	/* TypeCombinator::union(...$list); UNDEF = pending exception */
	static zv::Val unionOfList(HashTable *list)
	{
		uint32_t n = zend_hash_num_elements(list);
		zval *argv = (zval *) safe_emalloc(n, sizeof(zval), 0);
		uint32_t k = 0;
		for (auto entry : zv::TableRef(list)) {
			ZVAL_COPY_VALUE(&argv[k++], entry.value().raw());
		}
		zv::Val result = pt_type_combinator_union(n, argv);
		efree(argv);
		return result;
	}

	/* the private joinStaticVariableTypes(); UNDEF = pending exception */
	static zv::Val joinStaticVariableTypes(zval *bodyScope, zval *names, zval *types, zval *walkTypes, bool generalize)
	{
		zv::Arr joined = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(names)) {
			zend_string *name = Z_STR_P(entry.value().raw());
			HashTable *known = Z_ARRVAL_P(zend_hash_find(Z_ARRVAL_P(types), name));
			HashTable *walked = Z_ARRVAL_P(zend_hash_find(Z_ARRVAL_P(walkTypes), name));
			zv::Arr pair = zv::Arr::create(2);
			for (zend_ulong k = 0; k < 2; k++) {
				zval argv[2];
				ZVAL_COPY_VALUE(&argv[0], zend_hash_index_find(known, k));
				ZVAL_COPY_VALUE(&argv[1], zend_hash_index_find(walked, k));
				zv::Val unioned = pt_type_combinator_union(2, argv);
				if (UNEXPECTED(unioned.isUndef())) return zv::Val();
				pair.push(std::move(unioned));
			}
			joined.set(name, zv::Val(std::move(pair)));
		}
		if (!generalize) return zv::Val(std::move(joined));

		zv::Val previousScope = zv::Val::copyOf(zv::Ref(bodyScope));
		zv::Val joinedScope = zv::Val::copyOf(zv::Ref(bodyScope));
		zv::Arr writableNames = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(names)) {
			zend_string *name = Z_STR_P(entry.value().raw());
			HashTable *known = Z_ARRVAL_P(zend_hash_find(Z_ARRVAL_P(types), name));
			HashTable *joinedPair = Z_ARRVAL_P(zend_hash_find(joined.table(), name));
			zv::Val assignedPrevious = pt_mutating_scope_assign_variable(Z_OBJ_P(previousScope.raw()), name, zend_hash_index_find(known, 0), zend_hash_index_find(known, 1), pt_trinary_singleton(PT_TRI_YES));
			if (UNEXPECTED(assignedPrevious.isUndef())) return zv::Val();
			previousScope = std::move(assignedPrevious);
			zv::Val assignedJoined = pt_mutating_scope_assign_variable(Z_OBJ_P(joinedScope.raw()), name, zend_hash_index_find(joinedPair, 0), zend_hash_index_find(joinedPair, 1), pt_trinary_singleton(PT_TRI_YES));
			if (UNEXPECTED(assignedJoined.isUndef())) return zv::Val();
			joinedScope = std::move(assignedJoined);
			writableNames.set(name, zv::Val::boolean(true));
		}
		zv::Val generalizedScope = pt_mutating_scope_generalize_with_names(Z_OBJ_P(previousScope.raw()), Z_OBJ_P(joinedScope.raw()), writableNames.raw());
		if (UNEXPECTED(generalizedScope.isUndef())) return zv::Val();
		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(generalizedScope.raw()));
		if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
		zv::Arr generalized = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(names)) {
			zend_string *name = Z_STR_P(entry.value().raw());
			zv::Val type = pt_mutating_scope_get_variable_type(Z_OBJ_P(generalizedScope.raw()), name);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val nativeType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), name);
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			zv::Arr pair = zv::Arr::create(2);
			pair.push(std::move(type));
			pair.push(std::move(nativeType));
			generalized.set(name, zv::Val(std::move(pair)));
		}
		return zv::Val(std::move(generalized));
	}

	/* the private observeClosureSignatures() */
	zv::Val observeClosureSignatures(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *frame, zval *entries, zval *storageArg, zval *context, zval *statementStartTokenPositions)
	{
		zv::Val firstSiteStatementIndex = frameFirstSiteStatementIndex(frame);
		if (UNEXPECTED(firstSiteStatementIndex.isUndef())) return zv::Val();
		zend_long start = firstSiteStatementIndex.isNull() ? 0 : zval_get_long(firstSiteStatementIndex.raw());
		zval *startEntry = entryAt(entries, start);
		if (UNEXPECTED(startEntry == NULL)) return zv::Val();
		zv::Val state = cloneObject(entryState(startEntry));
		if (UNEXPECTED(state.isUndef())) return zv::Val();
		{
			zval *clonedScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
			zv::Val reframed = pt_mutating_scope_with_template_argument_frame(Z_OBJ_P(clonedScope), frame);
			if (UNEXPECTED(reframed.isUndef())) return zv::Val();
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)).assign(std::move(reframed));
		}
		// the second pass replays the observation pass's recording against the storage
		zv::Val storage = pt_expression_result_storage_duplicate(storageArg);
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		zv::Val nodeCallback = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
		if (UNEXPECTED(nodeCallback.isUndef())) return zv::Val();
		HashTable *stmtsTable = Z_ARRVAL_P(stmts);
		zend_long stmtCount = zend_hash_num_elements(stmtsTable);
		bool hasLabels;
		if (UNEXPECTED(!containsLabels(stmts, hasLabels))) return zv::Val();
		bool statsEnabled;
		if (UNEXPECTED(!templateArgumentStatsEnabled(statsEnabled))) return zv::Val();
		zv::Val suspendedGatherers = pt_node_scope_resolver_suspend_node_gatherers(nodeScopeResolver);
		if (UNEXPECTED(suspendedGatherers.isUndef())) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope)));
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) {
			(void) pt_node_scope_resolver_restore_node_gatherers(nodeScopeResolver, suspendedGatherers.raw());
			return zv::Val();
		}
		observeClosureSignaturesFrom(nodeScopeResolver, parentNode, stmts, stmtsTable, frame, entries, state.raw(), storage.raw(), nodeCallback.raw(), context, start, stmtCount, hasLabels, statsEnabled);
		pt_finally([&]() {
			(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw()));
			(void) pt_node_scope_resolver_restore_node_gatherers(nodeScopeResolver, suspendedGatherers.raw());
		});
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zval *stateScope = OBJ_PROP_NUM(Z_OBJ_P(state.raw()), stateSlots::scope);
		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(stateScope));
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		if (constraints.isNull()) {
			constraints = templateArgumentConstraintsCreateEmpty();
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		return pt_template_argument_resolver_resolve_observed_closures(OBJ_PROP_NUM(self, slots::templateArgumentResolver), constraints.raw(), frame, statementStartTokenPositions);
	}

	/* observeClosureSignatures()'s try block: the statements from $start on,
	 * walked or carried over; false = pending exception */
	bool observeClosureSignaturesFrom(zval *nodeScopeResolver, zval *parentNode, zval *stmts, HashTable *stmtsTable, zval *frame, zval *entries, zval *state, zval *storage, zval *nodeCallback, zval *context, zend_long start, zend_long stmtCount, bool hasLabels, bool statsEnabled)
	{
		for (zend_long i = start; i < stmtCount; i++) {
			zval *recordedEntryPair = entryAt(entries, i);
			if (UNEXPECTED(recordedEntryPair == NULL)) return false;
			zval *recordedEntry = entryState(recordedEntryPair);
			zend_object *stateObject = Z_OBJ_P(state);
			zend_object *recordedEntryObject = Z_OBJ_P(recordedEntry);
			zv::Val differingRoots = zv::Val::null();
			bool stateTerminated = Z_TYPE_P(OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			bool entryTerminated = Z_TYPE_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::alreadyTerminated)) == IS_TRUE;
			if (stateTerminated == entryTerminated) {
				differingRoots = pt_mutating_scope_get_differing_variable_roots(Z_OBJ_P(OBJ_PROP_NUM(stateObject, stateSlots::scope)), Z_OBJ_P(OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope)));
				if (UNEXPECTED(differingRoots.isUndef())) return false;
			}
			if (differingRoots.ref().isArray() && zend_hash_num_elements(Z_ARRVAL_P(differingRoots.raw())) == 0) {
				bool hasSite;
				if (UNEXPECTED(!frameHasSiteAtOrAfter(frame, i, hasSite))) return false;
				if (!hasSite) {
					// converged with the observation pass: the rest of its facts stand
					if (statsEnabled && UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("closureObservationStatementsReplayed"), stmtCount - i))) return false;
					zval *finalEntryPair = entryAt(entries, stmtCount);
					if (UNEXPECTED(finalEntryPair == NULL)) return false;
					zval *finalState = entryState(finalEntryPair);
					zv::Val carried = withRecordedConstraints(OBJ_PROP_NUM(stateObject, stateSlots::scope), OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope), OBJ_PROP_NUM(Z_OBJ_P(finalState), stateSlots::scope));
					if (UNEXPECTED(carried.isUndef())) return false;
					zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(carried));
					return true;
				}
			}

			zval *stmt = zend_hash_index_find(stmtsTable, (zend_ulong) i);
			if (UNEXPECTED(stmt == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: the function-like body's statements are not a list");
				return false;
			}
			ZVAL_DEREF(stmt);
			bool reWalk = differingRoots.isNull() || hasLabels;
			if (!reWalk && UNEXPECTED(!frameOwnsSiteInStatement(frame, i, reWalk))) return false;
			if (!reWalk && UNEXPECTED(!statementMentionsAnyVariable(stmt, differingRoots.raw(), reWalk))) return false;
			if (reWalk) {
				if (statsEnabled && UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("closureObservationStatements"), 1))) return false;
				if (UNEXPECTED(!processStatementStep(nodeScopeResolver, parentNode, stmts, i, stmt, state, storage, nodeCallback, context, true))) return false;
				continue;
			}

			if (statsEnabled && UNEXPECTED(!templateArgumentStatsIncrement(PT_LC("closureObservationStatementsReplayed"), 1))) return false;
			zval *recordedExitPair = entryAt(entries, i + 1);
			if (UNEXPECTED(recordedExitPair == NULL)) return false;
			zval *recordedExit = entryState(recordedExitPair);
			if (UNEXPECTED(!appendRecordedStatementResults(state, recordedEntry, recordedExit))) return false;
			zval *currentScope = OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope);
			zval *recordedEntryScope = OBJ_PROP_NUM(recordedEntryObject, stateSlots::scope);
			zval *recordedExitScope = OBJ_PROP_NUM(Z_OBJ_P(recordedExit), stateSlots::scope);
			zv::Val delta = pt_mutating_scope_with_recorded_statement_delta(Z_OBJ_P(currentScope), Z_OBJ_P(recordedEntryScope), Z_OBJ_P(recordedExitScope));
			if (UNEXPECTED(delta.isUndef())) return false;
			zv::Val carried = withRecordedConstraints(delta.raw(), recordedEntryScope, recordedExitScope);
			if (UNEXPECTED(carried.isUndef())) return false;
			zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(state), stateSlots::scope)).assign(std::move(carried));
		}
		return true;
	}

	/* the private withRecordedConstraints(); UNDEF = pending exception */
	static zv::Val withRecordedConstraints(zval *scope, zval *recordedEntry, zval *recordedExit)
	{
		zv::Val recorded = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(recordedExit));
		if (UNEXPECTED(recorded.isUndef())) return zv::Val();
		if (recorded.isNull()) return zv::Val::copyOf(zv::Ref(scope));
		zv::Val current = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(scope));
		if (UNEXPECTED(current.isUndef())) return zv::Val();
		if (current.isNull()) {
			current = templateArgumentConstraintsCreateEmpty();
			if (UNEXPECTED(current.isUndef())) return zv::Val();
		}
		zv::Val entryConstraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(recordedEntry));
		if (UNEXPECTED(entryConstraints.isUndef())) return zv::Val();
		zv::Val carried = pt_template_argument_constraints_with_recorded_facts(current.raw(), entryConstraints.raw(), recorded.raw());
		if (UNEXPECTED(carried.isUndef())) return zv::Val();
		return pt_mutating_scope_with_template_argument_constraints(Z_OBJ_P(scope), carried.raw());
	}

	/* the private containsLabels(); false = pending exception */
	[[nodiscard]] static bool containsLabels(zval *stmts, bool &out)
	{
		for (auto entry : zv::ArrRef(stmts)) {
			zval *stmt = entry.value().deref().raw();
			bool isLabel;
			if (UNEXPECTED(!isInstance(stmt, PT_CLASS_LABEL_STMT, isLabel))) return false;
			if (!isLabel) {
				zv::Val nested = pt_engine_node_get_attribute(Z_OBJ_P(stmt), PT_LC("nestedBackwardGotoLabels"));
				if (UNEXPECTED(nested.isUndef())) return false;
				if (nested.isNull()) continue;
			}
			out = true;
			return true;
		}
		out = false;
		return true;
	}

	/* [clone $state, $recording->count()] */
	static zv::Val snapshotEntry(zval *state, zval *recording)
	{
		zv::Val cloned = cloneObject(state);
		if (UNEXPECTED(cloned.isUndef())) return zv::Val();
		zend_long count;
		if (UNEXPECTED(!recordingNodeCallbackCount(recording, count))) return zv::Val();
		zv::Arr pair = zv::Arr::create(2);
		pair.push(std::move(cloned));
		pair.push(zv::Val::integer(count));
		return zv::Val(std::move(pair));
	}

	/* clone $object */
	static zv::Val cloneObject(zval *object)
	{
		zend_object *source = Z_OBJ_P(object);
		if (UNEXPECTED(source->handlers->clone_obj == NULL)) {
			zend_throw_error(NULL, "Trying to clone an uncloneable object of class %s", ZSTR_VAL(source->ce->name));
			return zv::Val();
		}
		zend_object *cloned = source->handlers->clone_obj(source);
		if (UNEXPECTED(EG(exception))) {
			if (cloned != NULL) OBJ_RELEASE(cloned);
			return zv::Val();
		}
		zval result;
		ZVAL_OBJ(&result, cloned);
		return zv::Val::adopt(result);
	}

	/* $entries[$i]; NULL = the internal error for a non-list body */
	static zval *entryAt(zval *entries, zend_long i)
	{
		zval *pair = zend_hash_index_find(Z_ARRVAL_P(entries), (zend_ulong) i);
		if (UNEXPECTED(pair == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: the function-like body's statements are not a list");
		}
		return pair;
	}

	static zval *entryState(zval *pair) { return zend_hash_index_find(Z_ARRVAL_P(pair), 0); }
	static zval *entryOffset(zval *pair) { return zend_hash_index_find(Z_ARRVAL_P(pair), 1); }

	/* $state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)
	 * ->withTemplateArgumentConstraints($parentConstraints) */
	[[nodiscard]] static bool restoreParentFrame(zval *state, zval *parentFrame, zval *parentConstraints)
	{
		zend_object *stateObject = Z_OBJ_P(state);
		zv::Val framed = pt_mutating_scope_with_template_argument_frame(Z_OBJ_P(OBJ_PROP_NUM(stateObject, stateSlots::scope)), parentFrame);
		if (UNEXPECTED(framed.isUndef())) return false;
		zv::Val constrained = pt_mutating_scope_with_template_argument_constraints(Z_OBJ_P(framed.raw()), parentConstraints);
		if (UNEXPECTED(constrained.isUndef())) return false;
		zv::Ref(OBJ_PROP_NUM(stateObject, stateSlots::scope)).assign(std::move(constrained));
		return true;
	}

	/* the private appendRecordedStatementResults() */
	[[nodiscard]] static bool appendRecordedStatementResults(zval *state, zval *from, zval *to)
	{
		zend_object *stateObject = Z_OBJ_P(state);
		zend_object *fromObject = Z_OBJ_P(from);
		zend_object *toObject = Z_OBJ_P(to);
		zval *toFlows = OBJ_PROP_NUM(toObject, stateSlots::variableFlows);
		zval *fromFlows = OBJ_PROP_NUM(fromObject, stateSlots::variableFlows);
		zval *stateFlows = OBJ_PROP_NUM(stateObject, stateSlots::variableFlows);
		zv::Val toFlowsHeld = zv::Val::copyOf(zv::Ref(toFlows));
		for (auto entry : zv::ArrRef(toFlowsHeld.raw())) {
			bool exists = entry.hasStringKey()
				? zend_hash_exists(Z_ARRVAL_P(fromFlows), entry.stringKey())
				: zend_hash_index_exists(Z_ARRVAL_P(fromFlows), entry.indexKey());
			if (exists) continue;
			SEPARATE_ARRAY(stateFlows);
			Z_TRY_ADDREF_P(entry.value().raw());
			if (entry.hasStringKey()) {
				zend_hash_update(Z_ARRVAL_P(stateFlows), entry.stringKey(), entry.value().raw());
			} else {
				zend_hash_index_update(Z_ARRVAL_P(stateFlows), entry.indexKey(), entry.value().raw());
			}
		}
		zval *stateHasYield = OBJ_PROP_NUM(stateObject, stateSlots::hasYield);
		ZVAL_BOOL(stateHasYield, Z_TYPE_P(stateHasYield) == IS_TRUE || (Z_TYPE_P(OBJ_PROP_NUM(toObject, stateSlots::hasYield)) == IS_TRUE && Z_TYPE_P(OBJ_PROP_NUM(fromObject, stateSlots::hasYield)) != IS_TRUE));
		zval *stateTerminated = OBJ_PROP_NUM(stateObject, stateSlots::alreadyTerminated);
		ZVAL_BOOL(stateTerminated, Z_TYPE_P(stateTerminated) == IS_TRUE || (Z_TYPE_P(OBJ_PROP_NUM(toObject, stateSlots::alreadyTerminated)) == IS_TRUE && Z_TYPE_P(OBJ_PROP_NUM(fromObject, stateSlots::alreadyTerminated)) != IS_TRUE));
		for (uint32_t pointsSlot : { stateSlots::exitPoints, stateSlots::throwPoints, stateSlots::impurePoints }) {
			zval *fromPoints = OBJ_PROP_NUM(fromObject, pointsSlot);
			zval *toPoints = OBJ_PROP_NUM(toObject, pointsSlot);
			zv::Val slice = arraySlice(toPoints, (zend_long) zend_hash_num_elements(Z_ARRVAL_P(fromPoints)));
			arrayMergeInto(OBJ_PROP_NUM(stateObject, pointsSlot), slice.raw());
		}
		return true;
	}

	/* the PHPSTAN_TEMPLATE_ARGUMENTS_DEBUG=1 line */
	[[nodiscard]] static bool printDebugDecision(zval *scope, zval *stmt, zend_long i, bool reWalk, zval *differingRoots)
	{
		zv::Val file = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(file.isUndef())) return false;
		zv::Val line = nodeGetStartLine(stmt);
		if (UNEXPECTED(line.isUndef())) return false;
		smart_str differing = {};
		if (Z_TYPE_P(differingRoots) != IS_ARRAY) {
			smart_str_appends(&differing, "non-variable key");
		} else {
			bool first = true;
			for (auto entry : zv::ArrRef(differingRoots)) {
				if (!first) smart_str_appends(&differing, ", ");
				first = false;
				zend_string *root = zval_get_string(entry.value().deref().raw());
				smart_str_append(&differing, root);
				zend_string_release(root);
			}
		}
		smart_str_0(&differing);
		zend_string *fileString = zval_get_string(file.raw());
		zend_printf("[template-arguments] %s:" ZEND_LONG_FMT " statement " ZEND_LONG_FMT ": %s (differing: %s)\n", ZSTR_VAL(fileString), zval_get_long(line.raw()), i, reWalk ? "re-walk" : "replay", differing.s != NULL ? ZSTR_VAL(differing.s) : "");
		zend_string_release(fileString);
		smart_str_free(&differing);
		return EG(exception) == NULL;
	}

	/* }}} */

	/* {{{ mentioned variables */

	/* the private statementMentionsAnyVariable() */
	[[nodiscard]] bool statementMentionsAnyVariable(zval *stmt, zval *variableNames, bool &out)
	{
		zv::Val mentions = pt_engine_node_get_attribute(Z_OBJ_P(stmt), PT_LC("templateArgumentMentionedVariables"));
		if (UNEXPECTED(mentions.isUndef())) return false;
		if (mentions.isNull()) {
			zv::Arr names = zv::Arr::empty();
			bool mentionsEverything = false;
			if (UNEXPECTED(!collectMentionedVariables(stmt, names, mentionsEverything))) return false;
			zv::Arr pair = zv::Arr::create(2);
			pair.push(std::move(names));
			pair.push(zv::Val::boolean(mentionsEverything));
			mentions = zv::Val(std::move(pair));
			if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(stmt), PT_LC("templateArgumentMentionedVariables"), mentions.raw()))) return false;
		}
		zval *names = mentions.ref().isArray() ? zend_hash_index_find(Z_ARRVAL_P(mentions.raw()), 0) : NULL;
		zval *mentionsEverything = mentions.ref().isArray() ? zend_hash_index_find(Z_ARRVAL_P(mentions.raw()), 1) : NULL;
		if (mentionsEverything != NULL && zend_is_true(mentionsEverything)) {
			out = true;
			return true;
		}
		out = false;
		if (Z_TYPE_P(variableNames) != IS_ARRAY || names == NULL) return true;
		ZVAL_DEREF(names);
		if (Z_TYPE_P(names) != IS_ARRAY) return true;
		for (auto entry : zv::ArrRef(variableNames)) {
			zval *variableName = entry.value().deref().raw();
			zval *found = NULL;
			if (Z_TYPE_P(variableName) == IS_STRING) {
				found = zend_symtable_find(Z_ARRVAL_P(names), Z_STR_P(variableName));
			} else if (Z_TYPE_P(variableName) == IS_LONG) {
				found = zend_hash_index_find(Z_ARRVAL_P(names), (zend_ulong) Z_LVAL_P(variableName));
			}
			if (found != NULL && Z_TYPE_P(found) != IS_NULL) {
				out = true;
				return true;
			}
		}
		return true;
	}

	/* the private collectMentionedVariables() */
	[[nodiscard]] bool collectMentionedVariables(zval *node, zv::Arr &names, bool &mentionsEverything)
	{
		bool ok = true;
		pt_engine_with_stack([&]() { ok = collectMentionedVariablesBody(node, names, mentionsEverything); });
		return ok;
	}

	[[nodiscard]] bool collectMentionedVariablesBody(zval *node, zv::Arr &names, bool &mentionsEverything)
	{
		bool is;
		if (UNEXPECTED(!isInstance(node, PT_CLASS_VARIABLE, is))) return false;
		if (is) {
			zval *name = nodeProperty(pt_sh_variable_name_site, node, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_STRING) {
				mentionsEverything = true;
				return true;
			}
			names.set(Z_STR_P(name), zv::Val::boolean(true));
			return true;
		}
		if (UNEXPECTED(!isInstance(node, PT_CLASS_CLOSURE_EXPR, is))) return false;
		if (is) {
			zval *isStatic = nodeProperty(pt_sh_static_site, node, PT_LC("static"));
			if (UNEXPECTED(isStatic == NULL)) return false;
			if (!zend_is_true(isStatic)) {
				names.set("this", zv::Val::boolean(true));
			}
			zval *uses = nodeProperty(pt_sh_uses_site, node, PT_LC("uses"));
			if (UNEXPECTED(uses == NULL)) return false;
			if (Z_TYPE_P(uses) != IS_ARRAY) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(uses));
				return EG(exception) == NULL;
			}
			zv::Val usesHeld = zv::Val::copyOf(zv::Ref(uses));
			for (auto entry : zv::ArrRef(usesHeld.raw())) {
				zval *var = nodeProperty(pt_sh_use_var_site, entry.value().raw(), PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return false;
				zval *name = nodeProperty(pt_sh_variable_name_site, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return false;
				if (Z_TYPE_P(name) != IS_STRING) {
					mentionsEverything = true;
					continue;
				}
				names.set(Z_STR_P(name), zv::Val::boolean(true));
			}
			return true;
		}

		bool dynamic;
		if (UNEXPECTED(!isInstance(node, PT_CLASS_EVAL_EXPR, dynamic))) return false;
		if (!dynamic && UNEXPECTED(!isInstance(node, PT_CLASS_INCLUDE_EXPR, dynamic))) return false;
		if (dynamic) {
			mentionsEverything = true;
		} else {
			if (UNEXPECTED(!isInstance(node, PT_CLASS_FUNC_CALL, is))) return false;
			if (is) {
				zval *name = nodeProperty(pt_sh_func_call_name_site, node, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return false;
				bool isName;
				if (UNEXPECTED(!isInstance(name, PT_CLASS_NAME, isName))) return false;
				if (isName) {
					zv::Val lower = nameToLowerString(name);
					if (UNEXPECTED(lower.isUndef())) return false;
					if (lower.ref().stringEquals("compact") || lower.ref().stringEquals("extract") || lower.ref().stringEquals("get_defined_vars")) {
						mentionsEverything = true;
					}
				}
			}
		}

		zv::Val subNodeNames = nodeGetSubNodeNames(node);
		if (UNEXPECTED(subNodeNames.isUndef())) return false;
		if (UNEXPECTED(!subNodeNames.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(subNodeNames.raw()));
			return EG(exception) == NULL;
		}
		zend_object *nodeObject = Z_OBJ_P(node);
		for (auto entry : zv::ArrRef(subNodeNames.raw())) {
			zend_string *subNodeName = zval_get_string(entry.value().deref().raw());
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *subNode = zend_read_property_ex(nodeObject->ce, nodeObject, subNodeName, 0, &rv);
			zend_string_release(subNodeName);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&rv);
				return false;
			}
			zv::Val held = zv::Val::copyOf(zv::Ref(subNode).deref());
			zval_ptr_dtor(&rv);
			bool isNode;
			if (UNEXPECTED(!isInstance(held.raw(), PT_CLASS_NODE, isNode))) return false;
			if (isNode) {
				if (UNEXPECTED(!collectMentionedVariables(held.raw(), names, mentionsEverything))) return false;
			} else if (held.ref().isArray()) {
				for (auto item : zv::ArrRef(held.raw())) {
					zval *itemValue = item.value().deref().raw();
					bool itemIsNode;
					if (UNEXPECTED(!isInstance(itemValue, PT_CLASS_NODE, itemIsNode))) return false;
					if (!itemIsNode) continue;
					if (UNEXPECTED(!collectMentionedVariables(itemValue, names, mentionsEverything))) return false;
				}
			}
		}
		return true;
	}

	/* }}} */

	/* {{{ PHPDocs */

	/* $this->fileTypeMapper->getResolvedPhpDoc($scope->getFile(),
	 * $scope->isInClass() ? $scope->getClassReflection()->getName() : null,
	 * $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
	 * $function !== null ? $function->getName() : null, $comment->getText()) */
	zv::Val resolvedPhpDocOf(zval *scope, zval *function, zval *comment)
	{
		zend_object *scopeObject = Z_OBJ_P(scope);
		zv::Val file = pt_mutating_scope_get_file(scopeObject);
		if (UNEXPECTED(file.isUndef())) return zv::Val();
		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(scopeObject, inClass))) return zv::Val();
		zv::Val className = zv::Val::null();
		if (inClass) {
			zv::Val classReflection = pt_mutating_scope_get_class_reflection(scopeObject);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!classReflection.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
				return zv::Val();
			}
			className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
		}
		bool inTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(scopeObject, inTrait))) return zv::Val();
		zv::Val traitName = zv::Val::null();
		if (inTrait) {
			zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(scopeObject);
			if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!traitReflection.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(traitReflection.raw()));
				return zv::Val();
			}
			traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection.raw()));
			if (UNEXPECTED(traitName.isUndef())) return zv::Val();
		}
		zv::Val functionName = zv::Val::null();
		if (Z_TYPE_P(function) != IS_NULL) {
			functionName = functionGetName(function);
			if (UNEXPECTED(functionName.isUndef())) return zv::Val();
		}
		zv::Val text = commentGetText(comment);
		if (UNEXPECTED(text.isUndef())) return zv::Val();
		zv::Args argv{file.raw(), className.raw(), traitName.raw(), functionName.raw(), text.raw()};
		return fileTypeMapperGetResolvedPhpDoc(OBJ_PROP_NUM(self, slots::fileTypeMapper), argv);
	}

	/* $stmt instanceof Expression && ($stmt->expr instanceof Assign ||
	 * AssignRef) && $stmt->expr->var instanceof Variable &&
	 * is_string($stmt->expr->var->name) ? that name : null (borrowed) */
	[[nodiscard]] static bool assignedVariableOf(zval *stmt, zend_string *&out)
	{
		out = NULL;
		bool is;
		if (UNEXPECTED(!isInstance(stmt, PT_CLASS_EXPRESSION_STMT, is))) return false;
		if (!is) return true;
		zval *expr = nodeProperty(pt_sh_expr_site, stmt, PT_LC("expr"));
		if (UNEXPECTED(expr == NULL)) return false;
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_ASSIGN_EXPR, is))) return false;
		if (!is && UNEXPECTED(!isInstance(expr, PT_CLASS_ASSIGN_REF_EXPR, is))) return false;
		if (!is) return true;
		zval *var = nodeProperty(pt_sh_var_site, expr, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		if (UNEXPECTED(!isInstance(var, PT_CLASS_VARIABLE, is))) return false;
		if (!is) return true;
		zval *name = nodeProperty(pt_sh_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) == IS_STRING) {
			out = Z_STR_P(name);
		}
		return true;
	}

	/* the private findSingleVariableLessVarTag() */
	zv::Val findSingleVariableLessVarTag(zval *scope, zval *stmt)
	{
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		zv::Arr variableLessTags = zv::Arr::empty();
		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(stmt));
		if (UNEXPECTED(comments.isUndef())) return zv::Val();
		if (comments.ref().isArray()) {
			for (auto entry : zv::ArrRef(comments.raw())) {
				zval *comment = entry.value().deref().raw();
				bool isDoc;
				if (UNEXPECTED(!isInstance(comment, PT_CLASS_DOC_COMMENT, isDoc))) return zv::Val();
				if (!isDoc) continue;
				zv::Val resolvedPhpDoc = resolvedPhpDocOf(scope, function.raw(), comment);
				if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
				zv::Val varTags = resolvedPhpDocGetVarTags(resolvedPhpDoc.raw());
				if (UNEXPECTED(varTags.isUndef())) return zv::Val();
				if (!varTags.ref().isArray()) continue;
				for (auto tagEntry : zv::ArrRef(varTags.raw())) {
					if (tagEntry.hasStringKey()) continue;
					variableLessTags.push(tagEntry.value().deref());
				}
			}
		}
		if (zend_hash_num_elements(variableLessTags.table()) != 1) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(zend_hash_index_find(variableLessTags.table(), 0)));
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::StatementsHandler;

/* {{{ direct entries for native callers (support.h): the native body for
 * the native class (the twin is final), the method otherwise */

namespace {

inline bool isNativeHandler(zval *handler)
{
	return EXPECTED(Z_OBJCE_P(handler) == pt_ce_statements_handler);
}

} // namespace

bool pt_statements_handler_process_nodes_with_storage(zval *handler, zval *nodeScopeResolver, zval *nodes, zval *scope, zval *storage, zval *nodeCallback)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).processNodesWithStorage(nodeScopeResolver, nodes, scope, storage, nodeCallback);
	zv::Args argv{nodeScopeResolver, nodes, scope, storage, nodeCallback};
	return !pt_type_call(Z_OBJ_P(handler), PT_LC("processnodeswithstorage"), 5, argv).isUndef();
}

zv::Val pt_statements_handler_do_process_stmt_nodes(zval *handler, zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).doProcessStmtNodes(nodeScopeResolver, parentNode, stmts, scope, storage, nodeCallback, context);
	zv::Args argv{nodeScopeResolver, parentNode, stmts, scope, storage, nodeCallback, context};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("doprocessstmtnodes"), 7, argv);
}

zv::Val pt_statements_handler_process_stmt_var_annotation(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).processStmtVarAnnotation(nodeScopeResolver, scope, storage, stmt, defaultExpr, nodeCallback);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, scope, storage, stmt, defaultExpr != NULL ? defaultExpr : &null, nodeCallback};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("processstmtvarannotation"), 6, argv);
}

zv::Val pt_statements_handler_get_overriding_throw_points(zval *handler, zval *statement, zval *scope)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).getOverridingThrowPoints(statement, scope);
	zv::Args argv{statement, scope};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("getoverridingthrowpoints"), 2, argv);
}

zv::Val pt_statements_handler_emit_var_tag_changed_node(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).emitVarTagChangedNode(nodeScopeResolver, scope, storage, stmt, defaultExpr, nodeCallback);
	zv::Args argv{nodeScopeResolver, scope, storage, stmt, defaultExpr, nodeCallback};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("emitvartagchangednode"), 6, argv);
}

zv::Val pt_statements_handler_get_variable_mention_flow(zval *handler, zval *stmt)
{
	if (isNativeHandler(handler)) return StatementsHandler(Z_OBJ_P(handler)).getVariableMentionFlow(stmt);
	return pt_type_call(Z_OBJ_P(handler), PT_LC("getvariablementionflow"), 1, stmt);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_statements_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StatementsHandler");
	ptdecl::StatementsHandler::declareClass(cls);
	cls.privateClassConstantLong("STATIC_WALK_WALKED", PT_SH_STATIC_WALK_WALKED);
	cls.privateClassConstantLong("STATIC_WALK_CARRIED", PT_SH_STATIC_WALK_CARRIED);
	cls.privateClassConstantLong("STATIC_WALK_REST", PT_SH_STATIC_WALK_REST);
	cls.privateClassConstantString("MENTIONED_VARIABLES_ATTRIBUTE", "templateArgumentMentionedVariables");
	ptdecl::StatementsHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fileTypeMapper, *templateArgumentObserver, *templateArgumentResolver, *container, *staticVariableInference;
		bool unresolvedTemplateArguments;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(fileTypeMapper)
			Z_PARAM_OBJECT(templateArgumentObserver)
			Z_PARAM_OBJECT(templateArgumentResolver)
			Z_PARAM_OBJECT(container)
			Z_PARAM_OBJECT(staticVariableInference)
			Z_PARAM_BOOL(unresolvedTemplateArguments)
		ZEND_PARSE_PARAMETERS_END();
		StatementsHandler(Z_OBJ_P(ZEND_THIS)).construct(fileTypeMapper, templateArgumentObserver, templateArgumentResolver, container, staticVariableInference, unresolvedTemplateArguments);
	});

	cls.method(sigs::processNodesWithStorage, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *nodes, *scope, *storage, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_ARRAY(nodes)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!StatementsHandler(Z_OBJ_P(ZEND_THIS)).processNodesWithStorage(nodeScopeResolver, nodes, scope, storage, nodeCallback))) RETURN_THROWS();
	});

	cls.method(sigs::doProcessStmtNodes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *parentNode, *stmts, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(parentNode)
			Z_PARAM_ARRAY(stmts)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementsHandler(Z_OBJ_P(ZEND_THIS)).doProcessStmtNodes(nodeScopeResolver, parentNode, stmts, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::getVariableMentionFlow, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(stmt)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementsHandler(Z_OBJ_P(ZEND_THIS)).getVariableMentionFlow(stmt));
	});

	cls.method(sigs::getOverridingThrowPoints, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statement, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(statement)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementsHandler(Z_OBJ_P(ZEND_THIS)).getOverridingThrowPoints(statement, scope));
	});

	cls.method(sigs::processStmtVarAnnotation, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *storage, *stmt, *defaultExpr, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT_OR_NULL(defaultExpr)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementsHandler(Z_OBJ_P(ZEND_THIS)).processStmtVarAnnotation(nodeScopeResolver, scope, storage, stmt, defaultExpr, nodeCallback));
	});

	cls.method(sigs::emitVarTagChangedNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *storage, *stmt, *defaultExpr, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(defaultExpr)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementsHandler(Z_OBJ_P(ZEND_THIS)).emitVarTagChangedNode(nodeScopeResolver, scope, storage, stmt, defaultExpr, nodeCallback));
	});

	cls.shadow(&pt_ce_statements_handler);
}

/* }}} */
