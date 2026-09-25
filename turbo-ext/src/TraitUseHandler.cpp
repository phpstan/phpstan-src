/*
 * PHPStanTurbo\TraitUseHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\TraitUseHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The search for the used trait's node recurses over the parsed file
 * natively under pt_engine_with_stack(), stopping at class-likes and
 * function-likes like the twin. MutatingScope, ClassReflection, the
 * reflection provider, ExpressionResultStorage, AttributesHandler, the
 * statement results and NodeScopeResolver are called through their direct
 * entries; FileHelper, Parser, BetterReflection and the php-parser nodes
 * through the sites below.
 */

#include "support.h"
#include "generated/TraitUseHandler.h"

namespace slots = ptdecl::TraitUseHandler::slot;
namespace sigs = ptdecl::TraitUseHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_trait_use_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_tuh_normalize_path_site;
pt_method_site pt_tuh_parse_file_site;
pt_method_site pt_tuh_adaptation_trait_to_lower_string_site;
pt_method_site pt_tuh_trait_to_lower_string_site;
pt_method_site pt_tuh_adaptation_method_to_lower_string_site;
pt_method_site pt_tuh_stmt_name_to_lower_string_site;
pt_method_site pt_tuh_ast_name_to_lower_string_site;
pt_method_site pt_tuh_node_start_line_site;
pt_method_site pt_tuh_get_sub_node_names_site;

/* }}} */

pt_property_site pt_tuh_traits_site;
pt_property_site pt_tuh_adaptations_site;
pt_property_site pt_tuh_adaptation_trait_site;
pt_property_site pt_tuh_adaptation_method_site;
pt_property_site pt_tuh_alias_new_modifier_site;
pt_property_site pt_tuh_alias_new_name_site;
pt_property_site pt_tuh_namespaced_name_site;
pt_property_site pt_tuh_stmts_site;
pt_property_site pt_tuh_method_name_site;
pt_property_site pt_tuh_method_flags_site;
pt_property_site pt_tuh_attr_groups_site;

/* the php-parser node properties the twin writes (module startup) */
zend_string *pt_tuh_flags = nullptr;
zend_string *pt_tuh_name = nullptr;

/* php-parser's Modifiers::VISIBILITY_MASK */
constexpr zend_long PT_TUH_VISIBILITY_MASK = 1 | 2 | 4;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* $node->toLowerString() of a Name / Identifier */
zv::Val toLowerString(pt_method_site &, zval *node)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		memberCallOnNonObject("toLowerString", node);
		return zv::Val();
	}
	return pt_name_node_to_lower_string(node);
}

/* clone $node */
zv::Val cloneNode(zval *node)
{
	zend_object *object = Z_OBJ_P(node);
	if (UNEXPECTED(object->handlers->clone_obj == NULL)) {
		zend_throw_error(NULL, "Trying to clone an uncloneable object of class %s", ZSTR_VAL(object->ce->name));
		return zv::Val();
	}
	zend_object *clone = object->handlers->clone_obj(object);
	if (UNEXPECTED(EG(exception))) {
		if (clone != NULL) OBJ_RELEASE(clone);
		return zv::Val();
	}
	zval result;
	ZVAL_OBJ(&result, clone);
	return zv::Val::adopt(result);
}

/* $node->$name (the engine's read, warnings included); UNDEF = pending exception */
zv::Val readProperty(zval *node, zend_string *name)
{
	zval rv;
	zval *value = Z_OBJ_P(node)->handlers->read_property(Z_OBJ_P(node), name, BP_VAR_R, NULL, &rv);
	if (UNEXPECTED(EG(exception))) {
		if (value == &rv) zval_ptr_dtor(&rv);
		return zv::Val();
	}
	zv::Val result = zv::Val::copyOf(zv::Ref(value).deref());
	if (value == &rv) zval_ptr_dtor(&rv);
	return result;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\TraitUseHandler; UNDEF / false =
 * pending exception. */
class TraitUseHandler
{
public:
	explicit TraitUseHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflectionProvider, zval *fileHelper, zval *parser, zval *attributesHandler)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		object.propAtWrite(slots::fileHelper, zv::Val::copyOf(zv::Ref(fileHelper)));
		object.propAtWrite(slots::parser, zv::Val::copyOf(zv::Ref(parser)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_TRAIT_USE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) context;
		// fresh storage - the same trait node objects are processed once per
		// using class must not see results from a previous pass
		zv::Val traitStorage = pt_expression_result_storage_new();
		if (UNEXPECTED(traitStorage.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), traitStorage.raw()))) return zv::Val();
		bool processed = processTraitUse(nodeScopeResolver, stmt, scope, traitStorage.raw(), nodeCallback);
		pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
		if (UNEXPECTED(!processed || EG(exception) != NULL)) return zv::Val();

		// class-level node callbacks (like ClassMethodsNode) are invoked with
		// the outer storage but ask about expressions inside the used trait
		if (UNEXPECTED(!pt_expression_result_storage_merge_results(storage, traitStorage.raw()))) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return TraitUseHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* Mirrors processTraitUse(). */
	[[nodiscard]] bool processTraitUse(zval *nodeScopeResolver, zval *node, zval *classScope, zval *storage, zval *nodeCallback) const
	{
		zval *traits = ptsh::readNodeProperty(pt_tuh_traits_site, node, PT_LC("traits"));
		if (UNEXPECTED(traits == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(traits) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(traits));
			return !EG(exception);
		}
		zv::Val iterated = zv::Val::copyOf(zv::Ref(traits));
		for (auto entry : zv::ArrRef(iterated.raw())) {
			if (UNEXPECTED(!processTrait(nodeScopeResolver, node, entry.value().deref().raw(), classScope, storage, nodeCallback))) return false;
		}
		return true;
	}

	/* the traits loop body over one used trait name */
	[[nodiscard]] bool processTrait(zval *nodeScopeResolver, zval *node, zval *trait, zval *classScope, zval *storage, zval *nodeCallback) const
	{
		zend_string *traitNameString = zval_try_get_string(trait);
		if (UNEXPECTED(traitNameString == NULL)) return false;
		zv::Val traitName = zv::Val::adoptString(traitNameString);
		// traits can use each other in a cycle (even use themselves) which is a runtime
		// fatal error in PHP, but must not send the analyser into an endless recursion
		zv::Str lowercasedTraitName = zv::Str::adopt(zend_string_tolower(traitNameString));
		{
			zval *currentlyProcessedTraits = OBJ_PROP_NUM(self, slots::currentlyProcessedTraits);
			ZVAL_DEREF(currentlyProcessedTraits);
			if (Z_TYPE_P(currentlyProcessedTraits) == IS_ARRAY && zend_symtable_exists(Z_ARRVAL_P(currentlyProcessedTraits), lowercasedTraitName.get())) return true;
		}
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		{
			bool hasClass;
			if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), traitName.raw(), hasClass))) return false;
			if (!hasClass) return true;
		}
		zv::Val traitReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), traitName.raw());
		if (UNEXPECTED(traitReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getFileName", traitReflection.raw());
			return false;
		}
		zv::Val traitFileName = pt_class_reflection_get_file_name(Z_OBJ_P(traitReflection.raw()));
		if (UNEXPECTED(traitFileName.isUndef())) return false;
		if (traitFileName.isNull()) return true; // trait from eval or from PHP itself
		zv::Val fileName = pt_call_method_cached(pt_tuh_normalize_path_site, Z_OBJ_P(OBJ_PROP_NUM(self, slots::fileHelper)), PT_LC("normalizepath"), 1, traitFileName.raw());
		if (UNEXPECTED(fileName.isUndef())) return false;
		{
			bool isAnalysed;
			if (UNEXPECTED(!pt_node_scope_resolver_is_analysed_file(nodeScopeResolver, fileName.raw(), isAnalysed))) return false;
			if (!isAnalysed) return true;
		}

		zv::Arr adaptations = zv::Arr::empty();
		zval *nodeAdaptations = ptsh::readNodeProperty(pt_tuh_adaptations_site, node, PT_LC("adaptations"));
		if (UNEXPECTED(nodeAdaptations == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(nodeAdaptations) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(nodeAdaptations));
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			zv::Val adaptationsHold = zv::Val::copyOf(zv::Ref(nodeAdaptations));
			for (auto entry : zv::ArrRef(adaptationsHold.raw())) {
				zval *adaptation = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(adaptation) != IS_OBJECT)) {
					zend_error(E_WARNING, "Attempt to read property \"trait\" on %s", zend_zval_value_name(adaptation));
					if (UNEXPECTED(EG(exception))) return false;
					adaptations.push(zv::Ref(adaptation));
					continue;
				}
				zval *adaptationTrait = ptsh::readNodeProperty(pt_tuh_adaptation_trait_site, adaptation, PT_LC("trait"));
				if (UNEXPECTED(adaptationTrait == NULL)) return false;
				if (Z_TYPE_P(adaptationTrait) == IS_NULL) {
					adaptations.push(zv::Ref(adaptation));
					continue;
				}
				zv::Val adaptationTraitHold = zv::Val::copyOf(zv::Ref(adaptationTrait));
				zv::Val adaptationTraitName = toLowerString(pt_tuh_adaptation_trait_to_lower_string_site, adaptationTraitHold.raw());
				if (UNEXPECTED(adaptationTraitName.isUndef())) return false;
				zv::Val usedTraitName = toLowerString(pt_tuh_trait_to_lower_string_site, trait);
				if (UNEXPECTED(usedTraitName.isUndef())) return false;
				if (!zend_is_identical(adaptationTraitName.raw(), usedTraitName.raw())) continue;
				adaptations.push(zv::Ref(adaptation));
			}
		}

		zv::Val parserNodes = pt_call_method_cached(pt_tuh_parse_file_site, Z_OBJ_P(OBJ_PROP_NUM(self, slots::parser)), PT_LC("parsefile"), 1, fileName.raw());
		if (UNEXPECTED(parserNodes.isUndef())) return false;

		/* $this->currentlyProcessedTraits[strtolower($traitName)] = true;
		 * try { ... } finally { unset(...); } */
		{
			zval *currentlyProcessedTraits = OBJ_PROP_NUM(self, slots::currentlyProcessedTraits);
			ZVAL_DEREF(currentlyProcessedTraits);
			if (EXPECTED(Z_TYPE_P(currentlyProcessedTraits) == IS_ARRAY)) {
				SEPARATE_ARRAY(currentlyProcessedTraits);
				zval processing;
				ZVAL_TRUE(&processing);
				zend_symtable_update(Z_ARRVAL_P(currentlyProcessedTraits), lowercasedTraitName.get(), &processing);
			}
		}
		bool processed = processNodesForTraitUse(nodeScopeResolver, parserNodes.raw(), traitReflection.raw(), classScope, storage, adaptations.raw(), nodeCallback);
		{
			zval *currentlyProcessedTraits = OBJ_PROP_NUM(self, slots::currentlyProcessedTraits);
			ZVAL_DEREF(currentlyProcessedTraits);
			if (EXPECTED(Z_TYPE_P(currentlyProcessedTraits) == IS_ARRAY)) {
				SEPARATE_ARRAY(currentlyProcessedTraits);
				zend_symtable_del(Z_ARRVAL_P(currentlyProcessedTraits), lowercasedTraitName.get());
			}
		}
		return processed;
	}

	/* Mirrors processNodesForTraitUse(). */
	[[nodiscard]] bool processNodesForTraitUse(zval *nodeScopeResolver, zval *node, zval *traitReflection, zval *scope, zval *storage, zval *adaptations, zval *nodeCallback) const
	{
		bool result = false;
		pt_engine_with_stack([&]() { result = processNodesForTraitUseStep(nodeScopeResolver, node, traitReflection, scope, storage, adaptations, nodeCallback); });
		return result;
	}

	[[nodiscard]] bool processNodesForTraitUseStep(zval *nodeScopeResolver, zval *node, zval *traitReflection, zval *scope, zval *storage, zval *adaptations, zval *nodeCallback) const
	{
		bool error = false;
		if (ptsh::isInstanceOf(node, PT_CLASS_NODE, error)) {
			bool isUsedTrait;
			if (UNEXPECTED(!isUsedTraitStatement(node, traitReflection, isUsedTrait))) return false;
			if (isUsedTrait) return processUsedTrait(nodeScopeResolver, node, traitReflection, scope, storage, adaptations, nodeCallback);
			if (ptsh::isInstanceOf(node, PT_CLASS_CLASS_LIKE_STMT, error)) return true;
			if (UNEXPECTED(error)) return false;
			if (ptsh::isInstanceOf(node, PT_CLASS_FUNCTION_LIKE, error)) return true;
			if (UNEXPECTED(error)) return false;
			zv::Val subNodeNames = pt_call_method_cached(pt_tuh_get_sub_node_names_site, Z_OBJ_P(node), PT_LC("getsubnodenames"), 0, NULL);
			if (UNEXPECTED(subNodeNames.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(subNodeNames.raw()) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(subNodeNames.raw()));
				return !EG(exception);
			}
			for (auto entry : zv::ArrRef(subNodeNames.raw())) {
				zend_string *subNodeName = zval_try_get_string(entry.value().deref().raw());
				if (UNEXPECTED(subNodeName == NULL)) return false;
				zv::Val subNode = readProperty(node, subNodeName);
				zend_string_release(subNodeName);
				if (UNEXPECTED(subNode.isUndef())) return false;
				if (UNEXPECTED(!processNodesForTraitUse(nodeScopeResolver, subNode.raw(), traitReflection, scope, storage, adaptations, nodeCallback))) return false;
			}
			return true;
		}
		if (UNEXPECTED(error)) return false;
		if (Z_TYPE_P(node) == IS_ARRAY) {
			zv::Val iterated = zv::Val::copyOf(zv::Ref(node));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!processNodesForTraitUse(nodeScopeResolver, entry.value().deref().raw(), traitReflection, scope, storage, adaptations, nodeCallback))) return false;
			}
		}
		return true;
	}

	/* $node instanceof Node\Stmt\Trait_ && $traitReflection->getName() ===
	 * (string) $node->namespacedName &&
	 * $traitReflection->getNativeReflection()->getStartLine() === $node->getStartLine() */
	[[nodiscard]] static bool isUsedTraitStatement(zval *node, zval *traitReflection, bool &out)
	{
		out = false;
		bool error = false;
		if (!ptsh::isInstanceOf(node, PT_CLASS_TRAIT_STMT, error)) return !error;
		zv::Val traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection));
		if (UNEXPECTED(traitName.isUndef())) return false;
		zval *namespacedName = ptsh::readNodeProperty(pt_tuh_namespaced_name_site, node, PT_LC("namespacedName"));
		if (UNEXPECTED(namespacedName == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(namespacedName) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$namespacedName must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name));
			return false;
		}
		zend_string *nodeName = pt_name_node_cast_string(namespacedName);
		if (UNEXPECTED(nodeName == NULL)) return false;
		bool sameName = Z_TYPE_P(traitName.raw()) == IS_STRING && zend_string_equals(Z_STR_P(traitName.raw()), nodeName);
		zend_string_release(nodeName);
		if (!sameName) return true;

		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(traitReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getStartLine", nativeReflection.raw());
			return false;
		}
		zv::Val startLine = pt_class_adapter_get_start_line(nativeReflection.raw());
		if (UNEXPECTED(startLine.isUndef())) return false;
		zv::Val nodeStartLine = pt_call_method_cached(pt_tuh_node_start_line_site, Z_OBJ_P(node), PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(nodeStartLine.isUndef())) return false;
		out = zend_is_identical(startLine.raw(), nodeStartLine.raw());
		return true;
	}

	/* the used trait's statements, the adaptations applied to clones of its
	 * methods, walked in the trait scope */
	[[nodiscard]] bool processUsedTrait(zval *nodeScopeResolver, zval *node, zval *traitReflection, zval *scope, zval *storage, zval *adaptations, zval *nodeCallback) const
	{
		zend_class_entry *aliasCe = pt_class(PT_CLASS_TRAIT_USE_ADAPTATION_ALIAS);
		if (UNEXPECTED(aliasCe == NULL)) return false;
		zv::Arr methodModifiers = zv::Arr::empty();
		zv::Arr methodNames = zv::Arr::empty();
		for (auto entry : zv::ArrRef(adaptations)) {
			zval *adaptation = entry.value().deref().raw();
			if (Z_TYPE_P(adaptation) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(adaptation), aliasCe)) continue;

			zval *method = ptsh::readNodeProperty(pt_tuh_adaptation_method_site, adaptation, PT_LC("method"));
			if (UNEXPECTED(method == NULL)) return false;
			zv::Val methodHold = zv::Val::copyOf(zv::Ref(method));
			zv::Val methodName = toLowerString(pt_tuh_adaptation_method_to_lower_string_site, methodHold.raw());
			if (UNEXPECTED(methodName.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(methodName.raw()) != IS_STRING)) {
				zend_type_error("Illegal offset type");
				return false;
			}
			zval *newModifier = ptsh::readNodeProperty(pt_tuh_alias_new_modifier_site, adaptation, PT_LC("newModifier"));
			if (UNEXPECTED(newModifier == NULL)) return false;
			if (Z_TYPE_P(newModifier) != IS_NULL) {
				methodModifiers.set(Z_STR_P(methodName.raw()), zv::Val::copyOf(zv::Ref(newModifier)));
			}

			zval *newName = ptsh::readNodeProperty(pt_tuh_alias_new_name_site, adaptation, PT_LC("newName"));
			if (UNEXPECTED(newName == NULL)) return false;
			if (Z_TYPE_P(newName) == IS_NULL) continue;
			methodNames.set(Z_STR_P(methodName.raw()), zv::Val::copyOf(zv::Ref(newName)));
		}

		zval *nodeStmts = ptsh::readNodeProperty(pt_tuh_stmts_site, node, PT_LC("stmts"));
		if (UNEXPECTED(nodeStmts == NULL)) return false;
		zv::Val stmts = zv::Val::copyOf(zv::Ref(nodeStmts));
		if (UNEXPECTED(Z_TYPE_P(stmts.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(stmts.raw()));
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			/* foreach iterates the array it started with; the writes go to $stmts */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(stmts.raw()));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!applyAdaptations(entry, stmts, methodModifiers.raw(), methodNames.raw()))) return false;
			}
		}

		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		if (!inClass) {
			pt_throw_should_not_happen();
			return false;
		}
		zv::Val traitScope = pt_mutating_scope_enter_trait(Z_OBJ_P(scope), traitReflection);
		if (UNEXPECTED(traitScope.isUndef())) return false;

		// attribute args are not processed as part of the trait statements
		// but rules like TraitAttributesRule ask about their types
		{
			zval *attrGroups = ptsh::readNodeProperty(pt_tuh_attr_groups_site, node, PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups == NULL)) return false;
			zv::Val attrGroupsHold = zv::Val::copyOf(zv::Ref(attrGroups));
			zv::Val noopNodeCallback = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
			if (UNEXPECTED(noopNodeCallback.isUndef())) return false;
			if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, node, attrGroupsHold.raw(), traitScope.raw(), storage, noopNodeCallback.raw()))) return false;
		}

		{
			zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(classReflection.isUndef())) return false;
			zv::Args nodeArgv{node, traitReflection, classReflection.raw()};
			zv::Val inTraitNode = pt_type_new(PT_CLASS_IN_TRAIT_NODE, 3, nodeArgv);
			if (UNEXPECTED(inTraitNode.isUndef())) return false;
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inTraitNode.raw(), traitScope.raw(), storage))) return false;
		}

		zv::Val statementContext = pt_statement_context_create_top_level();
		if (UNEXPECTED(statementContext.isUndef())) return false;
		return !pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, node, stmts.raw(), traitScope.raw(), storage, nodeCallback, statementContext.raw()).isUndef();
	}

	/* the stmts loop body: a ClassMethod replaced in $stmts by a clone with
	 * the alias adaptations applied */
	[[nodiscard]] static bool applyAdaptations(zv::ArrayEntry entry, zv::Val &stmts, zval *methodModifiers, zval *methodNames)
	{
		zval *stmt = entry.value().deref().raw();
		bool error = false;
		if (!ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_METHOD_STMT, error)) return !error;
		zval *name = ptsh::readNodeProperty(pt_tuh_method_name_site, stmt, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		zv::Val methodName = toLowerString(pt_tuh_stmt_name_to_lower_string_site, nameHold.raw());
		if (UNEXPECTED(methodName.isUndef())) return false;
		zv::Val methodAst = cloneNode(stmt);
		if (UNEXPECTED(methodAst.isUndef())) return false;
		{
			/* $stmts[$i] = $methodAst */
			zval *array = stmts.raw();
			SEPARATE_ARRAY(array);
			zval stored;
			ZVAL_COPY(&stored, methodAst.raw());
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				zend_hash_update(Z_ARRVAL_P(array), key, &stored);
			} else {
				zend_hash_index_update(Z_ARRVAL_P(array), entry.indexKey(), &stored);
			}
		}
		if (UNEXPECTED(Z_TYPE_P(methodName.raw()) != IS_STRING)) return true;
		zend_string *methodNameKey = Z_STR_P(methodName.raw());

		zval *modifier = zend_symtable_find(Z_ARRVAL_P(methodModifiers), methodNameKey);
		if (modifier != NULL) {
			zval *flags = ptsh::readNodeProperty(pt_tuh_method_flags_site, methodAst.raw(), PT_LC("flags"));
			if (UNEXPECTED(flags == NULL)) return false;
			zval newFlags;
			ZVAL_LONG(&newFlags, (zval_get_long(flags) & ~PT_TUH_VISIBILITY_MASK) | zval_get_long(modifier));
			zend_update_property_ex(Z_OBJCE_P(methodAst.raw()), Z_OBJ_P(methodAst.raw()), pt_tuh_flags, &newFlags);
			if (UNEXPECTED(EG(exception))) return false;
		}

		zval *newName = zend_symtable_find(Z_ARRVAL_P(methodNames), methodNameKey);
		if (newName == NULL) return true;

		zval *astName = ptsh::readNodeProperty(pt_tuh_method_name_site, methodAst.raw(), PT_LC("name"));
		if (UNEXPECTED(astName == NULL)) return false;
		zv::Val astNameHold = zv::Val::copyOf(zv::Ref(astName));
		zv::Val originalName = toLowerString(pt_tuh_ast_name_to_lower_string_site, astNameHold.raw());
		if (UNEXPECTED(originalName.isUndef())) return false;
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(methodAst.raw()), PT_LC("originalTraitMethodName"), originalName.raw()))) return false;
		zval *nameValue = zend_symtable_find(Z_ARRVAL_P(methodNames), methodNameKey);
		zend_update_property_ex(Z_OBJCE_P(methodAst.raw()), Z_OBJ_P(methodAst.raw()), pt_tuh_name, nameValue);
		return !EG(exception);
	}
};

} // namespace phpstanturbo

using phpstanturbo::TraitUseHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_trait_use_handler)
{
	pt_tuh_flags = zend_string_init_interned(PT_LC("flags"), 1);
	pt_tuh_name = zend_string_init_interned(PT_LC("name"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\TraitUseHandler");
	ptdecl::TraitUseHandler::declareClass(cls);
	ptdecl::TraitUseHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider, *fileHelper, *parser, *attributesHandler;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, reflectionProvider, fileHelper, parser, attributesHandler)) RETURN_THROWS();
		TraitUseHandler(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider, fileHelper, parser, attributesHandler);
	});

	cls.method<&TraitUseHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(TraitUseHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_trait_use_handler);
	pt_stmt_handler_entry_register(&pt_ce_trait_use_handler, &TraitUseHandler::processStmtEntry);
}

/* }}} */
