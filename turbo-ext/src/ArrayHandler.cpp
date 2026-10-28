/*
 * PHPStanTurbo\ArrayHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ArrayHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr,
 * $itemResults, $beforeScope); the item-type callback it hands to
 * InitializerExprTypeResolver::getArrayType() ($itemResults,
 * $nativeTypesPromoted) is a pt_ietr_get_type over the typeCallback's frame
 * (the resolver calls it synchronously), and so is getWalkedArrayType()'s
 * $getType; the specifyTypesCallback is SpecifiedTypes::emptySpecifyCallback(),
 * as in the twin.
 *
 * The twin's walk arrays (ArrayWalk / ArrayItemWalk) never leave the class:
 * they are C++ records, and its $resolve closures - private to walkItems() -
 * a Resolver chain over WalkView, the walk as it stands plus the item a
 * closure appends to its copy of it. The context's passed-to type resolver
 * is the \Closure ArgumentsHandler created.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), VariableWriteOffset, TypeCombinator and the Type
 * kernel are called through their direct entries; the VariableWrite value
 * target through its slots (VariableFlow.cpp's pt_variable_write_slots_of()),
 * InitializerExprTypeResolver::getArrayType() through its direct entry; the
 * LiteralArrayItem / LiteralArrayNode / VariableWrite virtual
 * nodes and the synthetic is_callable() call are instantiated through the
 * class map.
 *
 * The twin's $nextIndex is int|null and becomes null past PHP_INT_MAX: a
 * known flag and a long.
 */

#include "support.h"
#include "generated/ArrayHandler.h"

namespace slots = ptdecl::ArrayHandler::slot;
namespace sigs = ptdecl::ArrayHandler::sig;
#include "CallHandlerSupport.h"
#include "Engine.h"

#include <memory>
#include <vector>

zend_class_entry *pt_ce_array_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_arh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ArrayHandler::{closure}";

/* VariableWrite::KIND_ARRAY_LITERAL_ITEM */
constexpr zend_long PT_ARH_KIND_ARRAY_LITERAL_ITEM = 14;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

/* $initializerExprTypeResolver->getArrayType($expr, $getTypeCallback) */
zv::Val getArrayType(zval *initializerExprTypeResolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	return pt_initializer_expr_type_resolver_get_array_type(initializerExprTypeResolver, expr, getTypeCallback);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_arh_items_site;
pt_property_site pt_arh_item_key_site;
pt_property_site pt_arh_item_value_site;
pt_property_site pt_arh_item_unpack_site;
pt_property_site pt_arh_item_by_ref_site;
pt_property_site pt_arh_variable_name_site;
pt_property_site pt_arh_closure_uses_site;
pt_property_site pt_arh_closure_use_by_ref_site;

zval *exprItems(zval *expr) { return nodeProperty(pt_arh_items_site, expr, PT_LC("items")); }
zval *itemKey(zval *item) { return nodeProperty(pt_arh_item_key_site, item, PT_LC("key")); }
zval *itemValue(zval *item) { return nodeProperty(pt_arh_item_value_site, item, PT_LC("value")); }
zval *itemUnpack(zval *item) { return nodeProperty(pt_arh_item_unpack_site, item, PT_LC("unpack")); }
zval *itemByRef(zval *item) { return nodeProperty(pt_arh_item_by_ref_site, item, PT_LC("byRef")); }

/* }}} */

/* {{{ the VariableWrite value-flow target's getters */

/* $write->isOffsetWrite() / ->getVariableName() / ->getId(): the slot of
 * the final PHP class, the getter otherwise; UNDEF = pending exception */
zv::Val variableWriteGetter(zval *write, uint32_t pt_variable_write_slots::*member, const char *lcname, size_t len)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (EXPECTED(writeSlots != NULL)) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->*member));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), lcname, len, 0, NULL);
}

/* }}} */

/* {{{ small value helpers */

/* the permanent interned 'is_callable' (module startup) */
zend_string *pt_arh_is_callable = nullptr;

/* the twin's ?int $nextIndex: never past PHP_INT_MAX, where PHP cannot append */
struct NextIndex
{
	bool known = true;
	zend_long value = 0;
};

/* new ConstantIntegerType($value) */
zv::Val newConstantIntegerType(zend_long value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_integer_type_new(&out, value))) return zv::Val();
	return zv::Val::adopt(out);
}

/* new IntegerType() */
zv::Val newIntegerType()
{
	zval out;
	if (UNEXPECTED(!pt_integer_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

/* $result->getScope() as an owned value; UNDEF = pending exception */
zv::Val resultScope(zval *result)
{
	zv::Val hold;
	zval *borrowed = pt_expression_result_scope(result, hold);
	if (UNEXPECTED(borrowed == NULL)) return zv::Val();
	return zv::Val::copyOf(zv::Ref(borrowed));
}

/* }}} */

/* {{{ the walk records: the twin's ArrayWalk / ArrayItemWalk arrays */

struct ArrayWalk;

struct ArrayItemWalk
{
	zval *arrayItem; /* borrowed from the walk's items */
	zv::Val entryScope;
	zv::Val valueScope;
	zv::Val keyResult; /* UNDEF = null */
	zv::Val valueResult; /* UNDEF = null */
	std::unique_ptr<ArrayWalk> nested; /* NULL = null */
	zv::Val itemWrite; /* UNDEF = null */
};

struct ArrayWalk
{
	zval *expr; /* exprHold's - the record is never moved */
	zv::Val exprHold;
	zv::Val itemsHold; /* the node's items as foreach iterates them */
	zv::Val beforeScope;
	zv::Val scope;
	zv::Val context;
	std::vector<ArrayItemWalk> items;
	zv::Val literalWrite; /* UNDEF = null */
};

/* the walk the twin's $resolve closures receive - a walk as it stands, with
 * the twin's `$walk['items'][] = $item` copies spelled as one appended item
 * whose nested walk may be replaced by another view */
struct WalkView
{
	ArrayWalk *walk;
	ArrayItemWalk *extra = NULL;
	const WalkView *extraNested = NULL; /* NULL: extra's own nested walk */
};

/* the twin's $resolve: the top-level closure over the context's resolver,
 * or a nested literal's closure over its parent's (capturing the parent
 * walk so far, the item and its key type) */
struct Resolver
{
	zval *passedToTypeResolver = NULL;
	const Resolver *parent = NULL;
	ArrayWalk *parentWalk = NULL;
	ArrayItemWalk *parentItem = NULL;
	zval *keyType = NULL;
};

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ArrayHandler; UNDEF = pending
 * exception. */
class ArrayHandler
{
public:
	explicit ArrayHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory, zval *closureTypeResolver) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::closureTypeResolver, closureTypeResolver);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_ARRAY_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Val passedToTypeResolver = pt_expression_context_get_passed_to_type_resolver(context);
		if (UNEXPECTED(passedToTypeResolver.isUndef())) return zv::Val();
		bool closureItems = false;
		if (!passedToTypeResolver.isNull() && UNEXPECTED(!hasClosureItems(expr, closureItems))) return zv::Val();
		ArrayWalk walk;
		if (passedToTypeResolver.isNull() || !closureItems) {
			zv::Val passedToType = pt_expression_context_get_passed_to_type(context);
			if (UNEXPECTED(passedToType.isUndef())) return zv::Val();
			zv::Val nativePassedToType = pt_expression_context_get_native_passed_to_type(context);
			if (UNEXPECTED(nativePassedToType.isUndef())) return zv::Val();
			if (UNEXPECTED(!walkItems(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, passedToType.raw(), nativePassedToType.raw(), NULL, walk))) return zv::Val();
			return createResult(nodeScopeResolver, walk, storage, nodeCallback);
		}

		// the closures in the literal are typed from the parameter the call
		// resolves for the literal - priced from the other items, walked first,
		// and the closures' declared signatures
		Resolver resolve;
		resolve.passedToTypeResolver = passedToTypeResolver.raw();
		zval null;
		ZVAL_NULL(&null);
		if (UNEXPECTED(!walkItems(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, &null, &null, &resolve, walk))) return zv::Val();
		zv::Val passedToType, nativePassedToType;
		if (UNEXPECTED(!resolveWalk(nodeScopeResolver, resolve, WalkView{&walk}, passedToType, nativePassedToType))) return zv::Val();
		if (UNEXPECTED(!completeWalk(nodeScopeResolver, stmt, walk, storage, nodeCallback, passedToType.raw(), nativePassedToType.raw()))) return zv::Val();

		return createResult(nodeScopeResolver, walk, storage, nodeCallback);
	}

	/* Mirrors collectAbsorbedItems() */
	zv::Val collectAbsorbedItems(zval *expr, zval *itemResults, zval *scopeArg) const
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (Z_TYPE_P(items) != IS_ARRAY) return scope;
		zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
		zv::Arr itemTypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(itemsHold.raw())) {
			zval *arrayItem = entry.value().deref().raw();
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			zval *itemResult = zend_hash_index_find(Z_ARRVAL_P(itemResults), Z_OBJ_HANDLE_P(value));
			if (UNEXPECTED(itemResult == NULL)) {
				zend_error(E_WARNING, "Undefined array key %u", Z_OBJ_HANDLE_P(value));
				return zv::Val();
			}
			zv::Val itemType = pt_expression_result_get_type(itemResult);
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			bool hasMarkers;
			if (UNEXPECTED(!pt_closure_signature_inference_has_markers(itemType.raw(), hasMarkers))) return zv::Val();
			if (!hasMarkers) continue;
			itemTypes.push(std::move(itemType));
		}
		if (zend_hash_num_elements(itemTypes.table()) == 0) return scope;

		zv::Val arrayType;
		{
			ItemTypeFrame frame{itemResults, false};
			pt_ietr_get_type getTypeCallback{&itemTypeCallback, &frame, &itemTypeCallable};
			arrayType = getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, getTypeCallback);
			if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		}
		zv::Val itemTypesHold(std::move(itemTypes));
		zv::Val absorbed = pt_closure_signature_inference_collect_absorbed_into(itemTypesHold.raw(), arrayType.raw());
		if (UNEXPECTED(absorbed.isUndef())) return zv::Val();
		return pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), absorbed.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ArrayHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* Mirrors walkItems(): the items in evaluation order; with resolve, a
	 * closure / arrow function item and the closures of a nested literal are
	 * left for completeWalk(), a closure with a by-reference use is walked in
	 * place with the expected type resolved from the items before it.
	 * passedToType / nativePassedToType IS_NULL for null; false = pending
	 * exception */
	[[nodiscard]] bool walkItems(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context, zval *passedToTypeArg, zval *nativePassedToTypeArg, const Resolver *resolve, ArrayWalk &walk) const
	{
		walk.exprHold = zv::Val::copyOf(zv::Ref(expr));
		walk.expr = walk.exprHold.raw();
		walk.beforeScope = zv::Val::copyOf(zv::Ref(scopeArg));
		walk.scope = zv::Val::copyOf(zv::Ref(scopeArg));
		walk.context = zv::Val::copyOf(zv::Ref(context));
		{
			bool valueFlowDirect;
			if (UNEXPECTED(!pt_expression_context_is_value_flow_direct(context, valueFlowDirect))) return false;
			if (valueFlowDirect) {
				zv::Val literalWrite = pt_expression_context_get_value_flow_target(context);
				if (UNEXPECTED(literalWrite.isUndef())) return false;
				if (!literalWrite.isNull()) {
					if (UNEXPECTED(!literalWrite.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function isOffsetWrite() on %s", zend_zval_value_name(literalWrite.raw()));
						return false;
					}
					zv::Val offsetWrite = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::offsetWrite, PT_LC("isoffsetwrite"));
					if (UNEXPECTED(offsetWrite.isUndef())) return false;
					if (!zend_is_true(offsetWrite.raw())) walk.literalWrite = std::move(literalWrite);
				}
			}
		}
		zv::Val passedToType = getExpectedArrayType(passedToTypeArg);
		if (UNEXPECTED(passedToType.isUndef())) return false;
		zv::Val nativePassedToType = getExpectedArrayType(nativePassedToTypeArg);
		if (UNEXPECTED(nativePassedToType.isUndef())) return false;
		bool tracksKeys = !walk.literalWrite.isUndef() || !passedToType.isNull() || !nativePassedToType.isNull() || resolve != NULL;
		NextIndex nextIndex;

		zval *itemsSlot = exprItems(expr);
		if (UNEXPECTED(itemsSlot == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(itemsSlot) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(itemsSlot));
			return !EG(exception);
		}
		// foreach iterates the array it was handed
		walk.itemsHold = zv::Val::copyOf(zv::Ref(itemsSlot));
		walk.items.reserve(zend_hash_num_elements(Z_ARRVAL_P(walk.itemsHold.raw())));
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		for (zv::ArrayEntry entry : zv::ArrRef(walk.itemsHold.raw())) {
			zval *arrayItem = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(arrayItem) != IS_OBJECT)) {
				// Array_ items are ArrayItem nodes (an empty element is a parse error)
				zend_throw_error(NULL, "Attempt to read property \"key\" on %s", zend_zval_value_name(arrayItem));
				return false;
			}
			ArrayItemWalk item;
			item.arrayItem = arrayItem;
			item.entryScope = zv::Val::copyOf(zv::Ref(scope.raw()));
			zval *key = itemKey(arrayItem);
			if (UNEXPECTED(key == NULL)) return false;
			bool keyFirst;
			if (UNEXPECTED(!isKeyWalkedFirst(arrayItem, keyFirst))) return false;
			if (Z_TYPE_P(key) != IS_NULL && keyFirst) {
				if (UNEXPECTED(!walkKey(nodeScopeResolver, stmt, key, scope, storage, nodeCallback, context, item))) return false;
			}
			item.valueScope = zv::Val::copyOf(zv::Ref(scope.raw()));

			zv::Val keyType;
			if (tracksKeys) {
				zval offset;
				zv::Val offsetHold;
				if (UNEXPECTED(!getKeyTypeAndOffset(nodeScopeResolver, arrayItem, item.keyResult, scope.raw(), nextIndex, keyType, offset, offsetHold))) return false;
				if (!walk.literalWrite.isUndef()) {
					zv::Val variableName = variableWriteGetter(walk.literalWrite.raw(), &pt_variable_write_slots::variableName, PT_LC("getvariablename"));
					if (UNEXPECTED(variableName.isUndef())) return false;
					zv::Val parentId = variableWriteGetter(walk.literalWrite.raw(), &pt_variable_write_slots::id, PT_LC("getid"));
					if (UNEXPECTED(parentId.isUndef())) return false;
					zv::Args writeArgv{variableName.raw(), arrayItem, (zend_long) Z_OBJ_HANDLE_P(arrayItem), PT_ARH_KIND_ARRAY_LITERAL_ITEM, true, &offset, parentId.raw()};
					item.itemWrite = pt_type_new(PT_CLASS_VARIABLE_WRITE, 7, writeArgv);
					if (UNEXPECTED(item.itemWrite.isUndef())) return false;
				}
			}

			zval *unpack = itemUnpack(arrayItem);
			if (UNEXPECTED(unpack == NULL)) return false;
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val heldValue = zv::Val::copyOf(zv::Ref(value));
			bool deferred = false;
			zv::Val itemPassedToType = zv::Val::copyOf(zv::Ref(passedToType.raw()));
			zv::Val itemNativePassedToType = zv::Val::copyOf(zv::Ref(nativePassedToType.raw()));
			if (resolve != NULL && !zend_is_true(unpack)) {
				int isArray = isInstanceOf(heldValue.raw(), PT_CLASS_ARRAY_EXPR);
				if (UNEXPECTED(isArray < 0)) return false;
				bool nestedClosureItems = false;
				if (isArray == 1 && UNEXPECTED(!hasClosureItems(heldValue.raw(), nestedClosureItems))) return false;
				int isArrowFunction = isInstanceOf(heldValue.raw(), PT_CLASS_ARROW_FUNCTION);
				if (UNEXPECTED(isArrowFunction < 0)) return false;
				int isClosure = isInstanceOf(heldValue.raw(), PT_CLASS_CLOSURE_EXPR);
				if (UNEXPECTED(isClosure < 0)) return false;
				bool byRefUse = false;
				if (isClosure == 1 && UNEXPECTED(!hasByRefUse(heldValue.raw(), byRefUse))) return false;
				if (isArray == 1 && nestedClosureItems) {
					zval null;
					ZVAL_NULL(&null);
					zv::Val nestedContext = getValueContext(walk.context.raw(), item, &null, &null, NULL);
					if (UNEXPECTED(nestedContext.isUndef())) return false;
					Resolver nestedResolve;
					nestedResolve.parent = resolve;
					nestedResolve.parentWalk = &walk;
					nestedResolve.parentItem = &item;
					nestedResolve.keyType = keyType.raw();
					std::unique_ptr<ArrayWalk> nested(new ArrayWalk());
					// the nested walk recurses natively over the AST
					bool walked = false;
					pt_engine_with_stack([&]() {
						walked = walkItems(nodeScopeResolver, stmt, heldValue.raw(), scope.raw(), storage, nodeCallback, nestedContext.raw(), &null, &null, &nestedResolve, *nested);
					});
					if (UNEXPECTED(!walked)) return false;
					scope = zv::Val::copyOf(zv::Ref(nested->scope.raw()));
					item.nested = std::move(nested);
					deferred = true;
				} else if (isArrowFunction == 1 || (isClosure == 1 && !byRefUse)) {
					deferred = true;
				} else if (isClosure == 1) {
					zv::Val resolvedType, resolvedNativeType;
					if (UNEXPECTED(!resolveWalk(nodeScopeResolver, *resolve, WalkView{&walk, &item}, resolvedType, resolvedNativeType))) return false;
					itemPassedToType = getExpectedArrayType(resolvedType.raw());
					if (UNEXPECTED(itemPassedToType.isUndef())) return false;
					itemNativePassedToType = getExpectedArrayType(resolvedNativeType.raw());
					if (UNEXPECTED(itemNativePassedToType.isUndef())) return false;
				}
			}

			if (!deferred) {
				zv::Val valueContext = getValueContext(walk.context.raw(), item, itemPassedToType.raw(), itemNativePassedToType.raw(), keyType.isUndef() ? NULL : keyType.raw());
				if (UNEXPECTED(valueContext.isUndef())) return false;
				item.valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldValue.raw(), scope.raw(), storage, nodeCallback, valueContext.raw());
				if (UNEXPECTED(item.valueResult.isUndef())) return false;
				scope = resultScope(item.valueResult.raw());
				if (UNEXPECTED(scope.isUndef())) return false;
			}

			if (Z_TYPE_P(key) != IS_NULL && !keyFirst) {
				if (UNEXPECTED(!walkKey(nodeScopeResolver, stmt, key, scope, storage, nodeCallback, context, item))) return false;
			}

			if (!deferred) {
				// the item's callback fires after its key and value were processed,
				// with the item's entry scope
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, arrayItem, item.entryScope.raw(), storage))) return false;
			}
			walk.items.push_back(std::move(item));
			walk.scope = zv::Val::copyOf(zv::Ref(scope.raw()));
		}

		return true;
	}

	/* the key half of walkItems(): $item['keyResult'] and $scope; false =
	 * pending exception */
	[[nodiscard]] static bool walkKey(zval *nodeScopeResolver, zval *stmt, zval *key, zv::Val &scope, zval *storage, zval *nodeCallback, zval *context, ArrayItemWalk &item)
	{
		zv::Val keyContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(keyContext.isUndef())) return false;
		zv::Val heldKey = zv::Val::copyOf(zv::Ref(key));
		item.keyResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldKey.raw(), scope.raw(), storage, nodeCallback, keyContext.raw());
		if (UNEXPECTED(item.keyResult.isUndef())) return false;
		scope = resultScope(item.keyResult.raw());
		return !scope.isUndef();
	}

	/* Mirrors completeWalk(): the deferred closures, each on the scope at its
	 * own position; their template argument constraints carried over to the
	 * literal's scope. false = pending exception */
	[[nodiscard]] bool completeWalk(zval *nodeScopeResolver, zval *stmt, ArrayWalk &walk, zval *storage, zval *nodeCallback, zval *passedToTypeArg, zval *nativePassedToTypeArg) const
	{
		zv::Val passedToType = getExpectedArrayType(passedToTypeArg);
		if (UNEXPECTED(passedToType.isUndef())) return false;
		zv::Val nativePassedToType = getExpectedArrayType(nativePassedToTypeArg);
		if (UNEXPECTED(nativePassedToType.isUndef())) return false;
		NextIndex nextIndex;
		for (ArrayItemWalk &item : walk.items) {
			zval *arrayItem = item.arrayItem;
			zv::Val keyType;
			zval offset;
			zv::Val offsetHold;
			if (UNEXPECTED(!getKeyTypeAndOffset(nodeScopeResolver, arrayItem, item.keyResult, item.valueScope.raw(), nextIndex, keyType, offset, offsetHold))) return false;
			if (!item.valueResult.isUndef()) continue;

			zv::Val valueContext = getValueContext(walk.context.raw(), item, passedToType.raw(), nativePassedToType.raw(), keyType.raw());
			if (UNEXPECTED(valueContext.isUndef())) return false;
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val heldValue = zv::Val::copyOf(zv::Ref(value));
			if (item.nested) {
				zv::Val nestedPassedToType = pt_expression_context_get_passed_to_type(valueContext.raw());
				if (UNEXPECTED(nestedPassedToType.isUndef())) return false;
				zv::Val nestedNativePassedToType = pt_expression_context_get_native_passed_to_type(valueContext.raw());
				if (UNEXPECTED(nestedNativePassedToType.isUndef())) return false;
				bool completed = false;
				pt_engine_with_stack([&]() {
					completed = completeWalk(nodeScopeResolver, stmt, *item.nested, storage, nodeCallback, nestedPassedToType.raw(), nestedNativePassedToType.raw());
					if (completed) item.valueResult = createResult(nodeScopeResolver, *item.nested, storage, nodeCallback);
				});
				if (UNEXPECTED(!completed || item.valueResult.isUndef())) return false;
				// the nested literal is walked here instead of by processExprNode() -
				// store its result and fire its callback the same way
				if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, heldValue.raw(), item.valueResult.raw()))) return false;
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, heldValue.raw(), item.valueScope.raw(), storage, valueContext.raw()))) return false;
			} else {
				item.valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldValue.raw(), item.valueScope.raw(), storage, nodeCallback, valueContext.raw());
				if (UNEXPECTED(item.valueResult.isUndef())) return false;
			}
			zv::Val valueScope = resultScope(item.valueResult.raw());
			if (UNEXPECTED(valueScope.isUndef())) return false;
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(valueScope.raw()));
			if (UNEXPECTED(constraints.isUndef())) return false;
			zv::Val constrained = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(walk.scope.raw()), constraints.raw());
			if (UNEXPECTED(constrained.isUndef())) return false;
			walk.scope = std::move(constrained);
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, arrayItem, item.entryScope.raw(), storage))) return false;
		}

		return true;
	}

	/* the twin's `[$passedToType, $nativePassedToType] = $resolve($walk)`;
	 * false = pending exception */
	[[nodiscard]] bool resolveWalk(zval *nodeScopeResolver, const Resolver &resolve, const WalkView &view, zv::Val &type, zv::Val &nativeType) const
	{
		if (resolve.parent == NULL) {
			// fn (array $walk): array => $passedToTypeResolver($this->getWalkedArrayType($nodeScopeResolver, $walk))
			zv::Val arrayType = getWalkedArrayType(nodeScopeResolver, view);
			if (UNEXPECTED(arrayType.isUndef())) return false;
			zv::Val resolved = pt_type_call_callable(resolve.passedToTypeResolver, 1, arrayType.raw());
			if (UNEXPECTED(resolved.isUndef())) return false;
			return destructurePair(resolved.raw(), type, nativeType);
		}

		// $item['nested'] = $nestedWalk; $walk['items'][] = $item; $resolve($walk)
		WalkView parentView{resolve.parentWalk, resolve.parentItem, &view};
		zv::Val parentType, parentNativeType;
		if (UNEXPECTED(!resolveWalk(nodeScopeResolver, *resolve.parent, parentView, parentType, parentNativeType))) return false;
		if (UNEXPECTED(!requireObject(resolve.keyType, "toArrayKey"))) return false;
		zv::Val keyType = pt_type_op(Z_OBJ_P(resolve.keyType), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return false;
		zv::Val expectedArrayType = getExpectedArrayType(parentType.raw());
		if (UNEXPECTED(expectedArrayType.isUndef())) return false;
		type = getExpectedValueType(expectedArrayType.raw(), keyType.raw());
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Val expectedNativeArrayType = getExpectedArrayType(parentNativeType.raw());
		if (UNEXPECTED(expectedNativeArrayType.isUndef())) return false;
		nativeType = getExpectedValueType(expectedNativeArrayType.raw(), keyType.raw());
		return !nativeType.isUndef();
	}

	/* `[$a, $b] = $value`; false = pending exception */
	[[nodiscard]] static bool destructurePair(zval *value, zv::Val &first, zv::Val &second)
	{
		first = zv::Val::null();
		second = zv::Val::null();
		if (Z_TYPE_P(value) != IS_ARRAY) return true;
		for (zend_ulong index = 0; index < 2; index++) {
			zval *element = zend_hash_index_find(Z_ARRVAL_P(value), index);
			if (UNEXPECTED(element == NULL)) {
				zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
				if (UNEXPECTED(EG(exception))) return false;
				continue;
			}
			(index == 0 ? first : second) = zv::Val::copyOf(zv::Ref(element).deref());
		}
		return true;
	}

	/* "Call to a member function name() on ..." unless an object; false =
	 * pending exception */
	[[nodiscard]] static bool requireObject(zval *value, const char *method)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) return true;
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
		return false;
	}

	/* the `$getType = function (Expr $inner) use (&$getType, $types,
	 * $nodeScopeResolver, $scope): Type` of getWalkedArrayType() -
	 * InitializerExprTypeResolver calls it synchronously, over this frame */
	struct WalkedTypeFrame
	{
		zend_object *self;
		zval *types;
		zval *nodeScopeResolver;
		zval *scope;
	};

	static zv::Val walkedType(void *data, zval *inner)
	{
		WalkedTypeFrame *frame = static_cast<WalkedTypeFrame *>(data);
		ZVAL_DEREF(inner);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(inner) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(inner), exprCe))) {
			zend_type_error("%s(): Argument #1 ($inner) must be of type PhpParser\\Node\\Expr, %s given", pt_arh_closure_name, zend_zval_value_name(inner));
			return zv::Val();
		}
		zval *known = zend_hash_index_find(Z_ARRVAL_P(frame->types), Z_OBJ_HANDLE_P(inner));
		if (known != NULL) return zv::Val::copyOf(zv::Ref(known));

		ArrayHandler handler(frame->self);
		int isClosure = isInstanceOf(inner, PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(isClosure < 0)) return zv::Val();
		int isArrowFunction = isClosure == 1 ? 0 : isInstanceOf(inner, PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(isArrowFunction < 0)) return zv::Val();
		if (isClosure == 1 || isArrowFunction == 1) {
			return pt_closure_type_resolver_get_declared_closure_type(OBJ_PROP_NUM(handler.self, slots::closureTypeResolver), frame->scope, inner);
		}

		int isArray = isInstanceOf(inner, PT_CLASS_ARRAY_EXPR);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == 1) {
			pt_ietr_get_type getTypeCallback{&walkedType, frame, &walkedTypeCallable};
			return getArrayType(OBJ_PROP_NUM(handler.self, slots::initializerExprTypeResolver), inner, getTypeCallback);
		}

		for (int scalarClass : {PT_CLASS_SCALAR_STRING, PT_CLASS_SCALAR_INT, PT_CLASS_SCALAR_FLOAT}) {
			int isScalar = isInstanceOf(inner, scalarClass);
			if (UNEXPECTED(isScalar < 0)) return zv::Val();
			if (isScalar == 0) continue;
			zv::Val stateType = pt_node_scope_resolver_find_scope_state_type(frame->nodeScopeResolver, inner, frame->scope);
			if (UNEXPECTED(stateType.isUndef())) return zv::Val();
			if (!stateType.isNull()) return stateType;
			break;
		}

		return pt_type_new_mixed_type();
	}

	/* the callback as a PHP callable that outlives the call: the closure
	 * over copies of what the frame points at */
	static zv::Val walkedTypeCallable(void *data)
	{
		WalkedTypeFrame *frame = static_cast<WalkedTypeFrame *>(data);
		zval self;
		ZVAL_OBJ(&self, frame->self);
		return pt_native_closure(&walkedTypeBody, &self, frame->types, frame->nodeScopeResolver, frame->scope);
	}

	/* the same closure called from PHP — captures: $this, $types,
	 * $nodeScopeResolver, $scope */
	static void walkedTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		WalkedTypeFrame frame{Z_OBJ(captures[0]), &captures[1], &captures[2], &captures[3]};
		zv::Val type = walkedType(&frame, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* Mirrors getWalkedArrayType(): the literal's type from the items walked
	 * so far - a deferred closure its declared signature, an item not reached
	 * yet what does not depend on its position. UNDEF = pending exception */
	zv::Val getWalkedArrayType(zval *nodeScopeResolver, const WalkView &view) const
	{
		zv::Arr types = zv::Arr::empty();
		auto addItem = [&](ArrayItemWalk &item, const WalkView *nestedView) -> bool {
			zval *key = itemKey(item.arrayItem);
			if (UNEXPECTED(key == NULL)) return false;
			if (Z_TYPE_P(key) != IS_NULL) {
				if (UNEXPECTED(Z_TYPE_P(key) != IS_OBJECT)) {
					zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(key));
					return false;
				}
				// a plain variable key is walked after its value - read it from the scope meanwhile
				zv::Val keyType;
				if (!item.keyResult.isUndef()) {
					keyType = pt_expression_result_get_type(item.keyResult.raw());
				} else {
					keyType = pt_node_scope_resolver_find_scope_state_type(nodeScopeResolver, key, item.valueScope.raw());
					if (!keyType.isUndef() && keyType.isNull()) keyType = pt_type_new_mixed_type();
				}
				if (UNEXPECTED(keyType.isUndef())) return false;
				types.arrRef().setIndex(Z_OBJ_HANDLE_P(key), keyType.ref());
			}

			zval *value = itemValue(item.arrayItem);
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val valueType;
			if (!item.valueResult.isUndef()) {
				valueType = pt_expression_result_get_type(item.valueResult.raw());
			} else if (nestedView != NULL) {
				valueType = getWalkedArrayType(nodeScopeResolver, *nestedView);
			} else {
				return true;
			}
			if (UNEXPECTED(valueType.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
				return false;
			}
			types.arrRef().setIndex(Z_OBJ_HANDLE_P(value), valueType.ref());
			return true;
		};
		for (ArrayItemWalk &item : view.walk->items) {
			WalkView nestedView{item.nested.get()};
			if (UNEXPECTED(!addItem(item, item.nested ? &nestedView : NULL))) return zv::Val();
		}
		if (view.extra != NULL) {
			WalkView nestedView{view.extra->nested.get()};
			const WalkView *extraNested = view.extraNested != NULL ? view.extraNested : (view.extra->nested ? &nestedView : NULL);
			if (UNEXPECTED(!addItem(*view.extra, extraNested))) return zv::Val();
		}

		zv::Val typesValue(std::move(types));
		WalkedTypeFrame frame{self, typesValue.raw(), nodeScopeResolver, view.walk->scope.raw()};
		pt_ietr_get_type getTypeCallback{&walkedType, &frame, &walkedTypeCallable};
		return getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), view.walk->expr, getTypeCallback);
	}

	/* Mirrors createResult() */
	zv::Val createResult(zval *nodeScopeResolver, ArrayWalk &walk, zval *storage, zval *nodeCallback) const
	{
		zval *expr = walk.expr;
		zval *beforeScope = walk.beforeScope.raw();
		zv::Val scope = zv::Val::copyOf(zv::Ref(walk.scope.raw()));
		uint32_t itemCount = (uint32_t) walk.items.size();
		zv::Arr itemNodes = zv::Arr::create(itemCount);
		zv::Arr itemResults = zv::Arr::create(itemCount);
		zv::Arr variableFlows = zv::Arr::create(itemCount);
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;
		zv::Val hold;
		for (ArrayItemWalk &item : walk.items) {
			zval *arrayItem = item.arrayItem;
			if (UNEXPECTED(item.valueResult.isUndef())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}

			{
				zv::Args itemNodeArgv{item.entryScope.raw(), arrayItem};
				zv::Val itemNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_ITEM, 2, itemNodeArgv);
				if (UNEXPECTED(itemNode.isUndef())) return zv::Val();
				itemNodes.push(std::move(itemNode));
			}
			bool keyFirst;
			if (UNEXPECTED(!isKeyWalkedFirst(arrayItem, keyFirst))) return zv::Val();
			zval *key = itemKey(arrayItem);
			if (UNEXPECTED(key == NULL)) return zv::Val();
			if (Z_TYPE_P(key) != IS_NULL && !item.keyResult.isUndef()) {
				if (UNEXPECTED(Z_TYPE_P(key) != IS_OBJECT)) {
					zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(key));
					return zv::Val();
				}
				itemResults.arrRef().setIndex(Z_OBJ_HANDLE_P(key), item.keyResult.ref());
			}
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
				return zv::Val();
			}
			itemResults.arrRef().setIndex(Z_OBJ_HANDLE_P(value), item.valueResult.ref());

			if (keyFirst && !item.keyResult.isUndef()) {
				if (UNEXPECTED(!foldResult(item.keyResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, hold))) return zv::Val();
			}
			if (!item.itemWrite.isUndef()) {
				zv::Val writeFlow = pt_variable_flow_write(item.itemWrite.raw(), NULL);
				if (UNEXPECTED(writeFlow.isUndef())) return zv::Val();
				variableFlows.push(std::move(writeFlow));
			}
			if (UNEXPECTED(!foldResult(item.valueResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, hold))) return zv::Val();
			if (!keyFirst && !item.keyResult.isUndef()) {
				if (UNEXPECTED(!foldResult(item.keyResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, hold))) return zv::Val();
			}
			zval *byRef = itemByRef(arrayItem);
			if (UNEXPECTED(byRef == NULL)) return zv::Val();
			if (!zend_is_true(byRef)) continue;

			zv::Val escape = pt_variable_flow_builder_escape_root(value);
			if (UNEXPECTED(escape.isUndef())) return zv::Val();
			variableFlows.push(std::move(escape));
		}
		{
			zv::Val itemNodesValue(std::move(itemNodes));
			zv::Args nodeArgv{expr, itemNodesValue.raw()};
			zv::Val literalArrayNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_NODE, 2, nodeArgv);
			if (UNEXPECTED(literalArrayNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, literalArrayNode.raw(), scope.raw(), storage))) return zv::Val();
		}
		{
			zv::Val observingFrame = pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, scope.raw());
			if (UNEXPECTED(observingFrame.isUndef())) return zv::Val();
			if (!observingFrame.isNull()) {
				scope = collectAbsorbedItems(expr, itemResults.raw(), scope.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val itemResultsValue(std::move(itemResults));
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, itemResultsValue.raw(), beforeScope);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		zv::Val result = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		// Dependencies::merge($callableDependencies, ...$itemResults' dependencies):
		// the non-null ones gathered first, so that an array whose items depend on
		// nothing allocates nothing
		zv::Val callableDependencies = getCallableDependencies(beforeScope, expr, itemResultsValue.raw(), result.raw());
		if (UNEXPECTED(callableDependencies.isUndef())) return zv::Val();
		zv::Arr dependencyList;
		if (!callableDependencies.isNull()) {
			dependencyList = zv::Arr::create(1);
			dependencyList.push(std::move(callableDependencies));
		}
		for (auto entry : zv::ArrRef(itemResultsValue.raw())) {
			zv::Val dependenciesHold;
			zval *itemDependencies = pt_expression_result_dependencies(entry.value().deref().raw(), dependenciesHold);
			if (UNEXPECTED(itemDependencies == NULL)) return zv::Val();
			if (Z_TYPE_P(itemDependencies) == IS_NULL) continue;
			if (dependencyList.isUndef()) dependencyList = zv::Arr::create(1);
			dependencyList.push(zv::Ref(itemDependencies));
		}
		if (dependencyList.isUndef()) return result;
		zv::Val dependencies = pt_dependencies_merge_list(dependencyList.table());
		if (UNEXPECTED(dependencies.isUndef())) return zv::Val();
		return pt_expression_result_with_dependencies(result.raw(), dependencies.raw());
	}

	/* Mirrors getValueContext(); passedToType / nativePassedToType IS_NULL
	 * for null, keyType NULL for null. UNDEF = pending exception */
	static zv::Val getValueContext(zval *context, ArrayItemWalk &item, zval *passedToType, zval *nativePassedToType, zval *keyType)
	{
		zv::Val valueContext;
		if (!item.itemWrite.isUndef()) {
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			valueContext = pt_expression_context_enter_value_flow(deepContext.raw(), item.itemWrite.raw(), false);
		} else {
			valueContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		}
		if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
		if (keyType == NULL || (Z_TYPE_P(passedToType) == IS_NULL && Z_TYPE_P(nativePassedToType) == IS_NULL)) return valueContext;
		zval *unpack = itemUnpack(item.arrayItem);
		if (UNEXPECTED(unpack == NULL)) return zv::Val();
		if (zend_is_true(unpack)) return valueContext;
		zval *value = itemValue(item.arrayItem);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		bool expectsType = false;
		for (int valueClass : {PT_CLASS_ARRAY_EXPR, PT_CLASS_CLOSURE_EXPR, PT_CLASS_ARROW_FUNCTION}) {
			int is = isInstanceOf(value, valueClass);
			if (UNEXPECTED(is < 0)) return zv::Val();
			if (is == 1) {
				expectsType = true;
				break;
			}
		}
		if (!expectsType) return valueContext;

		if (UNEXPECTED(!requireObject(keyType, "toArrayKey"))) return zv::Val();
		zv::Val arrayKeyType = pt_type_op(Z_OBJ_P(keyType), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(arrayKeyType.isUndef())) return zv::Val();
		zv::Val expectedValueType = getExpectedValueType(passedToType, arrayKeyType.raw());
		if (UNEXPECTED(expectedValueType.isUndef())) return zv::Val();
		zv::Val expectedNativeValueType = getExpectedValueType(nativePassedToType, arrayKeyType.raw());
		if (UNEXPECTED(expectedNativeValueType.isUndef())) return zv::Val();
		return pt_expression_context_enter_passed_to_type(valueContext.raw(), expectedValueType.raw(), expectedNativeValueType.raw());
	}

	/* Mirrors getKeyTypeAndOffset(): the type of the item's key and the
	 * offset its value is written to (*offset borrowed from offsetHold or a
	 * long/null), advancing the next implicit index; false = pending
	 * exception */
	[[nodiscard]] static bool getKeyTypeAndOffset(zval *nodeScopeResolver, zval *arrayItem, zv::Val &keyResult, zval *scope, NextIndex &nextIndex, zv::Val &keyType, zval &offset, zv::Val &offsetHold)
	{
		ZVAL_NULL(&offset);
		zval *unpack = itemUnpack(arrayItem);
		if (UNEXPECTED(unpack == NULL)) return false;
		if (zend_is_true(unpack)) {
			nextIndex.known = false;
			keyType = newIntegerType();
			return !keyType.isUndef();
		}

		zval *key = itemKey(arrayItem);
		if (UNEXPECTED(key == NULL)) return false;
		if (Z_TYPE_P(key) == IS_NULL) {
			keyType = nextIndex.known ? newConstantIntegerType(nextIndex.value) : newIntegerType();
			if (UNEXPECTED(keyType.isUndef())) return false;
			if (nextIndex.known) {
				ZVAL_LONG(&offset, nextIndex.value);
				if (nextIndex.value == ZEND_LONG_MAX) {
					nextIndex.known = false;
				} else {
					nextIndex.value++;
				}
			}
			return true;
		}

		if (!keyResult.isUndef()) {
			keyType = pt_expression_result_get_type(keyResult.raw());
		} else {
			keyType = pt_node_scope_resolver_find_scope_state_type(nodeScopeResolver, key, scope);
			if (!keyType.isUndef() && keyType.isNull()) keyType = pt_type_new_mixed_type();
		}
		if (UNEXPECTED(keyType.isUndef())) return false;
		offsetHold = pt_variable_write_offset_from_type(keyType.raw());
		if (UNEXPECTED(offsetHold.isUndef())) return false;
		ZVAL_COPY_VALUE(&offset, offsetHold.raw());
		if (Z_TYPE(offset) == IS_NULL) {
			nextIndex.known = false;
		} else if (Z_TYPE(offset) == IS_LONG && nextIndex.known) {
			if (Z_LVAL(offset) == ZEND_LONG_MAX) {
				nextIndex.known = false;
			} else if (Z_LVAL(offset) + 1 > nextIndex.value) {
				nextIndex.value = Z_LVAL(offset) + 1;
			}
		}
		return true;
	}

	/* Mirrors isKeyWalkedFirst(): a plain variable key is read when PHP adds
	 * the item - after its value is evaluated. false = pending exception */
	[[nodiscard]] static bool isKeyWalkedFirst(zval *arrayItem, bool &out)
	{
		zval *key = itemKey(arrayItem);
		if (UNEXPECTED(key == NULL)) return false;
		if (Z_TYPE_P(key) == IS_NULL) {
			out = false;
			return true;
		}
		int isVariable = isInstanceOf(key, PT_CLASS_VARIABLE);
		if (UNEXPECTED(isVariable < 0)) return false;
		if (isVariable == 0) {
			out = true;
			return true;
		}
		zval *name = nodeProperty(pt_arh_variable_name_site, key, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		out = Z_TYPE_P(name) != IS_STRING
			|| zend_string_equals_literal(Z_STR_P(name), "this")
			|| pt_is_superglobal_name(Z_STR_P(name));
		return true;
	}

	/* Mirrors hasClosureItems(); false = pending exception */
	[[nodiscard]] static bool hasClosureItems(zval *expr, bool &out)
	{
		out = false;
		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(items));
			return !EG(exception);
		}
		zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
		for (zv::ArrayEntry entry : zv::ArrRef(itemsHold.raw())) {
			zval *arrayItem = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(arrayItem) != IS_OBJECT)) {
				zend_throw_error(NULL, "Attempt to read property \"unpack\" on %s", zend_zval_value_name(arrayItem));
				return false;
			}
			zval *unpack = itemUnpack(arrayItem);
			if (UNEXPECTED(unpack == NULL)) return false;
			if (zend_is_true(unpack)) continue;
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return false;
			for (int closureClass : {PT_CLASS_CLOSURE_EXPR, PT_CLASS_ARROW_FUNCTION}) {
				int is = isInstanceOf(value, closureClass);
				if (UNEXPECTED(is < 0)) return false;
				if (is == 1) {
					out = true;
					return true;
				}
			}
			int isArray = isInstanceOf(value, PT_CLASS_ARRAY_EXPR);
			if (UNEXPECTED(isArray < 0)) return false;
			if (isArray == 1) {
				bool nested = false;
				bool checked = false;
				pt_engine_with_stack([&]() { checked = hasClosureItems(value, nested); });
				if (UNEXPECTED(!checked)) return false;
				if (nested) {
					out = true;
					return true;
				}
			}
		}
		return true;
	}

	/* Mirrors hasByRefUse(); false = pending exception */
	[[nodiscard]] static bool hasByRefUse(zval *closure, bool &out)
	{
		out = false;
		zval *uses = nodeProperty(pt_arh_closure_uses_site, closure, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(uses) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(uses));
			return !EG(exception);
		}
		zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
		for (zv::ArrayEntry entry : zv::ArrRef(usesHold.raw())) {
			zval *use = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(use) != IS_OBJECT)) {
				zend_throw_error(NULL, "Attempt to read property \"byRef\" on %s", zend_zval_value_name(use));
				return false;
			}
			zval *byRef = nodeProperty(pt_arh_closure_use_by_ref_site, use, PT_LC("byRef"));
			if (UNEXPECTED(byRef == NULL)) return false;
			if (zend_is_true(byRef)) {
				out = true;
				return true;
			}
		}
		return true;
	}

	/* Mirrors getCallableDependencies(): an array that may be a callable -
	 * `[Foo::class, 'method']` - depends on what calling it returns; PHP null
	 * for nothing, UNDEF = pending exception */
	static zv::Val getCallableDependencies(zval *scope, zval *expr, zval *itemResults, zval *result)
	{
		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(items));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) != 2) return zv::Val::null();
		zval *first = zend_hash_index_find(Z_ARRVAL_P(items), 0);
		if (first == NULL) return zv::Val::null();
		ZVAL_DEREF(first);
		if (Z_TYPE_P(first) == IS_NULL) return zv::Val::null();

		// a class constant, property default or enum case value is not called where it is
		// declared - testing it would reflect whatever class its first item happens to name
		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		if (inClass) {
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			if (function.isNull()) {
				bool inAnonymousFunction;
				if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return zv::Val();
				if (!inAnonymousFunction) {
					zv::Val functionCallStack = pt_type_call(Z_OBJ_P(scope), PT_LC("getfunctioncallstack"), 0, NULL);
					if (UNEXPECTED(functionCallStack.isUndef())) return zv::Val();
					if (Z_TYPE_P(functionCallStack.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(functionCallStack.raw())) == 0) return zv::Val::null();
				}
			}
		}

		if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(first));
			return zv::Val();
		}
		zval *value = itemValue(first);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
			return zv::Val();
		}
		zval *firstItemResult = zend_hash_index_find(Z_ARRVAL_P(itemResults), Z_OBJ_HANDLE_P(value));
		if (firstItemResult == NULL || Z_TYPE_P(firstItemResult) == IS_NULL) return zv::Val::null();
		zv::Val firstItemType = pt_expression_result_get_type(firstItemResult);
		if (UNEXPECTED(firstItemType.isUndef())) return zv::Val();
		zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(firstItemType.raw()), PT_LC("isclassstring"), 0, NULL);
		if (UNEXPECTED(isClassString < 0)) return zv::Val();
		if (isClassString != PT_TRI_YES) return zv::Val::null();

		zv::Val arrayType = pt_expression_result_get_type(result);
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(arrayType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_NO) return zv::Val::null();

		zv::Val variants = pt_type_call(Z_OBJ_P(arrayType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Arr returnTypes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(variants.raw())) {
			zv::Val returnTypeHold;
			zval *returnType = pt_parameters_acceptor_return_type(entry.value().deref().raw(), returnTypeHold);
			if (UNEXPECTED(returnType == NULL)) return zv::Val();
			returnTypes.push(zv::Ref(returnType));
		}

		return pt_dependencies_create_in(scope, returnTypes.raw());
	}

	/* $variableFlows[] = $result->getVariableFlow(); the $hasYield /
	 * $throwPoints / $impurePoints / $isAlwaysTerminating folds; false =
	 * pending exception */
	[[nodiscard]] static bool foldResult(zval *result, zv::Arr &variableFlows, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating, zv::Val &hold)
	{
		zv::Val flow = pt_expression_result_variable_flow(result);
		if (UNEXPECTED(flow.isUndef())) return false;
		variableFlows.push(std::move(flow));
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		zval *borrowed = pt_expression_result_throw_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_expression_result_impure_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		return isAlwaysTerminating || pt_expression_result_is_always_terminating(result, isAlwaysTerminating);
	}

	/* Mirrors getExpectedArrayType(); $type NULL / IS_NULL for null; PHP null
	 * for null, UNDEF = pending exception */
	static zv::Val getExpectedArrayType(zval *type)
	{
		if (type == NULL || Z_TYPE_P(type) == IS_NULL) return zv::Val::null();
		zend_long isIterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(isIterable < 0)) return zv::Val();
		if (isIterable == PT_TRI_NO) return zv::Val::null();

		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(type));

		zv::Val keyType = pt_type_new_mixed_type();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val itemType = pt_type_new_mixed_type();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zval arrayType;
		if (UNEXPECTED(!pt_array_type_new(&arrayType, keyType.raw(), itemType.raw()))) return zv::Val();
		zv::Val arrayTypeValue = zv::Val::adopt(arrayType);
		zv::Args argv{type, arrayTypeValue.raw()};
		return pt_type_combinator_intersect(2, argv);
	}

	/* Mirrors getExpectedValueType(); PHP null for null, UNDEF = pending
	 * exception */
	static zv::Val getExpectedValueType(zval *arrayType, zval *keyType)
	{
		if (Z_TYPE_P(arrayType) == IS_NULL) return zv::Val::null();
		zend_long hasOffsetValueType = pt_type_op_trinary(Z_OBJ_P(arrayType), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, keyType);
		if (UNEXPECTED(hasOffsetValueType < 0)) return zv::Val();
		if (hasOffsetValueType == PT_TRI_NO) return zv::Val::null();

		return pt_type_op(Z_OBJ_P(arrayType), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType);
	}

	/* static function (Expr $inner) use ($itemResults, $nativeTypesPromoted):
	 * Type — InitializerExprTypeResolver calls it synchronously, over the
	 * typeCallback's frame */
	struct ItemTypeFrame
	{
		zval *itemResults;
		bool nativeTypesPromoted;
	};

	static zv::Val itemTypeCallback(void *data, zval *inner)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		ZVAL_DEREF(inner);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(inner) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(inner), exprCe))) {
			zend_type_error("%s(): Argument #1 ($inner) must be of type PhpParser\\Node\\Expr, %s given", pt_arh_closure_name, zend_zval_value_name(inner));
			return zv::Val();
		}
		zval *itemResult = zend_hash_index_find(Z_ARRVAL_P(frame->itemResults), Z_OBJ_HANDLE_P(inner));
		if (UNEXPECTED(itemResult == NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return frame->nativeTypesPromoted ? pt_expression_result_get_native_type(itemResult) : pt_expression_result_get_type(itemResult);
	}

	/* the callback as a PHP callable that outlives the call (the resolver
	 * hands it to OversizedArrayBuilder): the closure over copies of
	 * $itemResults and $nativeTypesPromoted */
	static zv::Val itemTypeCallable(void *data)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		return pt_native_closure(&itemTypeCallbackBody, frame->itemResults, frame->nativeTypesPromoted);
	}

	/* the same closure called from PHP — captures: $itemResults,
	 * $nativeTypesPromoted */
	static void itemTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		ItemTypeFrame frame{&captures[0], Z_TYPE(captures[1]) == IS_TRUE};
		zv::Val type = itemTypeCallback(&frame, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* the typeCallback's body */
	zv::Val resolveType(bool nativeTypesPromoted, zval *expr, zval *itemResults, zval *beforeScope) const
	{
		zv::Val type;
		{
			ItemTypeFrame frame{itemResults, nativeTypesPromoted};
			pt_ietr_get_type getTypeCallback{&itemTypeCallback, &frame, &itemTypeCallable};
			type = getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, getTypeCallback);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(items));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) != 2) return type;
		zval *first = zend_hash_index_find(Z_ARRVAL_P(items), 0);
		if (first == NULL || Z_TYPE_P(first) == IS_NULL) return type;
		zval *second = zend_hash_index_find(Z_ARRVAL_P(items), 1);
		if (second == NULL || Z_TYPE_P(second) == IS_NULL) return type;
		zv::Val isCallableCall;
		{
			zv::Val name = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, zv::Args{pt_arh_is_callable});
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, expr);
			if (UNEXPECTED(arg.isUndef())) return zv::Val();
			zv::Arr callArgs = zv::Arr::create(1);
			callArgs.push(std::move(arg));
			zv::Val callArgsValue(std::move(callArgs));
			zv::Args callArgv{name.raw(), callArgsValue.raw()};
			isCallableCall = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
			if (UNEXPECTED(isCallableCall.isUndef())) return zv::Val();
		}
		zend_long hasExpressionType = pt_mutating_scope_has_expression_type(Z_OBJ_P(beforeScope), isCallableCall.raw());
		if (UNEXPECTED(hasExpressionType < 0)) return zv::Val();
		if (hasExpressionType != PT_TRI_YES) return type;
		// the narrowed type read from expressionTypes directly
		zv::Val trackedType = pt_mutating_scope_get_tracked_expression_type(Z_OBJ_P(beforeScope), Z_OBJ_P(isCallableCall.raw()));
		if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
		if (UNEXPECTED(!trackedType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(trackedType.raw()));
			return zv::Val();
		}
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(trackedType.raw()), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue != PT_TRI_YES) return type;
		// isCallable() is asked last - it reflects the class named by the first
		// item, which is expensive and unnecessary for arrays never narrowed by
		// is_callable()
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(type.raw()));
			return zv::Val();
		}
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(type.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable != PT_TRI_MAYBE) return type;

		zval callableType;
		if (UNEXPECTED(!pt_callable_type_new(&callableType))) return zv::Val();
		zv::Val callableTypeValue = zv::Val::adopt(callableType);
		zv::Args intersectArgv{type.raw(), callableTypeValue.raw()};
		return pt_type_combinator_intersect(2, intersectArgv);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $itemResults,
	 * $beforeScope): Type — captures: $this, $expr, $itemResults, $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		zv::Val type = ArrayHandler(Z_OBJ(captures[0])).resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_array_handler)
{
	pt_arh_is_callable = zend_string_init_interned(PT_LC("is_callable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ArrayHandler");
	ptdecl::ArrayHandler::declareClass(cls);
	ptdecl::ArrayHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory, *closureTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory, closureTypeResolver)) RETURN_THROWS();
		ArrayHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory, closureTypeResolver);
	});

	cls.method<&ArrayHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ArrayHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_array_handler);
	pt_expr_handler_entry_register(&pt_ce_array_handler, &ArrayHandler::processExprEntry);
}

/* }}} */
