/*
 * PHPStanTurbo\MutatingScope — native implementation of
 * PHPStan\Analyser\MutatingScope, the class PHPStan\Analyser\MutatingScope
 * IS in a production run (reg::Class::shadow()); the PHP twin keeps its
 * body as the reference implementation and is what the prefixed
 * activation of tests/scope-family.php compares against, one process
 * holding both. The design notes below follow the twin's file order,
 * one family of methods per section.
 *
 * Design: the class, its layout, dispatch and collaborators
 * ---------------------------------------------------------
 * Class shape. Not final: NodeCallbackScope (final, PHP) extends it and a
 * third party may too. The class carries the twin's interfaces (Scope,
 * NodeCallbackInvoker, CollectedDataEmitter), so every one of their
 * methods must be declared here — linking checks that.
 *
 * Layout. The twin's properties are declared typed property slots in the
 * twin's declaration order — the five class-body properties first
 * (resolvedTypes, nodeCallbackScope, namespace, scopeOutOfFirstLevelStatement,
 * scopeWithPromotedNativeTypes), then the 32 promoted constructor
 * properties in parameter order ($namespace is the one constructor
 * parameter that is not promoted) — so the std object handlers do
 * GC/clone/free and a PHP subclass's own properties follow them. Promoted
 * properties never carry the parameter's default (IS_PROP_UNINIT until the
 * constructor writes them); the class-body ones carry theirs. The names
 * are load-bearing: ScopeOps.cpp (scopeWith(), the merge/invalidate
 * bodies) and ScopeContext.cpp (the exact-class `context` fast
 * path) resolve the twin's properties by name through
 * pt_instance_prop_offset(), and NodeCallbackScope reads
 * $this->scopeFactory, $this->context, $this->expressionTypes & co. as a
 * subclass — all of which keep working on the native class unchanged.
 *
 * Dispatch. `$this->method()` on a non-final method goes through the
 * object's class entry (thisCall(): a direct C++ call when the object is
 * exactly the native class or its method is the native handler,
 * pt_type_call() by name otherwise — NodeCallbackScope overrides
 * toNodeCallbackScope/toWalkScope/getType/getNativeType/getParentScope/
 * getScopeType/getScopeNativeType/getKeepVoidType/
 * filterByTruthyValue/filterByFalseyValue/pushInFunctionCall/popInFunctionCall,
 * and a subclass may override anything
 * else). Private methods are direct C++ calls, as PHP never dispatches
 * them.
 *
 * Collaborators. The 14 injected services (Container, InternalScopeFactory,
 * ReflectionProvider, InitializerExprTypeResolver, ExtensionsCollection,
 * ExprPrinter, TypeSpecifier, PropertyReflectionFinder, Parser,
 * ConstantResolver, ExpressionResultStorageStack, ScopeContext, PhpVersion,
 * AttributeReflectionFactory) are held as PHP objects in their slots and
 * called by name (pt_type_call) — they are PHP classes today, except
 * ScopeContext (native: ScopeContext.cpp; read through its slots when the
 * object is exactly pt_ce_scope_context, by name otherwise — the prefixed
 * harness hands the native scope a PHP context because the PHP factory's
 * create() is typed with the real name). ExpressionTypeHolder (native) is
 * read through its slots when it is one (pt_ce_expr_type_holder), by name
 * otherwise; ScopeOps' bodies through pt_scope_ops_* direct entries;
 * StaticTypeFactory and the Type constructors through their pt_* exports;
 * ExpressionResultStorageStack is a PHP class (its stack is a plain array)
 * and stays a by-name collaborator. `new` of PHP classes (ConstFetch,
 * FullyQualified, UndefinedVariableException) and static calls
 * (VolatileExpressionHelper) go through the class map (pt_type_new /
 * pt_type_call_static); the by-reference array parameters of
 * VolatileExpressionHelper are passed as fresh references and read back.
 *
 * Construction cost. LazyInternalScopeFactory::create() (1.9M/run) and
 * toNodeCallbackScope() build scopes: the constructor is zpp over the 33
 * parameters plus 37 slot writes (ZVAL_COPY into the slots, no
 * allocation besides the object; `''` → null for $namespace), and the
 * factory's create() itself stays a PHP call from here — the twin's
 * $this->scopeFactory->create(...) sites build their 18 arguments from
 * the slots (CreateArgs) and call it by name.
 *
 * Design: the type resolution core (twin 1054–1808)
 * -------------------------------------------------
 * getType() reaches ScopeOps' memo and tracked-holder fast paths through
 * direct entries (pt_scope_ops_get_type_from_cache /
 * pt_scope_ops_expression_type_by_key), TypeUtils::resolveLateResolvableTypes()
 * through pt_type_utils_resolve_late_resolvable_types(), and writes the
 * $resolvedTypes memo into its slot. The NodeScopeResolver::$guard*
 * diagnostics are read as the static properties they are (class map).
 * resolveType()'s ExpressionTypeResolverExtension sweep stays a by-name
 * call, ExprHandlerRegistry::resolve() is its direct entry
 * (pt_expr_handler_registry_resolve()); the on-demand
 * pricing (resolveTypeOfNewWorldHandlerNode & co.) goes through
 * $this->container->getByType() with the twin's compile-time class-name
 * strings, `new ExpressionResultStorage()` / findExpressionResult() /
 * duplicate() through the native storage's direct entries (which fall back
 * to the methods for a PHP twin storage — the prefixed harness's case),
 * and the ExpressionResult / ExpressionResultStorageStack collaborators by
 * name. $this->toWalkScope() is a dispatched call (NodeCallbackScope
 * overrides it): the walk scope it returns is any MutatingScope, so its
 * $nativeTypesPromoted is read through the slot when it is this class and
 * by property name otherwise. `clone $this` (withTemplateArgumentConstraints)
 * is the object's clone handler; the property write of
 * withTemplateArgumentFrame() on the factory's result goes through the
 * engine write path from the result's own class (the property is
 * protected). getClosureScopeCacheKey() builds the joined parts in a
 * smart_str and hashes them with the engine's MD5. resolveName() /
 * resolveTypeByName() use the ClassReflection and ReflectionAccess
 * readers and the StaticType / ThisType / ObjectType constructors;
 * getTypeFromValue() the ConstantTypeHelper direct entry. getKeepVoidType()
 * and getCurrentTypesOfSpecifiedExpr() go through the private
 * getScopeStateType() / resolveScopeStateType() (twin 3473 / 3490); the
 * public reflection lookups those dispatch (getInstancePropertyReflection,
 * getStaticPropertyReflection, getMethodReflection) always go through the
 * object's method, so a subclass override answers.
 *
 * Design: the in-function-call stack and the enter* families (twin 1835-2558)
 * ---------------------------------------------------------------------------
 * pushInFunctionCall() / popInFunctionCall() copy the call stack, push or
 * array_pop() it and hand it to create(); the twin's
 * `$scope->resolvedTypes = $this->resolvedTypes` goes through the engine
 * write path from the result's own class (the factory answers with any
 * MutatingScope). Both are named handlers, as are getParentScope() and
 * (later) filterByTruthyValue/filterByFalseyValue: NodeCallbackScope
 * overrides them, so a $this-dispatch must be able to identify the native
 * body. isInClassExists() / isInFunctionExists() build their
 * `\class_exists('X')` FuncCall from the node class map and ask
 * $this->getType(); the call-stack readers filter the entries that carry a
 * reflection.
 *
 * The enter* families are argument assembly: enterClass() and enterTrait()
 * build the new ScopeContext through the context's own method (by name --
 * the differential harness hands the native scope a PHP context) and pass
 * the twin's exact create() argument list. enterClassMethod(),
 * enterPropertyHook() and enterFunction() assemble a
 * PhpMethodFromParserNodeReflection / PhpFunctionFromParserNodeReflection
 * (28 / 22 constructor arguments, an Args<N> list of borrowed and owned
 * values) out of getRealParameterTypes() / getRealParameterDefaultValues()
 * / getParameterAttributes() / transformStaticType(), and hand it to
 * enterFunctionLike(), which builds the parameter tables: the
 * ConditionalTypeForParameter holders (native ConditionalExpressionHolders
 * over the native class entry), the variadic parameter's array shape, the
 * ParameterVariableOriginalValueExpr entries, and array_merge() of the
 * constant types with them (PHP's semantics: string keys of the later win,
 * integer keys are appended). The closure-bind family
 * (enterClosureBind/restoreOriginalScopeAfterClosureBind/restoreThis/
 * enterClosureCall/withClosureBindScopeClasses) rewrites the $this entry of
 * the two tables through the TablePair and reads the other scope's
 * slots directly (the parameter is class-checked against
 * pt_ce_mutating_scope), dispatching only its isInClass() by name.
 *
 * getPhpVersion(), getFunctionType() and isParameterValueNullable() sit
 * with them, out of the twin's file order (the twin dispatches the first
 * two through $this, so they are named handlers too); getPhpVersion()
 * reads the twin's PHP_MIN_ANALYZABLE_VERSION_ID / MAX_PHP_VERSION as the
 * #defines at the top of this file and builds its PhpVersions over the
 * native IntegerRangeType / ConstantIntegerType.
 *
 * Design: the function entries, assignment and specification (twin 2560-3990)
 * ---------------------------------------------------------------------------
 * This is where the twin stops answering out of one scope and starts
 * chaining: `$scope = $this->a()->b()`. The first call is a $this-dispatch;
 * every one after it runs on whatever InternalScopeFactory::create()
 * answered, which is any MutatingScope. The otherProp() / otherCall() /
 * otherPrivate() helpers cover that: a table property of another scope by
 * slot when the object is (a subclass of) the native class and by name
 * otherwise, a public method always by name, and a private method — never
 * registered, so the engine cannot find one on the native class — as the
 * native body for a native object and the object's own method otherwise.
 * The same split drives the in-place specification: specifyExpressionType()
 * opens an unpublished working copy through the factory and writes into it,
 * and assignVariable() writes holders straight into the scope the factory
 * answered (writeScopeTable(), the engine's SEPARATE_ARRAY path, never the
 * slot index of a foreign class).
 *
 * enterAnonymousFunctionWithoutReflection() and its arrow-function sibling
 * assemble the parameter and use tables (getFunctionType() narrowed by
 * getCallableParameterType(), the NodeFinder walks of
 * invalidateStaticExpressions() and the `use` filter as
 * pt_find_first_recursive() collectors), then hand them to create();
 * enterAnonymousFunction() / enterArrowFunction() only add the
 * ClosureTypeResolver's reflection and rebuild the argument list from the
 * scope their sibling answered. The invalidation family reaches ScopeOps
 * through the new pt_scope_ops_scope_with /
 * pt_scope_ops_invalidate_expression_entries /
 * pt_scope_ops_invalidate_methods_on_expression /
 * pt_scope_ops_intertwined_ref_root_variable_name direct entries (rule 4
 * forbids the class map for a shadowed class), and
 * specifyExpressionTypeInPlace()'s offset narrowing through
 * pt_static_type_factory_int_offset_accessible /
 * ..._general_offset_accessible and pt_has_offset_value_type_new().
 *
 * Design: the narrowing application and the scope merges (twin 3993-4773)
 * -----------------------------------------------------------------------
 * applySpecifiedTypes() is the batch that turns a SpecifiedTypes into a
 * scope. Its deferred augments and conditional-expression recipes are
 * evaluated against THIS scope (the application point of the narrowing)
 * before and after the batch respectively; the augment queue is a growing
 * list walked by index, which is what the twin's array_shift()/append pair
 * amounts to. The batch itself is a std::vector of TypeSpecification
 * entries collected out of getSureTypes()/getSureNotTypes()/
 * getAlternativeTypes() and std::stable_sort()ed by the twin's comparator
 * (shorter expression keys first, sure specifications before sure-not ones
 * — PHP's usort is stable). Every entry then runs on whatever scope the
 * previous one answered, so the whole loop goes through the
 * foreign-scope helpers: otherPrivate() for setExpressionCertaintyKeepingType(),
 * unsetExpression(), getCurrentTypesOfSpecifiedExpr(), isComplexUnionType(),
 * openSpecificationScope(), specifyExpressionTypeInPlace() and
 * processConditionalExpressionsAfterSpecifying(); otherProp()/otherTable()
 * for the holder maps; the public getters by name. The final create() is
 * built from that scope (fillCreateArgsFromOther()) and goes to ITS
 * factory. The holders the batch records are built with pt_holder_create()
 * — the native ExpressionTypeHolder, which is the twin's class in
 * production.
 *
 * The conditional-expression bookkeeping and the merges reach ScopeOps
 * through five new direct entries (pt_scope_ops_match_conditional_expressions,
 * ..._merge_variable_holders, ..._finish_merge,
 * ..._intersect_conditional_expressions, ..._create_conditional_expressions;
 * ..._should_invalidate_expression for the generalization), and
 * TrinaryLogic::lazyExtremeIdentity()/maxMin()/or() are the PT_TRI_* bit
 * arithmetic they are (YES = 3, MAYBE = 1, NO = 0). The three private merge
 * helpers (withoutPreciseClassConstantFetches, preserveVacuousConditional-
 * Expressions, mergeSameGuardConditionalExpressions) answer with a new
 * table, as the twin's by-value arrays do, and build their union holder
 * over pt_ce_cond_expr_holder + pt_ceh_key_build() (rule 4: never the class
 * map for a shadowed class). exitFirstLevelStatements() memoizes on the
 * scopeOutOfFirstLevelStatement slot and carries $resolvedTypes over
 * through the engine write path, like pushInFunctionCall().
 *
 * Design: the closure and loop scopes, the generalization, the
 * comparisons and the member-access queries (twin 4775-5884)
 * ---------------------------------------------------------------------
 * processClosureScope() and
 * processAlwaysIterableForeachScopeWithoutPollute() are table rewrites
 * over another scope's holders (the by-ref `use` list, the loop's final
 * scope) whose certainty arithmetic is TrinaryLogic::and() as the bitwise
 * AND it is (YES = 3, MAYBE = 1, NO = 0). generalizeWith() ->
 * generalizeWithVariableState() -> generalizeVariableTypeHolders() is the
 * loop fixed-point: the twin's `uksort(strlen <=> strlen)` is a
 * std::stable_sort over a collected entry list (PHP's sort is stable too),
 * the already-generalized expressions are re-tested through the
 * pt_scope_ops_should_invalidate_expression() direct entry, and the
 * writable-variable set is seeded from the
 * IntertwinedVariableByReferenceWithExpr holders of both scopes.
 *
 * generalizeType() is the one long body of the family: it sorts
 * the unions of both inputs, flattened, into the seven buckets the twin
 * names (constant integers / floats / booleans / strings, constant arrays,
 * general arrays, integer ranges, everything else), each bucket a pair of
 * list arrays, and rebuilds a type out of them. Every bucket test that is
 * a class test goes through the shadowed classes' own entries
 * (pt_ce_constant_integer_type & co., rule 4) and every one that is a
 * predicate through the type ops (PT_OP_IS_CONSTANT_ARRAY, PT_OP_IS_ARRAY);
 * the arithmetic of the integer and range arms is plain zend_longs with
 * ZEND_LONG_MIN/MAX for the open bounds, the shapes go through
 * pt_constant_array_type_builder_*(), and the accessories through
 * pt_non_empty_array_type_new() / pt_accessory_array_list_type_new() /
 * pt_oversized_array_type_new(). `TypeCombinator::union(...$list)` is a
 * std::vector of borrowed zvals handed to pt_type_combinator_union().
 *
 * equals() and its two private comparators walk the tables and the
 * conditional holders through the holder readers. The visibility
 * queries (canAccessProperty/canReadProperty/canWriteProperty/
 * canCallMethod/canAccessConstant) share one memberAccessibleFromScope()
 * that runs the twin's `$canAccessClassMember` closure over the
 * closure-bind classes and the scope's own class, parameterized by the
 * private predicate (isPrivate() / isPrivateSet()) the two callers pass.
 * The union-filtering member lookups (filterTypeWithMethod(),
 * getMethodReflection(), getNakedMethod(), getPropertyReflection(),
 * getInstancePropertyReflection(), getStaticPropertyReflection(),
 * getConstantReflection(), getIterableKeyType(), getIterableValueType())
 * need a PHP callable for UnionType::filterTypes(): one
 * pt_type_native_callback() holder whose state is the member name and the
 * lowercase predicate to ask of every inner type. debug() builds its
 * descriptions with the engine's spprintf and pt_type_describe_precise(),
 * and invokeNodeCallback() / emitCollectedData() enter the stored callable
 * through pt_type_call_callable().
 */

#include "TypeTraits.h"
#include "generated/MutatingScope.h"

namespace sigs = ptdecl::MutatingScope::sig;
#include "TypeOps.h"

#include <algorithm>
#include <vector>

/* md5.h carries no extern "C" guard of its own */
extern "C" {
#include "ext/standard/md5.h"
}
#include "zend_smart_str.h"

#include <cctype>
#include <cstring>

zend_class_entry *pt_ce_mutating_scope = nullptr;

/* OBJ_PROP_NUM slots, in the twin's declaration order: the class-body
 * properties first, the promoted constructor properties after them */
enum : uint32_t
{
	PT_MS_PROP_RESOLVED_TYPES = 0,
	PT_MS_PROP_NODE_CALLBACK_SCOPE,
	PT_MS_PROP_NAMESPACE,
	PT_MS_PROP_SCOPE_OUT_OF_FIRST_LEVEL_STATEMENT,
	PT_MS_PROP_SCOPE_WITH_PROMOTED_NATIVE_TYPES,
	PT_MS_PROP_CONTAINER,
	PT_MS_PROP_SCOPE_FACTORY,
	PT_MS_PROP_REFLECTION_PROVIDER,
	PT_MS_PROP_INITIALIZER_EXPR_TYPE_RESOLVER,
	PT_MS_PROP_EXPRESSION_TYPE_RESOLVER_EXTENSIONS,
	PT_MS_PROP_EXPR_PRINTER,
	PT_MS_PROP_TYPE_SPECIFIER,
	PT_MS_PROP_PROPERTY_REFLECTION_FINDER,
	PT_MS_PROP_PARSER,
	PT_MS_PROP_CONSTANT_RESOLVER,
	PT_MS_PROP_EXPRESSION_RESULT_STORAGE_STACK,
	PT_MS_PROP_CONTEXT,
	PT_MS_PROP_PHP_VERSION,
	PT_MS_PROP_ATTRIBUTE_REFLECTION_FACTORY,
	PT_MS_PROP_CONFIGURED_PHP_VERSION_RANGE_HELPER,
	PT_MS_PROP_NODE_CALLBACK,
	PT_MS_PROP_DECLARE_STRICT_TYPES,
	PT_MS_PROP_FUNCTION,
	PT_MS_PROP_EXPRESSION_TYPES,
	PT_MS_PROP_NATIVE_EXPRESSION_TYPES,
	PT_MS_PROP_CONDITIONAL_EXPRESSIONS,
	PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES,
	PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION,
	PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT,
	PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS,
	PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS,
	PT_MS_PROP_IN_FUNCTION_CALLS_STACK,
	PT_MS_PROP_AFTER_EXTRACT_CALL,
	PT_MS_PROP_PARENT_SCOPE,
	PT_MS_PROP_NATIVE_TYPES_PROMOTED,
	PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME,
	PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS,
	PT_MS_PROP_COUNT,
};

/* private const COMPLEX_UNION_TYPE_MEMBER_LIMIT */
#define PT_MS_COMPLEX_UNION_TYPE_MEMBER_LIMIT 8

/* private const GLOBAL_CONSTANT_FETCH_KEYS_LIMIT */
#define PT_MS_GLOBAL_CONSTANT_FETCH_KEYS_LIMIT 8192

/* PHPStan\Analyser\ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID and
 * PHPStan\Php\PhpVersionFactory::MAX_PHP_VERSION — the one place the native
 * code reads them from (getPhpVersion()) */
#define PT_MS_PHP_MIN_ANALYZABLE_VERSION_ID 50207
#define PT_MS_MAX_PHP_VERSION 80699

/* the handlers the $this-dispatch fast paths identify (a subclass may
 * override any of these) */
static void ZEND_FASTCALL msGetFile(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsDeclareStrictTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsReadonlyPropertyFetch(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsInClass(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetClassReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetFunction(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetNamespace(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msCanAnyVariableExist(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msHasVariableType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsInAnonymousFunction(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetNodeKey(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msHasExpressionType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsInFirstLevelStatement(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msToWalkScope(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetVariableType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msDuplicateWith(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msObtainResultForNode(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msWithTemplateArgumentConstraints(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msWithoutMemoizedTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetNativeType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msDoNotTreatPhpDocTypesAsCertain(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msResolveName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msResolveTypeByName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetParentScope(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msPushInFunctionCall(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msPopInFunctionCall(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetPhpVersion(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetFunctionType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msIsParameterValueNullable(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msGetCurrentExpressionResultStorage(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msEnterAnonymousFunctionWithoutReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msEnterArrowFunctionWithoutReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msAssignVariable(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msAssignExpression(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msSpecifyExpressionType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msInvalidateExpression(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msFilterByTruthyValue(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msFilterByFalseyValue(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msApplySpecifiedTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL msFilterTypeWithMethod(INTERNAL_FUNCTION_PARAMETERS);

/* {{{ the internal scope factory's own state (LazyInternalScopeFactory)

 * Every scope the native code derives goes through
 * $this->scopeFactory->create(...), whose twin only resolves its services out
 * of the container once and then news the scope class. The services live in
 * the factory's own property slots after that first create(), so the whole
 * method is those slots plus a `new` — which is what factoryCreate() below
 * does, leaving the twin's create() to the first call of each factory (while
 * the memos are still null) and to any other InternalScopeFactory. */

/* the services create() passes on, in the scope constructor's parameter
 * order; all of them are `??=` memos the first create() fills */
enum : uint32_t
{
	PT_ISF_REFLECTION_PROVIDER = 0,
	PT_ISF_INITIALIZER_EXPR_TYPE_RESOLVER,
	PT_ISF_EXPRESSION_TYPE_RESOLVER_EXTENSIONS,
	PT_ISF_EXPR_PRINTER,
	PT_ISF_TYPE_SPECIFIER,
	PT_ISF_PROPERTY_REFLECTION_FINDER,
	PT_ISF_CONSTANT_RESOLVER,
	PT_ISF_PHP_VERSION,
	PT_ISF_ATTRIBUTE_REFLECTION_FACTORY,
	PT_ISF_CONFIGURED_PHP_VERSION_RANGE_HELPER,
	PT_ISF_MEMO_COUNT
};

static const char *const pt_isf_memo_names[PT_ISF_MEMO_COUNT] = {
	"reflectionProvider",
	"initializerExprTypeResolver",
	"expressionTypeResolverExtensions",
	"exprPrinter",
	"typeSpecifier",
	"propertyReflectionFinder",
	"constantResolver",
	"phpVersionType",
	"attributeReflectionFactory",
	"configuredPhpVersionRangeHelper",
};

/* the instance-property slot offsets of the factory's class entry, resolved
 * once (the twin is final, so there is one) and forgotten at rinit */
struct InternalScopeFactorySlots
{
	zend_class_entry *ce;
	uint32_t memos[PT_ISF_MEMO_COUNT];
	uint32_t container;
	uint32_t parser;
	uint32_t expressionResultStorageStack;
	uint32_t nodeCallback;
	uint32_t createsNodeCallbackScopes;
};

static InternalScopeFactorySlots pt_isf_slots = {};

void pt_mutating_scope_rinit()
{
	pt_isf_slots.ce = NULL;
}

/* the slots of an object that is exactly a LazyInternalScopeFactory; NULL
 * when it is of some other class (the caller then calls create()) — or, with
 * `error` set and an exception pending, when the class map cannot resolve the
 * class at all */
static const InternalScopeFactorySlots *internalScopeFactorySlots(zend_object *factory, bool &error)
{
	error = false;
	if (EXPECTED(factory->ce == pt_isf_slots.ce)) return &pt_isf_slots;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_LAZY_INTERNAL_SCOPE_FACTORY);
	if (ce == NULL) {
		error = EG(exception) != NULL;
		return NULL;
	}
	if (factory->ce != ce) return NULL;
	InternalScopeFactorySlots slots;
	slots.ce = ce;
	int32_t offsets[PT_ISF_MEMO_COUNT + 5];
	for (uint32_t i = 0; i < PT_ISF_MEMO_COUNT; i++) {
		offsets[i] = pt_instance_prop_offset(ce, pt_isf_memo_names[i], strlen(pt_isf_memo_names[i]));
	}
	offsets[PT_ISF_MEMO_COUNT + 0] = pt_instance_prop_offset(ce, PT_LC("container"));
	offsets[PT_ISF_MEMO_COUNT + 1] = pt_instance_prop_offset(ce, PT_LC("currentSimpleVersionParser"));
	offsets[PT_ISF_MEMO_COUNT + 2] = pt_instance_prop_offset(ce, PT_LC("expressionResultStorageStack"));
	offsets[PT_ISF_MEMO_COUNT + 3] = pt_instance_prop_offset(ce, PT_LC("nodeCallback"));
	offsets[PT_ISF_MEMO_COUNT + 4] = pt_instance_prop_offset(ce, PT_LC("createsNodeCallbackScopes"));
	for (uint32_t i = 0; i < PT_ISF_MEMO_COUNT + 5; i++) {
		if (UNEXPECTED(offsets[i] < 0)) {
			/* not the twin this reader knows: every call goes through the method */
			return NULL;
		}
	}
	for (uint32_t i = 0; i < PT_ISF_MEMO_COUNT; i++) {
		slots.memos[i] = (uint32_t) offsets[i];
	}
	slots.container = (uint32_t) offsets[PT_ISF_MEMO_COUNT + 0];
	slots.parser = (uint32_t) offsets[PT_ISF_MEMO_COUNT + 1];
	slots.expressionResultStorageStack = (uint32_t) offsets[PT_ISF_MEMO_COUNT + 2];
	slots.nodeCallback = (uint32_t) offsets[PT_ISF_MEMO_COUNT + 3];
	slots.createsNodeCallbackScopes = (uint32_t) offsets[PT_ISF_MEMO_COUNT + 4];
	pt_isf_slots = slots;
	return &pt_isf_slots;
}

/* }}} */

namespace phpstanturbo {

/* the 18 arguments of InternalScopeFactory::create(), in its parameter
 * order — the twin's $this->scopeFactory->create(...) sites fill them from
 * the slots, a few literals and the dispatched getters (whose results the
 * struct keeps alive for the call) */
struct CreateArgs
{
	enum : uint32_t
	{
		CONTEXT = 0,
		DECLARE_STRICT_TYPES,
		FUNCTION,
		NAMESPACE_,
		EXPRESSION_TYPES,
		NATIVE_EXPRESSION_TYPES,
		CONDITIONAL_EXPRESSIONS,
		IN_CLOSURE_BIND_SCOPE_CLASSES,
		ANONYMOUS_FUNCTION_REFLECTION,
		IN_FIRST_LEVEL_STATEMENT,
		CURRENTLY_ASSIGNED_EXPRESSIONS,
		CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS,
		IN_FUNCTION_CALLS_STACK,
		AFTER_EXTRACT_CALL,
		PARENT_SCOPE,
		NATIVE_TYPES_PROMOTED,
		TEMPLATE_ARGUMENT_FRAME,
		TEMPLATE_ARGUMENT_CONSTRAINTS,
		COUNT,
	};

	zval argv[COUNT];
	zv::Val owned[COUNT];

	CreateArgs()
	{
		for (uint32_t i = 0; i < COUNT; i++) {
			ZVAL_NULL(&argv[i]);
		}
	}

	void set(uint32_t i, zv::Ref borrowed) { ZVAL_COPY_VALUE(&argv[i], borrowed.raw()); }
	void setBool(uint32_t i, bool value) { ZVAL_BOOL(&argv[i], value); }
	void setNull(uint32_t i) { ZVAL_NULL(&argv[i]); }
	void setEmptyArray(uint32_t i) { ZVAL_EMPTY_ARRAY(&argv[i]); }
	void setOwned(uint32_t i, zv::Val value)
	{
		owned[i] = std::move(value);
		ZVAL_COPY_VALUE(&argv[i], owned[i].raw());
	}
};

/* the argument list of a `new` of a PHP class: borrowed or owned values in
 * the constructor's parameter order */
template <uint32_t N>
struct Args
{
	zval argv[N];
	zv::Val owned[N];
	uint32_t count = 0;

	void add(zv::Ref borrowed) { ZVAL_COPY_VALUE(&argv[count++], borrowed.raw()); }
	void addBool(bool value) { ZVAL_BOOL(&argv[count++], value); }
	void addNull() { ZVAL_NULL(&argv[count++]); }
	void addEmptyArray() { ZVAL_EMPTY_ARRAY(&argv[count++]); }

	void addOwned(zv::Val value)
	{
		owned[count] = std::move(value);
		ZVAL_COPY_VALUE(&argv[count], owned[count].raw());
		count++;
	}
};

/* an owned create() argument whose producer may have thrown */
#define PT_MS_ARG_CREATE(args, index, expr) \
	do { \
		zv::Val value_ = (expr); \
		if (UNEXPECTED(value_.isUndef())) { \
			return zv::Val(); \
		} \
		(args).setOwned((index), std::move(value_)); \
	} while (0)

/* an owned argument whose producer may have thrown */
#define PT_MS_ARG_OWNED(args, expr) \
	do { \
		zv::Val value_ = (expr); \
		if (UNEXPECTED(value_.isUndef())) { \
			return zv::Val(); \
		} \
		(args).addOwned(std::move(value_)); \
	} while (0)

/* Mirrors PHPStan\Analyser\MutatingScope. State lives in the PHP object's
 * property slots. Methods returning zv::Val use UNDEF to signal a pending
 * exception; a legitimate PHP null is zv::Val::null(). */
class MutatingScope
{
public:
	explicit MutatingScope(zend_object *self) : self(self) {}

	/* {{{ the slots */

	zv::Ref slot(uint32_t index) const { return zv::ObjRef(self).propAt(index); }

	/* a slot write that also clears IS_PROP_UNINIT (the promoted typed
	 * properties start uninitialized) */
	void writeSlot(uint32_t index, zv::Val value)
	{
		zval *p = OBJ_PROP_NUM(self, index);
		zv::ObjRef(self).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(p) = 0;
	}

	zv::Val copyOfSlot(uint32_t index) const { return zv::Val::copyOf(slot(index)); }

	bool slotBool(uint32_t index) const { return Z_TYPE_P(slot(index).raw()) == IS_TRUE; }

	zval *thisZval()
	{
		ZVAL_OBJ(&selfZval, self);
		return &selfZval;
	}

	/* }}} */

	/* {{{ $this->context reads: the native ScopeContext's slots when the
	 * context is one, its methods otherwise (the differential harness
	 * builds the native scope over a PHP context) */

	zv::Val contextFile() const { return contextSlot(PT_LC("getfile"), pt_scope_context_file); }
	zv::Val contextClassReflection() const { return contextSlot(PT_LC("getclassreflection"), pt_scope_context_class_reflection); }
	zv::Val contextTraitReflection() const { return contextSlot(PT_LC("gettraitreflection"), pt_scope_context_trait_reflection); }

	zv::Val contextSlot(const char *lcname, size_t len, zval *(*reader)(zend_object *)) const
	{
		zv::Ref context = slot(PT_MS_PROP_CONTEXT);
		if (UNEXPECTED(!context.isObject())) return uninitializedProperty("context");
		if (EXPECTED(context.asObject()->ce == pt_ce_scope_context)) return zv::Val::copyOf(zv::Ref(reader(context.asObject())));
		return pt_type_call(context.asObject(), lcname, len, 0, NULL);
	}

	/* the Error the twin's typed-property read raises when the constructor
	 * never ran */
	static zv::Val uninitializedProperty(const char *name)
	{
		zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\MutatingScope::$%s must not be accessed before initialization", name);
		return zv::Val();
	}

	/* }}} */

	/* {{{ $this-dispatch: through the object's class entry, straight to the
	 * C++ body when the object's method is the native one */

	template <typename Direct>
	zv::Val thisCall(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) { return pt_this_call(self, self->ce == pt_ce_mutating_scope, lcname, len, handler, argc, argv, direct); }

	/* the same for a bool-returning method; false = pending exception */
	template <typename Direct>
	bool thisCallBool(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, bool &out, Direct direct) { return pt_this_call_bool(self, self->ce == pt_ce_mutating_scope, lcname, len, handler, argc, argv, out, direct); }

	zv::Val thisGetFile() { return thisCall(PT_LC("getfile"), msGetFile, 0, NULL, [&]() { return getFile(); }); }
	bool thisIsDeclareStrictTypes(bool &out) { return thisCallBool(PT_LC("isdeclarestricttypes"), msIsDeclareStrictTypes, 0, NULL, out, [&](bool &o) { o = isDeclareStrictTypes(); return true; }); }
	bool thisIsInClass(bool &out) { return thisCallBool(PT_LC("isinclass"), msIsInClass, 0, NULL, out, [&](bool &o) { return isInClass(o); }); }
	zv::Val thisGetClassReflection() { return thisCall(PT_LC("getclassreflection"), msGetClassReflection, 0, NULL, [&]() { return getClassReflection(); }); }
	zv::Val thisGetFunction() { return thisCall(PT_LC("getfunction"), msGetFunction, 0, NULL, [&]() { return getFunction(); }); }
	zv::Val thisGetNamespace() { return thisCall(PT_LC("getnamespace"), msGetNamespace, 0, NULL, [&]() { return getNamespace(); }); }
	bool thisCanAnyVariableExist(bool &out) { return thisCallBool(PT_LC("cananyvariableexist"), msCanAnyVariableExist, 0, NULL, out, [&](bool &o) { return canAnyVariableExist(o); }); }
	bool thisIsInAnonymousFunction(bool &out) { return thisCallBool(PT_LC("isinanonymousfunction"), msIsInAnonymousFunction, 0, NULL, out, [&](bool &o) { o = isInAnonymousFunction(); return true; }); }
	bool thisIsInFirstLevelStatement(bool &out) { return thisCallBool(PT_LC("isinfirstlevelstatement"), msIsInFirstLevelStatement, 0, NULL, out, [&](bool &o) { o = isInFirstLevelStatement(); return true; }); }
	zv::Val thisHasVariableType(zval *variableName) { return thisCall(PT_LC("hasvariabletype"), msHasVariableType, 1, variableName, [&]() { return hasVariableType(Z_STR_P(variableName)); }); }
	zv::Val thisGetNodeKey(zval *node) { return thisCall(PT_LC("getnodekey"), msGetNodeKey, 1, node, [&]() { return getNodeKey(Z_OBJ_P(node)); }); }
	zv::Val thisHasExpressionType(zval *node) { return thisCall(PT_LC("hasexpressiontype"), msHasExpressionType, 1, node, [&]() { return hasExpressionType(Z_OBJ_P(node)); }); }

	bool thisIsReadonlyPropertyFetch(zval *expr, bool allowOnlyOnThis, bool &out)
	{
		zv::Args args{expr, allowOnlyOnThis};
		return thisCallBool(PT_LC("isreadonlypropertyfetch"), msIsReadonlyPropertyFetch, 2, args, out, [&](bool &o) { return isReadonlyPropertyFetch(Z_OBJ_P(expr), allowOnlyOnThis, o); });
	}

	zv::Val thisToWalkScope() { return thisCall(PT_LC("towalkscope"), msToWalkScope, 0, NULL, [&]() { return toWalkScope(); }); }
	zv::Val thisGetVariableType(zval *variableName) { return thisCall(PT_LC("getvariabletype"), msGetVariableType, 1, variableName, [&]() { return getVariableType(Z_STR_P(variableName)); }); }
	zv::Val thisGetType(zval *node) { return thisCall(PT_LC("gettype"), msGetType, 1, node, [&]() { return getType(Z_OBJ_P(node)); }); }
	zv::Val thisObtainResultForNode(zval *node) { return thisCall(PT_LC("obtainresultfornode"), msObtainResultForNode, 1, node, [&]() { return obtainResultForNode(Z_OBJ_P(node)); }); }
	zv::Val thisWithTemplateArgumentConstraints(zval *constraints) { return thisCall(PT_LC("withtemplateargumentconstraints"), msWithTemplateArgumentConstraints, 1, constraints, [&]() { return withTemplateArgumentConstraints(constraints); }); }
	zv::Val thisWithoutMemoizedTypes() { return thisCall(PT_LC("withoutmemoizedtypes"), msWithoutMemoizedTypes, 0, NULL, [&]() { return withoutMemoizedTypes(); }); }
	zv::Val thisGetNativeType(zval *expr) { return thisCall(PT_LC("getnativetype"), msGetNativeType, 1, expr, [&]() { return getNativeType(expr); }); }
	zv::Val thisDoNotTreatPhpDocTypesAsCertain() { return thisCall(PT_LC("donottreatphpdoctypesascertain"), msDoNotTreatPhpDocTypesAsCertain, 0, NULL, [&]() { return doNotTreatPhpDocTypesAsCertain(); }); }
	zv::Val thisResolveName(zval *name) { return thisCall(PT_LC("resolvename"), msResolveName, 1, name, [&]() { return resolveName(Z_OBJ_P(name)); }); }
	zv::Val thisResolveTypeByName(zval *name) { return thisCall(PT_LC("resolvetypebyname"), msResolveTypeByName, 1, name, [&]() { return resolveTypeByName(Z_OBJ_P(name)); }); }
	zv::Val thisGetPhpVersion() { return thisCall(PT_LC("getphpversion"), msGetPhpVersion, 0, NULL, [&]() { return getPhpVersion(); }); }

	zv::Val thisFilterTypeWithMethod(zval *typeWithMethod, zval *methodName)
	{
		zv::Args args{typeWithMethod, methodName};
		return thisCall(PT_LC("filtertypewithmethod"), msFilterTypeWithMethod, 2, args, [&]() { return filterTypeWithMethod(typeWithMethod, Z_STR_P(methodName)); });
	}

	bool thisIsParameterValueNullable(zval *parameter, bool &out)
	{
		return thisCallBool(PT_LC("isparametervaluenullable"), msIsParameterValueNullable, 1, parameter, out, [&](bool &o) { return isParameterValueNullable(Z_OBJ_P(parameter), o); });
	}

	zv::Val thisGetFunctionType(zval *type, bool isNullable, bool isVariadic)
	{
		zv::Args args{type, isNullable, isVariadic};
		return thisCall(PT_LC("getfunctiontype"), msGetFunctionType, 3, args, [&]() { return getFunctionType(type, isNullable, isVariadic); });
	}

	/* $this->duplicateWith(...) with its eight arguments in a zval array */
	zv::Val thisDuplicateWith(zval *args)
	{
		return thisCall(PT_LC("duplicatewith"), msDuplicateWith, 8, args, [&]() {
			return duplicateWith(&args[0], &args[1], &args[2], &args[3], &args[4], &args[5], zend_is_true(&args[6]), zend_is_true(&args[7]));
		});
	}

	/* a public method the twin calls on $this (getInstancePropertyReflection,
	 * getStaticPropertyReflection, getMethodReflection): always the
	 * object's method, so a subclass override answers */
	zv::Val thisCallByName(const char *lcname, size_t len, uint32_t argc, zval *argv) { return pt_type_call(self, lcname, len, argc, argv); }

	/* }}} */

	/* {{{ ExpressionTypeHolder reads: the native holder's slots, the
	 * methods of anything else (the twin calls them either way) */

	static zv::Val holderExpr(zv::Ref holder) { return holderRead(holder, PT_ETH_PROP_EXPR, PT_LC("getexpr")); }
	static zv::Val holderType(zv::Ref holder) { return holderRead(holder, PT_ETH_PROP_TYPE, PT_LC("gettype")); }

	/* the PT_TRI_* value of $holder->getCertainty(); -1 = pending exception */
	[[nodiscard]] static zend_long holderCertainty(zv::Ref holder)
	{
		zv::Ref value = holder.deref();
		if (EXPECTED(value.isObject() && value.asObject()->ce == pt_ce_expr_type_holder)) return pt_holder_certainty_value(value.asObject());
		zv::Val certainty = holderRead(holder, PT_ETH_PROP_CERTAINTY, PT_LC("getcertainty"));
		if (UNEXPECTED(certainty.isUndef())) return -1;
		return pt_type_trinary_value(certainty.raw());
	}

	static zv::Val holderRead(zv::Ref holder, uint32_t slot, const char *lcname, size_t len)
	{
		zv::Ref value = holder.deref();
		if (EXPECTED(value.isObject())) {
			if (EXPECTED(value.asObject()->ce == pt_ce_expr_type_holder)) return zv::Val::copyOf(zv::ObjRef(value.asObject()).propAt(slot));
			return pt_type_call(value.asObject(), lcname, len, 0, NULL);
		}
		zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(value.raw()));
		return zv::Val();
	}

	/* }}} */

	/* {{{ small helpers */

	/* $value instanceof <class-map class>; false with an exception pending
	 * when the key cannot be resolved (an undeclared class is "no") */
	[[nodiscard]] static bool isInstance(zv::Ref value, int classIdx, bool &out)
	{
		out = false;
		if (!value.isObject()) return true;
		zend_class_entry *ce = pt_class_loaded(classIdx);
		if (ce == NULL) return EG(exception) == NULL;
		out = instanceof_function(value.asObject()->ce, ce);
		return true;
	}

	/* $object->name / $object->var & co.: a public property read by name
	 * (the node classes' declared subnodes); NULL with an Error pending
	 * when the class has no such property */
	static zv::Ref nodeProp(zend_object *object, const char *name, size_t len)
	{
		zv::Ref value = zv::ObjRef(object).prop(name, len);
		if (UNEXPECTED(value.raw() == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s has no property $%s", ZSTR_VAL(object->ce->name), name);
		}
		return value;
	}

	static zv::Val trinary(zend_long value) { return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value))); }

	/*
	 * $factory->create(...$args) — the twin's LazyInternalScopeFactory::create()
	 * without its frame: the memoized services out of the factory's slots and
	 * the scope built here, which is all that method does once its `??=` memos
	 * are filled.
	 *
	 * The method itself answers whenever that is not provably the same thing:
	 * another InternalScopeFactory implementation, a factory whose memos the
	 * first create() has not filled yet, and — the differential tests'
	 * prefixed activation, where NodeCallbackScope extends the PHP twin
	 * instead of the native class — a run in which the classes create() would
	 * instantiate are not the native ones. UNDEF = pending exception.
	 */
	/* $factory->create(...$args) through the method */
	static zv::Val factoryCreateCall(zend_object *factory, CreateArgs &args)
	{
		return pt_type_call(factory, PT_LC("create"), CreateArgs::COUNT, args.argv);
	}

	static zv::Val factoryCreate(zend_object *factory, CreateArgs &args)
	{
		bool error;
		const InternalScopeFactorySlots *slots = internalScopeFactorySlots(factory, error);
		if (UNEXPECTED(error)) return zv::Val();
		zend_class_entry *nodeCallbackScope = slots != NULL ? pt_class_loaded(PT_CLASS_NODE_CALLBACK_SCOPE) : NULL;
		if (UNEXPECTED(nodeCallbackScope == NULL || pt_ce_mutating_scope == NULL || nodeCallbackScope->parent != pt_ce_mutating_scope)) {
			if (UNEXPECTED(slots != NULL && EG(exception) != NULL)) return zv::Val();
			return factoryCreateCall(factory, args);
		}
		for (uint32_t i = 0; i < PT_ISF_MEMO_COUNT; i++) {
			if (UNEXPECTED(Z_TYPE_P(OBJ_PROP(factory, slots->memos[i])) != IS_OBJECT)) return factoryCreateCall(factory, args);
		}
		zval *container = OBJ_PROP(factory, slots->container);
		zval *parser = OBJ_PROP(factory, slots->parser);
		zval *storageStack = OBJ_PROP(factory, slots->expressionResultStorageStack);
		if (UNEXPECTED(Z_TYPE_P(container) != IS_OBJECT || Z_TYPE_P(parser) != IS_OBJECT || Z_TYPE_P(storageStack) != IS_OBJECT)) {
			/* an uninitialized promoted property — the twin's Error, raised
			 * by the method reading it */
			return factoryCreateCall(factory, args);
		}

		/* the argument types the twin's create() signature would enforce and
		 * the new scope reads without checking; anything else is the method's
		 * TypeError to raise */
		static const uint32_t arrayArgs[] = {
			CreateArgs::EXPRESSION_TYPES,
			CreateArgs::NATIVE_EXPRESSION_TYPES,
			CreateArgs::CONDITIONAL_EXPRESSIONS,
			CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES,
			CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS,
			CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS,
			CreateArgs::IN_FUNCTION_CALLS_STACK,
		};
		if (UNEXPECTED(Z_TYPE(args.argv[CreateArgs::CONTEXT]) != IS_OBJECT)) return factoryCreateCall(factory, args);
		for (uint32_t i = 0; i < sizeof(arrayArgs) / sizeof(arrayArgs[0]); i++) {
			if (UNEXPECTED(Z_TYPE(args.argv[arrayArgs[i]]) != IS_ARRAY)) return factoryCreateCall(factory, args);
		}

		/* $className = $this->createsNodeCallbackScopes ? NodeCallbackScope::class : MutatingScope::class; */
		zend_class_entry *className = Z_TYPE_P(OBJ_PROP(factory, slots->createsNodeCallbackScopes)) == IS_TRUE
			? nodeCallbackScope
			: pt_ce_mutating_scope;
		zval scope;
		if (UNEXPECTED(object_init_ex(&scope, className) != SUCCESS)) return zv::Val();

		ConstructArgs a = {};
		zval factoryZval;
		ZVAL_OBJ(&factoryZval, factory);
		a.container = container;
		a.scopeFactory = &factoryZval;
		a.reflectionProvider = OBJ_PROP(factory, slots->memos[PT_ISF_REFLECTION_PROVIDER]);
		a.initializerExprTypeResolver = OBJ_PROP(factory, slots->memos[PT_ISF_INITIALIZER_EXPR_TYPE_RESOLVER]);
		a.expressionTypeResolverExtensions = OBJ_PROP(factory, slots->memos[PT_ISF_EXPRESSION_TYPE_RESOLVER_EXTENSIONS]);
		a.exprPrinter = OBJ_PROP(factory, slots->memos[PT_ISF_EXPR_PRINTER]);
		a.typeSpecifier = OBJ_PROP(factory, slots->memos[PT_ISF_TYPE_SPECIFIER]);
		a.propertyReflectionFinder = OBJ_PROP(factory, slots->memos[PT_ISF_PROPERTY_REFLECTION_FINDER]);
		a.parser = parser;
		a.constantResolver = OBJ_PROP(factory, slots->memos[PT_ISF_CONSTANT_RESOLVER]);
		a.expressionResultStorageStack = storageStack;
		a.context = &args.argv[CreateArgs::CONTEXT];
		a.phpVersion = OBJ_PROP(factory, slots->memos[PT_ISF_PHP_VERSION]);
		a.attributeReflectionFactory = OBJ_PROP(factory, slots->memos[PT_ISF_ATTRIBUTE_REFLECTION_FACTORY]);
		a.configuredPhpVersionRangeHelper = OBJ_PROP(factory, slots->memos[PT_ISF_CONFIGURED_PHP_VERSION_RANGE_HELPER]);
		a.nodeCallback = OBJ_PROP(factory, slots->nodeCallback);
		a.declareStrictTypes = Z_TYPE(args.argv[CreateArgs::DECLARE_STRICT_TYPES]) == IS_TRUE;
		a.function = &args.argv[CreateArgs::FUNCTION];
		a.ns = Z_TYPE(args.argv[CreateArgs::NAMESPACE_]) == IS_STRING ? Z_STR(args.argv[CreateArgs::NAMESPACE_]) : NULL;
		a.expressionTypes = &args.argv[CreateArgs::EXPRESSION_TYPES];
		a.nativeExpressionTypes = &args.argv[CreateArgs::NATIVE_EXPRESSION_TYPES];
		a.conditionalExpressions = &args.argv[CreateArgs::CONDITIONAL_EXPRESSIONS];
		a.inClosureBindScopeClasses = &args.argv[CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES];
		a.anonymousFunctionReflection = &args.argv[CreateArgs::ANONYMOUS_FUNCTION_REFLECTION];
		a.inFirstLevelStatement = Z_TYPE(args.argv[CreateArgs::IN_FIRST_LEVEL_STATEMENT]) == IS_TRUE;
		a.currentlyAssignedExpressions = &args.argv[CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS];
		a.currentlyAllowedUndefinedExpressions = &args.argv[CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS];
		a.inFunctionCallsStack = &args.argv[CreateArgs::IN_FUNCTION_CALLS_STACK];
		a.afterExtractCall = Z_TYPE(args.argv[CreateArgs::AFTER_EXTRACT_CALL]) == IS_TRUE;
		a.parentScope = &args.argv[CreateArgs::PARENT_SCOPE];
		a.nativeTypesPromoted = Z_TYPE(args.argv[CreateArgs::NATIVE_TYPES_PROMOTED]) == IS_TRUE;
		a.templateArgumentFrame = &args.argv[CreateArgs::TEMPLATE_ARGUMENT_FRAME];
		a.templateArgumentConstraints = &args.argv[CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS];
		MutatingScope(Z_OBJ(scope)).construct(a);
		return zv::Val::adopt(scope);
	}

	/* $this->scopeFactory->create(...$args); UNDEF = pending exception */
	zv::Val scopeFactoryCreate(CreateArgs &args)
	{
		zv::Ref factory = slot(PT_MS_PROP_SCOPE_FACTORY);
		if (UNEXPECTED(!factory.isObject())) return uninitializedProperty("scopeFactory");
		return factoryCreate(factory.asObject(), args);
	}

	/* the arguments every twin site passes from the slots unchanged; the
	 * dispatched getters are filled by the callers that use them. false =
	 * the twin's Error on a promoted property the constructor never wrote */
	bool fillFromSlots(CreateArgs &a)
	{
		if (UNEXPECTED(slot(PT_MS_PROP_CONTEXT).isUndef())) {
			(void) uninitializedProperty("context");
			return false;
		}
		if (UNEXPECTED(slot(PT_MS_PROP_EXPRESSION_TYPES).isUndef() || slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS).isUndef())) {
			(void) uninitializedProperty(slot(PT_MS_PROP_EXPRESSION_TYPES).isUndef() ? "expressionTypes" : "templateArgumentConstraints");
			return false;
		}
		a.set(CreateArgs::CONTEXT, slot(PT_MS_PROP_CONTEXT));
		a.set(CreateArgs::EXPRESSION_TYPES, slot(PT_MS_PROP_EXPRESSION_TYPES));
		a.set(CreateArgs::NATIVE_EXPRESSION_TYPES, slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES));
		a.set(CreateArgs::CONDITIONAL_EXPRESSIONS, slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS));
		a.set(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES));
		a.set(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, slot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION));
		a.set(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS, slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS));
		a.set(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS));
		a.set(CreateArgs::IN_FUNCTION_CALLS_STACK, slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK));
		a.set(CreateArgs::AFTER_EXTRACT_CALL, slot(PT_MS_PROP_AFTER_EXTRACT_CALL));
		a.set(CreateArgs::PARENT_SCOPE, slot(PT_MS_PROP_PARENT_SCOPE));
		a.set(CreateArgs::NATIVE_TYPES_PROMOTED, slot(PT_MS_PROP_NATIVE_TYPES_PROMOTED));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return true;
	}

	/* $this->isDeclareStrictTypes(), $this->getFunction(),
	 * $this->getNamespace(), $this->isInFirstLevelStatement() — the four
	 * dispatched getters most sites pass; false = pending exception */
	[[nodiscard]] bool fillDispatched(CreateArgs &a, bool withFunction, bool withFirstLevel)
	{
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return false;
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		if (withFunction) {
			zv::Val function = thisGetFunction();
			if (UNEXPECTED(function.isUndef())) return false;
			a.setOwned(CreateArgs::FUNCTION, std::move(function));
		}
		zv::Val ns = thisGetNamespace();
		if (UNEXPECTED(ns.isUndef())) return false;
		a.setOwned(CreateArgs::NAMESPACE_, std::move(ns));
		if (withFirstLevel) {
			bool inFirstLevelStatement;
			if (UNEXPECTED(!thisIsInFirstLevelStatement(inFirstLevelStatement))) return false;
			a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, inFirstLevelStatement);
		}
		return true;
	}

	/* }}} */

	/* {{{ __construct */

	/* the 33 constructor arguments as zpp delivers them (NULL = a defaulted
	 * optional parameter) */
	struct ConstructArgs
	{
		zval *container, *scopeFactory, *reflectionProvider, *initializerExprTypeResolver, *expressionTypeResolverExtensions, *exprPrinter, *typeSpecifier, *propertyReflectionFinder, *parser, *constantResolver, *expressionResultStorageStack, *context, *phpVersion, *attributeReflectionFactory, *configuredPhpVersionRangeHelper, *nodeCallback;
		bool declareStrictTypes;
		zval *function;
		zend_string *ns;
		zval *expressionTypes, *nativeExpressionTypes, *conditionalExpressions, *inClosureBindScopeClasses, *anonymousFunctionReflection;
		bool inFirstLevelStatement;
		zval *currentlyAssignedExpressions, *currentlyAllowedUndefinedExpressions, *inFunctionCallsStack;
		bool afterExtractCall;
		zval *parentScope;
		bool nativeTypesPromoted;
		zval *templateArgumentFrame, *templateArgumentConstraints;
	};

	void construct(const ConstructArgs &a)
	{
		writeSlot(PT_MS_PROP_CONTAINER, zv::Val::copyOf(zv::Ref(a.container)));
		writeSlot(PT_MS_PROP_SCOPE_FACTORY, zv::Val::copyOf(zv::Ref(a.scopeFactory)));
		writeSlot(PT_MS_PROP_REFLECTION_PROVIDER, zv::Val::copyOf(zv::Ref(a.reflectionProvider)));
		writeSlot(PT_MS_PROP_INITIALIZER_EXPR_TYPE_RESOLVER, zv::Val::copyOf(zv::Ref(a.initializerExprTypeResolver)));
		writeSlot(PT_MS_PROP_EXPRESSION_TYPE_RESOLVER_EXTENSIONS, zv::Val::copyOf(zv::Ref(a.expressionTypeResolverExtensions)));
		writeSlot(PT_MS_PROP_EXPR_PRINTER, zv::Val::copyOf(zv::Ref(a.exprPrinter)));
		writeSlot(PT_MS_PROP_TYPE_SPECIFIER, zv::Val::copyOf(zv::Ref(a.typeSpecifier)));
		writeSlot(PT_MS_PROP_PROPERTY_REFLECTION_FINDER, zv::Val::copyOf(zv::Ref(a.propertyReflectionFinder)));
		writeSlot(PT_MS_PROP_PARSER, zv::Val::copyOf(zv::Ref(a.parser)));
		writeSlot(PT_MS_PROP_CONSTANT_RESOLVER, zv::Val::copyOf(zv::Ref(a.constantResolver)));
		writeSlot(PT_MS_PROP_EXPRESSION_RESULT_STORAGE_STACK, zv::Val::copyOf(zv::Ref(a.expressionResultStorageStack)));
		writeSlot(PT_MS_PROP_CONTEXT, zv::Val::copyOf(zv::Ref(a.context)));
		writeSlot(PT_MS_PROP_PHP_VERSION, zv::Val::copyOf(zv::Ref(a.phpVersion)));
		writeSlot(PT_MS_PROP_ATTRIBUTE_REFLECTION_FACTORY, zv::Val::copyOf(zv::Ref(a.attributeReflectionFactory)));
		writeSlot(PT_MS_PROP_CONFIGURED_PHP_VERSION_RANGE_HELPER, zv::Val::copyOf(zv::Ref(a.configuredPhpVersionRangeHelper)));
		writeSlot(PT_MS_PROP_NODE_CALLBACK, optional(a.nodeCallback));
		writeSlot(PT_MS_PROP_DECLARE_STRICT_TYPES, zv::Val::boolean(a.declareStrictTypes));
		writeSlot(PT_MS_PROP_FUNCTION, optional(a.function));
		/* if ($namespace === '') { $namespace = null; } $this->namespace = $namespace; */
		writeSlot(PT_MS_PROP_NAMESPACE, a.ns == NULL || ZSTR_LEN(a.ns) == 0 ? zv::Val::null() : zv::Val::string(a.ns));
		writeSlot(PT_MS_PROP_EXPRESSION_TYPES, optionalArray(a.expressionTypes));
		writeSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, optionalArray(a.nativeExpressionTypes));
		writeSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, optionalArray(a.conditionalExpressions));
		writeSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, optionalArray(a.inClosureBindScopeClasses));
		writeSlot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION, optional(a.anonymousFunctionReflection));
		writeSlot(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, zv::Val::boolean(a.inFirstLevelStatement));
		writeSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, optionalArray(a.currentlyAssignedExpressions));
		writeSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, optionalArray(a.currentlyAllowedUndefinedExpressions));
		writeSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, optionalArray(a.inFunctionCallsStack));
		writeSlot(PT_MS_PROP_AFTER_EXTRACT_CALL, zv::Val::boolean(a.afterExtractCall));
		writeSlot(PT_MS_PROP_PARENT_SCOPE, optional(a.parentScope));
		writeSlot(PT_MS_PROP_NATIVE_TYPES_PROMOTED, zv::Val::boolean(a.nativeTypesPromoted));
		writeSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME, optional(a.templateArgumentFrame));
		writeSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, optional(a.templateArgumentConstraints));
	}

	static zv::Val optional(zval *value) { return value == NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(value)); }
	static zv::Val optionalArray(zval *value) { return value == NULL ? zv::Val(zv::Arr::empty()) : zv::Val::copyOf(zv::Ref(value)); }

	/* }}} */

	zv::Val toNodeCallbackScope()
	{
		zv::Ref memo = slot(PT_MS_PROP_NODE_CALLBACK_SCOPE);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Ref factory = slot(PT_MS_PROP_SCOPE_FACTORY);
		if (UNEXPECTED(!factory.isObject())) return uninitializedProperty("scopeFactory");
		zv::Val nodeCallbackScopeFactory = pt_type_call(factory.asObject(), PT_LC("tonodecallbackscopefactory"), 0, NULL);
		if (UNEXPECTED(nodeCallbackScopeFactory.isUndef())) return zv::Val();
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(nodeCallbackScopeFactory.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function create() on %s", zend_zval_value_name(nodeCallbackScopeFactory.raw()));
			return zv::Val();
		}
		zv::Val nodeCallbackScope = factoryCreate(Z_OBJ_P(nodeCallbackScopeFactory.raw()), a);
		if (UNEXPECTED(nodeCallbackScope.isUndef())) return zv::Val();
		bool isNodeCallbackScope;
		if (UNEXPECTED(!isInstance(nodeCallbackScope.ref(), PT_CLASS_NODE_CALLBACK_SCOPE, isNodeCallbackScope))) return zv::Val();
		if (isNodeCallbackScope) {
			zv::Val seeded = pt_type_call(Z_OBJ_P(nodeCallbackScope.raw()), PT_LC("seedwalkscope"), 1, thisZval());
			if (UNEXPECTED(seeded.isUndef())) return zv::Val();
		}

		writeSlot(PT_MS_PROP_NODE_CALLBACK_SCOPE, zv::Val::copyOf(nodeCallbackScope.ref()));
		return nodeCallbackScope;
	}

	zv::Val toWalkScope() { return self_(); }

	/** @deprecated */
	zv::Val toMutatingScope() { return self_(); }

	zv::Val self_()
	{
		zval z;
		ZVAL_OBJ_COPY(&z, self);
		return zv::Val::adopt(z);
	}

	zv::Val getFile() { return contextFile(); }

	zv::Val getFileDescription()
	{
		zv::Val traitReflection = contextTraitReflection();
		if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
		if (traitReflection.isNull()) return thisGetFile();

		zv::Val classReflection = contextClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getDisplayName() on %s", zend_zval_value_name(classReflection.raw()));
			return zv::Val();
		}

		zv::Val className = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getdisplayname"), 0, NULL);
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val isAnonymous = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("isanonymous"), 0, NULL);
		if (UNEXPECTED(isAnonymous.isUndef())) return zv::Val();
		if (!zend_is_true(isAnonymous.raw())) {
			zend_string *name = zval_get_string(className.raw());
			className = zv::Val::adoptString(zend_strpprintf(0, "class %s", ZSTR_VAL(name)));
			zend_string_release(name);
		}

		/* $traitReflection = $this->context->getTraitReflection(); — read again, as the twin does */
		traitReflection = contextTraitReflection();
		if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getFileName() on %s", zend_zval_value_name(traitReflection.raw()));
			return zv::Val();
		}
		zv::Val fileName = pt_type_call(Z_OBJ_P(traitReflection.raw()), PT_LC("getfilename"), 0, NULL);
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		if (fileName.isNull()) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zend_string *file = zval_get_string(fileName.raw());
		zend_string *name = zval_get_string(className.raw());
		zv::Val result = zv::Val::adoptString(zend_strpprintf(0, "%s (in context of %s)", ZSTR_VAL(file), ZSTR_VAL(name)));
		zend_string_release(name);
		zend_string_release(file);
		return result;
	}

	bool isDeclareStrictTypes() const { return slotBool(PT_MS_PROP_DECLARE_STRICT_TYPES); }

	zv::Val enterDeclareStrictTypes()
	{
		/* create($this->context, true, null, null, $this->expressionTypes,
		 * $this->nativeExpressionTypes, templateArgumentFrame: ...,
		 * templateArgumentConstraints: ...) — the skipped parameters at
		 * the interface's defaults */
		CreateArgs a;
		a.set(CreateArgs::CONTEXT, slot(PT_MS_PROP_CONTEXT));
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, true);
		a.setNull(CreateArgs::FUNCTION);
		a.setNull(CreateArgs::NAMESPACE_);
		a.set(CreateArgs::EXPRESSION_TYPES, slot(PT_MS_PROP_EXPRESSION_TYPES));
		a.set(CreateArgs::NATIVE_EXPRESSION_TYPES, slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES));
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES);
		a.setNull(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION);
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.setNull(CreateArgs::PARENT_SCOPE);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	/* private; UNDEF = pending exception */
	zv::Val rememberConstructorExpressions(zv::Ref currentExpressionTypes)
	{
		bool hasCustomSerialization;
		if (UNEXPECTED(!classHasCustomSerialization(hasCustomSerialization))) return zv::Val();
		bool rememberPropertyState = !hasCustomSerialization;
		zv::Arr expressionTypes = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(currentExpressionTypes.raw())) {
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			bool is;
			if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_FUNC_CALL, is))) return zv::Val();
			if (is) {
				zv::Ref name = nodeProp(Z_OBJ_P(expr.raw()), PT_LC("name"));
				if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
				bool isName;
				if (UNEXPECTED(!isInstance(name.deref(), PT_CLASS_NAME, isName))) return zv::Val();
				if (!isName) continue;
				/* interface_exists() etc. imply class_exists() therefore not listed here */
				zv::Ref functionName = nodeProp(name.deref().asObject(), PT_LC("name"));
				if (UNEXPECTED(functionName.raw() == NULL)) return zv::Val();
				zv::Ref fn = functionName.deref();
				if (!fn.stringEquals("class_exists") && !fn.stringEquals("function_exists")) continue;
			} else {
				if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_PROPERTY_FETCH, is))) return zv::Val();
				if (is) {
					bool isReadonly = false;
					if (!rememberPropertyState || !thisIsReadonlyPropertyFetch(expr.raw(), true, isReadonly)) {
						if (UNEXPECTED(EG(exception))) return zv::Val();
						continue;
					}
					if (!isReadonly) continue;
				} else {
					if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_PROPERTY_INITIALIZATION_EXPR, is))) return zv::Val();
					if (is) {
						if (!rememberPropertyState) continue;
					} else {
						if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_CONST_FETCH, is))) return zv::Val();
						if (!is) continue;
					}
				}
			}

			zval copy;
			ZVAL_COPY(&copy, entry.value().raw());
			pt_ht_update(expressionTypes.table(), entry.stringKeyOrNull(), entry.indexKey(), &copy);
		}

		zval *thisHolder = zend_hash_str_find(Z_ARRVAL_P(currentExpressionTypes.raw()), PT_LC("$this"));
		if (thisHolder != NULL) {
			zval copy;
			ZVAL_COPY(&copy, thisHolder);
			zend_hash_str_update(expressionTypes.table(), PT_LC("$this"), &copy);
		}

		return zv::Val(std::move(expressionTypes));
	}

	/* private; false = pending exception */
	[[nodiscard]] bool classHasCustomSerialization(bool &out)
	{
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return false;
		if (!inClass) {
			out = false;
			return true;
		}

		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasNativeMethod() on %s", zend_zval_value_name(classReflection.raw()));
			return false;
		}
		zend_object *reflection = Z_OBJ_P(classReflection.raw());
		/* self::CUSTOM_SERIALIZATION_METHODS */
		static const char *const methodNames[] = { "__sleep", "__serialize", "__unserialize" };
		for (const char *methodName : methodNames) {
			bool has;
			if (UNEXPECTED(!hasNativeMethod(reflection, methodName, has))) return false;
			if (has) {
				out = true;
				return true;
			}
		}

		zval interfaceName;
		ZVAL_STR(&interfaceName, zend_ce_serializable->name);
		zv::Val implements = pt_type_call(reflection, PT_LC("implementsinterface"), 1, &interfaceName);
		if (UNEXPECTED(implements.isUndef())) return false;
		if (!zend_is_true(implements.raw())) {
			out = false;
			return true;
		}
		return hasNativeMethod(reflection, "unserialize", out);
	}

	static bool hasNativeMethod(zend_object *classReflection, const char *methodName, bool &out)
	{
		zval name;
		ZVAL_STRING(&name, methodName);
		zv::Val result = pt_type_call(classReflection, PT_LC("hasnativemethod"), 1, &name);
		zval_ptr_dtor(&name);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	zv::Val rememberConstructorScope()
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, false, false))) return zv::Val();
		a.setNull(CreateArgs::FUNCTION);
		zv::Val expressionTypes = rememberConstructorExpressions(slot(PT_MS_PROP_EXPRESSION_TYPES));
		if (UNEXPECTED(expressionTypes.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, std::move(expressionTypes));
		zv::Val nativeExpressionTypes = rememberConstructorExpressions(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES));
		if (UNEXPECTED(nativeExpressionTypes.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, std::move(nativeExpressionTypes));
		a.set(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slot(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		return scopeFactoryCreate(a);
	}

	/** @internal called by ScopeOps; false = pending exception */
	[[nodiscard]] bool isReadonlyPropertyFetch(zend_object *exprObject, bool allowOnlyOnThis, bool &out)
	{
		zv::Ref phpVersion = slot(PT_MS_PROP_PHP_VERSION);
		if (UNEXPECTED(!phpVersion.isObject())) {
			(void) uninitializedProperty("phpVersion");
			return false;
		}
		zv::Val supports = pt_type_call(phpVersion.asObject(), PT_LC("supportsreadonlyproperties"), 0, NULL);
		if (UNEXPECTED(supports.isUndef())) return false;
		if (!zend_is_true(supports.raw())) {
			out = false;
			return true;
		}

		zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(propertyFetchCe == NULL || variableCe == NULL || identifierCe == NULL)) return false;
		/* the loop variable owns each $expr = $expr->var step */
		zval exprZv;
		ZVAL_OBJ_COPY(&exprZv, exprObject);
		zv::Val expr = zv::Val::adopt(exprZv);
		while (Z_TYPE_P(expr.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(expr.raw()), propertyFetchCe)) {
			zend_object *fetch = Z_OBJ_P(expr.raw());
			zv::Ref var = nodeProp(fetch, PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL)) return false;
			var = var.deref();
			if (var.instanceOf(variableCe)) {
				if (allowOnlyOnThis) {
					zv::Ref name = nodeProp(fetch, PT_LC("name"));
					if (UNEXPECTED(name.raw() == NULL)) return false;
					zv::Ref varName = nodeProp(var.asObject(), PT_LC("name"));
					if (UNEXPECTED(varName.raw() == NULL)) return false;
					varName = varName.deref();
					if (!name.deref().instanceOf(identifierCe) || !varName.isString() || !varName.stringEquals("this")) {
						out = false;
						return true;
					}
				}
			} else if (!var.instanceOf(propertyFetchCe)) {
				out = false;
				return true;
			}

			zv::Ref finder = slot(PT_MS_PROP_PROPERTY_REFLECTION_FINDER);
			if (UNEXPECTED(!finder.isObject())) {
				(void) uninitializedProperty("propertyReflectionFinder");
				return false;
			}
			zv::Args args{expr.raw(), self};
			zv::Val propertyReflection = pt_type_call(finder.asObject(), PT_LC("findpropertyreflectionfromnode"), 2, args);
			if (UNEXPECTED(propertyReflection.isUndef())) return false;
			if (propertyReflection.isNull()) {
				out = false;
				return true;
			}
			if (UNEXPECTED(Z_TYPE_P(propertyReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getNativeReflection() on %s", zend_zval_value_name(propertyReflection.raw()));
				return false;
			}

			zv::Val nativePropertyReflection = pt_type_call(Z_OBJ_P(propertyReflection.raw()), PT_LC("getnativereflection"), 0, NULL);
			if (UNEXPECTED(nativePropertyReflection.isUndef())) return false;
			if (nativePropertyReflection.isNull()) {
				out = false;
				return true;
			}
			if (UNEXPECTED(Z_TYPE_P(nativePropertyReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isReadOnly() on %s", zend_zval_value_name(nativePropertyReflection.raw()));
				return false;
			}
			zv::Val isReadOnly = pt_type_call(Z_OBJ_P(nativePropertyReflection.raw()), PT_LC("isreadonly"), 0, NULL);
			if (UNEXPECTED(isReadOnly.isUndef())) return false;
			if (!zend_is_true(isReadOnly.raw())) {
				out = false;
				return true;
			}

			expr = zv::Val::copyOf(var);
		}

		out = true;
		return true;
	}

	/* false = pending exception */
	[[nodiscard]] bool isInClass(bool &out)
	{
		zv::Val classReflection = contextClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return false;
		out = !classReflection.isNull();
		return true;
	}

	bool isInTrait(bool &out)
	{
		zv::Val traitReflection = contextTraitReflection();
		if (UNEXPECTED(traitReflection.isUndef())) return false;
		out = !traitReflection.isNull();
		return true;
	}

	zv::Val getClassReflection() { return contextClassReflection(); }
	zv::Val getTraitReflection() { return contextTraitReflection(); }
	zv::Val getFunction() const { return copyOfSlot(PT_MS_PROP_FUNCTION); }

	zv::Val getFunctionName()
	{
		zv::Ref function = slot(PT_MS_PROP_FUNCTION);
		if (function.isNull()) return zv::Val::null();
		if (UNEXPECTED(!function.isObject())) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(function.raw()));
			return zv::Val();
		}
		return pt_type_call(function.asObject(), PT_LC("getname"), 0, NULL);
	}

	zv::Val getNamespace() const { return copyOfSlot(PT_MS_PROP_NAMESPACE); }
	zv::Val getParentScope() const { return copyOfSlot(PT_MS_PROP_PARENT_SCOPE); }

	/* false = pending exception */
	[[nodiscard]] bool canAnyVariableExist(bool &out)
	{
		/* ($this->function === null && !$this->isInAnonymousFunction()) || $this->afterExtractCall */
		if (slot(PT_MS_PROP_FUNCTION).isNull()) {
			bool inAnonymousFunction;
			if (UNEXPECTED(!thisIsInAnonymousFunction(inAnonymousFunction))) return false;
			if (!inAnonymousFunction) {
				out = true;
				return true;
			}
		}
		out = slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL);
		return true;
	}

	zv::Val afterExtractCall()
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, true);
		return scopeFactoryCreate(a);
	}

	/* the tables with $exprString dropped from both; the twin's
	 * `$expressionTypes = $this->expressionTypes; unset(...)` copy-on-write
	 * pair */
	struct TablePair
	{
		zv::Arr expressionTypes;
		zv::Arr nativeExpressionTypes;
		bool changed = false;

		explicit TablePair(const MutatingScope &scope)
			: expressionTypes(zv::Arr::copyOfTable(Z_ARRVAL_P(scope.slot(PT_MS_PROP_EXPRESSION_TYPES).raw()))),
			nativeExpressionTypes(zv::Arr::copyOfTable(Z_ARRVAL_P(scope.slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw())))
		{
		}

		void unset(zend_string *key)
		{
			expressionTypes.separate();
			zend_symtable_del(expressionTypes.table(), key);
			nativeExpressionTypes.separate();
			zend_symtable_del(nativeExpressionTypes.table(), key);
		}

		bool existsInEither(const char *key, size_t len)
		{
			return zend_hash_str_exists(expressionTypes.table(), key, len)
				|| zend_hash_str_exists(nativeExpressionTypes.table(), key, len);
		}
	};

	/* create(...) with the twin's "everything from $this, the tables
	 * replaced" argument list; consumes the pair's tables */
	zv::Val createWithTables(TablePair &tables)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(tables.expressionTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(tables.nativeExpressionTypes)));
		return scopeFactoryCreate(a);
	}

	zv::Val afterClearstatcacheCall()
	{
		/* list from https://www.php.net/manual/en/function.clearstatcache.php */
		static const char *const functionNames[] = {
			"stat", "lstat", "file_exists", "is_writable", "is_writeable", "is_readable", "is_executable", "is_file", "is_dir", "is_link",
			"filectime", "fileatime", "filemtime", "fileinode", "filegroup", "fileowner", "filesize", "filetype", "fileperms",
		};
		TablePair tables(*this);
		/* foreach (array_keys($expressionTypes) as $exprString): the keys of
		 * the copy are the keys of the slot's table, walked there */
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_EXPRESSION_TYPES).raw())) {
			zend_string *exprString = entry.stringKeyOrNull();
			if (exprString == NULL) {
				/* an integer key never starts with a function name */
				continue;
			}
			for (const char *functionName : functionNames) {
				size_t len = strlen(functionName);
				const char *s = ZSTR_VAL(exprString);
				size_t n = ZSTR_LEN(exprString);
				bool plain = n > len && memcmp(s, functionName, len) == 0 && s[len] == '(';
				bool qualified = n > len + 1 && s[0] == '\\' && memcmp(s + 1, functionName, len) == 0 && s[len + 1] == '(';
				if (!plain && !qualified) continue;
				tables.unset(exprString);
				tables.changed = true;
				break;
			}
		}

		if (!tables.changed) return self_();
		return createWithTables(tables);
	}

	zv::Val afterOpenSslCall(zend_string *openSslFunctionName)
	{
		TablePair tables(*this);

		if (!tables.existsInEither(PT_LC("\\openssl_error_string()"))) return self_();

		static const char *const invalidating[] = {
			"openssl_cipher_iv_length", "openssl_cms_decrypt", "openssl_cms_encrypt", "openssl_cms_read", "openssl_cms_sign", "openssl_cms_verify",
			"openssl_csr_export_to_file", "openssl_csr_export", "openssl_csr_get_public_key", "openssl_csr_get_subject", "openssl_csr_new", "openssl_csr_sign",
			"openssl_decrypt", "openssl_dh_compute_key", "openssl_digest", "openssl_encrypt", "openssl_get_curve_names", "openssl_get_privatekey",
			"openssl_get_publickey", "openssl_open", "openssl_pbkdf2", "openssl_pkcs12_export_to_file", "openssl_pkcs12_export", "openssl_pkcs12_read",
			"openssl_pkcs7_decrypt", "openssl_pkcs7_encrypt", "openssl_pkcs7_read", "openssl_pkcs7_sign", "openssl_pkcs7_verify", "openssl_pkey_derive",
			"openssl_pkey_export_to_file", "openssl_pkey_export", "openssl_pkey_get_private", "openssl_pkey_get_public", "openssl_pkey_new",
			"openssl_private_decrypt", "openssl_private_encrypt", "openssl_public_decrypt", "openssl_public_encrypt", "openssl_random_pseudo_bytes",
			"openssl_seal", "openssl_sign", "openssl_spki_export_challenge", "openssl_spki_export", "openssl_spki_new", "openssl_spki_verify",
			"openssl_verify", "openssl_x509_checkpurpose", "openssl_x509_export_to_file", "openssl_x509_export", "openssl_x509_fingerprint",
			"openssl_x509_read", "openssl_x509_verify",
		};
		for (const char *name : invalidating) {
			if (zend_string_equals_cstr(openSslFunctionName, name, strlen(name))) {
				zend_string *key = zend_string_init(PT_LC("\\openssl_error_string()"), 0);
				tables.unset(key);
				zend_string_release(key);
				tables.changed = true;
				break;
			}
		}

		if (!tables.changed) return self_();
		return createWithTables(tables);
	}

	/* {{{ VolatileExpressionHelper::<method>(...): the twin passes the
	 * two tables by reference — fresh references around the pair's
	 * arrays, read back after the call; -1 = pending exception, else the
	 * bool result */
	int volatileHelperCall(const char *lcname, size_t len, TablePair &tables, uint32_t extraArgc, zval *extraArgv, bool withThis)
	{
		zval argv[5];
		uint32_t argc = 0;
		if (withThis) {
			ZVAL_OBJ(&argv[argc++], self);
		}
		zval exprRef, nativeRef;
		ZVAL_NEW_REF(&exprRef, tables.expressionTypes.raw());
		ZVAL_UNDEF(tables.expressionTypes.raw());
		ZVAL_NEW_REF(&nativeRef, tables.nativeExpressionTypes.raw());
		ZVAL_UNDEF(tables.nativeExpressionTypes.raw());
		ZVAL_COPY_VALUE(&argv[argc++], &exprRef);
		ZVAL_COPY_VALUE(&argv[argc++], &nativeRef);
		for (uint32_t i = 0; i < extraArgc; i++) {
			ZVAL_COPY_VALUE(&argv[argc++], &extraArgv[i]);
		}
		zv::Val result = pt_type_call_static_ce(pt_ce_volatile_expression_helper, lcname, len, argc, argv);
		/* the tables as the helper left them (unwrapped, the references dropped) */
		zval *exprInner = Z_REFVAL(exprRef);
		zval *nativeInner = Z_REFVAL(nativeRef);
		Z_TRY_ADDREF_P(exprInner);
		Z_TRY_ADDREF_P(nativeInner);
		tables.expressionTypes = zv::Arr::adoptVal(zv::Val::adopt(*exprInner));
		tables.nativeExpressionTypes = zv::Arr::adoptVal(zv::Val::adopt(*nativeInner));
		zval_ptr_dtor(&exprRef);
		zval_ptr_dtor(&nativeRef);
		if (UNEXPECTED(result.isUndef())) return -1;
		return zend_is_true(result.raw()) ? 1 : 0;
	}
	/* }}} */

	zv::Val invalidateVolatileExpressions()
	{
		TablePair tables(*this);

		int changed = volatileHelperCall(PT_LC("invalidatevolatilefunctioncalls"), tables, 0, NULL, false);
		if (UNEXPECTED(changed < 0)) return zv::Val();
		int superglobals = volatileHelperCall(PT_LC("invalidatesuperglobals"), tables, 0, NULL, false);
		if (UNEXPECTED(superglobals < 0)) return zv::Val();
		changed = superglobals || changed;
		int existence = volatileHelperCall(PT_LC("invalidatenegativeexistencechecks"), tables, 0, NULL, true);
		if (UNEXPECTED(existence < 0)) return zv::Val();
		changed = existence || changed;

		if (!changed) return self_();
		return createWithTables(tables);
	}

	zv::Val invalidateExistenceCheckExpressions(zval *functionNames, zval *declaredSymbolName)
	{
		TablePair tables(*this);

		zv::Args extra{functionNames, declaredSymbolName};
		int changed = volatileHelperCall(PT_LC("invalidatenegativeexistencechecks"), tables, 2, extra, true);
		if (UNEXPECTED(changed < 0)) return zv::Val();
		if (!changed) return self_();
		return createWithTables(tables);
	}

	zv::Val hasVariableType(zend_string *variableName) { return pt_scope_ops_has_variable_type(thisZval(), variableName); }

	zv::Val getVariableType(zend_string *variableName)
	{
		zval nameZv;
		ZVAL_STR(&nameZv, variableName);
		zv::Val hasVariableTypeResult = thisHasVariableType(&nameZv);
		if (UNEXPECTED(hasVariableTypeResult.isUndef())) return zv::Val();
		zend_long hasVariableType = pt_type_trinary_value(hasVariableTypeResult.raw());
		if (UNEXPECTED(hasVariableType < 0)) return zv::Val();

		if (hasVariableType == PT_TRI_MAYBE) {
			if (zend_string_equals_literal(variableName, "argc")) return pt_static_type_factory_argc();
			if (zend_string_equals_literal(variableName, "argv")) return pt_static_type_factory_argv();
			bool canAnyVariableExist;
			if (UNEXPECTED(!thisCanAnyVariableExist(canAnyVariableExist))) return zv::Val();
			if (canAnyVariableExist) return newMixedType(false);
		}

		if (hasVariableType == PT_TRI_NO) {
			zv::Args args{self, variableName};
			zv::Val exception = pt_type_new(PT_CLASS_UNDEFINED_VARIABLE_EXCEPTION, 2, args);
			if (UNEXPECTED(exception.isUndef())) return zv::Val();
			zval exceptionZv = exception.take();
			zend_throw_exception_object(&exceptionZv);
			return zv::Val();
		}

		zv::Str varExprString = zv::Str::adopt(zend_strpprintf(0, "$%s", ZSTR_VAL(variableName)));
		zval *holder = zend_symtable_find(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), varExprString.get());
		if (holder == NULL) {
			if (isGlobalVariable(variableName)) {
				/* new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true)) */
				zval integer, string, mixed, benevolent, array;
				if (UNEXPECTED(!pt_integer_type_new(&integer))) return zv::Val();
				if (UNEXPECTED(!pt_string_type_new(&string))) {
					zval_ptr_dtor(&integer);
					return zv::Val();
				}
				zv::Arr members = zv::Arr::create(2);
				members.push(zv::Val::adopt(integer));
				members.push(zv::Val::adopt(string));
				if (UNEXPECTED(!pt_benevolent_union_type_new(&benevolent, members.raw()))) return zv::Val();
				zv::Val benevolentVal = zv::Val::adopt(benevolent);
				if (UNEXPECTED(!pt_mixed_type_new(&mixed, true))) return zv::Val();
				zv::Val mixedVal = zv::Val::adopt(mixed);
				if (UNEXPECTED(!pt_array_type_new(&array, benevolentVal.raw(), mixedVal.raw()))) return zv::Val();
				return zv::Val::adopt(array);
			}
			return newMixedType(false);
		}

		return holderType(zv::Ref(holder));
	}

	static zv::Val newMixedType(bool isExplicitMixed)
	{
		zval mixed;
		if (UNEXPECTED(!pt_mixed_type_new(&mixed, isExplicitMixed))) return zv::Val();
		return zv::Val::adopt(mixed);
	}

	/* getDefinedVariables() (certainty yes) / getMaybeDefinedVariables()
	 * (certainty maybe): one body, parametrized by the certainty value */
	zv::Val definedVariables(zend_long certainty)
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return zv::Val();
		zv::Arr variables = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_EXPRESSION_TYPES).raw())) {
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			if (!expr.ref().instanceOf(variableCe)) continue;
			zend_long holderCertaintyValue = holderCertainty(entry.value());
			if (UNEXPECTED(holderCertaintyValue < 0)) return zv::Val();
			if (holderCertaintyValue != certainty) continue;

			/* substr($exprString, 1) */
			zend_string *exprString = entry.stringKeyOrNull();
			zv::Str owned;
			if (exprString == NULL) {
				owned = zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
				exprString = owned.get();
			}
			variables.push(zv::Val::adoptString(zend_string_init(ZSTR_VAL(exprString) + 1, ZSTR_LEN(exprString) - 1, 0)));
		}

		return zv::Val(std::move(variables));
	}

	zv::Val getDefinedVariables() { return definedVariables(PT_TRI_YES); }
	zv::Val getMaybeDefinedVariables() { return definedVariables(PT_TRI_MAYBE); }

	/* {{{ findPossiblyImpureCallDescriptions(): the NodeFinder::findFirst()
	 * walks natively (pt_find_first_recursive visits the same pre-order),
	 * the filter closure's `$this->getNodeKey($node) === $key` in the
	 * matcher; the walker's failure flag lives in the embedded pt_find_ctx */
	struct KeyMatchCtx
	{
		pt_find_ctx base;
		MutatingScope *scope;
		zend_class_entry *exprCe;
		zend_string *key;
	};

	static bool keyMatcher(zend_object *node, void *vctx)
	{
		KeyMatchCtx *ctx = (KeyMatchCtx *) vctx;
		if (!instanceof_function(node->ce, ctx->exprCe)) return false;
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		zv::Val key = ctx->scope->thisGetNodeKey(&nodeZv);
		if (UNEXPECTED(key.isUndef())) {
			ctx->base.failed = true;
			return false;
		}
		return Z_TYPE_P(key.raw()) == IS_STRING && zend_string_equals(Z_STR_P(key.raw()), ctx->key);
	}

	/* $nodeFinder->findFirst([$expr], fn => getNodeKey($node) === $key);
	 * NULL for no match — with `failed` set when an exception is pending */
	zend_object *findFirstByKey(zend_object *expr, zend_string *key, bool &failed)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) {
			failed = true;
			return NULL;
		}
		KeyMatchCtx ctx;
		memset(&ctx, 0, sizeof(ctx));
		ctx.scope = this;
		ctx.exprCe = exprCe;
		ctx.key = key;
		zend_object *found = pt_find_first_recursive(expr, keyMatcher, &ctx);
		failed = ctx.base.failed;
		return found;
	}

	/* array_values(array_unique($descriptions)) for a list of strings */
	static zv::Val uniqueValues(zv::Arr &descriptions)
	{
		zv::Arr result = zv::Arr::create(descriptions.arrRef().size());
		zv::ScratchTable seen(descriptions.arrRef().size());
		for (auto entry : descriptions.arrRef()) {
			zend_string *description = Z_STR_P(entry.value().raw());
			if (zend_hash_exists(seen.table(), description)) continue;
			zval marker;
			ZVAL_TRUE(&marker);
			zend_hash_add_new(seen.table(), description, &marker);
			result.push(entry.value());
		}
		return zv::Val(std::move(result));
	}

	/* the key of $holderExpr->callExpr / ->impactedExpr through
	 * $this->getNodeKey(); UNDEF = pending exception */
	zv::Val impureExprKey(zend_object *holderExpr, const char *prop, size_t len)
	{
		zv::Ref expr = nodeProp(holderExpr, prop, len);
		if (UNEXPECTED(expr.raw() == NULL)) return zv::Val();
		return thisGetNodeKey(expr.deref().raw());
	}

	/* the string of a getNodeKey() result (an Error when a subclass
	 * returned something else) */
	static zend_string *keyString(zv::Val &key)
	{
		if (UNEXPECTED(Z_TYPE_P(key.raw()) != IS_STRING)) {
			zend_type_error("MutatingScope::getNodeKey(): Return value must be of type string, %s returned", zend_zval_value_name(key.raw()));
			return NULL;
		}
		return Z_STR_P(key.raw());
	}

	zv::Val findPossiblyImpureCallDescriptions(zend_object *expr)
	{
		zend_class_entry *impureCe = pt_class(PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR);
		if (UNEXPECTED(impureCe == NULL)) return zv::Val();
		zv::Arr callExprDescriptions = zv::Arr::create(0);
		bool foundCallExprMatch = false;
		zv::ScratchTable matchedCallExprKeys(0);
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_EXPRESSION_TYPES).raw())) {
			zv::Val holderExprVal = holderExpr(entry.value());
			if (UNEXPECTED(holderExprVal.isUndef())) return zv::Val();
			if (!holderExprVal.ref().instanceOf(impureCe)) continue;
			zend_object *holderExprObject = Z_OBJ_P(holderExprVal.raw());

			zv::Val callExprKeyVal = impureExprKey(holderExprObject, PT_LC("callExpr"));
			if (UNEXPECTED(callExprKeyVal.isUndef())) return zv::Val();
			zend_string *callExprKey = keyString(callExprKeyVal);
			if (UNEXPECTED(callExprKey == NULL)) return zv::Val();

			bool failed;
			zend_object *found = findFirstByKey(expr, callExprKey, failed);
			if (UNEXPECTED(failed)) return zv::Val();
			if (found == NULL) continue;

			foundCallExprMatch = true;
			zval marker;
			ZVAL_TRUE(&marker);
			zend_symtable_update(matchedCallExprKeys.table(), callExprKey, &marker);

			/* Only show the tip when the scope's type for the call expression
			 * differs from the declared return type, meaning control flow
			 * narrowing affected the type (the cached value was narrowed). */
			zval foundZv;
			ZVAL_OBJ(&foundZv, found);
			zv::Val scopeType = thisGetType(&foundZv);
			if (UNEXPECTED(scopeType.isUndef())) return zv::Val();
			zv::Val declaredReturnType = holderType(entry.value());
			if (UNEXPECTED(declaredReturnType.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(scopeType.raw()) != IS_OBJECT || Z_TYPE_P(declaredReturnType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on a non-object");
				return zv::Val();
			}
			bool declaredAccepts, scopeAccepts;
			if (UNEXPECTED(!isSuperTypeOfYes(Z_OBJ_P(declaredReturnType.raw()), scopeType.raw(), declaredAccepts))) return zv::Val();
			if (declaredAccepts) {
				if (UNEXPECTED(!isSuperTypeOfYes(Z_OBJ_P(scopeType.raw()), declaredReturnType.raw(), scopeAccepts))) return zv::Val();
				if (scopeAccepts) continue;
			}

			zv::Val description = pt_type_call(holderExprObject, PT_LC("getcalldescription"), 0, NULL);
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			callExprDescriptions.push(std::move(description));
		}

		/* If the first pass found a callExpr in the error expression but
		 * filtered it out (return type wasn't narrowed), the error is
		 * explained by the return type alone - skip the fallback. */
		if (foundCallExprMatch && callExprDescriptions.arrRef().size() == 0) return pt_op_empty_array();

		/* Second pass: match by impactedExpr for cases where a maybe-impure method
		 * on an object didn't invalidate it, but a different method's return
		 * value was narrowed on that object.
		 * Skip when the expression itself is a direct method/static call -
		 * those are passed by ImpossibleCheckType rules where the error is
		 * about the call's arguments, not about object state. */
		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		zend_class_entry *staticCallCe = pt_class(PT_CLASS_STATIC_CALL);
		if (UNEXPECTED(methodCallCe == NULL || staticCallCe == NULL)) return zv::Val();
		if (!(instanceof_function(expr->ce, methodCallCe) || instanceof_function(expr->ce, staticCallCe))) {
			zv::Arr impactedExprDescriptions = zv::Arr::create(0);
			for (auto entry : zv::ArrRef(slot(PT_MS_PROP_EXPRESSION_TYPES).raw())) {
				zv::Val holderExprVal = holderExpr(entry.value());
				if (UNEXPECTED(holderExprVal.isUndef())) return zv::Val();
				if (!holderExprVal.ref().instanceOf(impureCe)) continue;
				zend_object *holderExprObject = Z_OBJ_P(holderExprVal.raw());

				zv::Val impactedExprKeyVal = impureExprKey(holderExprObject, PT_LC("impactedExpr"));
				if (UNEXPECTED(impactedExprKeyVal.isUndef())) return zv::Val();
				zend_string *impactedExprKey = keyString(impactedExprKeyVal);
				if (UNEXPECTED(impactedExprKey == NULL)) return zv::Val();

				/* Skip if impactedExpr is the same as callExpr (function calls) */
				zv::Val callExprKeyVal = impureExprKey(holderExprObject, PT_LC("callExpr"));
				if (UNEXPECTED(callExprKeyVal.isUndef())) return zv::Val();
				zend_string *callExprKey = keyString(callExprKeyVal);
				if (UNEXPECTED(callExprKey == NULL)) return zv::Val();
				if (zend_string_equals(impactedExprKey, callExprKey)) continue;

				/* Skip if this entry's callExpr was already matched in the first pass
				 * ($callExprKey = $this->getNodeKey($holderExpr->callExpr) again) */
				zv::Val callExprKeyAgain = impureExprKey(holderExprObject, PT_LC("callExpr"));
				if (UNEXPECTED(callExprKeyAgain.isUndef())) return zv::Val();
				zend_string *callExprKey2 = keyString(callExprKeyAgain);
				if (UNEXPECTED(callExprKey2 == NULL)) return zv::Val();
				if (zend_symtable_exists(matchedCallExprKeys.table(), callExprKey2)) continue;

				bool failed;
				zend_object *found = findFirstByKey(expr, impactedExprKey, failed);
				if (UNEXPECTED(failed)) return zv::Val();
				if (found == NULL) continue;

				zv::Val description = pt_type_call(holderExprObject, PT_LC("getcalldescription"), 0, NULL);
				if (UNEXPECTED(description.isUndef())) return zv::Val();
				impactedExprDescriptions.push(std::move(description));
			}

			/* Prefer impactedExpr matches (intermediate calls that could have
			 * invalidated the object) over callExpr matches */
			if (impactedExprDescriptions.arrRef().size() > 0) return uniqueValues(impactedExprDescriptions);
		}

		if (callExprDescriptions.arrRef().size() > 0) return uniqueValues(callExprDescriptions);

		return pt_op_empty_array();
	}

	/* $a->isSuperTypeOf($b)->yes(); false = pending exception */
	[[nodiscard]] static bool isSuperTypeOfYes(zend_object *a, zval *b, bool &out)
	{
		zv::Val result = pt_type_op(a, PT_OP_IS_SUPER_TYPE_OF, 1, b);
		if (UNEXPECTED(result.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(result.raw()));
			return false;
		}
		zv::Val yes = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("yes"), 0, NULL);
		if (UNEXPECTED(yes.isUndef())) return false;
		out = zend_is_true(yes.raw());
		return true;
	}

	/* }}} */

	/* private */
	static bool isGlobalVariable(zend_string *variableName) { return pt_is_superglobal_name(variableName); }

	/* false = pending exception */
	[[nodiscard]] bool hasConstant(zend_object *name, bool &out)
	{
		zv::Val nameString = pt_type_call(name, PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(nameString.isUndef())) return false;
		bool isCompilerHaltOffset = Z_TYPE_P(nameString.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(nameString.raw()), "__COMPILER_HALT_OFFSET__");
		if (isCompilerHaltOffset) return fileHasCompilerHaltStatementCalls(out);

		zv::Val globalConstantType = getGlobalConstantType(name);
		if (UNEXPECTED(globalConstantType.isUndef())) return false;
		if (!globalConstantType.isNull()) {
			out = true;
			return true;
		}

		zv::Ref reflectionProvider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
		if (UNEXPECTED(!reflectionProvider.isObject())) {
			(void) uninitializedProperty("reflectionProvider");
			return false;
		}
		zv::Args args{name, self};
		zv::Val result = pt_type_call(reflectionProvider.asObject(), PT_LC("hasconstant"), 2, args);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* private; false = pending exception */
	[[nodiscard]] bool fileHasCompilerHaltStatementCalls(bool &out)
	{
		zv::Ref parser = slot(PT_MS_PROP_PARSER);
		if (UNEXPECTED(!parser.isObject())) {
			(void) uninitializedProperty("parser");
			return false;
		}
		zv::Val file = thisGetFile();
		if (UNEXPECTED(file.isUndef())) return false;
		zv::Val nodes = pt_type_call(parser.asObject(), PT_LC("parsefile"), 1, file.raw());
		if (UNEXPECTED(nodes.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nodes.raw()) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(nodes.raw()));
			return false;
		}
		for (auto entry : zv::ArrRef(nodes.raw())) {
			bool isHaltCompiler;
			if (UNEXPECTED(!isInstance(entry.value().deref(), PT_CLASS_HALT_COMPILER, isHaltCompiler))) return false;
			if (isHaltCompiler) {
				out = true;
				return true;
			}
		}

		out = false;
		return true;
	}

	bool isInAnonymousFunction() const { return !slot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION).isNull(); }
	zv::Val getAnonymousFunctionReflection() const { return copyOfSlot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION); }

	zv::Val getAnonymousFunctionReturnType()
	{
		zv::Ref reflection = slot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION);
		if (reflection.isNull()) return zv::Val::null();
		if (UNEXPECTED(!reflection.isObject())) {
			zend_throw_error(NULL, "Call to a member function getReturnType() on %s", zend_zval_value_name(reflection.raw()));
			return zv::Val();
		}
		return pt_type_call(reflection.asObject(), PT_LC("getreturntype"), 0, NULL);
	}

	zv::Val withAnonymousFunctionReflection(zval *anonymousFunctionReflection)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.set(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, zv::Ref(anonymousFunctionReflection));
		return scopeFactoryCreate(a);
	}

	/* {{{ the type resolution core (twin 1054–1808) */

	/* {{{ collaborator reads shared by the type resolution core */

	/* $this->expressionResultStorageStack->getCurrent(); UNDEF = pending
	 * exception, else null or the storage */
	zv::Val currentStorage()
	{
		zv::Ref stack = slot(PT_MS_PROP_EXPRESSION_RESULT_STORAGE_STACK);
		if (UNEXPECTED(!stack.isObject())) return uninitializedProperty("expressionResultStorageStack");
		return pt_expression_result_storage_stack_current(stack.raw());
	}

	/* $storage->findExpressionResult($node) for the (non-null) result of
	 * currentStorage(); UNDEF = pending exception, else null or the result */
	static zv::Val storageFind(zv::Val &storage, zend_object *node)
	{
		if (UNEXPECTED(Z_TYPE_P(storage.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function findExpressionResult() on %s", zend_zval_value_name(storage.raw()));
			return zv::Val();
		}
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		return pt_expression_result_storage_find(storage.raw(), &nodeZv);
	}

	/* $storage !== null ? $storage->duplicate() : new ExpressionResultStorage() */
	static zv::Val onDemandStorage(zv::Val &storage)
	{
		if (storage.isNull()) return pt_expression_result_storage_new();
		if (UNEXPECTED(Z_TYPE_P(storage.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function duplicate() on %s", zend_zval_value_name(storage.raw()));
			return zv::Val();
		}
		return pt_expression_result_storage_duplicate(storage.raw());
	}

	/* $this->container->getByType($className); UNDEF = pending exception */
	zv::Val containerGetByType(const char *className, size_t len)
	{
		zv::Ref container = slot(PT_MS_PROP_CONTAINER);
		if (UNEXPECTED(!container.isObject())) return uninitializedProperty("container");
		zval name;
		ZVAL_STRINGL(&name, className, len);
		zv::Val service = pt_type_call(container.asObject(), PT_LC("getbytype"), 1, &name);
		zval_ptr_dtor(&name);
		return service;
	}

	/* the object of a getByType() / toWalkScope() result, an Error on
	 * anything else (a subclass returned a non-object) */
	static zend_object *requireObject(zv::Val &value, const char *methodName)
	{
		if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", methodName, zend_zval_value_name(value.raw()));
			return NULL;
		}
		return Z_OBJ_P(value.raw());
	}

	/* $this->container->getByType(NodeScopeResolver::class)->processExprOnDemand($node, $scope, $storage) */
	zv::Val processExprOnDemand(zend_object *node, zval *scope, zv::Val storage)
	{
		zv::Val resolver = containerGetByType(PT_LC("PHPStan\\Analyser\\NodeScopeResolver"));
		if (UNEXPECTED(resolver.isUndef())) return zv::Val();
		zend_object *resolverObject = requireObject(resolver, "processExprOnDemand");
		if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		return pt_node_scope_resolver_process_expr_on_demand(resolver.raw(), &nodeZv, scope, storage.raw());
	}

	/* $scope->nativeTypesPromoted of any scope object (the walk scope a
	 * subclass's toWalkScope() returns need not be this class): the slot
	 * of a native scope, the property table of anything else; false =
	 * pending exception */
	static bool scopeNativeTypesPromoted(zend_object *scope, bool &out)
	{
		if (pt_ce_mutating_scope != NULL && instanceof_function(scope->ce, pt_ce_mutating_scope)) {
			zv::Ref value = zv::ObjRef(scope).propAt(PT_MS_PROP_NATIVE_TYPES_PROMOTED);
			if (UNEXPECTED(value.isUndef())) {
				(void) uninitializedProperty("nativeTypesPromoted");
				return false;
			}
			out = value.isTrue();
			return true;
		}
		zv::Ref value = zv::ObjRef(scope).prop(PT_LC("nativeTypesPromoted"));
		if (UNEXPECTED(value.raw() == NULL)) {
			zend_error(E_WARNING, "Undefined property: %s::$nativeTypesPromoted", ZSTR_VAL(scope->ce->name));
			out = false;
			return EG(exception) == NULL;
		}
		if (UNEXPECTED(value.isUndef())) {
			zend_throw_error(NULL, "Typed property %s::$nativeTypesPromoted must not be accessed before initialization", ZSTR_VAL(scope->ce->name));
			return false;
		}
		out = zend_is_true(value.deref().raw());
		return true;
	}

	/* $result->getTypeOnScope($scope, $scope->nativeTypesPromoted) — the
	 * property read again at the call, as the twin spells it */
	static zv::Val typeOnScope(zend_object *result, zval *scope)
	{
		bool promoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(Z_OBJ_P(scope), promoted))) return zv::Val();
		zv::Args args{scope, promoted};
		return pt_type_call(result, PT_LC("gettypeonscope"), 2, args);
	}

	/* a promoted slot the constructor never wrote: the twin's Error */
	bool requireSlot(uint32_t index, const char *name)
	{
		if (UNEXPECTED(slot(index).isUndef())) {
			(void) uninitializedProperty(name);
			return false;
		}
		return true;
	}

	/* $holder->getExpr() instanceof PossiblyImpureCallExpr; false = pending exception */
	[[nodiscard]] static bool holderExprIsPossiblyImpureCall(zv::Ref holder, bool &out)
	{
		zv::Val expr = holderExpr(holder);
		if (UNEXPECTED(expr.isUndef())) return false;
		return isInstance(expr.ref(), PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR, out);
	}

	/* $a->equals($b): the native holders' body when both are native, the
	 * method of anything else; false = pending exception */
	[[nodiscard]] static bool holderEquals(zv::Ref a, zv::Ref b, bool &out)
	{
		a = a.deref();
		b = b.deref();
		if (UNEXPECTED(!a.isObject())) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(a.raw()));
			return false;
		}
		if (EXPECTED(a.asObject()->ce == pt_ce_expr_type_holder && b.isObject() && b.asObject()->ce == pt_ce_expr_type_holder)) {
			return pt_holder_equals(a.raw(), b.raw(), &out);
		}
		zv::Val result = pt_type_call(a.asObject(), PT_LC("equals"), 1, b.raw());
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $a === $b for two objects (false for anything else, as === is) */
	static bool sameObject(zv::Ref a, zv::Ref b)
	{
		a = a.deref();
		b = b.deref();
		return a.isObject() && b.isObject() && a.asObject() == b.asObject();
	}

	/* $table[$key] for the key of a foreach entry over another table (a
	 * string key is never numeric in a PHP array — plain hash lookup) */
	static zval *findByEntryKey(HashTable *table, const zv::ArrayEntry &entry)
	{
		zend_string *key = entry.stringKeyOrNull();
		return key != NULL ? zend_hash_find(table, key) : zend_hash_index_find(table, entry.indexKey());
	}

	/* isset($table[$key]) for such a key */
	static bool issetByEntryKey(HashTable *table, const zv::ArrayEntry &entry)
	{
		zval *found = findByEntryKey(table, entry);
		if (found == NULL) return false;
		ZVAL_DEREF(found);
		return Z_TYPE_P(found) != IS_NULL;
	}

	/* $table[$key] = $value / unset($table[$key]) for such a key */
	static void updateByEntryKey(HashTable *table, const zv::ArrayEntry &entry, zval *value)
	{
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			zend_hash_update(table, key, value);
		} else {
			zend_hash_index_update(table, entry.indexKey(), value);
		}
	}

	static void deleteByEntryKey(HashTable *table, const zv::ArrayEntry &entry)
	{
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			zend_hash_del(table, key);
		} else {
			zend_hash_index_del(table, entry.indexKey());
		}
	}

	/* the string of a foreach key handed to a string parameter of a
	 * private static helper: an integer key is the twin's strict_types
	 * TypeError */
	static zend_string *entryKeyString(const zv::ArrayEntry &entry, const char *method, const char *parameter)
	{
		zend_string *key = entry.stringKeyOrNull();
		if (UNEXPECTED(key == NULL)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::%s(): Argument #1 ($%s) must be of type string, int given", method, parameter);
			return NULL;
		}
		return key;
	}

	/* $array === ['static'] / $array === [$className]: one entry at key 0
	 * holding that string */
	static bool isSingleStringList(HashTable *array, const char *value, size_t len)
	{
		if (zend_hash_num_elements(array) != 1) return false;
		zval *first = zend_hash_index_find(array, 0);
		return first != NULL && Z_TYPE_P(first) == IS_STRING && zend_string_equals_cstr(Z_STR_P(first), value, len);
	}

	/* $this->inClosureBindScopeClasses[0] as a string return value: the
	 * twin's undefined-offset warning then the return-type TypeError when
	 * the entry is missing; NULL = pending exception */
	[[nodiscard]] static zend_string *firstBindScopeClass(HashTable *array, const char *method)
	{
		zval *first = zend_hash_index_find(array, 0);
		if (UNEXPECTED(first == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (EG(exception)) return NULL;
			zend_type_error("PHPStan\\Analyser\\MutatingScope::%s(): Return value must be of type string, null returned", method);
			return NULL;
		}
		ZVAL_DEREF(first);
		if (UNEXPECTED(Z_TYPE_P(first) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::%s(): Return value must be of type string, %s returned", method, zend_zval_value_name(first));
			return NULL;
		}
		return Z_STR_P(first);
	}

	/* $expr instanceof Variable && is_string($expr->name); false = pending exception */
	[[nodiscard]] static bool isVariableWithStringName(zend_object *expr, bool &out)
	{
		zend_class_entry *variableCe = pt_class_loaded(PT_CLASS_VARIABLE);
		if (variableCe == NULL) {
			out = false;
			return EG(exception) == NULL;
		}
		if (!instanceof_function(expr->ce, variableCe)) {
			out = false;
			return true;
		}
		zv::Ref name = nodeProp(expr, PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return false;
		out = name.deref().isString();
		return true;
	}

	/* $expr->name instanceof <class-map class> (Identifier / VarLikeIdentifier) */
	static bool nodeNameIs(zend_object *expr, int classIdx, bool &out)
	{
		zv::Ref name = nodeProp(expr, PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return false;
		return isInstance(name.deref(), classIdx, out);
	}

	/* !$call->isFirstClassCallable() && $call->getArgs() === []; false = pending exception */
	[[nodiscard]] static bool isArgumentLessPlainCall(zend_object *call, bool &out)
	{
		zv::Val fcc = pt_type_call(call, PT_LC("isfirstclasscallable"), 0, NULL);
		if (UNEXPECTED(fcc.isUndef())) return false;
		if (zend_is_true(fcc.raw())) {
			out = false;
			return true;
		}
		zv::Val args = pt_type_call(call, PT_LC("getargs"), 0, NULL);
		if (UNEXPECTED(args.isUndef())) return false;
		out = Z_TYPE_P(args.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(args.raw())) == 0;
		return true;
	}

	/* throw new ShouldNotHappenException(sprintf('...%s on line %d...', get_class($node), $node->getStartLine())) */
	static void throwUnprocessedNode(zend_object *node, const char *format)
	{
		zv::Val line = pt_type_call(node, PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(line.isUndef())) return;
		zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (UNEXPECTED(ce == NULL)) return;
		zend_throw_exception_ex(ce, 0, format, ZSTR_VAL(node->ce->name), (int) zval_get_long(line.raw()));
	}

	/* }}} */

	/* {{{ the NodeScopeResolver::$guard* diagnostics getType() and
	 * obtainResultForNode() read */

	/* NodeScopeResolver::$<name>; NULL = pending exception */
	[[nodiscard]] static zval *guardStatic(const char *name, size_t len)
	{
		zend_class_entry *ce = pt_ce_node_scope_resolver;
		if (UNEXPECTED(ce == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: PHPStan\\Analyser\\NodeScopeResolver is not activated");
			return NULL;
		}
		zval *value = zend_read_static_property(ce, name, len, 0);
		if (UNEXPECTED(value == NULL)) return NULL;
		ZVAL_DEREF(value);
		return value;
	}

	/* isset(NodeScopeResolver::$<name>[$id]); false = pending exception */
	[[nodiscard]] static bool guardIdIsSet(const char *name, size_t len, zend_ulong id, bool &out)
	{
		zval *table = guardStatic(name, len);
		if (UNEXPECTED(table == NULL)) return false;
		out = false;
		if (Z_TYPE_P(table) == IS_ARRAY) {
			zval *found = zend_hash_index_find(Z_ARRVAL_P(table), id);
			if (found != NULL) {
				ZVAL_DEREF(found);
				out = Z_TYPE_P(found) != IS_NULL;
			}
		}
		return true;
	}

	/* NodeScopeResolver::$guardNewWorld && isset($guardRealExprIds[id]) &&
	 * !isset($guardProcessedExprIds[id]); false = pending exception */
	[[nodiscard]] static bool guardFires(zend_object *node, bool &fires)
	{
		fires = false;
		zval *flag = guardStatic(PT_LC("guardNewWorld"));
		if (UNEXPECTED(flag == NULL)) return false;
		if (!zend_is_true(flag)) return true;
		bool real;
		if (UNEXPECTED(!guardIdIsSet(PT_LC("guardRealExprIds"), node->handle, real))) return false;
		if (!real) return true;
		bool processed;
		if (UNEXPECTED(!guardIdIsSet(PT_LC("guardProcessedExprIds"), node->handle, processed))) return false;
		fires = !processed;
		return true;
	}

	/* the nodes getType()'s guard exempts: a variable read is scope state
	 * and a literal is a constant */
	static bool isVariableOrLiteral(zend_object *node, bool &out)
	{
		if (UNEXPECTED(!isVariableWithStringName(node, out))) return false;
		if (out) return true;
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		static const int literalClasses[] = { PT_CLASS_SCALAR_STRING, PT_CLASS_SCALAR_INT, PT_CLASS_SCALAR_FLOAT };
		for (int classIdx : literalClasses) {
			if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), classIdx, out))) return false;
			if (out) return true;
		}
		return true;
	}

	/* }}} */

	/** @api */
	zv::Val getType(zend_object *node)
	{
		bool fires;
		if (UNEXPECTED(!guardFires(node, fires))) return zv::Val();
		if (UNEXPECTED(fires)) {
			bool exempt;
			if (UNEXPECTED(!isVariableOrLiteral(node, exempt))) return zv::Val();
			if (!exempt) {
				throwUnprocessedNode(node, "getType() asked about non-synthetic %s on line %d before it was processed by processExprNode() - it should consume the node's ExpressionResult instead.");
				return zv::Val();
			}
		}

		zend_string *keyRaw = NULL;
		zv::Val cached = pt_scope_ops_get_type_from_cache(thisZval(), node, &keyRaw);
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		zv::Str key = zv::Str::adopt(keyRaw);
		if (!cached.isNull()) return cached;

		zv::Val resolved = resolveType(key.get(), node);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		zv::Val type = pt_type_utils_resolve_late_resolvable_types(resolved.raw());
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		/* $this->resolvedTypes[$key] = $type */
		zval *table = OBJ_PROP_NUM(self, PT_MS_PROP_RESOLVED_TYPES);
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zend_throw_error(NULL, "Cannot use a scalar value as an array");
			return zv::Val();
		}
		SEPARATE_ARRAY(table);
		zval copy;
		ZVAL_COPY(&copy, type.raw());
		zend_symtable_update(Z_ARRVAL_P(table), key.get(), &copy);
		return type;
	}

	zv::Val getScopeType(zval *expr) { return thisGetType(expr); }

	zv::Val getScopeNativeType(zval *expr) { return thisGetNativeType(expr); }

	/* getNodeKey() 1093 and getExprPrinter() 1099: below, out of the twin's file order */

	/** @internal called by ScopeOps */
	zv::Val duplicateWith(zval *expressionTypes, zval *nativeExpressionTypes, zval *conditionalExpressions, zval *currentlyAssignedExpressions, zval *currentlyAllowedUndefinedExpressions, zval *inFunctionCallsStack, bool inFirstLevelStatement, bool afterExtractCall)
	{
		CreateArgs a;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONTEXT, "context"))) return zv::Val();
		a.set(CreateArgs::CONTEXT, slot(PT_MS_PROP_CONTEXT));
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.set(CreateArgs::EXPRESSION_TYPES, zv::Ref(expressionTypes));
		a.set(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Ref(nativeExpressionTypes));
		a.set(CreateArgs::CONDITIONAL_EXPRESSIONS, zv::Ref(conditionalExpressions));
		static const struct { uint32_t slot; uint32_t arg; const char *name; } fromSlots[] = {
			{ PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses" },
			{ PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION, CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, "anonymousFunctionReflection" },
		};
		for (const auto &entry : fromSlots) {
			if (UNEXPECTED(!requireSlot(entry.slot, entry.name))) return zv::Val();
			a.set(entry.arg, slot(entry.slot));
		}
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, inFirstLevelStatement);
		a.set(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS, zv::Ref(currentlyAssignedExpressions));
		a.set(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, zv::Ref(currentlyAllowedUndefinedExpressions));
		a.set(CreateArgs::IN_FUNCTION_CALLS_STACK, zv::Ref(inFunctionCallsStack));
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, afterExtractCall);
		static const struct { uint32_t slot; uint32_t arg; const char *name; } tailFromSlots[] = {
			{ PT_MS_PROP_PARENT_SCOPE, CreateArgs::PARENT_SCOPE, "parentScope" },
			{ PT_MS_PROP_NATIVE_TYPES_PROMOTED, CreateArgs::NATIVE_TYPES_PROMOTED, "nativeTypesPromoted" },
			{ PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME, CreateArgs::TEMPLATE_ARGUMENT_FRAME, "templateArgumentFrame" },
			{ PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints" },
		};
		for (const auto &entry : tailFromSlots) {
			if (UNEXPECTED(!requireSlot(entry.slot, entry.name))) return zv::Val();
			a.set(entry.arg, slot(entry.slot));
		}
		return scopeFactoryCreate(a);
	}

	/* $relevantRoots: NULL for null */
	zv::Val getClosureScopeCacheKey(zval *relevantRoots)
	{
		zval *cacheLevel = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_CACHE);
		if (UNEXPECTED(cacheLevel == NULL)) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		/* $parts, joined by "\n" as they are collected (implode) */
		smart_str parts = {};
		bool first = true;
		auto separate = [&]() {
			if (!first) {
				smart_str_appendc(&parts, '\n');
			}
			first = false;
		};
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_EXPRESSION_TYPES).raw())) {
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) {
				smart_str_free(&parts);
				return zv::Val();
			}
			bool isVirtual;
			if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_VIRTUAL_NODE, isVirtual))) {
				smart_str_free(&parts);
				return zv::Val();
			}
			if (isVirtual) continue;
			zend_string *exprString = entry.stringKeyOrNull();
			if (relevantRoots != NULL) {
				if (UNEXPECTED(exprString == NULL)) {
					entryKeyString(entry, "exprStringIsRootedIn", "exprString");
					smart_str_free(&parts);
					return zv::Val();
				}
				bool rooted;
				if (UNEXPECTED(!exprStringIsRootedIn(exprString, Z_ARRVAL_P(relevantRoots), rooted))) {
					smart_str_free(&parts);
					return zv::Val();
				}
				if (!rooted) continue;
			}
			zv::Val type = holderType(entry.value());
			if (UNEXPECTED(type.isUndef())) {
				smart_str_free(&parts);
				return zv::Val();
			}
			zv::Val description = describeAt(type, cacheLevel);
			if (UNEXPECTED(description.isUndef())) {
				smart_str_free(&parts);
				return zv::Val();
			}
			separate();
			if (exprString != NULL) {
				smart_str_append(&parts, exprString);
			} else {
				smart_str_append_long(&parts, (zend_long) entry.indexKey());
			}
			smart_str_appendl(&parts, "::", 2);
			smart_str_append(&parts, Z_STR_P(description.raw()));
		}
		separate();
		smart_str_appendl(&parts, "---", 3);

		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) {
			smart_str_free(&parts);
			return zv::Val();
		}
		zv::ArrRef stack(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw());
		separate();
		smart_str_appendc(&parts, ':');
		smart_str_append_long(&parts, (zend_long) stack.size());
		for (auto entry : stack) {
			/* [, $parameter] */
			zv::Ref pair = entry.value().deref();
			zval *parameter = NULL;
			if (pair.isArray()) {
				parameter = zend_hash_index_find(Z_ARRVAL_P(pair.raw()), 1);
				if (parameter == NULL) {
					zend_error(E_WARNING, "Undefined array key 1");
					if (UNEXPECTED(EG(exception))) {
						smart_str_free(&parts);
						return zv::Val();
					}
				}
			}
			separate();
			if (parameter == NULL || Z_TYPE_P(parameter) == IS_NULL) {
				smart_str_appendl(&parts, ",null", 5);
				continue;
			}
			ZVAL_DEREF(parameter);
			if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(parameter));
				smart_str_free(&parts);
				return zv::Val();
			}
			zv::Val type = pt_type_call(Z_OBJ_P(parameter), PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(type.isUndef())) {
				smart_str_free(&parts);
				return zv::Val();
			}
			zv::Val description = describeAt(type, cacheLevel);
			if (UNEXPECTED(description.isUndef())) {
				smart_str_free(&parts);
				return zv::Val();
			}
			smart_str_appendc(&parts, ',');
			smart_str_append(&parts, Z_STR_P(description.raw()));
		}
		smart_str_0(&parts);

		/* md5(implode("\n", $parts)) */
		PHP_MD5_CTX context;
		unsigned char digest[16];
		char hex[33];
		PHP_MD5Init(&context);
		if (parts.s != NULL) {
			PHP_MD5Update(&context, (const unsigned char *) ZSTR_VAL(parts.s), ZSTR_LEN(parts.s));
		}
		PHP_MD5Final(digest, &context);
		make_digest_ex(hex, digest, sizeof(digest));
		smart_str_free(&parts);
		return zv::Val::string(hex, 32);
	}

	/* $type->describe($level) as a string; UNDEF = pending exception */
	static zv::Val describeAt(zv::Val &type, zval *level)
	{
		if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function describe() on %s", zend_zval_value_name(type.raw()));
			return zv::Val();
		}
		zv::Val description = pt_type_op(Z_OBJ_P(type.raw()), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(description.raw()) != IS_STRING)) {
			zend_string *converted = zval_get_string(description.raw());
			if (UNEXPECTED(EG(exception))) {
				zend_string_release(converted);
				return zv::Val();
			}
			return zv::Val::adoptString(converted);
		}
		return description;
	}

	/* private static; false = pending exception */
	[[nodiscard]] static bool exprStringIsRootedIn(zend_string *exprString, HashTable *roots, bool &out)
	{
		for (auto entry : zv::TableRef(roots)) {
			zv::Ref root = entry.value().deref();
			if (root.isString() && zend_string_equals(exprString, root.asString())) {
				out = true;
				return true;
			}
			if (UNEXPECTED(!root.isString())) {
				zend_type_error("str_starts_with(): Argument #2 ($needle) must be of type string, %s given", zend_zval_value_name(root.raw()));
				return false;
			}
			zend_string *rootString = root.asString();
			if (ZSTR_LEN(exprString) < ZSTR_LEN(rootString) || memcmp(ZSTR_VAL(exprString), ZSTR_VAL(rootString), ZSTR_LEN(rootString)) != 0) continue;
			/* $exprString[strlen($root)] — the strings differ, so the
			 * expression is strictly longer */
			unsigned char next = (unsigned char) ZSTR_VAL(exprString)[ZSTR_LEN(rootString)];
			if (next != '_' && !isalnum(next)) {
				out = true;
				return true;
			}
		}
		out = false;
		return true;
	}

	/* private */
	zv::Val resolveType(zend_string *exprString, zend_object *node)
	{
		zv::Ref extensions = slot(PT_MS_PROP_EXPRESSION_TYPE_RESOLVER_EXTENSIONS);
		if (UNEXPECTED(!extensions.isObject())) return uninitializedProperty("expressionTypeResolverExtensions");
		zv::Val all = pt_extensions_collection_get_all(extensions.asObject());
		if (UNEXPECTED(all.isUndef())) return zv::Val();
		if (EXPECTED(Z_TYPE_P(all.raw()) == IS_ARRAY)) {
			zv::Args args{node, self};
			for (auto entry : zv::ArrRef(all.raw())) {
				zv::Ref extension = entry.value().deref();
				if (UNEXPECTED(!extension.isObject())) {
					zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(extension.raw()));
					return zv::Val();
				}
				zv::Val type = pt_type_call(extension.asObject(), PT_LC("gettype"), 2, args);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (!type.isNull()) return type;
			}
		} else {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(all.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}

		zv::Val expressionType = pt_scope_ops_expression_type_by_key(thisZval(), node, exprString);
		if (UNEXPECTED(expressionType.isUndef())) return zv::Val();
		if (!expressionType.isNull()) return expressionType;

		/* NodeScopeResolver intercepts a first-class callable CallLike before
		 * the ExprHandler dispatch - no handler supports the original node,
		 * its closure type lives on the stored result's typeCallback */
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		bool isCallLike;
		if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), PT_CLASS_CALL_LIKE, isCallLike))) return zv::Val();
		if (isCallLike) {
			zv::Val fcc = pt_type_call(node, PT_LC("isfirstclasscallable"), 0, NULL);
			if (UNEXPECTED(fcc.isUndef())) return zv::Val();
			if (zend_is_true(fcc.raw())) return resolveTypeOfNewWorldHandlerNode(node);
		}

		zv::Ref container = slot(PT_MS_PROP_CONTAINER);
		if (UNEXPECTED(!container.isObject())) return uninitializedProperty("container");
		zv::Val exprHandler = pt_expr_handler_registry_resolve(node, container.raw());
		if (UNEXPECTED(exprHandler.isUndef())) return zv::Val();
		if (!exprHandler.isNull()) return resolveTypeOfNewWorldHandlerNode(node);

		return pt_type_new_mixed_type();
	}

	/* private */
	zv::Val resolveTypeOfNewWorldHandlerNode(zend_object *node)
	{
		/* the hooks are the boundary between the rule-facing world and the
		 * engine - a rule's NodeCallbackScope must not flow into result
		 * callbacks or on-demand processing */
		zv::Val scope = thisToWalkScope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(requireObject(scope, "toWalkScope") == NULL)) return zv::Val();
		zv::Val storage = currentStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		bool counterfactualAsk = false;
		if (!storage.isNull()) {
			zv::Val result = storageFind(storage, node);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (!result.isNull()) {
				zend_object *resultObject = requireObject(result, "canResolveOwnType");
				if (UNEXPECTED(resultObject == NULL)) return zv::Val();
				zv::Val canResolve = pt_type_call(resultObject, PT_LC("canresolveowntype"), 0, NULL);
				if (UNEXPECTED(canResolve.isUndef())) return zv::Val();
				if (zend_is_true(canResolve.raw())) {
					/* a counterfactual ask must re-price the node on that
					 * scope - the memoized walk-position type answers a
					 * different question */
					bool promoted;
					if (UNEXPECTED(!scopeNativeTypesPromoted(Z_OBJ_P(scope.raw()), promoted))) return zv::Val();
					zv::Args args{scope.raw(), promoted};
					zv::Val matches = pt_type_call(resultObject, PT_LC("askscopevariablestatematches"), 2, args);
					if (UNEXPECTED(matches.isUndef())) return zv::Val();
					counterfactualAsk = !zend_is_true(matches.raw());
					if (!counterfactualAsk) return typeOnScope(resultObject, scope.raw());
				}
			}
		}

		/* A closure/arrow function type is computed directly - never by
		 * processing it on demand, which would re-enter
		 * ClosureHandler::processExpr() endlessly */
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		bool isClosure;
		if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), PT_CLASS_CLOSURE_EXPR, isClosure))) return zv::Val();
		if (!isClosure) {
			if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), PT_CLASS_ARROW_FUNCTION, isClosure))) return zv::Val();
		}
		if (isClosure) {
			zv::Val closureTypeResolver = containerGetByType(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver"));
			if (UNEXPECTED(closureTypeResolver.isUndef())) return zv::Val();
			zend_object *resolverObject = requireObject(closureTypeResolver, "getClosureType");
			if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
			zv::Args args{scope.raw(), node, false, storage.raw()};
			return pt_type_call(resolverObject, PT_LC("getclosuretype"), 4, args);
		}

		if (!counterfactualAsk && !storage.isNull()) {
			zv::Val stored = storageFind(storage, node);
			if (UNEXPECTED(stored.isUndef())) return zv::Val();
			if (!stored.isNull()) {
				zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
				if (ce != NULL) {
					zend_throw_exception_ex(ce, 0, "ExpressionResult of %s cannot resolve its own type (no eager type, no typeCallback).", ZSTR_VAL(node->ce->name));
				}
				return zv::Val();
			}
		}

		/* a synthetic node, or no analysis in progress */
		zv::Val resolver = containerGetByType(PT_LC("PHPStan\\Analyser\\NodeScopeResolver"));
		if (UNEXPECTED(resolver.isUndef())) return zv::Val();
		zend_object *resolverObject = requireObject(resolver, "processExprOnDemand");
		if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
		zv::Val onDemand = onDemandStorage(storage);
		if (UNEXPECTED(onDemand.isUndef())) return zv::Val();
		zval onDemandNode;
		ZVAL_OBJ(&onDemandNode, node);
		zv::Val onDemandResult = pt_node_scope_resolver_process_expr_on_demand(resolver.raw(), &onDemandNode, scope.raw(), onDemand.raw());
		if (UNEXPECTED(onDemandResult.isUndef())) return zv::Val();
		zend_object *onDemandObject = requireObject(onDemandResult, "getTypeOnScope");
		if (UNEXPECTED(onDemandObject == NULL)) return zv::Val();
		return typeOnScope(onDemandObject, scope.raw());
	}

	/* private; null (no analysis in progress) or [Type, Type] */
	zv::Val getCurrentTypesOfSpecifiedExpr(zend_object *expr)
	{
		zv::Val storage = currentStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (storage.isNull()) return zv::Val::null();

		zv::Val exprResult = storageFind(storage, expr);
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		bool narrowable;
		if (UNEXPECTED(!isNarrowableSpecifiedExpr(expr, narrowable))) return zv::Val();
		if (narrowable) {
			bool containsNullsafe = false;
			if (!exprResult.isNull()) {
				zend_object *resultObject = requireObject(exprResult, "containsNullsafe");
				if (UNEXPECTED(resultObject == NULL)) return zv::Val();
				zv::Val contains = pt_type_call(resultObject, PT_LC("containsnullsafe"), 0, NULL);
				if (UNEXPECTED(contains.isUndef())) return zv::Val();
				containsNullsafe = zend_is_true(contains.raw());
			}
			if (!containsNullsafe) {
				if (UNEXPECTED(!requireSlot(PT_MS_PROP_NATIVE_TYPES_PROMOTED, "nativeTypesPromoted"))) return zv::Val();
				zv::Val phpDoc = resolveScopeStateType(expr, slotBool(PT_MS_PROP_NATIVE_TYPES_PROMOTED));
				if (UNEXPECTED(phpDoc.isUndef())) return zv::Val();
				zv::Val native = resolveScopeStateType(expr, true);
				if (UNEXPECTED(native.isUndef())) return zv::Val();
				return typePair(std::move(phpDoc), std::move(native));
			}
		}

		if (exprResult.isNull()) {
			/* a call subject (or a synthetic plain-chain variant) is priced
			 * on demand: one walk answers both flavours */
			zv::Val scope = thisToWalkScope();
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			if (UNEXPECTED(requireObject(scope, "toWalkScope") == NULL)) return zv::Val();
			zv::Val duplicated = onDemandStorage(storage);
			if (UNEXPECTED(duplicated.isUndef())) return zv::Val();
			zv::Val result = processExprOnDemand(expr, scope.raw(), std::move(duplicated));
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_object *resultObject = requireObject(result, "getTypeOnScope");
			if (UNEXPECTED(resultObject == NULL)) return zv::Val();
			zv::Val phpDoc = typeOnScope(resultObject, scope.raw());
			if (UNEXPECTED(phpDoc.isUndef())) return zv::Val();
			zv::Args args{scope.raw(), true};
			zv::Val native = pt_type_call(resultObject, PT_LC("gettypeonscope"), 2, args);
			if (UNEXPECTED(native.isUndef())) return zv::Val();
			return typePair(std::move(phpDoc), std::move(native));
		}

		/* a type tracked for the whole expression on the asking scope wins
		 * over the stored result's own type */
		zend_object *resultObject = requireObject(exprResult, "getTypeOnScope");
		if (UNEXPECTED(resultObject == NULL)) return zv::Val();
		zv::Val phpDoc = typeOnScope(resultObject, thisZval());
		if (UNEXPECTED(phpDoc.isUndef())) return zv::Val();
		zv::Args args{self, true};
		zv::Val native = pt_type_call(resultObject, PT_LC("gettypeonscope"), 2, args);
		if (UNEXPECTED(native.isUndef())) return zv::Val();
		return typePair(std::move(phpDoc), std::move(native));
	}

	static zv::Val typePair(zv::Val phpDoc, zv::Val native)
	{
		zv::Arr pair = zv::Arr::create(2);
		pair.push(std::move(phpDoc));
		pair.push(std::move(native));
		return zv::Val(std::move(pair));
	}

	/* a variable read, property/offset fetch, or an argument-less instance
	 * call — the shapes whose scope-view type derives from tracked state */
	static bool isNarrowableSpecifiedExpr(zend_object *expr, bool &out)
	{
		if (UNEXPECTED(!isVariableWithStringName(expr, out))) return false;
		if (out) return true;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		static const int fetchClasses[] = { PT_CLASS_PROPERTY_FETCH, PT_CLASS_ARRAY_DIM_FETCH, PT_CLASS_STATIC_PROPERTY_FETCH };
		for (int classIdx : fetchClasses) {
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), classIdx, out))) return false;
			if (out) return true;
		}
		bool isMethodCall;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_METHOD_CALL, isMethodCall))) return false;
		if (!isMethodCall) {
			out = false;
			return true;
		}
		bool nameIsIdentifier;
		if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return false;
		if (!nameIsIdentifier) {
			out = false;
			return true;
		}
		return isArgumentLessPlainCall(expr, out);
	}

	/** @internal */
	zv::Val specifyTypesOfNewWorldHandlerNode(zend_object *node, zval *context)
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		zv::Val result = thisObtainResultForNode(&nodeZv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_object *resultObject = requireObject(result, "getSpecifiedTypesForScope");
		if (UNEXPECTED(resultObject == NULL)) return zv::Val();
		zv::Val scope = thisToWalkScope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Args args{scope.raw(), context};
		return pt_type_call(resultObject, PT_LC("getspecifiedtypesforscope"), 2, args);
	}

	zv::Val obtainResultForNode(zend_object *node)
	{
		zv::Val scope = thisToWalkScope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val storage = currentStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (!storage.isNull()) {
			zv::Val result = storageFind(storage, node);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (!result.isNull()) return result;
		}

		bool fires;
		if (UNEXPECTED(!guardFires(node, fires))) return zv::Val();
		if (UNEXPECTED(fires)) {
			throwUnprocessedNode(node, "obtainResultForNode() asked about non-synthetic %s on line %d before it was processed by processExprNode() - it should consume the node's ExpressionResult instead.");
			return zv::Val();
		}

		/* a synthetic node, or no analysis in progress */
		zv::Val resolver = containerGetByType(PT_LC("PHPStan\\Analyser\\NodeScopeResolver"));
		if (UNEXPECTED(resolver.isUndef())) return zv::Val();
		zend_object *resolverObject = requireObject(resolver, "processExprOnDemand");
		if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
		zv::Val onDemand = onDemandStorage(storage);
		if (UNEXPECTED(onDemand.isUndef())) return zv::Val();
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		return pt_node_scope_resolver_process_expr_on_demand(resolver.raw(), &nodeZv, scope.raw(), onDemand.raw());
	}

	/* false = pending exception */
	[[nodiscard]] bool pushExpressionResultStorage(zval *storage)
	{
		zv::Ref stack = slot(PT_MS_PROP_EXPRESSION_RESULT_STORAGE_STACK);
		if (UNEXPECTED(!stack.isObject())) {
			(void) uninitializedProperty("expressionResultStorageStack");
			return false;
		}
		return pt_expression_result_storage_stack_push(stack.raw(), storage);
	}

	bool popExpressionResultStorage()
	{
		zv::Ref stack = slot(PT_MS_PROP_EXPRESSION_RESULT_STORAGE_STACK);
		if (UNEXPECTED(!stack.isObject())) {
			(void) uninitializedProperty("expressionResultStorageStack");
			return false;
		}
		return pt_expression_result_storage_stack_pop(stack.raw());
	}

	/* protected: the settled stored result of the current storage -
	 * NodeCallbackScope's no-switch fast path */
	zv::Val findSettledStoredResult(zend_object *node)
	{
		zv::Val storage = currentStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (storage.isNull()) return zv::Val::null();
		return storageFind(storage, node);
	}

	zv::Val getCurrentExpressionResultStorage() { return currentStorage(); }

	/* $frame: IS_NULL or the frame */
	zv::Val withTemplateArgumentFrame(zval *frame)
	{
		zv::Val scope = thisWithoutMemoizedTypes();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(scope.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to assign property \"templateArgumentFrame\" on %s", zend_zval_value_name(scope.raw()));
			return zv::Val();
		}
		/* $scope->templateArgumentFrame = $frame — the engine write path
		 * from the scope's own class (the property is protected) */
		zend_object *scopeObject = Z_OBJ_P(scope.raw());
		zend_update_property(scopeObject->ce, scopeObject, PT_LC("templateArgumentFrame"), frame);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return scope;
	}

	zv::Val getCurrentTemplateArgumentFrame() const { return copyOfSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME); }

	zv::Val getTemplateArgumentConstraints() const { return copyOfSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS); }

	/* $constraints: IS_NULL or the constraints */
	zv::Val withTemplateArgumentConstraints(zval *constraints)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		zv::Ref current = slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS);
		bool same = Z_TYPE_P(constraints) == IS_NULL
			? current.isNull()
			: (current.isObject() && current.asObject() == Z_OBJ_P(constraints));
		if (same) return self_();
		/* $scope = clone $this */
		zend_object *clone = self->handlers->clone_obj(self);
		if (UNEXPECTED(EG(exception))) {
			if (clone != NULL) {
				OBJ_RELEASE(clone);
			}
			return zv::Val();
		}
		MutatingScope scope(clone);
		scope.writeSlot(PT_MS_PROP_NODE_CALLBACK_SCOPE, zv::Val::null());
		scope.writeSlot(PT_MS_PROP_SCOPE_OUT_OF_FIRST_LEVEL_STATEMENT, zv::Val::null());
		scope.writeSlot(PT_MS_PROP_SCOPE_WITH_PROMOTED_NATIVE_TYPES, zv::Val::null());
		scope.writeSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, zv::Val::copyOf(zv::Ref(constraints)));
		zval cloneZv;
		ZVAL_OBJ(&cloneZv, clone);
		return zv::Val::adopt(cloneZv);
	}

	/* Inference facts join independently of variable-state convergence and branch termination. */
	zv::Val addTemplateArgumentConstraints(zval *constraints)
	{
		if (Z_TYPE_P(constraints) == IS_NULL) return self_();
		zv::Val isEmpty = pt_type_call(Z_OBJ_P(constraints), PT_LC("isempty"), 0, NULL);
		if (UNEXPECTED(isEmpty.isUndef())) return zv::Val();
		if (zend_is_true(isEmpty.raw())) return self_();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		zv::Ref current = slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS);
		zv::Val merged;
		if (current.isNull()) {
			merged = zv::Val::copyOf(zv::Ref(constraints));
		} else {
			if (UNEXPECTED(!current.isObject())) {
				zend_throw_error(NULL, "Call to a member function merge() on %s", zend_zval_value_name(current.raw()));
				return zv::Val();
			}
			merged = pt_type_call(current.asObject(), PT_LC("merge"), 1, constraints);
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
		}
		return thisWithTemplateArgumentConstraints(merged.raw());
	}

	/* A copy of this scope without its memoized type answers. */
	zv::Val withoutMemoizedTypes()
	{
		static const struct { uint32_t slot; const char *name; } tables[] = {
			{ PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes" },
			{ PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes" },
			{ PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions" },
			{ PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions" },
			{ PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions" },
			{ PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack" },
			{ PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, "inFirstLevelStatement" },
			{ PT_MS_PROP_AFTER_EXTRACT_CALL, "afterExtractCall" },
		};
		zval args[8];
		for (uint32_t i = 0; i < 8; i++) {
			if (UNEXPECTED(!requireSlot(tables[i].slot, tables[i].name))) return zv::Val();
			ZVAL_COPY_VALUE(&args[i], slot(tables[i].slot).raw());
		}
		return thisDuplicateWith(args);
	}

	/* The variables rooting the tracked expressions whose state differs
	 * between this scope and $other; null when a differing entry has no
	 * variable root. $other is an instance of this class (a subclass
	 * included) — its slots are read directly. */
	zv::Val getDifferingVariableRoots(zend_object *otherObject)
	{
		MutatingScope other(otherObject);
		zv::Arr roots = zv::Arr::create(0);
		static const struct { uint32_t slot; const char *name; } tables[] = {
			{ PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes" },
			{ PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes" },
		};
		for (const auto &table : tables) {
			if (UNEXPECTED(!requireSlot(table.slot, table.name) || !other.requireSlot(table.slot, table.name))) return zv::Val();
			HashTable *ours = Z_ARRVAL_P(slot(table.slot).raw());
			HashTable *theirs = Z_ARRVAL_P(other.slot(table.slot).raw());
			for (auto entry : zv::TableRef(ours)) {
				bool impure;
				if (UNEXPECTED(!holderExprIsPossiblyImpureCall(entry.value(), impure))) return zv::Val();
				if (impure) continue;
				zval *theirHolder = findByEntryKey(theirs, entry);
				if (theirHolder != NULL && Z_TYPE_P(theirHolder) != IS_NULL) {
					bool equal = sameObject(entry.value(), zv::Ref(theirHolder));
					if (!equal && UNEXPECTED(!holderEquals(entry.value(), zv::Ref(theirHolder), equal))) return zv::Val();
					if (equal) continue;
				}
				bool noRoot;
				if (UNEXPECTED(!addVariableRoot(roots, entry, noRoot))) return zv::Val();
				if (noRoot) return zv::Val::null();
			}
			for (auto entry : zv::TableRef(theirs)) {
				if (issetByEntryKey(ours, entry)) continue;
				bool impure;
				if (UNEXPECTED(!holderExprIsPossiblyImpureCall(entry.value(), impure))) return zv::Val();
				if (impure) continue;
				bool noRoot;
				if (UNEXPECTED(!addVariableRoot(roots, entry, noRoot))) return zv::Val();
				if (noRoot) return zv::Val::null();
			}
		}

		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions") || !other.requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) {
			return zv::Val();
		}
		HashTable *ourConditionals = Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw());
		HashTable *theirConditionals = Z_ARRVAL_P(other.slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw());
		const struct { HashTable *ours; HashTable *theirs; } conditionalTables[] = {
			{ ourConditionals, theirConditionals },
			{ theirConditionals, ourConditionals },
		};
		for (const auto &table : conditionalTables) {
			for (auto entry : zv::TableRef(table.ours)) {
				zval *theirHolders = findByEntryKey(table.theirs, entry);
				if (theirHolders != NULL && Z_TYPE_P(theirHolders) != IS_NULL && zend_is_identical(theirHolders, entry.value().raw())) continue;
				zend_string *key = entryKeyString(entry, "getVariableRootOfExpressionKey", "key");
				if (UNEXPECTED(key == NULL)) return zv::Val();
				zv::Str root = getVariableRootOfExpressionKey(key);
				if (root.isNull()) {
					zv::Ref holders = entry.value().deref();
					if (UNEXPECTED(!holders.isArray())) {
						zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(holders.raw()));
						if (UNEXPECTED(EG(exception))) return zv::Val();
						continue;
					}
					for (auto holderEntry : zv::ArrRef(holders.raw())) {
						zv::Ref holder = holderEntry.value().deref();
						if (UNEXPECTED(!holder.isObject())) {
							zend_throw_error(NULL, "Call to a member function getTypeHolder() on %s", zend_zval_value_name(holder.raw()));
							return zv::Val();
						}
						zv::Val typeHolder = pt_type_call(holder.asObject(), PT_LC("gettypeholder"), 0, NULL);
						if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
						bool impure;
						if (UNEXPECTED(!holderExprIsPossiblyImpureCall(typeHolder.ref(), impure))) return zv::Val();
						if (!impure) return zv::Val::null();
					}
					continue;
				}
				roots.set(root.get(), zv::Val::boolean(true));
			}
		}

		/* array_keys($roots) */
		zv::Arr keys = zv::Arr::create(roots.arrRef().size());
		for (auto entry : roots.arrRef()) {
			keys.push(zv::Val::string(entry.stringKey()));
		}
		return zv::Val(std::move(keys));
	}

	/* $root = self::getVariableRootOfExpressionKey($key); `noRoot` when
	 * it is null (the caller returns null), else $roots[$root] = true;
	 * false = pending exception */
	[[nodiscard]] static bool addVariableRoot(zv::Arr &roots, const zv::ArrayEntry &entry, bool &noRoot)
	{
		zend_string *key = entryKeyString(entry, "getVariableRootOfExpressionKey", "key");
		if (UNEXPECTED(key == NULL)) return false;
		zv::Str root = getVariableRootOfExpressionKey(key);
		noRoot = root.isNull();
		if (!noRoot) {
			roots.set(root.get(), zv::Val::boolean(true));
		}
		return true;
	}

	/* private static: preg_match('/^\$([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)/'); NULL for no match */
	static zv::Str getVariableRootOfExpressionKey(zend_string *key)
	{
		const unsigned char *s = (const unsigned char *) ZSTR_VAL(key);
		size_t n = ZSTR_LEN(key);
		if (n < 2 || s[0] != '$') return zv::Str();
		unsigned char c = s[1];
		if (!((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || c == '_' || c >= 0x80)) return zv::Str();
		size_t end = 2;
		while (end < n) {
			c = s[end];
			if ((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9') || c == '_' || c >= 0x80) {
				end++;
			} else {
				break;
			}
		}
		return zv::Str::adopt(zend_string_init((const char *) s + 1, end - 1, 0));
	}

	/* This scope after a statement whose recorded walk stands. */
	zv::Val withRecordedStatementDelta(zend_object *recordedEntryObject, zend_object *recordedExitObject)
	{
		MutatingScope recordedEntry(recordedEntryObject);
		MutatingScope recordedExit(recordedExitObject);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !recordedExit.requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !recordedEntry.requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) {
			return zv::Val();
		}
		zv::Arr conditionalExpressions = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()));
		HashTable *entryConditionals = Z_ARRVAL_P(recordedEntry.slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw());
		HashTable *exitConditionals = Z_ARRVAL_P(recordedExit.slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw());
		for (auto entry : zv::TableRef(exitConditionals)) {
			zval *entryHolders = findByEntryKey(entryConditionals, entry);
			if (entryHolders != NULL && Z_TYPE_P(entryHolders) != IS_NULL && zend_is_identical(entryHolders, entry.value().raw())) continue;
			conditionalExpressions.separate();
			zval copy;
			ZVAL_COPY(&copy, entry.value().raw());
			updateByEntryKey(conditionalExpressions.table(), entry, &copy);
		}
		for (auto entry : zv::TableRef(entryConditionals)) {
			if (issetByEntryKey(exitConditionals, entry)) continue;
			conditionalExpressions.separate();
			deleteByEntryKey(conditionalExpressions.table(), entry);
		}

		static const struct { uint32_t slot; const char *name; } holderTables[] = {
			{ PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes" },
			{ PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes" },
		};
		zv::Val deltas[2];
		for (uint32_t i = 0; i < 2; i++) {
			if (UNEXPECTED(!requireSlot(holderTables[i].slot, holderTables[i].name)
				|| !recordedEntry.requireSlot(holderTables[i].slot, holderTables[i].name)
				|| !recordedExit.requireSlot(holderTables[i].slot, holderTables[i].name))) {
				return zv::Val();
			}
			deltas[i] = applyRecordedHolderDelta(slot(holderTables[i].slot), recordedEntry.slot(holderTables[i].slot), recordedExit.slot(holderTables[i].slot));
			if (UNEXPECTED(deltas[i].isUndef())) return zv::Val();
		}
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, "inFirstLevelStatement") || !recordedExit.requireSlot(PT_MS_PROP_AFTER_EXTRACT_CALL, "afterExtractCall"))) {
			return zv::Val();
		}

		zval args[8];
		ZVAL_COPY_VALUE(&args[0], deltas[0].raw());
		ZVAL_COPY_VALUE(&args[1], deltas[1].raw());
		ZVAL_COPY_VALUE(&args[2], conditionalExpressions.raw());
		ZVAL_EMPTY_ARRAY(&args[3]);
		ZVAL_EMPTY_ARRAY(&args[4]);
		ZVAL_EMPTY_ARRAY(&args[5]);
		ZVAL_BOOL(&args[6], slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		ZVAL_BOOL(&args[7], recordedExit.slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL));
		return thisDuplicateWith(args);
	}

	/* private static; the twin's by-value $current parameter: a copy of
	 * the table, written on first change */
	static zv::Val applyRecordedHolderDelta(zv::Ref current, zv::Ref recordedEntry, zv::Ref recordedExit)
	{
		zv::Arr result = zv::Arr::copyOfTable(Z_ARRVAL_P(current.raw()));
		HashTable *entryTable = Z_ARRVAL_P(recordedEntry.raw());
		HashTable *exitTable = Z_ARRVAL_P(recordedExit.raw());
		for (auto entry : zv::TableRef(exitTable)) {
			zval *entryHolder = findByEntryKey(entryTable, entry);
			if (entryHolder != NULL && Z_TYPE_P(entryHolder) != IS_NULL) {
				bool equal = sameObject(zv::Ref(entryHolder), entry.value());
				if (!equal && UNEXPECTED(!holderEquals(zv::Ref(entryHolder), entry.value(), equal))) return zv::Val();
				if (equal) continue;
			}
			result.separate();
			zval copy;
			ZVAL_COPY(&copy, entry.value().raw());
			updateByEntryKey(result.table(), entry, &copy);
		}
		for (auto entry : zv::TableRef(entryTable)) {
			if (issetByEntryKey(exitTable, entry)) continue;
			result.separate();
			deleteByEntryKey(result.table(), entry);
		}
		return zv::Val(std::move(result));
	}

	/** @api */
	zv::Val getNativeType(zval *expr)
	{
		zv::Val promoted = promoteNativeTypes();
		if (UNEXPECTED(promoted.isUndef())) return zv::Val();
		zend_object *promotedObject = requireObject(promoted, "getType");
		if (UNEXPECTED(promotedObject == NULL)) return zv::Val();
		return MutatingScope(promotedObject).thisGetType(expr);
	}

	zv::Val getKeepVoidType(zend_object *node)
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		/* !Match_ && !Yield_ && !YieldFrom && ((!FuncCall && !MethodCall &&
		 * !NullsafeMethodCall && !StaticCall) || isFirstClassCallable()) */
		bool plain = true;
		static const int valueClasses[] = { PT_CLASS_MATCH, PT_CLASS_YIELD, PT_CLASS_YIELD_FROM };
		for (int classIdx : valueClasses) {
			bool is;
			if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), classIdx, is))) return zv::Val();
			if (is) {
				plain = false;
				break;
			}
		}
		if (plain) {
			static const int callClasses[] = { PT_CLASS_FUNC_CALL, PT_CLASS_METHOD_CALL, PT_CLASS_NULLSAFE_METHOD_CALL, PT_CLASS_STATIC_CALL };
			bool isCall = false;
			for (int classIdx : callClasses) {
				if (UNEXPECTED(!isInstance(zv::Ref(&nodeZv), classIdx, isCall))) return zv::Val();
				if (isCall) break;
			}
			if (isCall) {
				zv::Val fcc = pt_type_call(node, PT_LC("isfirstclasscallable"), 0, NULL);
				if (UNEXPECTED(fcc.isUndef())) return zv::Val();
				plain = zend_is_true(fcc.raw());
			}
		}
		if (plain) return getScopeStateType(node);

		zv::Val originalType = getScopeStateType(node);
		if (UNEXPECTED(originalType.isUndef())) return zv::Val();
		bool containsNull;
		if (UNEXPECTED(!pt_type_combinator_contains_null(originalType.raw(), containsNull))) return zv::Val();
		if (!containsNull) return originalType;

		/* the null may be a projected void: read the call's/match's raw
		 * (void-kept) own type */
		zv::Val storage = currentStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		zv::Val result = zv::Val::null();
		if (!storage.isNull()) {
			result = storageFind(storage, node);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}
		if (result.isNull()) {
			zv::Val resolver = containerGetByType(PT_LC("PHPStan\\Analyser\\NodeScopeResolver"));
			if (UNEXPECTED(resolver.isUndef())) return zv::Val();
			zend_object *resolverObject = requireObject(resolver, "processExprOnDemand");
			if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
			zv::Val scope = thisToWalkScope();
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zv::Val onDemand = onDemandStorage(storage);
			if (UNEXPECTED(onDemand.isUndef())) return zv::Val();
			zval nodeZv;
			ZVAL_OBJ(&nodeZv, node);
			result = pt_node_scope_resolver_process_expr_on_demand(resolver.raw(), &nodeZv, scope.raw(), onDemand.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}
		zend_object *resultObject = requireObject(result, "getKeepVoidType");
		if (UNEXPECTED(resultObject == NULL)) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_NATIVE_TYPES_PROMOTED, "nativeTypesPromoted"))) return zv::Val();
		zval promoted;
		ZVAL_BOOL(&promoted, slotBool(PT_MS_PROP_NATIVE_TYPES_PROMOTED));
		return pt_type_call(resultObject, PT_LC("getkeepvoidtype"), 1, &promoted);
	}

	zv::Val doNotTreatPhpDocTypesAsCertain() { return promoteNativeTypes(); }

	/* private */
	zv::Val promoteNativeTypes()
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_NATIVE_TYPES_PROMOTED, "nativeTypesPromoted"))) return zv::Val();
		if (slotBool(PT_MS_PROP_NATIVE_TYPES_PROMOTED)) return self_();

		zv::Ref memo = slot(PT_MS_PROP_SCOPE_WITH_PROMOTED_NATIVE_TYPES);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		/* create($this->context, $this->declareStrictTypes, $this->function,
		 * $this->namespace, $this->nativeExpressionTypes, [], [], ...,
		 * nativeTypesPromoted: true) — the slots, not the dispatched getters */
		CreateArgs a;
		static const struct { uint32_t slot; uint32_t arg; const char *name; } fromSlots[] = {
			{ PT_MS_PROP_CONTEXT, CreateArgs::CONTEXT, "context" },
			{ PT_MS_PROP_DECLARE_STRICT_TYPES, CreateArgs::DECLARE_STRICT_TYPES, "declareStrictTypes" },
			{ PT_MS_PROP_FUNCTION, CreateArgs::FUNCTION, "function" },
			{ PT_MS_PROP_NAMESPACE, CreateArgs::NAMESPACE_, "namespace" },
			{ PT_MS_PROP_NATIVE_EXPRESSION_TYPES, CreateArgs::EXPRESSION_TYPES, "nativeExpressionTypes" },
			{ PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses" },
			{ PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION, CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, "anonymousFunctionReflection" },
			{ PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, CreateArgs::IN_FIRST_LEVEL_STATEMENT, "inFirstLevelStatement" },
			{ PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions" },
			{ PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions" },
			{ PT_MS_PROP_IN_FUNCTION_CALLS_STACK, CreateArgs::IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack" },
			{ PT_MS_PROP_AFTER_EXTRACT_CALL, CreateArgs::AFTER_EXTRACT_CALL, "afterExtractCall" },
			{ PT_MS_PROP_PARENT_SCOPE, CreateArgs::PARENT_SCOPE, "parentScope" },
			{ PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME, CreateArgs::TEMPLATE_ARGUMENT_FRAME, "templateArgumentFrame" },
			{ PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints" },
		};
		for (const auto &entry : fromSlots) {
			if (UNEXPECTED(!requireSlot(entry.slot, entry.name))) return zv::Val();
			a.set(entry.arg, slot(entry.slot));
		}
		a.setEmptyArray(CreateArgs::NATIVE_EXPRESSION_TYPES);
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, true);
		zv::Val created = scopeFactoryCreate(a);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		writeSlot(PT_MS_PROP_SCOPE_WITH_PROMOTED_NATIVE_TYPES, zv::Val::copyOf(created.ref()));
		return created;
	}

	/** @api */
	zv::Val resolveName(zend_object *name)
	{
		/* (string) $name */
		zval nameZv;
		ZVAL_OBJ(&nameZv, name);
		zv::Str originalClass = zv::Str::adopt(zval_get_string(&nameZv));
		if (UNEXPECTED(EG(exception))) return zv::Val();
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (inClass) {
			zv::Str lowerClass = zv::Str::adopt(zend_string_tolower(originalClass.get()));
			if (zend_string_equals_literal(lowerClass.get(), "self") || zend_string_equals_literal(lowerClass.get(), "static")) {
				if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) return zv::Val();
				HashTable *bindScopeClasses = Z_ARRVAL_P(slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES).raw());
				if (zend_hash_num_elements(bindScopeClasses) != 0 && !isSingleStringList(bindScopeClasses, PT_LC("static"))) {
					zend_string *first = firstBindScopeClass(bindScopeClasses, "resolveName");
					if (UNEXPECTED(first == NULL)) return zv::Val();
					return zv::Val::string(first);
				}
				zv::Val classReflection = thisGetClassReflection();
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				zend_object *reflection = requireObject(classReflection, "getName");
				if (UNEXPECTED(reflection == NULL)) return zv::Val();
				return pt_class_reflection_get_name(reflection);
			} else if (zend_string_equals_literal(lowerClass.get(), "parent")) {
				zv::Val classReflection = thisGetClassReflection();
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				zend_object *reflection = requireObject(classReflection, "getParentClass");
				if (UNEXPECTED(reflection == NULL)) return zv::Val();
				zv::Val parentClass = pt_type_call(reflection, PT_LC("getparentclass"), 0, NULL);
				if (UNEXPECTED(parentClass.isUndef())) return zv::Val();
				if (!parentClass.isNull()) {
					/* $currentClassReflection->getParentClass()->getName() — read again, as the twin does */
					parentClass = pt_type_call(reflection, PT_LC("getparentclass"), 0, NULL);
					if (UNEXPECTED(parentClass.isUndef())) return zv::Val();
					zend_object *parent = requireObject(parentClass, "getName");
					if (UNEXPECTED(parent == NULL)) return zv::Val();
					return pt_class_reflection_get_name(parent);
				}
			}
		}

		return zv::Val::string(originalClass.get());
	}

	/** @api */
	zv::Val resolveTypeByName(zend_object *name)
	{
		zv::Val lower = pt_type_call(name, PT_LC("tolowerstring"), 0, NULL);
		if (UNEXPECTED(lower.isUndef())) return zv::Val();
		if (Z_TYPE_P(lower.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(lower.raw()), "static")) {
			bool inClass;
			if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
			if (inClass) {
				if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) return zv::Val();
				HashTable *bindScopeClasses = Z_ARRVAL_P(slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES).raw());
				if (zend_hash_num_elements(bindScopeClasses) != 0 && !isSingleStringList(bindScopeClasses, PT_LC("static"))) {
					zval *first = zend_hash_index_find(bindScopeClasses, 0);
					zval nullZv;
					ZVAL_NULL(&nullZv);
					if (first == NULL) {
						zend_error(E_WARNING, "Undefined array key 0");
						if (UNEXPECTED(EG(exception))) return zv::Val();
						first = &nullZv;
					}
					zv::Ref provider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
					if (UNEXPECTED(!provider.isObject())) return uninitializedProperty("reflectionProvider");
					bool hasClass;
					if (UNEXPECTED(!pt_reflection_provider_has_class(provider.asObject(), first, hasClass))) return zv::Val();
					if (hasClass) {
						zv::Val classReflection = pt_reflection_provider_get_class(provider.asObject(), first);
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
						return newStaticType(classReflection, "StaticType");
					}
				}

				zv::Val classReflection = thisGetClassReflection();
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				return newStaticType(classReflection, "StaticType");
			}
		}

		zval nameZv;
		ZVAL_OBJ(&nameZv, name);
		zv::Val originalClass = thisResolveName(&nameZv);
		if (UNEXPECTED(originalClass.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(originalClass.raw()) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::resolveName(): Return value must be of type string, %s returned", zend_zval_value_name(originalClass.raw()));
			return zv::Val();
		}
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (inClass) {
			if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) return zv::Val();
			HashTable *bindScopeClasses = Z_ARRVAL_P(slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES).raw());
			if (isSingleStringList(bindScopeClasses, ZSTR_VAL(Z_STR_P(originalClass.raw())), ZSTR_LEN(Z_STR_P(originalClass.raw())))) {
				zv::Ref provider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
				if (UNEXPECTED(!provider.isObject())) return uninitializedProperty("reflectionProvider");
				bool hasClass;
				if (UNEXPECTED(!pt_reflection_provider_has_class(provider.asObject(), originalClass.raw(), hasClass))) return zv::Val();
				if (hasClass) {
					zv::Val classReflection = pt_reflection_provider_get_class(provider.asObject(), originalClass.raw());
					if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
					return newThisType(classReflection);
				}
				return pt_type_new_object_type(originalClass.raw());
			}

			zv::Val classReflection = thisGetClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			zv::Val thisType = newThisType(classReflection);
			if (UNEXPECTED(thisType.isUndef())) return zv::Val();
			zv::Val ancestor = pt_type_op(Z_OBJ_P(thisType.raw()), PT_OP_GET_ANCESTOR_WITH_CLASS_NAME, 1, originalClass.raw());
			if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
			if (!ancestor.isNull()) return ancestor;
		}

		return pt_type_new_object_type(originalClass.raw());
	}

	/* new StaticType($classReflection) / new ThisType($classReflection):
	 * the constructors' TypeError on anything but a ClassReflection */
	static zv::Val newStaticType(zv::Val &classReflection, const char *className)
	{
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Type\\%s::__construct(): Argument #1 ($classReflection) must be of type PHPStan\\Reflection\\ClassReflection, %s given", className, zend_zval_value_name(classReflection.raw()));
			return zv::Val();
		}
		zval out;
		if (UNEXPECTED(!pt_static_type_new(&out, classReflection.raw()))) return zv::Val();
		return zv::Val::adopt(out);
	}

	static zv::Val newThisType(zv::Val &classReflection)
	{
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Type\\ThisType::__construct(): Argument #1 ($classReflection) must be of type PHPStan\\Reflection\\ClassReflection, %s given", zend_zval_value_name(classReflection.raw()));
			return zv::Val();
		}
		zval out;
		if (UNEXPECTED(!pt_this_type_new(&out, classReflection.raw()))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/** @api */
	static zv::Val getTypeFromValue(zval *value) { return pt_constant_type_helper_get_type_from_value(value); }

	/* }}} */

	/* {{{ twin 1835-2011: the in-function-call stack, enterClass(),
	 * enterTrait() */

	/* array_pop($array): drops the last entry and gives back the auto-index
	 * it took (the twin's `$stack = $this->inFunctionCallsStack; array_pop($stack)`) */
	static void arrayPop(zv::Arr &array)
	{
		if (zend_hash_num_elements(array.table()) == 0) return;
		array.separate();
		HashTable *table = array.table();
		zend_string *lastKey = NULL;
		zend_ulong lastIndex = 0;
		for (auto entry : zv::TableRef(table)) {
			lastKey = entry.stringKeyOrNull();
			lastIndex = entry.indexKey();
		}
		if (lastKey != NULL) {
			zend_hash_del(table, lastKey);
			return;
		}
		if ((zend_long) lastIndex == table->nNextFreeElement - 1) {
			table->nNextFreeElement = (zend_long) lastIndex;
		}
		zend_hash_index_del(table, lastIndex);
	}

	/* $scope->resolvedTypes = $this->resolvedTypes — the public memo, written
	 * through the engine path from the result's own class (the factory
	 * answers with any MutatingScope); false = pending exception */
	[[nodiscard]] bool assignResolvedTypes(zv::Val &scope)
	{
		if (UNEXPECTED(Z_TYPE_P(scope.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to assign property \"resolvedTypes\" on %s", zend_zval_value_name(scope.raw()));
			return false;
		}
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_RESOLVED_TYPES, "resolvedTypes"))) return false;
		zend_object *object = Z_OBJ_P(scope.raw());
		zend_update_property(object->ce, object, PT_LC("resolvedTypes"), slot(PT_MS_PROP_RESOLVED_TYPES).raw());
		return EXPECTED(EG(exception) == NULL);
	}

	/* create(...) with everything from $this and the given call stack */
	zv::Val createWithFunctionCallStack(zv::Arr stack)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.setOwned(CreateArgs::IN_FUNCTION_CALLS_STACK, zv::Val(std::move(stack)));
		return scopeFactoryCreate(a);
	}

	/* $reflection: MethodReflection|FunctionReflection|null (untyped in the
	 * twin), $parameter: ParameterReflection|null */
	zv::Val pushInFunctionCall(zval *reflection, zval *parameter, bool rememberTypes)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) return zv::Val();
		zv::Arr stack = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw()));
		zv::Arr pair = zv::Arr::create(2);
		pair.push(zv::Ref(reflection));
		pair.push(zv::Ref(parameter));
		stack.push(zv::Val(std::move(pair)));

		zv::Val functionScope = createWithFunctionCallStack(std::move(stack));
		if (UNEXPECTED(functionScope.isUndef())) return zv::Val();
		if (rememberTypes) {
			if (UNEXPECTED(!assignResolvedTypes(functionScope))) return zv::Val();
		}
		return functionScope;
	}

	zv::Val popInFunctionCall()
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) return zv::Val();
		zv::Arr stack = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw()));
		arrayPop(stack);

		zv::Val parentScope = createWithFunctionCallStack(std::move(stack));
		if (UNEXPECTED(parentScope.isUndef())) return zv::Val();
		if (UNEXPECTED(!assignResolvedTypes(parentScope))) return zv::Val();
		return parentScope;
	}

	/* $this->inFunctionCallsStack entry's [0] — the reflection of the call,
	 * NULL when the entry carries none (the twin's list destructuring) */
	static zval *inFunctionCallReflection(zv::Ref entryValue)
	{
		zv::Ref item = entryValue.deref();
		if (!item.isArray()) return NULL;
		return zend_hash_index_find(item.asArrayTable(), 0);
	}

	/** @api; false = pending exception */
	[[nodiscard]] bool isInClassExists(zend_string *className, bool &out)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) return false;
		static const char *const classExistsFunctions[] = { "class_exists", "interface_exists", "trait_exists", "enum_exists" };
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw())) {
			zval *inFunctionCall = inFunctionCallReflection(entry.value());
			if (inFunctionCall == NULL) continue;
			zv::Ref reflection = zv::Ref(inFunctionCall).deref();
			bool isFunctionReflection;
			if (UNEXPECTED(!isInstance(reflection, PT_CLASS_FUNCTION_REFLECTION, isFunctionReflection))) return false;
			if (!isFunctionReflection) continue;
			zv::Val name = pt_type_call(reflection.asObject(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return false;
			for (const char *functionName : classExistsFunctions) {
				if (name.ref().isString() && zend_string_equals_cstr(name.ref().asString(), functionName, strlen(functionName))) {
					out = true;
					return true;
				}
			}
		}

		/* interface_exists() etc. imply class_exists() therefore not listed here */
		return existenceCheckIsTrue(PT_LC("class_exists"), className, out);
	}

	/** @api; false = pending exception */
	[[nodiscard]] bool isInFunctionExists(zend_string *functionName, bool &out)
	{
		return existenceCheckIsTrue(PT_LC("function_exists"), functionName, out);
	}

	/* $this->getType(new FuncCall(new FullyQualified($check), [new Arg(new
	 * String_(ltrim($name, '\\')))]))->isTrue()->yes() */
	bool existenceCheckIsTrue(const char *check, size_t checkLen, zend_string *name, bool &out)
	{
		const char *value = ZSTR_VAL(name);
		size_t valueLen = ZSTR_LEN(name);
		while (valueLen > 0 && *value == '\\') {
			value++;
			valueLen--;
		}
		zv::Val literal = zv::Val::string(value, valueLen);
		zv::Val string_ = pt_type_new(PT_CLASS_SCALAR_STRING, 1, literal.raw());
		if (UNEXPECTED(string_.isUndef())) return false;
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, string_.raw());
		if (UNEXPECTED(arg.isUndef())) return false;
		zv::Arr args = zv::Arr::create(1);
		args.push(std::move(arg));
		zv::Val checkName = zv::Val::string(check, checkLen);
		zv::Val fullyQualified = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, checkName.raw());
		if (UNEXPECTED(fullyQualified.isUndef())) return false;
		zv::Args argv{fullyQualified.raw(), args.raw()};
		zv::Val expr = pt_type_new(PT_CLASS_FUNC_CALL, 2, argv);
		if (UNEXPECTED(expr.isUndef())) return false;
		zv::Val type = thisGetType(expr.raw());
		if (UNEXPECTED(type.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(type.raw()));
			return false;
		}
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(type.raw()), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return false;
		out = isTrue == PT_TRI_YES;
		return true;
	}

	/* the two call-stack readers: the entries' reflections (withParameters:
	 * the entries themselves) of the entries that carry one, reindexed */
	zv::Val functionCallStack(bool withParameters)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) return zv::Val();
		zv::Arr stack = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw())) {
			zval *inFunctionCall = inFunctionCallReflection(entry.value());
			if (inFunctionCall == NULL || zv::Ref(inFunctionCall).deref().isNull()) continue;
			stack.push(withParameters ? entry.value().deref() : zv::Ref(inFunctionCall).deref());
		}
		return zv::Val(std::move(stack));
	}

	zv::Val getFunctionCallStack() { return functionCallStack(false); }
	zv::Val getFunctionCallStackWithParameters() { return functionCallStack(true); }

	/* getConstantTypes() (twin 5763) / getNativeConstantTypes() (5798) over
	 * the given table slot */
	zv::Val constantTypesOf(uint32_t tableSlot, const char *name)
	{
		if (UNEXPECTED(!requireSlot(tableSlot, name))) return zv::Val();
		zv::Arr constantTypes = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(slot(tableSlot).raw())) {
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			bool isConstFetch;
			if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_CONST_FETCH, isConstFetch))) return zv::Val();
			if (!isConstFetch) continue;
			zval copy;
			ZVAL_COPY(&copy, entry.value().raw());
			pt_ht_update(constantTypes.table(), entry.stringKeyOrNull(), entry.indexKey(), &copy);
		}
		return zv::Val(std::move(constantTypes));
	}

	zv::Val getConstantTypes() { return constantTypesOf(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"); }
	zv::Val getNativeConstantTypes() { return constantTypesOf(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes"); }

	/* new Variable($name) */
	static zv::Val newVariable(const char *name, size_t len)
	{
		zv::Val nameVal = zv::Val::string(name, len);
		return pt_type_new(PT_CLASS_VARIABLE, 1, nameVal.raw());
	}

	/* $this->context-><method>(...$argv) — the ScopeContext is native, but
	 * its state-changing methods are called by name (the differential
	 * harness hands the native scope a PHP context) */
	zv::Val contextCall(const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Ref context = slot(PT_MS_PROP_CONTEXT);
		if (UNEXPECTED(!context.isObject())) return uninitializedProperty("context");
		return pt_type_call(context.asObject(), lcname, len, argc, argv);
	}

	/** @api */
	zv::Val enterClass(zval *classReflection)
	{
		/* $thisHolder = ExpressionTypeHolder::createYes(new Variable('this'), new ThisType($classReflection)) */
		zv::Val thisVariable = newVariable(PT_LC("this"));
		if (UNEXPECTED(thisVariable.isUndef())) return zv::Val();
		zval thisTypeZv;
		if (UNEXPECTED(!pt_this_type_new(&thisTypeZv, classReflection))) return zv::Val();
		zv::Val thisType = zv::Val::adopt(thisTypeZv);
		zval thisHolderZv;
		pt_holder_create(&thisHolderZv, thisVariable.raw(), thisType.raw(), PT_TRI_YES);
		zv::Val thisHolder = zv::Val::adopt(thisHolderZv);

		zv::Val constantTypesVal = getConstantTypes();
		if (UNEXPECTED(constantTypesVal.isUndef())) return zv::Val();
		zv::Arr constantTypes = zv::Arr::adoptVal(std::move(constantTypesVal));
		constantTypes.set("$this", zv::Val::copyOf(thisHolder.ref()));

		zv::Val nativeConstantTypesVal = getNativeConstantTypes();
		if (UNEXPECTED(nativeConstantTypesVal.isUndef())) return zv::Val();
		zv::Arr nativeConstantTypes = zv::Arr::adoptVal(std::move(nativeConstantTypesVal));
		nativeConstantTypes.set("$this", zv::Val::copyOf(thisHolder.ref()));

		zv::Val context = contextCall(PT_LC("enterclass"), 1, classReflection);
		if (UNEXPECTED(context.isUndef())) return zv::Val();

		CreateArgs a;
		a.setOwned(CreateArgs::CONTEXT, std::move(context));
		if (UNEXPECTED(!fillDispatched(a, false, false))) return zv::Val();
		a.setNull(CreateArgs::FUNCTION);
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(constantTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(nativeConstantTypes)));
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES);
		a.setNull(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION);
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		/* $classReflection->isAnonymous() ? $this : null */
		zv::Val isAnonymous = pt_type_call(Z_OBJ_P(classReflection), PT_LC("isanonymous"), 0, NULL);
		if (UNEXPECTED(isAnonymous.isUndef())) return zv::Val();
		if (zend_is_true(isAnonymous.raw())) {
			a.set(CreateArgs::PARENT_SCOPE, zv::Ref(thisZval()));
		} else {
			a.setNull(CreateArgs::PARENT_SCOPE);
		}
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	zv::Val enterTrait(zval *traitReflection)
	{
		/* $namespace = the trait name without its last segment, null when it has one segment */
		zv::Val traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection));
		if (UNEXPECTED(traitName.isUndef())) return zv::Val();
		zend_string *name = zval_get_string(traitName.raw());
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Str ownedName = zv::Str::adopt(name);
		zv::Val ns = zv::Val::null();
		for (size_t i = ZSTR_LEN(name); i > 0; i--) {
			if (ZSTR_VAL(name)[i - 1] == '\\') {
				ns = zv::Val::string(ZSTR_VAL(name), i - 1);
				break;
			}
		}

		zv::Val context = contextCall(PT_LC("entertrait"), 1, traitReflection);
		if (UNEXPECTED(context.isUndef())) return zv::Val();

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		a.setOwned(CreateArgs::CONTEXT, std::move(context));
		/* the dispatched getters this site passes, $namespace among them not */
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		zv::Val function = thisGetFunction();
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::FUNCTION, std::move(function));
		a.setOwned(CreateArgs::NAMESPACE_, std::move(ns));
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.setNull(CreateArgs::PARENT_SCOPE);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		return scopeFactoryCreate(a);
	}

	/* {{{ out of the twin's file order, for the function-like family:
	 * getPhpVersion() (twin 5835), isParameterValueNullable() (2868),
	 * getFunctionType() (2881) */

	zv::Val getPhpVersion()
	{
		zv::Val constantName = zv::Val::string(PT_LC("PHP_VERSION_ID"));
		zv::Val nameNode = pt_type_new(PT_CLASS_NAME, 1, constantName.raw());
		if (UNEXPECTED(nameNode.isUndef())) return zv::Val();
		zv::Val constType = getGlobalConstantType(Z_OBJ_P(nameNode.raw()));
		if (UNEXPECTED(constType.isUndef())) return zv::Val();

		bool isOverallPhpVersionRange = false;
		if (constType.ref().isObject() && constType.ref().instanceOf(pt_ce_integer_range_type)) {
			zv::Val min = pt_type_call(constType.ref().asObject(), PT_LC("getmin"), 0, NULL);
			if (UNEXPECTED(min.isUndef())) return zv::Val();
			if (min.ref().isLong() && min.ref().asLong() == PT_MS_PHP_MIN_ANALYZABLE_VERSION_ID) {
				zv::Val max = pt_type_call(constType.ref().asObject(), PT_LC("getmax"), 0, NULL);
				if (UNEXPECTED(max.isUndef())) return zv::Val();
				if (max.isNull() || (max.ref().isLong() && max.ref().asLong() == PT_MS_MAX_PHP_VERSION)) {
					isOverallPhpVersionRange = true;
				}
			}
		}

		if (!constType.isNull() && !isOverallPhpVersionRange) return pt_type_new(PT_CLASS_PHP_VERSIONS, 1, constType.raw());

		// The analysed PHP version range comes either from the NEON phpVersion min/max
		// config or from the composer.json "require.php" constraint - the very same
		// source ConstantResolver narrows PHP_VERSION_ID with, so that
		// Scope::getPhpVersion() never contradicts the PHP_VERSION_ID constant.
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONFIGURED_PHP_VERSION_RANGE_HELPER, "configuredPhpVersionRangeHelper"))) return zv::Val();
		zv::Ref rangeHelper = slot(PT_MS_PROP_CONFIGURED_PHP_VERSION_RANGE_HELPER);
		zv::Val range = pt_type_call(rangeHelper.asObject(), PT_LC("getversionrange"), 0, NULL);
		if (UNEXPECTED(range.isUndef())) return zv::Val();
		/* [$minPhpVersion, $maxPhpVersion] = ... */
		zval *bounds[2] = { NULL, NULL };
		if (range.ref().isArray()) {
			for (zend_ulong i = 0; i < 2; i++) {
				zval *bound = zend_hash_index_find(range.ref().asArrayTable(), i);
				if (UNEXPECTED(bound == NULL)) {
					zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, i);
					if (UNEXPECTED(EG(exception))) return zv::Val();
					continue;
				}
				ZVAL_DEREF(bound);
				if (Z_TYPE_P(bound) != IS_NULL) bounds[i] = bound;
			}
		}
		zval *minPhpVersion = bounds[0];
		zval *maxPhpVersion = bounds[1];
		bool narrowed = minPhpVersion != NULL;
		if (!narrowed && maxPhpVersion != NULL) {
			zv::Val maxVersionId = pt_type_call(Z_OBJ_P(maxPhpVersion), PT_LC("getversionid"), 0, NULL);
			if (UNEXPECTED(maxVersionId.isUndef())) return zv::Val();
			narrowed = !(maxVersionId.ref().isLong() && maxVersionId.ref().asLong() == PT_MS_MAX_PHP_VERSION);
		}
		if (narrowed) {
			zval interval[2];
			zv::Val minVersionId, maxVersionId;
			if (minPhpVersion != NULL) {
				minVersionId = pt_type_call(Z_OBJ_P(minPhpVersion), PT_LC("getversionid"), 0, NULL);
				if (UNEXPECTED(minVersionId.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&interval[0], minVersionId.raw());
			} else {
				ZVAL_LONG(&interval[0], PT_MS_PHP_MIN_ANALYZABLE_VERSION_ID);
			}
			if (maxPhpVersion != NULL) {
				maxVersionId = pt_type_call(Z_OBJ_P(maxPhpVersion), PT_LC("getversionid"), 0, NULL);
				if (UNEXPECTED(maxVersionId.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&interval[1], maxVersionId.raw());
			} else {
				ZVAL_NULL(&interval[1]);
			}
			zv::Val versionRange = pt_type_call_static_ce(pt_ce_integer_range_type, PT_LC("frominterval"), 2, interval);
			if (UNEXPECTED(versionRange.isUndef())) return zv::Val();
			return pt_type_new(PT_CLASS_PHP_VERSIONS, 1, versionRange.raw());
		}

		zv::Ref phpVersion = slot(PT_MS_PROP_PHP_VERSION);
		if (UNEXPECTED(!phpVersion.isObject())) return uninitializedProperty("phpVersion");
		zv::Val versionId = pt_type_call(phpVersion.asObject(), PT_LC("getversionid"), 0, NULL);
		if (UNEXPECTED(versionId.isUndef())) return zv::Val();
		zval constantInteger;
		if (UNEXPECTED(!pt_constant_integer_type_new(&constantInteger, zval_get_long(versionId.raw())))) return zv::Val();
		zv::Val constantIntegerType = zv::Val::adopt(constantInteger);
		return pt_type_new(PT_CLASS_PHP_VERSIONS, 1, constantIntegerType.raw());
	}

	/* $this->getPhpVersion()->supportsNamedArguments()->no() negated — the
	 * test the variadic parameter types are built on; false = pending exception */
	[[nodiscard]] bool phpVersionSupportsNamedArguments(bool &out)
	{
		zv::Val phpVersions = thisGetPhpVersion();
		if (UNEXPECTED(phpVersions.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(phpVersions.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function supportsNamedArguments() on %s", zend_zval_value_name(phpVersions.raw()));
			return false;
		}
		zend_long supports = pt_type_call_trinary(Z_OBJ_P(phpVersions.raw()), PT_LC("supportsnamedarguments"), 0, NULL);
		if (UNEXPECTED(supports < 0)) return false;
		out = supports != PT_TRI_NO;
		return true;
	}

	/* IntegerRangeType::createAllGreaterThanOrEqualTo(0) */
	static zv::Val allGreaterThanOrEqualToZero()
	{
		zval zero;
		ZVAL_LONG(&zero, 0);
		return pt_type_call_static_ce(pt_ce_integer_range_type, PT_LC("createallgreaterthanorequalto"), 1, &zero);
	}

	/* the variadic parameter's array type: keyed by int|string under named
	 * arguments, a list otherwise */
	static zv::Val variadicArrayType(zval *itemType, bool supportsNamedArguments)
	{
		zv::Val keyType = allGreaterThanOrEqualToZero();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		if (supportsNamedArguments) {
			zval stringTypeZv;
			if (UNEXPECTED(!pt_string_type_new(&stringTypeZv))) return zv::Val();
			zv::Val stringType = zv::Val::adopt(stringTypeZv);
			zv::Arr keyTypes = zv::Arr::create(2);
			keyTypes.push(keyType.ref());
			keyTypes.push(stringType.ref());
			zval unionZv;
			if (UNEXPECTED(!pt_union_type_new(&unionZv, keyTypes.raw()))) return zv::Val();
			zv::Val unionType = zv::Val::adopt(unionZv);
			zval arrayZv;
			if (UNEXPECTED(!pt_array_type_new(&arrayZv, unionType.raw(), itemType))) return zv::Val();
			return zv::Val::adopt(arrayZv);
		}

		zval arrayZv;
		if (UNEXPECTED(!pt_array_type_new(&arrayZv, keyType.raw(), itemType))) return zv::Val();
		zv::Val arrayType = zv::Val::adopt(arrayZv);
		zval listZv;
		if (UNEXPECTED(!pt_accessory_array_list_type_new(&listZv))) return zv::Val();
		zv::Val listType = zv::Val::adopt(listZv);
		zv::Arr types = zv::Arr::create(2);
		types.push(arrayType.ref());
		types.push(listType.ref());
		zval intersectionZv;
		if (UNEXPECTED(!pt_intersection_type_new(&intersectionZv, types.raw()))) return zv::Val();
		return zv::Val::adopt(intersectionZv);
	}

	/** @api */
	bool isParameterValueNullable(zend_object *parameter, bool &out)
	{
		zv::Ref defaultValue = nodeProp(parameter, PT_LC("default"));
		if (UNEXPECTED(defaultValue.raw() == NULL)) return false;
		bool isConstFetch;
		if (UNEXPECTED(!isInstance(defaultValue.deref(), PT_CLASS_CONST_FETCH, isConstFetch))) return false;
		if (!isConstFetch) {
			out = false;
			return true;
		}
		/* strtolower((string) $parameter->default->name) === 'null' */
		zv::Ref name = nodeProp(defaultValue.deref().asObject(), PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return false;
		zend_string *nameString = zval_get_string(name.deref().raw());
		if (UNEXPECTED(nameString == NULL)) return false;
		zend_string *lower = zend_string_tolower(nameString);
		zend_string_release(nameString);
		out = zend_string_equals_literal(lower, "null");
		zend_string_release(lower);
		return EXPECTED(EG(exception) == NULL);
	}

	/** @api; $type: Name|Identifier|ComplexType|null */
	zv::Val getFunctionType(zval *type, bool isNullable, bool isVariadic)
	{
		if (isVariadic) {
			bool supportsNamedArguments;
			if (UNEXPECTED(!phpVersionSupportsNamedArguments(supportsNamedArguments))) return zv::Val();
			zv::Val itemType = thisGetFunctionType(type, isNullable, false);
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			return variadicArrayType(itemType.raw(), supportsNamedArguments);
		}

		bool isName;
		if (UNEXPECTED(!isInstance(zv::Ref(type), PT_CLASS_NAME, isName))) return zv::Val();
		if (isName) {
			if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) return zv::Val();
			HashTable *bindScopeClasses = Z_ARRVAL_P(slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES).raw());
			if (zend_hash_num_elements(bindScopeClasses) != 0 && !isSingleStringList(bindScopeClasses, PT_LC("static"))) {
				zv::Val lower = pt_type_call(Z_OBJ_P(type), PT_LC("tolowerstring"), 0, NULL);
				if (UNEXPECTED(lower.isUndef())) return zv::Val();
				bool isRelativeName = lower.ref().isString()
					&& (zend_string_equals_literal(lower.ref().asString(), "static")
						|| zend_string_equals_literal(lower.ref().asString(), "self")
						|| zend_string_equals_literal(lower.ref().asString(), "parent"));
				if (isRelativeName) {
					zval nullZv;
					ZVAL_NULL(&nullZv);
					zval *first = zend_hash_index_find(bindScopeClasses, 0);
					if (UNEXPECTED(first == NULL)) {
						zend_error(E_WARNING, "Undefined array key 0");
						if (UNEXPECTED(EG(exception))) return zv::Val();
						first = &nullZv;
					}
					zv::Ref provider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
					if (UNEXPECTED(!provider.isObject())) return uninitializedProperty("reflectionProvider");
					bool hasClass;
					if (UNEXPECTED(!pt_reflection_provider_has_class(provider.asObject(), first, hasClass))) return zv::Val();
					if (hasClass) {
						zv::Val classReflection = pt_reflection_provider_get_class(provider.asObject(), first);
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
						zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromclassreflection"), 1, classReflection.raw());
						if (UNEXPECTED(context.isUndef())) return zv::Val();
						return initializerExprTypeResolverFunctionType(type, isNullable, context.raw());
					}
				}
			}
		}

		zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromscope"), 1, thisZval());
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		return initializerExprTypeResolverFunctionType(type, isNullable, context.raw());
	}

	/* $this->initializerExprTypeResolver->getFunctionType($type, $isNullable, false, $context) */
	zv::Val initializerExprTypeResolverFunctionType(zval *type, bool isNullable, zval *context)
	{
		zv::Ref resolver = slot(PT_MS_PROP_INITIALIZER_EXPR_TYPE_RESOLVER);
		if (UNEXPECTED(!resolver.isObject())) return uninitializedProperty("initializerExprTypeResolver");
		zv::Args argv{type, isNullable, bool(false), context};
		return pt_type_call(resolver.asObject(), PT_LC("getfunctiontype"), 4, argv);
	}

	/* }}} */

	/* {{{ twin 2012-2369: the function-like family */

	/* array_map(static fn (Type $type): Type => TemplateTypeHelper::toArgument($type), $types),
	 * with $this->transformStaticType() around it where the twin has it
	 * (array_map over one array keeps the keys) */
	zv::Val mapToArgument(zval *types, bool transform)
	{
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types)));
		for (auto entry : zv::ArrRef(types)) {
			zv::Val mapped = pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("toargument"), 1, entry.value().deref().raw());
			if (UNEXPECTED(mapped.isUndef())) return zv::Val();
			if (transform) {
				zv::Val transformed = transformStaticType(mapped.raw());
				if (UNEXPECTED(transformed.isUndef())) return zv::Val();
				mapped = std::move(transformed);
			}
			zval value = mapped.take();
			pt_ht_update(result.table(), entry.stringKeyOrNull(), entry.indexKey(), &value);
		}
		return zv::Val(std::move(result));
	}

	/* array_merge($first, $second) */
	static zv::Val arrayMerge(HashTable *first, HashTable *second)
	{
		zv::Arr merged = zv::Arr::create(zend_hash_num_elements(first) + zend_hash_num_elements(second));
		for (HashTable *table : { first, second }) {
			for (auto entry : zv::TableRef(table)) {
				zval copy;
				ZVAL_COPY(&copy, entry.value().deref().raw());
				if (entry.stringKeyOrNull() != NULL) {
					zend_hash_update(merged.table(), entry.stringKeyOrNull(), &copy);
				} else {
					zend_hash_next_index_insert(merged.table(), &copy);
				}
			}
		}
		return zv::Val(std::move(merged));
	}

	/* $parameter->var->name of a Node\Param, the twin's
	 * ShouldNotHappenException when it is not a plain Variable; NULL =
	 * pending exception */
	static zend_string *parameterVariableName(zend_object *parameter)
	{
		zv::Ref var = nodeProp(parameter, PT_LC("var"));
		if (UNEXPECTED(var.raw() == NULL)) return NULL;
		bool isVariable;
		if (UNEXPECTED(!isInstance(var.deref(), PT_CLASS_VARIABLE, isVariable))) return NULL;
		if (isVariable) {
			zv::Ref name = nodeProp(var.deref().asObject(), PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) return NULL;
			if (name.deref().isString()) return name.deref().asString();
		}
		pt_throw_should_not_happen();
		return NULL;
	}

	/* $functionLike->getParams() */
	static zv::Val functionLikeParams(zend_object *functionLike)
	{
		return pt_type_call(functionLike, PT_LC("getparams"), 0, NULL);
	}

	/* private (twin 2164) */
	zv::Val transformStaticType(zval *type)
	{
		zv::Val traverser = pt_type_new(PT_CLASS_TRANSFORM_STATIC_TYPE_TRAVERSER, 1, thisZval());
		if (UNEXPECTED(traverser.isUndef())) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_type_traverser_map(&mapped, type, traverser.raw()))) return zv::Val();
		return zv::Val::adopt(mapped);
	}

	/* private (twin 2172) */
	zv::Val getRealParameterTypes(zend_object *functionLike)
	{
		zv::Val params = functionLikeParams(functionLike);
		if (UNEXPECTED(params.isUndef())) return zv::Val();
		if (UNEXPECTED(!params.ref().isArray())) {
			zend_type_error("phpstan_turbo: getParams() must return array, %s returned", zend_zval_value_name(params.raw()));
			return zv::Val();
		}
		zv::Arr realParameterTypes = zv::Arr::create(zend_hash_num_elements(params.ref().asArrayTable()));
		for (auto entry : zv::ArrRef(params.raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_string *name = parameterVariableName(parameter.asObject());
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Str ownedName = zv::Str::copyOf(name);
			bool nullable;
			if (UNEXPECTED(!thisIsParameterValueNullable(parameter.raw(), nullable))) return zv::Val();
			zv::Ref flags = nodeProp(parameter.asObject(), PT_LC("flags"));
			if (UNEXPECTED(flags.raw() == NULL)) return zv::Val();
			zv::Ref type = nodeProp(parameter.asObject(), PT_LC("type"));
			if (UNEXPECTED(type.raw() == NULL)) return zv::Val();
			bool isNullable = nullable && flags.deref().isLong() && flags.deref().asLong() == 0;
			zv::Val parameterType = thisGetFunctionType(type.deref().raw(), isNullable, false);
			if (UNEXPECTED(parameterType.isUndef())) return zv::Val();
			realParameterTypes.set(ownedName.get(), std::move(parameterType));
		}
		return zv::Val(std::move(realParameterTypes));
	}

	/* private (twin 2192) */
	zv::Val getRealParameterDefaultValues(zend_object *functionLike)
	{
		zv::Val params = functionLikeParams(functionLike);
		if (UNEXPECTED(params.isUndef())) return zv::Val();
		if (UNEXPECTED(!params.ref().isArray())) {
			zend_type_error("phpstan_turbo: getParams() must return array, %s returned", zend_zval_value_name(params.raw()));
			return zv::Val();
		}
		zv::Arr defaultValues = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(params.raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Ref defaultValue = nodeProp(parameter.asObject(), PT_LC("default"));
			if (UNEXPECTED(defaultValue.raw() == NULL)) return zv::Val();
			if (defaultValue.deref().isNull()) continue;
			zend_string *name = parameterVariableName(parameter.asObject());
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Str ownedName = zv::Str::copyOf(name);
			zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromscope"), 1, thisZval());
			if (UNEXPECTED(context.isUndef())) return zv::Val();
			zv::Ref resolver = slot(PT_MS_PROP_INITIALIZER_EXPR_TYPE_RESOLVER);
			if (UNEXPECTED(!resolver.isObject())) return uninitializedProperty("initializerExprTypeResolver");
			zv::Args argv{defaultValue.deref().raw(), context.raw()};
			zv::Val type = pt_type_call(resolver.asObject(), PT_LC("gettype"), 2, argv);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			defaultValues.set(ownedName.get(), std::move(type));
		}
		return zv::Val(std::move(defaultValues));
	}

	/* private (twin 2211) */
	zv::Val getParameterAttributes(zend_object *functionLike)
	{
		zval classNameZv = {};
		ZVAL_NULL(&classNameZv);
		zv::Val className;
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (inClass) {
			zv::Val classReflection = thisGetClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
				return zv::Val();
			}
			className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&classNameZv, className.raw());
		}

		zv::Val params = functionLikeParams(functionLike);
		if (UNEXPECTED(params.isUndef())) return zv::Val();
		if (UNEXPECTED(!params.ref().isArray())) {
			zend_type_error("phpstan_turbo: getParams() must return array, %s returned", zend_zval_value_name(params.raw()));
			return zv::Val();
		}
		zv::Arr parameterAttributes = zv::Arr::create(zend_hash_num_elements(params.ref().asArrayTable()));
		for (auto entry : zv::ArrRef(params.raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_string *name = parameterVariableName(parameter.asObject());
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Str ownedName = zv::Str::copyOf(name);
			zv::Ref attrGroups = nodeProp(parameter.asObject(), PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups.raw() == NULL)) return zv::Val();
			zv::Val attributes = attributesFromAttrGroups(attrGroups.deref().raw(), &classNameZv, functionLike);
			if (UNEXPECTED(attributes.isUndef())) return zv::Val();
			parameterAttributes.set(ownedName.get(), std::move(attributes));
		}
		return zv::Val(std::move(parameterAttributes));
	}

	/* $this->attributeReflectionFactory->fromAttrGroups($attrGroups,
	 * InitializerExprContext::fromStubParameter($className, $this->getFile(), $functionLike)) */
	zv::Val attributesFromAttrGroups(zval *attrGroups, zval *className, zend_object *functionLike)
	{
		zv::Val file = thisGetFile();
		if (UNEXPECTED(file.isUndef())) return zv::Val();
		zv::Args contextArgs{className, file.raw(), functionLike};
		zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromstubparameter"), 3, contextArgs);
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Ref factory = slot(PT_MS_PROP_ATTRIBUTE_REFLECTION_FACTORY);
		if (UNEXPECTED(!factory.isObject())) return uninitializedProperty("attributeReflectionFactory");
		zv::Args argv{attrGroups, context.raw()};
		return pt_type_call(factory.asObject(), PT_LC("fromattrgroups"), 2, argv);
	}

	/* Assertions::createEmpty() */
	static zv::Val emptyAssertions() { return pt_type_call_static(PT_CLASS_ASSERTIONS, PT_LC("createempty"), 0, NULL); }

	/** @api (twin 2012) */
	zv::Val enterClassMethod(zval *classMethod, zval *templateTypeMap, zval *phpDocParameterTypes, zval *phpDocReturnType, zval *throwType, zval *deprecatedDescription, bool isDeprecated, bool isInternal, bool isFinal, zval *isPure, bool acceptsNamedArguments, zval *asserts, zval *selfOutType, zval *phpDocComment, zval *parameterOutTypes, zval *immediatelyInvokedCallableParameters, zval *phpDocClosureThisTypeParameters, bool isConstructor, zval *resolvedPhpDocBlock, zval *phpDocPureUnlessCallableIsImpureParameters)
	{
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (!inClass) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zend_object *classMethodObject = Z_OBJ_P(classMethod);
		Args<28> a;
		PT_MS_ARG_OWNED(a, thisGetClassReflection());
		a.add(zv::Ref(classMethod));
		a.addNull();
		PT_MS_ARG_OWNED(a, thisGetFile());
		a.add(zv::Ref(templateTypeMap));
		PT_MS_ARG_OWNED(a, getRealParameterTypes(classMethodObject));
		PT_MS_ARG_OWNED(a, mapToArgument(phpDocParameterTypes, true));
		PT_MS_ARG_OWNED(a, getRealParameterDefaultValues(classMethodObject));
		PT_MS_ARG_OWNED(a, getParameterAttributes(classMethodObject));
		{
			zv::Ref returnType = nodeProp(classMethodObject, PT_LC("returnType"));
			if (UNEXPECTED(returnType.raw() == NULL)) return zv::Val();
			zv::Val functionType = thisGetFunctionType(returnType.deref().raw(), false, false);
			if (UNEXPECTED(functionType.isUndef())) return zv::Val();
			PT_MS_ARG_OWNED(a, transformStaticType(functionType.raw()));
		}
		PT_MS_ARG_OWNED(a, transformedArgumentOrNull(phpDocReturnType));
		PT_MS_ARG_OWNED(a, transformedArgumentOrNull(throwType));
		a.add(zv::Ref(deprecatedDescription));
		a.addBool(isDeprecated);
		a.addBool(isInternal);
		a.addBool(isFinal);
		a.add(zv::Ref(isPure));
		a.addBool(acceptsNamedArguments);
		if (Z_TYPE_P(asserts) == IS_NULL) {
			PT_MS_ARG_OWNED(a, emptyAssertions());
		} else {
			a.add(zv::Ref(asserts));
		}
		a.add(zv::Ref(selfOutType));
		a.add(zv::Ref(phpDocComment));
		a.add(zv::Ref(resolvedPhpDocBlock));
		PT_MS_ARG_OWNED(a, mapToArgument(parameterOutTypes, true));
		a.add(zv::Ref(immediatelyInvokedCallableParameters));
		PT_MS_ARG_OWNED(a, mapToArgument(phpDocClosureThisTypeParameters, true));
		a.addBool(isConstructor);
		{
			zv::Val classReflection = thisGetClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
				return zv::Val();
			}
			zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Ref attrGroups = nodeProp(classMethodObject, PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups.raw() == NULL)) return zv::Val();
			PT_MS_ARG_OWNED(a, attributesFromAttrGroups(attrGroups.deref().raw(), className.raw(), classMethodObject));
		}
		a.add(zv::Ref(phpDocPureUnlessCallableIsImpureParameters));

		zv::Val reflection = pt_type_new(PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, a.count, a.argv);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		zv::Val isStatic = pt_type_call(classMethodObject, PT_LC("isstatic"), 0, NULL);
		if (UNEXPECTED(isStatic.isUndef())) return zv::Val();
		return enterFunctionLike(reflection.raw(), !zend_is_true(isStatic.raw()));
	}

	/* $type !== null ? $this->transformStaticType(TemplateTypeHelper::toArgument($type)) : null */
	zv::Val transformedArgumentOrNull(zval *type)
	{
		if (Z_TYPE_P(type) == IS_NULL) return zv::Val::null();
		zv::Val argument = pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("toargument"), 1, type);
		if (UNEXPECTED(argument.isUndef())) return zv::Val();
		return transformStaticType(argument.raw());
	}

	/* (twin 2077) */
	zv::Val enterPropertyHook(zval *hook, zend_string *propertyName, zval *nativePropertyTypeNode, zval *phpDocPropertyType, zval *phpDocParameterTypes, zval *throwType, zval *deprecatedDescription, bool isDeprecated, zval *isPure, zval *phpDocComment, zval *resolvedPhpDocBlock)
	{
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (!inClass) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val mappedParameterTypes = mapToArgument(phpDocParameterTypes, true);
		if (UNEXPECTED(mappedParameterTypes.isUndef())) return zv::Val();
		zv::Arr parameterTypes = zv::Arr::adoptVal(std::move(mappedParameterTypes));

		zend_object *hookObject = Z_OBJ_P(hook);
		zv::Ref hookName = nodeProp(hookObject, PT_LC("name"));
		if (UNEXPECTED(hookName.raw() == NULL)) return zv::Val();
		if (UNEXPECTED(!hookName.deref().isObject())) {
			zend_throw_error(NULL, "Call to a member function toLowerString() on %s", zend_zval_value_name(hookName.deref().raw()));
			return zv::Val();
		}
		zv::Val lowerName = pt_type_call(hookName.deref().asObject(), PT_LC("tolowerstring"), 0, NULL);
		if (UNEXPECTED(lowerName.isUndef())) return zv::Val();
		bool isSet = lowerName.ref().isString() && zend_string_equals_literal(lowerName.ref().asString(), "set");
		bool isGet = lowerName.ref().isString() && zend_string_equals_literal(lowerName.ref().asString(), "get");

		zv::Val ownedHook;
		zv::Val realReturnType;
		zv::Val phpDocReturnType = zv::Val::null();
		if (isSet) {
			zv::Ref params = nodeProp(hookObject, PT_LC("params"));
			if (UNEXPECTED(params.raw() == NULL)) return zv::Val();
			if (params.deref().isArray() && zend_hash_num_elements(params.deref().asArrayTable()) == 0) {
				/* $hook = clone $hook; $hook->params = [new Node\Param(new Variable('value'), type: $nativePropertyTypeNode)]; */
				zend_object *clone = hookObject->handlers->clone_obj(hookObject);
				if (UNEXPECTED(EG(exception))) {
					if (clone != NULL) {
						OBJ_RELEASE(clone);
					}
					return zv::Val();
				}
				zval cloneZv;
				ZVAL_OBJ(&cloneZv, clone);
				ownedHook = zv::Val::adopt(cloneZv);
				hookObject = clone;

				zv::Val valueVariable = newVariable(PT_LC("value"));
				if (UNEXPECTED(valueVariable.isUndef())) return zv::Val();
				zv::Args paramArgs{valueVariable.raw(), zv::null, nativePropertyTypeNode};
				zv::Val param = pt_type_new(PT_CLASS_PARAM, 3, paramArgs);
				if (UNEXPECTED(param.isUndef())) return zv::Val();
				zv::Arr newParams = zv::Arr::create(1);
				newParams.push(std::move(param));
				zend_update_property(hookObject->ce, hookObject, PT_LC("params"), newParams.raw());
				if (UNEXPECTED(EG(exception))) return zv::Val();
			}

			zv::Ref currentParams = nodeProp(hookObject, PT_LC("params"));
			if (UNEXPECTED(currentParams.raw() == NULL)) return zv::Val();
			zval *firstParam = currentParams.deref().isArray() ? zend_hash_index_find(currentParams.deref().asArrayTable(), 0) : NULL;
			if (firstParam != NULL && Z_TYPE_P(phpDocPropertyType) != IS_NULL) {
				zv::Ref var = zv::Ref(firstParam).deref().isObject() ? nodeProp(Z_OBJ_P(zv::Ref(firstParam).deref().raw()), PT_LC("var")) : zv::Ref(NULL);
				if (var.raw() != NULL) {
					bool isVariable;
					if (UNEXPECTED(!isInstance(var.deref(), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
					if (isVariable) {
						zv::Ref varName = nodeProp(var.deref().asObject(), PT_LC("name"));
						if (UNEXPECTED(varName.raw() == NULL)) return zv::Val();
						if (varName.deref().isString()) {
							zend_string *key = varName.deref().asString();
							if (zend_symtable_find(parameterTypes.table(), key) == NULL) {
								zv::Val valueParamType = transformedArgumentOrNull(phpDocPropertyType);
								if (UNEXPECTED(valueParamType.isUndef())) return zv::Val();
								parameterTypes.set(key, std::move(valueParamType));
							}
						}
					}
				} else if (UNEXPECTED(EG(exception))) {
					return zv::Val();
				}
			}

			zval voidType;
			if (UNEXPECTED(!pt_void_type_new(&voidType))) return zv::Val();
			realReturnType = zv::Val::adopt(voidType);
		} else if (isGet) {
			realReturnType = thisGetFunctionType(nativePropertyTypeNode, false, false);
			if (UNEXPECTED(realReturnType.isUndef())) return zv::Val();
			phpDocReturnType = transformedArgumentOrNull(phpDocPropertyType);
			if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
		} else {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val realParameterTypes = getRealParameterTypes(hookObject);
		if (UNEXPECTED(realParameterTypes.isUndef())) return zv::Val();

		Args<28> a;
		PT_MS_ARG_OWNED(a, thisGetClassReflection());
		{
			zval hookZv;
			ZVAL_OBJ(&hookZv, hookObject);
			a.add(zv::Ref(&hookZv));
		}
		a.addOwned(zv::Val::string(propertyName));
		PT_MS_ARG_OWNED(a, thisGetFile());
		{
			zval emptyMap;
			if (UNEXPECTED(!pt_template_type_map_empty(&emptyMap))) return zv::Val();
			a.addOwned(zv::Val::adopt(emptyMap));
		}
		a.addOwned(std::move(realParameterTypes));
		a.addOwned(zv::Val(std::move(parameterTypes)));
		a.addEmptyArray();
		PT_MS_ARG_OWNED(a, getParameterAttributes(hookObject));
		a.addOwned(std::move(realReturnType));
		a.addOwned(std::move(phpDocReturnType));
		PT_MS_ARG_OWNED(a, transformedArgumentOrNull(throwType));
		a.add(zv::Ref(deprecatedDescription));
		a.addBool(isDeprecated);
		a.addBool(false);
		a.addBool(false);
		a.add(zv::Ref(isPure));
		a.addBool(true);
		PT_MS_ARG_OWNED(a, emptyAssertions());
		a.addNull();
		a.add(zv::Ref(phpDocComment));
		a.add(zv::Ref(resolvedPhpDocBlock));
		a.addEmptyArray();
		a.addEmptyArray();
		a.addEmptyArray();
		a.addBool(false);
		{
			zv::Val classReflection = thisGetClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
				return zv::Val();
			}
			zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Ref attrGroups = nodeProp(hookObject, PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups.raw() == NULL)) return zv::Val();
			PT_MS_ARG_OWNED(a, attributesFromAttrGroups(attrGroups.deref().raw(), className.raw(), hookObject));
		}
		a.addEmptyArray();

		zv::Val reflection = pt_type_new(PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, a.count, a.argv);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		return enterFunctionLike(reflection.raw(), true);
	}

	/** @api (twin 2237) */
	zv::Val enterFunction(zval *function, zval *templateTypeMap, zval *phpDocParameterTypes, zval *phpDocReturnType, zval *throwType, zval *deprecatedDescription, bool isDeprecated, bool isInternal, zval *isPure, bool acceptsNamedArguments, zval *asserts, zval *phpDocComment, zval *parameterOutTypes, zval *immediatelyInvokedCallableParameters, zval *phpDocClosureThisTypeParameters, zval *pureUnlessCallableIsImpureParameters)
	{
		zend_object *functionObject = Z_OBJ_P(function);
		Args<28> a;
		a.add(zv::Ref(function));
		PT_MS_ARG_OWNED(a, thisGetFile());
		a.add(zv::Ref(templateTypeMap));
		PT_MS_ARG_OWNED(a, getRealParameterTypes(functionObject));
		PT_MS_ARG_OWNED(a, mapToArgument(phpDocParameterTypes, false));
		PT_MS_ARG_OWNED(a, getRealParameterDefaultValues(functionObject));
		PT_MS_ARG_OWNED(a, getParameterAttributes(functionObject));
		{
			zv::Ref returnType = nodeProp(functionObject, PT_LC("returnType"));
			if (UNEXPECTED(returnType.raw() == NULL)) return zv::Val();
			PT_MS_ARG_OWNED(a, thisGetFunctionType(returnType.deref().raw(), returnType.deref().isNull(), false));
		}
		if (Z_TYPE_P(phpDocReturnType) == IS_NULL) {
			a.addNull();
		} else {
			PT_MS_ARG_OWNED(a, pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("toargument"), 1, phpDocReturnType));
		}
		a.add(zv::Ref(throwType));
		a.add(zv::Ref(deprecatedDescription));
		a.addBool(isDeprecated);
		a.addBool(isInternal);
		a.add(zv::Ref(isPure));
		a.addBool(acceptsNamedArguments);
		if (Z_TYPE_P(asserts) == IS_NULL) {
			PT_MS_ARG_OWNED(a, emptyAssertions());
		} else {
			a.add(zv::Ref(asserts));
		}
		a.add(zv::Ref(phpDocComment));
		PT_MS_ARG_OWNED(a, mapToArgument(parameterOutTypes, false));
		a.add(zv::Ref(immediatelyInvokedCallableParameters));
		a.add(zv::Ref(phpDocClosureThisTypeParameters));
		{
			zval nullClassName;
			ZVAL_NULL(&nullClassName);
			zv::Ref attrGroups = nodeProp(functionObject, PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups.raw() == NULL)) return zv::Val();
			PT_MS_ARG_OWNED(a, attributesFromAttrGroups(attrGroups.deref().raw(), &nullClassName, functionObject));
		}
		a.add(zv::Ref(pureUnlessCallableIsImpureParameters));

		zv::Val reflection = pt_type_new(PT_CLASS_PHP_FUNCTION_FROM_PARSER_NODE_REFLECTION, a.count, a.argv);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		return enterFunctionLike(reflection.raw(), false);
	}

	/* private (twin 2285) */
	zv::Val enterFunctionLike(zval *functionReflection, bool preserveConstructorScope)
	{
		zend_object *reflection = Z_OBJ_P(functionReflection);
		zv::Val functionParameters = pt_type_call(reflection, PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(functionParameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!functionParameters.ref().isArray())) {
			zend_type_error("phpstan_turbo: getParameters() must return array, %s returned", zend_zval_value_name(functionParameters.raw()));
			return zv::Val();
		}

		zv::Arr parametersByName = zv::Arr::create(zend_hash_num_elements(functionParameters.ref().asArrayTable()));
		for (auto entry : zv::ArrRef(functionParameters.raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(parameter.raw()));
				return zv::Val();
			}
			zv::Val name = pt_type_call(parameter.asObject(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zend_string *key = zval_get_string(name.raw());
			if (UNEXPECTED(key == NULL)) return zv::Val();
			zval copy;
			ZVAL_COPY(&copy, parameter.raw());
			zend_symtable_update(parametersByName.table(), key, &copy);
			zend_string_release(key);
		}

		zv::Arr expressionTypes;
		zv::Arr nativeExpressionTypes;
		if (preserveConstructorScope) {
			if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes") || !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes"))) {
				return zv::Val();
			}
			expressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()));
			nativeExpressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()));
		} else {
			expressionTypes = zv::Arr::create(0);
			nativeExpressionTypes = zv::Arr::create(0);
		}
		zv::Arr conditionalTypes = zv::Arr::create(0);

		for (auto entry : zv::ArrRef(functionParameters.raw())) {
			zv::Ref parameter = entry.value().deref();
			zend_object *parameterObject = parameter.asObject();
			zv::Val parameterType = pt_type_call(parameterObject, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(parameterType.isUndef())) return zv::Val();
			zv::Val name = pt_type_call(parameterObject, PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zend_string *parameterNameRaw = zval_get_string(name.raw());
			if (UNEXPECTED(parameterNameRaw == NULL)) return zv::Val();
			zv::Str parameterName = zv::Str::adopt(parameterNameRaw);

			if (parameterType.ref().isObject() && parameterType.ref().instanceOf(pt_ce_conditional_type_for_parameter)) {
				if (UNEXPECTED(!addConditionalParameterTypes(conditionalTypes, parametersByName, parameterType.ref(), parameterName.get()))) return zv::Val();
			}

			zv::Str paramExprString = zv::Str::adopt(zend_strpprintf(0, "$%s", ZSTR_VAL(parameterName.get())));
			zv::Val isVariadic = pt_type_call(parameterObject, PT_LC("isvariadic"), 0, NULL);
			if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
			if (zend_is_true(isVariadic.raw())) {
				bool named;
				if (UNEXPECTED(!acceptsNamedArgumentsHere(reflection, named))) return zv::Val();
				zv::Val wrapped = variadicArrayType(parameterType.raw(), named);
				if (UNEXPECTED(wrapped.isUndef())) return zv::Val();
				parameterType = std::move(wrapped);
			}

			zv::Val parameterNode = newVariable(ZSTR_VAL(parameterName.get()), ZSTR_LEN(parameterName.get()));
			if (UNEXPECTED(parameterNode.isUndef())) return zv::Val();
			zval holderZv;
			pt_holder_create(&holderZv, parameterNode.raw(), parameterType.raw(), PT_TRI_YES);
			expressionTypes.set(paramExprString.get(), zv::Val::adopt(holderZv));

			zv::Val originalValueName = zv::Val::string(parameterName.get());
			zv::Val parameterOriginalValueExpr = pt_type_new(PT_CLASS_PARAMETER_VARIABLE_ORIGINAL_VALUE_EXPR, 1, originalValueName.raw());
			if (UNEXPECTED(parameterOriginalValueExpr.isUndef())) return zv::Val();
			zv::Val originalValueExprString = thisGetNodeKey(parameterOriginalValueExpr.raw());
			if (UNEXPECTED(originalValueExprString.isUndef())) return zv::Val();
			zend_string *originalValueKeyRaw = zval_get_string(originalValueExprString.raw());
			if (UNEXPECTED(originalValueKeyRaw == NULL)) return zv::Val();
			zv::Str originalValueKey = zv::Str::adopt(originalValueKeyRaw);
			pt_holder_create(&holderZv, parameterOriginalValueExpr.raw(), parameterType.raw(), PT_TRI_YES);
			expressionTypes.set(originalValueKey.get(), zv::Val::adopt(holderZv));

			zv::Val nativeParameterType = pt_type_call(parameterObject, PT_LC("getnativetype"), 0, NULL);
			if (UNEXPECTED(nativeParameterType.isUndef())) return zv::Val();
			if (zend_is_true(isVariadic.raw())) {
				bool named;
				if (UNEXPECTED(!acceptsNamedArgumentsHere(reflection, named))) return zv::Val();
				zv::Val wrapped = variadicArrayType(nativeParameterType.raw(), named);
				if (UNEXPECTED(wrapped.isUndef())) return zv::Val();
				nativeParameterType = std::move(wrapped);
			}
			pt_holder_create(&holderZv, parameterNode.raw(), nativeParameterType.raw(), PT_TRI_YES);
			nativeExpressionTypes.set(paramExprString.get(), zv::Val::adopt(holderZv));
			pt_holder_create(&holderZv, parameterOriginalValueExpr.raw(), nativeParameterType.raw(), PT_TRI_YES);
			nativeExpressionTypes.set(originalValueKey.get(), zv::Val::adopt(holderZv));
		}

		CreateArgs a;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONTEXT, "context"))) return zv::Val();
		a.set(CreateArgs::CONTEXT, slot(PT_MS_PROP_CONTEXT));
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		a.set(CreateArgs::FUNCTION, zv::Ref(functionReflection));
		zv::Val ns = thisGetNamespace();
		if (UNEXPECTED(ns.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::NAMESPACE_, std::move(ns));
		zv::Val constantTypes = getConstantTypes();
		if (UNEXPECTED(constantTypes.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, arrayMerge(Z_ARRVAL_P(constantTypes.raw()), expressionTypes.table()));
		zv::Val nativeConstantTypes = getNativeConstantTypes();
		if (UNEXPECTED(nativeConstantTypes.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, arrayMerge(Z_ARRVAL_P(nativeConstantTypes.raw()), nativeExpressionTypes.table()));
		a.setOwned(CreateArgs::CONDITIONAL_EXPRESSIONS, zv::Val(std::move(conditionalTypes)));
		a.setEmptyArray(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES);
		a.setNull(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION);
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.setNull(CreateArgs::PARENT_SCOPE);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	/* !$this->getPhpVersion()->supportsNamedArguments()->no() && $functionReflection->acceptsNamedArguments()->yes() */
	bool acceptsNamedArgumentsHere(zend_object *functionReflection, bool &out)
	{
		bool supportsNamedArguments;
		if (UNEXPECTED(!phpVersionSupportsNamedArguments(supportsNamedArguments))) return false;
		if (!supportsNamedArguments) {
			out = false;
			return true;
		}
		zend_long accepts = pt_type_call_trinary(functionReflection, PT_LC("acceptsnamedarguments"), 0, NULL);
		if (UNEXPECTED(accepts < 0)) return false;
		out = accepts == PT_TRI_YES;
		return true;
	}

	/* the two ConditionalExpressionHolders a ConditionalTypeForParameter
	 * parameter adds to $conditionalTypes; false = pending exception */
	[[nodiscard]] bool addConditionalParameterTypes(zv::Arr &conditionalTypes, zv::Arr &parametersByName, zv::Ref parameterType, zend_string *parameterName)
	{
		zend_object *conditional = parameterType.asObject();
		zv::Val targetParameterName = pt_type_call(conditional, PT_LC("getparametername"), 0, NULL);
		if (UNEXPECTED(targetParameterName.isUndef())) return false;
		zend_string *rawTargetName = zval_get_string(targetParameterName.raw());
		if (UNEXPECTED(rawTargetName == NULL)) return false;
		zv::Str fullTargetName = zv::Str::adopt(rawTargetName);
		/* substr($parameterType->getParameterName(), 1) */
		zv::Str targetName = zv::Str::adopt(ZSTR_LEN(fullTargetName.get()) == 0
			? zend_string_init("", 0, 0)
			: zend_string_init(ZSTR_VAL(fullTargetName.get()) + 1, ZSTR_LEN(fullTargetName.get()) - 1, 0));
		zval *targetParameter = zend_symtable_find(parametersByName.table(), targetName.get());
		if (targetParameter == NULL) return true;
		if (UNEXPECTED(Z_TYPE_P(targetParameter) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(targetParameter));
			return false;
		}

		zv::Val isNegated = pt_type_call(conditional, PT_LC("isnegated"), 0, NULL);
		if (UNEXPECTED(isNegated.isUndef())) return false;
		bool negated = zend_is_true(isNegated.raw());
		zv::Val ifType = negated
			? pt_type_call(conditional, PT_LC("getelse"), 0, NULL)
			: pt_type_call(conditional, PT_LC("getif"), 0, NULL);
		if (UNEXPECTED(ifType.isUndef())) return false;
		zv::Val elseType = negated
			? pt_type_call(conditional, PT_LC("getif"), 0, NULL)
			: pt_type_call(conditional, PT_LC("getelse"), 0, NULL);
		if (UNEXPECTED(elseType.isUndef())) return false;
		zv::Val targetParameterType = pt_type_call(Z_OBJ_P(targetParameter), PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(targetParameterType.isUndef())) return false;
		zv::Val target = pt_type_call(conditional, PT_LC("gettarget"), 0, NULL);
		if (UNEXPECTED(target.isUndef())) return false;

		zv::Args intersectArgs{targetParameterType.raw(), target.raw()};
		zv::Val intersected = pt_type_combinator_intersect(2, intersectArgs);
		if (UNEXPECTED(intersected.isUndef())) return false;
		zv::Val removed = pt_type_combinator_remove(targetParameterType.raw(), target.raw());
		if (UNEXPECTED(removed.isUndef())) return false;

		zv::Val targetVariable = newVariable(ZSTR_VAL(targetName.get()), ZSTR_LEN(targetName.get()));
		if (UNEXPECTED(targetVariable.isUndef())) return false;
		zv::Val parameterVariable = newVariable(ZSTR_VAL(parameterName), ZSTR_LEN(parameterName));
		if (UNEXPECTED(parameterVariable.isUndef())) return false;

		zv::Str key = zv::Str::adopt(zend_strpprintf(0, "$%s", ZSTR_VAL(parameterName)));
		zval *bucket = zend_symtable_find(conditionalTypes.table(), key.get());
		zv::Arr holders;
		if (bucket != NULL && Z_TYPE_P(bucket) == IS_ARRAY) {
			holders = zv::Arr::copyOfTable(Z_ARRVAL_P(bucket));
			holders.separate();
		} else {
			holders = zv::Arr::create(2);
		}

		for (uint32_t i = 0; i < 2; i++) {
			zval conditionHolder;
			pt_holder_create(&conditionHolder, targetVariable.raw(), i == 0 ? intersected.raw() : removed.raw(), PT_TRI_YES);
			zv::Val condition = zv::Val::adopt(conditionHolder);
			zv::Arr conditions = zv::Arr::create(1);
			conditions.set(fullTargetName.get(), std::move(condition));
			zval typeHolder;
			pt_holder_create(&typeHolder, parameterVariable.raw(), i == 0 ? ifType.raw() : elseType.raw(), PT_TRI_YES);
			zv::Val holderType = zv::Val::adopt(typeHolder);
			zv::Args holderArgs{conditions.raw(), holderType.raw()};
			zv::Val holder = pt_type_new_ce(pt_ce_cond_expr_holder, 2, holderArgs);
			if (UNEXPECTED(holder.isUndef())) return false;
			zv::Val holderKey = pt_type_call(Z_OBJ_P(holder.raw()), PT_LC("getkey"), 0, NULL);
			if (UNEXPECTED(holderKey.isUndef())) return false;
			zend_string *rawHolderKey = zval_get_string(holderKey.raw());
			if (UNEXPECTED(rawHolderKey == NULL)) return false;
			holders.set(rawHolderKey, std::move(holder));
			zend_string_release(rawHolderKey);
		}

		conditionalTypes.set(key.get(), zv::Val(std::move(holders)));
		return true;
	}

	/** @api (twin 2370) */
	zv::Val enterNamespace(zend_string *namespaceName)
	{
		zv::Val context = contextCall(PT_LC("beginfile"), 0, NULL);
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		CreateArgs a;
		a.setOwned(CreateArgs::CONTEXT, std::move(context));
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		a.setNull(CreateArgs::FUNCTION);
		a.setOwned(CreateArgs::NAMESPACE_, zv::Val::string(namespaceName));
		a.setEmptyArray(CreateArgs::EXPRESSION_TYPES);
		a.setEmptyArray(CreateArgs::NATIVE_EXPRESSION_TYPES);
		a.setEmptyArray(CreateArgs::CONDITIONAL_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES);
		a.setNull(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION);
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.setNull(CreateArgs::PARENT_SCOPE);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	/* }}} */

	/* {{{ twin 2385-2558: the closure-bind family */

	/* the two tables with $this set to the given types (IS_NULL drops the
	 * entry), as enterClosureBind() and enterClosureCall() build them */
	bool bindThisTables(TablePair &tables, zval *thisType, zval *nativeThisType)
	{
		zv::Str ownedKey = zv::Str::adopt(zend_string_init(PT_LC("$this"), 0));
		zend_string *key = ownedKey.get();
		for (uint32_t i = 0; i < 2; i++) {
			zval *type = i == 0 ? thisType : nativeThisType;
			zv::Arr &table = i == 0 ? tables.expressionTypes : tables.nativeExpressionTypes;
			table.separate();
			if (Z_TYPE_P(type) == IS_NULL) {
				zend_symtable_del(table.table(), key);
				continue;
			}
			/* a Variable of its own per table, as the twin writes it */
			zv::Val thisVariable = newVariable(PT_LC("this"));
			if (UNEXPECTED(thisVariable.isUndef())) return false;
			zval holder;
			pt_holder_create(&holder, thisVariable.raw(), type, PT_TRI_YES);
			zend_symtable_update(table.table(), key, &holder);
		}
		return true;
	}

	/* create(...) with the twin's closure-bind argument list: the tables,
	 * the given bind scope classes, everything else from $this */
	zv::Val createWithTablesAndBindScopeClasses(TablePair &tables, zv::Val scopeClasses)
	{
		CreateArgs a;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONTEXT, "context") || !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions") || !requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) {
			return zv::Val();
		}
		a.set(CreateArgs::CONTEXT, slot(PT_MS_PROP_CONTEXT));
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		zv::Val function = thisGetFunction();
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::FUNCTION, std::move(function));
		zv::Val ns = thisGetNamespace();
		if (UNEXPECTED(ns.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::NAMESPACE_, std::move(ns));
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(tables.expressionTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(tables.nativeExpressionTypes)));
		a.set(CreateArgs::CONDITIONAL_EXPRESSIONS, slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS));
		a.setOwned(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, std::move(scopeClasses));
		a.set(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, slot(PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.setNull(CreateArgs::PARENT_SCOPE);
		a.setBool(CreateArgs::NATIVE_TYPES_PROMOTED, false);
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	zv::Val enterClosureBind(zval *thisType, zval *nativeThisType, zval *scopeClasses)
	{
		TablePair tables(*this);
		if (UNEXPECTED(!bindThisTables(tables, thisType, nativeThisType))) return zv::Val();

		/* if ($scopeClasses === ['static'] && $this->isInClass()) { $scopeClasses = [$this->getClassReflection()->getName()]; } */
		zv::Val ownScopeClasses = zv::Val::copyOf(zv::Ref(scopeClasses));
		if (isSingleStringList(Z_ARRVAL_P(scopeClasses), PT_LC("static"))) {
			bool inClass;
			if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
			if (inClass) {
				zv::Val classReflection = thisGetClassReflection();
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
					return zv::Val();
				}
				zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
				if (UNEXPECTED(className.isUndef())) return zv::Val();
				zv::Arr single = zv::Arr::create(1);
				single.push(std::move(className));
				ownScopeClasses = zv::Val(std::move(single));
			}
		}

		return createWithTablesAndBindScopeClasses(tables, std::move(ownScopeClasses));
	}

	zv::Val restoreOriginalScopeAfterClosureBind(zend_object *originalScopeObject)
	{
		MutatingScope originalScope(originalScopeObject);
		if (UNEXPECTED(!originalScope.requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !originalScope.requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !originalScope.requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) {
			return zv::Val();
		}
		TablePair tables(*this);
		zend_string *key = zend_string_init(PT_LC("$this"), 0);
		zv::Str ownedKey = zv::Str::adopt(key);
		for (uint32_t i = 0; i < 2; i++) {
			uint32_t sourceSlot = i == 0 ? PT_MS_PROP_EXPRESSION_TYPES : PT_MS_PROP_NATIVE_EXPRESSION_TYPES;
			zv::Arr &table = i == 0 ? tables.expressionTypes : tables.nativeExpressionTypes;
			zval *holder = zend_symtable_find(Z_ARRVAL_P(originalScope.slot(sourceSlot).raw()), key);
			table.separate();
			if (holder == NULL || Z_TYPE_P(holder) == IS_NULL) {
				zend_symtable_del(table.table(), key);
				continue;
			}
			zval copy;
			ZVAL_COPY(&copy, holder);
			zend_symtable_update(table.table(), key, &copy);
		}

		return createWithTablesAndBindScopeClasses(tables, zv::Val::copyOf(originalScope.slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES)));
	}

	zv::Val restoreThis(zend_object *restoreThisScopeObject)
	{
		MutatingScope restoreThisScope(restoreThisScopeObject);
		if (UNEXPECTED(!restoreThisScope.requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !restoreThisScope.requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !restoreThisScope.requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) {
			return zv::Val();
		}
		TablePair tables(*this);
		zv::Val inClassResult = pt_type_call(restoreThisScopeObject, PT_LC("isinclass"), 0, NULL);
		if (UNEXPECTED(inClassResult.isUndef())) return zv::Val();
		if (zend_is_true(inClassResult.raw())) {
			for (uint32_t i = 0; i < 2; i++) {
				uint32_t sourceSlot = i == 0 ? PT_MS_PROP_EXPRESSION_TYPES : PT_MS_PROP_NATIVE_EXPRESSION_TYPES;
				zv::Arr &table = i == 0 ? tables.expressionTypes : tables.nativeExpressionTypes;
				for (auto entry : zv::ArrRef(restoreThisScope.slot(sourceSlot).raw())) {
					zend_string *exprString = entry.stringKeyOrNull();
					if (exprString == NULL || ZSTR_LEN(exprString) < 5 || memcmp(ZSTR_VAL(exprString), "$this", 5) != 0) continue;
					table.separate();
					zval copy;
					ZVAL_COPY(&copy, entry.value().raw());
					zend_symtable_update(table.table(), exprString, &copy);
				}
			}
		} else {
			zv::Str thisKey = zv::Str::adopt(zend_string_init(PT_LC("$this"), 0));
			tables.unset(thisKey.get());
		}

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		zv::Val function = thisGetFunction();
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::FUNCTION, std::move(function));
		zv::Val ns = thisGetNamespace();
		if (UNEXPECTED(ns.isUndef())) return zv::Val();
		a.setOwned(CreateArgs::NAMESPACE_, std::move(ns));
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(tables.expressionTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(tables.nativeExpressionTypes)));
		a.set(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, restoreThisScope.slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES));
		/* the twin passes $this->inFirstLevelStatement, not the getter */
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		return scopeFactoryCreate(a);
	}

	zv::Val enterClosureCall(zval *thisType, zval *nativeThisType)
	{
		TablePair tables(*this);
		if (UNEXPECTED(!bindThisTables(tables, thisType, nativeThisType))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(thisType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getObjectClassNames() on %s", zend_zval_value_name(thisType));
			return zv::Val();
		}
		zv::Val classNames = pt_type_op(Z_OBJ_P(thisType), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) return zv::Val();
		return createWithTablesAndBindScopeClasses(tables, std::move(classNames));
	}

	/** @api */
	bool isInClosureBind(bool &out)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, "inClosureBindScopeClasses"))) return false;
		out = zend_hash_num_elements(Z_ARRVAL_P(slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES).raw())) != 0;
		return true;
	}

	zv::Val withClosureBindScopeClasses(zval *scopeClasses)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.set(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, zv::Ref(scopeClasses));
		return scopeFactoryCreate(a);
	}

	/* }}} */

	/* }}} */

	/* {{{ the $this-dispatch helpers of the assignment family */

	static void optionalArg(zval *out, zval *value)
	{
		if (value == NULL) {
			ZVAL_NULL(out);
		} else {
			ZVAL_COPY_VALUE(out, value);
		}
	}

	/* $this->phpVersion-><method>() as a bool; false = pending exception */
	[[nodiscard]] bool phpVersionBool(const char *lcname, size_t len, bool &out)
	{
		zv::Ref phpVersion = slot(PT_MS_PROP_PHP_VERSION);
		if (UNEXPECTED(!phpVersion.isObject())) {
			(void) uninitializedProperty("phpVersion");
			return false;
		}
		return otherCallBool(phpVersion.asObject(), lcname, len, out);
	}

	zv::Val thisGetCurrentExpressionResultStorage()
	{
		return thisCall(PT_LC("getcurrentexpressionresultstorage"), msGetCurrentExpressionResultStorage, 0, NULL, [&]() { return getCurrentExpressionResultStorage(); });
	}

	zv::Val thisEnterAnonymousFunctionWithoutReflection(zend_object *closure, zval *callableParameters, zval *nativeCallableParameters)
	{
		zval args[3];
		ZVAL_OBJ(&args[0], closure);
		optionalArg(&args[1], callableParameters);
		optionalArg(&args[2], nativeCallableParameters);
		return thisCall(PT_LC("enteranonymousfunctionwithoutreflection"), msEnterAnonymousFunctionWithoutReflection, 3, args, [&]() {
			return enterAnonymousFunctionWithoutReflection(closure, callableParameters, nativeCallableParameters);
		});
	}

	zv::Val thisEnterArrowFunctionWithoutReflection(zend_object *arrowFunction, zval *callableParameters, zval *nativeCallableParameters)
	{
		zval args[3];
		ZVAL_OBJ(&args[0], arrowFunction);
		optionalArg(&args[1], callableParameters);
		optionalArg(&args[2], nativeCallableParameters);
		return thisCall(PT_LC("enterarrowfunctionwithoutreflection"), msEnterArrowFunctionWithoutReflection, 3, args, [&]() {
			return enterArrowFunctionWithoutReflection(arrowFunction, callableParameters, nativeCallableParameters);
		});
	}

	/* $this->assignVariable($name, $type, $nativeType, $certainty, $intertwinedPropagatedFrom) */
	zv::Val thisAssignVariable(zval *args)
	{
		return thisCall(PT_LC("assignvariable"), msAssignVariable, 5, args, [&]() {
			return assignVariable(Z_STR(args[0]), &args[1], &args[2], &args[3], &args[4]);
		});
	}

	zv::Val thisAssignExpression(zval *expr, zval *type, zval *nativeType)
	{
		zv::Args args{expr, type, nativeType};
		return thisCall(PT_LC("assignexpression"), msAssignExpression, 3, args, [&]() { return assignExpression(Z_OBJ_P(expr), type, nativeType); });
	}

	zv::Val thisSpecifyExpressionType(zval *expr, zval *type, zval *nativeType, zval *certainty)
	{
		zv::Args args{expr, type, nativeType, certainty};
		return thisCall(PT_LC("specifyexpressiontype"), msSpecifyExpressionType, 4, args, [&]() { return specifyExpressionType(Z_OBJ_P(expr), type, nativeType, certainty); });
	}

	zv::Val thisApplySpecifiedTypes(zval *specifiedTypes)
	{
		return thisCall(PT_LC("applyspecifiedtypes"), msApplySpecifiedTypes, 1, specifiedTypes, [&]() { return applySpecifiedTypes(specifiedTypes); });
	}

	zv::Val thisInvalidateExpression(zval *expr, bool requireMoreCharacters, zval *invalidatingClass, bool keepPropertyFetches)
	{
		zval args[4];
		ZVAL_COPY_VALUE(&args[0], expr);
		ZVAL_BOOL(&args[1], requireMoreCharacters);
		optionalArg(&args[2], invalidatingClass);
		ZVAL_BOOL(&args[3], keepPropertyFetches);
		return thisCall(PT_LC("invalidateexpression"), msInvalidateExpression, 4, args, [&]() {
			return invalidateExpression(expr, requireMoreCharacters, invalidatingClass, keepPropertyFetches);
		});
	}

	/* }}} */

	/* {{{ twin 2560-3990: the anonymous- and arrow-function
	 * entries, the assignment / invalidation family and the specification
	 * machinery */

	/* {{{ foreign scope objects: the twin writes $scope = $this->a()->b(),
	 * so every call after the first runs on whatever the factory answered
	 * — any MutatingScope, a PHP twin under the differential prefix */

	/* a table property of another scope object: its own slot when the
	 * object is (a subclass of) the native class, resolved by name
	 * otherwise; NULL with an Error pending when it has no such property */
	[[nodiscard]] static zval *otherProp(zend_object *object, uint32_t nativeSlot, const char *name, size_t len)
	{
		if (EXPECTED(pt_ce_mutating_scope != NULL && instanceof_function(object->ce, pt_ce_mutating_scope))) return OBJ_PROP_NUM(object, nativeSlot);
		int32_t offset = pt_instance_prop_offset(object->ce, name, len);
		if (UNEXPECTED(offset < 0)) {
			zend_throw_error(NULL, "phpstan_turbo: %s has no property $%s", ZSTR_VAL(object->ce->name), name);
			return NULL;
		}
		return OBJ_PROP(object, (uint32_t) offset);
	}

	/* $other-><method>() answering a bool; false = pending exception */
	[[nodiscard]] static bool otherCallBool(zend_object *object, const char *lcname, size_t len, bool &out)
	{
		zv::Val result = pt_type_call(object, lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* a private method of the twin on another scope object: the native body
	 * when the object is (a subclass of) the native class — private methods
	 * are never registered, so the engine would not find one there — the
	 * object's own method otherwise (the PHP twin under the prefix) */
	template <typename Direct>
	static zv::Val otherPrivate(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv, Direct direct)
	{
		if (EXPECTED(pt_ce_mutating_scope != NULL && instanceof_function(object->ce, pt_ce_mutating_scope))) {
			MutatingScope other(object);
			return direct(other);
		}
		return pt_type_call(object, lcname, len, argc, argv);
	}

	/* $other->getType($expr) / ->getNativeType($expr) */
	static zv::Val otherGetType(zend_object *object, zval *expr, bool native)
	{
		if (native) return pt_type_call(object, PT_LC("getnativetype"), 1, expr);
		return pt_type_call(object, PT_LC("gettype"), 1, expr);
	}

	/* }}} */

	/* {{{ the NodeFinder walks of these entries: findInstanceOf() collects
	 * every match in the same pre-order pt_find_first_recursive() visits,
	 * findFirst() stops at the first one */

	struct InstanceOfCtx
	{
		pt_find_ctx base;
		zend_class_entry *ce;
		zv::Arr *found;
	};

	static bool instanceOfCollector(zend_object *node, void *vctx)
	{
		InstanceOfCtx *ctx = (InstanceOfCtx *) vctx;
		if (instanceof_function(node->ce, ctx->ce)) {
			zval nodeZv;
			ZVAL_OBJ(&nodeZv, node);
			ctx->found->push(zv::Ref(&nodeZv));
		}
		return false;
	}

	/* (new NodeFinder())->findInstanceOf([$expr], $className) */
	static bool findInstancesOf(zend_object *expr, int classIdx, zv::Arr &out)
	{
		zend_class_entry *ce = pt_class(classIdx);
		if (UNEXPECTED(ce == NULL)) return false;
		InstanceOfCtx ctx;
		memset(&ctx, 0, sizeof(ctx));
		ctx.ce = ce;
		ctx.found = &out;
		pt_find_first_recursive(expr, instanceOfCollector, &ctx);
		return EXPECTED(!ctx.base.failed && EG(exception) == NULL);
	}

	struct StaticExprCtx
	{
		pt_find_ctx base;
		zend_class_entry *staticCall;
		zend_class_entry *staticPropertyFetch;
	};

	static bool staticExprMatcher(zend_object *node, void *vctx)
	{
		StaticExprCtx *ctx = (StaticExprCtx *) vctx;
		return instanceof_function(node->ce, ctx->staticCall) || instanceof_function(node->ce, ctx->staticPropertyFetch);
	}

	/* }}} */

	/** @api (twin 2560) */
	zv::Val enterAnonymousFunction(zend_object *closure, zval *callableParameters, zval *nativeCallableParameters)
	{
		zv::Val closureTypeResolver = containerGetByType(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver"));
		if (UNEXPECTED(closureTypeResolver.isUndef())) return zv::Val();
		zend_object *resolverObject = requireObject(closureTypeResolver, "getClosureType");
		if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
		zv::Val storage = thisGetCurrentExpressionResultStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		zv::Args resolverArgs{thisZval(), closure, true, storage.raw()};
		zv::Val anonymousFunctionReflection = pt_type_call(resolverObject, PT_LC("getclosuretype"), 4, resolverArgs);
		if (UNEXPECTED(anonymousFunctionReflection.isUndef())) return zv::Val();

		zv::Val scope = thisEnterAnonymousFunctionWithoutReflection(closure, callableParameters, nativeCallableParameters);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope, "isDeclareStrictTypes");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		return createForFunctionEntry(scopeObject, std::move(anonymousFunctionReflection), false);
	}

	/* the create() argument list enterAnonymousFunction() (2560) and
	 * enterArrowFunction() (2791) build out of the scope their
	 * *WithoutReflection() sibling answered — the arrow function keeps that
	 * scope's afterExtractCall and parentScope, the closure resets them */
	zv::Val createForFunctionEntry(zend_object *scopeObject, zv::Val anonymousFunctionReflection, bool fromArrowFunction)
	{
		CreateArgs a;
		zval *context = otherProp(scopeObject, PT_MS_PROP_CONTEXT, PT_LC("context"));
		zval *expressionTypes = otherProp(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		zval *nativeExpressionTypes = otherProp(scopeObject, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		zval *conditionalExpressions = otherProp(scopeObject, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		zval *inClosureBindScopeClasses = otherProp(scopeObject, PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, PT_LC("inClosureBindScopeClasses"));
		if (UNEXPECTED(context == NULL || expressionTypes == NULL || nativeExpressionTypes == NULL || conditionalExpressions == NULL || inClosureBindScopeClasses == NULL)) {
			return zv::Val();
		}
		a.set(CreateArgs::CONTEXT, zv::Ref(context));
		bool declareStrictTypes;
		if (UNEXPECTED(!otherCallBool(scopeObject, PT_LC("isdeclarestricttypes"), declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		PT_MS_ARG_CREATE(a, CreateArgs::FUNCTION, pt_type_call(scopeObject, PT_LC("getfunction"), 0, NULL));
		PT_MS_ARG_CREATE(a, CreateArgs::NAMESPACE_, pt_type_call(scopeObject, PT_LC("getnamespace"), 0, NULL));
		a.set(CreateArgs::EXPRESSION_TYPES, zv::Ref(expressionTypes));
		a.set(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Ref(nativeExpressionTypes));
		a.set(CreateArgs::CONDITIONAL_EXPRESSIONS, zv::Ref(conditionalExpressions));
		a.set(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, zv::Ref(inClosureBindScopeClasses));
		a.setOwned(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, std::move(anonymousFunctionReflection));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack") || !requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) {
			return zv::Val();
		}
		a.set(CreateArgs::IN_FUNCTION_CALLS_STACK, slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK));
		if (fromArrowFunction) {
			zval *afterExtractCall = otherProp(scopeObject, PT_MS_PROP_AFTER_EXTRACT_CALL, PT_LC("afterExtractCall"));
			zval *parentScope = otherProp(scopeObject, PT_MS_PROP_PARENT_SCOPE, PT_LC("parentScope"));
			if (UNEXPECTED(afterExtractCall == NULL || parentScope == NULL)) return zv::Val();
			a.set(CreateArgs::AFTER_EXTRACT_CALL, zv::Ref(afterExtractCall));
			a.set(CreateArgs::PARENT_SCOPE, zv::Ref(parentScope));
		} else {
			a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
			a.set(CreateArgs::PARENT_SCOPE, zv::Ref(thisZval()));
		}
		a.set(CreateArgs::NATIVE_TYPES_PROMOTED, slot(PT_MS_PROP_NATIVE_TYPES_PROMOTED));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	/* '$' . $name */
	static zend_string *dollarName(zend_string *name)
	{
		return zend_strpprintf(0, "$%s", ZSTR_VAL(name));
	}

	/* ExpressionTypeHolder::createYes($expr, $type) into $table[$key] */
	static void setHolder(zv::Arr &table, zend_string *key, zval *expr, zval *type, zend_long certainty)
	{
		zval holder;
		pt_holder_create(&holder, expr, type, certainty);
		table.set(key, zv::Val::adopt(holder));
	}

	/* the parameter tables of the anonymous- and arrow-function entries:
	 * $this->getFunctionType() narrowed by the callable parameter at $index
	 * where one is given; false = pending exception */
	[[nodiscard]] bool parameterTypes(zend_object *parameter, zval *callableParameters, zval *nativeCallableParameters, zend_long index, zv::Val &parameterType, zv::Val &nativeParameterType)
	{
		zv::Ref typeNode = nodeProp(parameter, PT_LC("type"));
		zv::Ref variadic = nodeProp(parameter, PT_LC("variadic"));
		if (UNEXPECTED(typeNode.raw() == NULL || variadic.raw() == NULL)) return false;
		zval parameterZv;
		ZVAL_OBJ(&parameterZv, parameter);
		bool isNullable;
		if (UNEXPECTED(!thisIsParameterValueNullable(&parameterZv, isNullable))) return false;
		parameterType = thisGetFunctionType(typeNode.deref().raw(), isNullable, zend_is_true(variadic.deref().raw()));
		if (UNEXPECTED(parameterType.isUndef())) return false;
		nativeParameterType = zv::Val::copyOf(parameterType.ref());
		if (callableParameters != NULL) {
			zv::Val callableType = getCallableParameterType(parameter, callableParameters, index);
			if (UNEXPECTED(callableType.isUndef())) return false;
			parameterType = intersectButNotNever(parameterType.raw(), callableType.raw());
			if (UNEXPECTED(parameterType.isUndef())) return false;
		}
		if (nativeCallableParameters != NULL) {
			zv::Val callableType = getCallableParameterType(parameter, nativeCallableParameters, index);
			if (UNEXPECTED(callableType.isUndef())) return false;
			nativeParameterType = intersectButNotNever(nativeParameterType.raw(), callableType.raw());
			if (UNEXPECTED(nativeParameterType.isUndef())) return false;
		}
		return true;
	}

	/* (twin 2596) */
	zv::Val enterAnonymousFunctionWithoutReflection(zend_object *closure, zval *callableParameters, zval *nativeCallableParameters)
	{
		zv::Arr expressionTypes = zv::Arr::create(0);
		zv::Arr nativeTypes = zv::Arr::create(0);

		zv::Ref params = nodeProp(closure, PT_LC("params"));
		if (UNEXPECTED(params.raw() == NULL)) return zv::Val();
		for (auto entry : zv::ArrRef(params.deref().raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_string *name = parameterVariableName(parameter.asObject());
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Str key = zv::Str::adopt(dollarName(name));
			zv::Val parameterType, nativeParameterType;
			if (UNEXPECTED(!parameterTypes(parameter.asObject(), callableParameters, nativeCallableParameters, (zend_long) entry.indexKey(), parameterType, nativeParameterType))) {
				return zv::Val();
			}
			zv::Ref var = nodeProp(parameter.asObject(), PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
			setHolder(expressionTypes, key.get(), var.deref().raw(), parameterType.raw(), PT_TRI_YES);
			setHolder(nativeTypes, key.get(), var.deref().raw(), nativeParameterType.raw(), PT_TRI_YES);
		}

		zv::ScratchTable nonRefVariableNames(8);
		zv::ScratchTable useVariableNames(8);
		zval marker;
		ZVAL_TRUE(&marker);
		zv::Ref uses = nodeProp(closure, PT_LC("uses"));
		if (UNEXPECTED(uses.raw() == NULL)) return zv::Val();
		for (auto entry : zv::ArrRef(uses.deref().raw())) {
			zv::Ref use = entry.value().deref();
			if (UNEXPECTED(!use.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Ref var = nodeProp(use.asObject(), PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
			if (UNEXPECTED(!var.deref().isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Ref nameRef = nodeProp(var.deref().asObject(), PT_LC("name"));
			if (UNEXPECTED(nameRef.raw() == NULL)) return zv::Val();
			if (UNEXPECTED(!nameRef.deref().isString())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_string *variableName = nameRef.deref().asString();
			zv::Str key = zv::Str::adopt(dollarName(variableName));
			zend_symtable_update(useVariableNames.table(), key.get(), &marker);

			zv::Ref byRef = nodeProp(use.asObject(), PT_LC("byRef"));
			if (UNEXPECTED(byRef.raw() == NULL)) return zv::Val();
			if (zend_is_true(byRef.deref().raw())) {
				zval mixedZv;
				if (UNEXPECTED(!pt_mixed_type_new(&mixedZv))) return zv::Val();
				zv::Val mixedType = zv::Val::adopt(mixedZv);
				zval holderZv;
				pt_holder_create(&holderZv, var.deref().raw(), mixedType.raw(), PT_TRI_YES);
				zv::Val holder = zv::Val::adopt(holderZv);
				expressionTypes.set(key.get(), zv::Val::copyOf(holder.ref()));
				nativeTypes.set(key.get(), std::move(holder));
				continue;
			}
			zend_symtable_update(nonRefVariableNames.table(), variableName, &marker);

			zval nameZv;
			ZVAL_STR(&nameZv, variableName);
			zv::Val variableType, variableNativeType;
			zv::Val has = thisHasVariableType(&nameZv);
			if (UNEXPECTED(has.isUndef())) return zv::Val();
			zend_long certainty = pt_type_trinary_value(has.raw());
			if (UNEXPECTED(certainty < 0)) return zv::Val();
			if (certainty == PT_TRI_NO) {
				zval errorZv;
				if (UNEXPECTED(!pt_error_type_new(&errorZv))) return zv::Val();
				variableType = zv::Val::adopt(errorZv);
				zval nativeErrorZv;
				if (UNEXPECTED(!pt_error_type_new(&nativeErrorZv))) return zv::Val();
				variableNativeType = zv::Val::adopt(nativeErrorZv);
			} else {
				variableType = thisGetVariableType(&nameZv);
				if (UNEXPECTED(variableType.isUndef())) return zv::Val();
				/* a plain variable read is scope state — never priced via
				 * the node, which may not have been processed yet */
				zv::Val nativeScope = thisDoNotTreatPhpDocTypesAsCertain();
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				zend_object *nativeScopeObject = requireObject(nativeScope, "hasVariableType");
				if (UNEXPECTED(nativeScopeObject == NULL)) return zv::Val();
				zv::Val nativeHas = pt_type_call(nativeScopeObject, PT_LC("hasvariabletype"), 1, &nameZv);
				if (UNEXPECTED(nativeHas.isUndef())) return zv::Val();
				zend_long nativeCertainty = pt_type_trinary_value(nativeHas.raw());
				if (UNEXPECTED(nativeCertainty < 0)) return zv::Val();
				if (nativeCertainty == PT_TRI_NO) {
					zval errorZv;
					if (UNEXPECTED(!pt_error_type_new(&errorZv))) return zv::Val();
					variableNativeType = zv::Val::adopt(errorZv);
				} else {
					variableNativeType = pt_type_call(nativeScopeObject, PT_LC("getvariabletype"), 1, &nameZv);
					if (UNEXPECTED(variableNativeType.isUndef())) return zv::Val();
				}
			}
			setHolder(expressionTypes, key.get(), var.deref().raw(), variableType.raw(), PT_TRI_YES);
			setHolder(nativeTypes, key.get(), var.deref().raw(), variableNativeType.raw(), PT_TRI_YES);
		}

		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		zv::Val nonStaticExpressions = invalidateStaticExpressions(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()));
		if (UNEXPECTED(nonStaticExpressions.isUndef())) return zv::Val();
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return zv::Val();
		for (auto entry : zv::ArrRef(nonStaticExpressions.raw())) {
			zend_string *exprString = entry.stringKeyOrNull();
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			if (UNEXPECTED(!expr.ref().isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: ExpressionTypeHolder::getExpr() must return an object");
				return zv::Val();
			}
			if (instanceof_function(expr.ref().asObject()->ce, variableCe)) continue;
			zv::Arr variables = zv::Arr::create(0);
			if (UNEXPECTED(!findInstancesOf(expr.ref().asObject(), PT_CLASS_VARIABLE, variables))) return zv::Val();
			if (zend_hash_num_elements(variables.table()) == 0) {
				bool unchangeable;
				if (UNEXPECTED(!expressionTypeIsUnchangeable(entry.value(), unchangeable))) return zv::Val();
				if (!unchangeable) continue;
			}
			bool skip = false;
			for (auto variableEntry : variables.arrRef()) {
				zv::Ref variableName = nodeProp(Z_OBJ_P(variableEntry.value().raw()), PT_LC("name"));
				if (UNEXPECTED(variableName.raw() == NULL)) return zv::Val();
				if (!variableName.deref().isString()
					|| !zend_hash_exists(nonRefVariableNames.table(), variableName.deref().asString())) {
					skip = true;
					break;
				}
			}
			if (skip || exprString == NULL) continue;
			expressionTypes.set(exprString, zv::Val::copyOf(entry.value().deref()));
		}

		zval thisNameZv;
		ZVAL_STR(&thisNameZv, ZSTR_KNOWN(ZEND_STR_THIS));
		zv::Val hasThis = thisHasVariableType(&thisNameZv);
		if (UNEXPECTED(hasThis.isUndef())) return zv::Val();
		zend_long thisCertainty = pt_type_trinary_value(hasThis.raw());
		if (UNEXPECTED(thisCertainty < 0)) return zv::Val();
		zv::Ref isStatic = nodeProp(closure, PT_LC("static"));
		if (UNEXPECTED(isStatic.raw() == NULL)) return zv::Val();
		if (thisCertainty == PT_TRI_YES && !zend_is_true(isStatic.deref().raw())) {
			zv::Val node = newVariable(PT_LC("this"));
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			zv::Val type = thisGetType(node.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val nativeType = thisGetNativeType(node.raw());
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			zv::Str thisKey = zv::Str::adopt(zend_string_init(PT_LC("$this"), 0));
			setHolder(expressionTypes, thisKey.get(), node.raw(), type.raw(), PT_TRI_YES);
			setHolder(nativeTypes, thisKey.get(), node.raw(), nativeType.raw(), PT_TRI_YES);

			bool supportsReadOnlyProperties;
			if (UNEXPECTED(!phpVersionBool(PT_LC("supportsreadonlyproperties"), supportsReadOnlyProperties))) return zv::Val();
			if (supportsReadOnlyProperties) {
				for (auto entry : zv::ArrRef(nonStaticExpressions.raw())) {
					zend_string *exprString = entry.stringKeyOrNull();
					if (exprString == NULL) continue;
					zv::Val expr = holderExpr(entry.value());
					if (UNEXPECTED(expr.isUndef())) return zv::Val();
					bool isPropertyFetch;
					if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_PROPERTY_FETCH, isPropertyFetch))) return zv::Val();
					if (!isPropertyFetch) continue;
					bool readonly;
					if (UNEXPECTED(!thisIsReadonlyPropertyFetch(expr.raw(), true, readonly))) return zv::Val();
					if (!readonly) continue;
					expressionTypes.set(exprString, zv::Val::copyOf(entry.value().deref()));
				}
			}
		}

		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) return zv::Val();
		zv::Arr filteredConditionalExpressions = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw())) {
			zend_string *conditionalExprString = entry.stringKeyOrNull();
			if (conditionalExprString == NULL || !zend_hash_exists(useVariableNames.table(), conditionalExprString)) continue;
			zv::Arr filteredHolders = zv::Arr::create(0);
			for (auto holderEntry : zv::ArrRef(entry.value().deref().raw())) {
				if (UNEXPECTED(!holderEntry.value().deref().isObject())) {
					zend_throw_error(NULL, "Call to a member function getConditionExpressionTypeHolders() on %s", zend_zval_value_name(holderEntry.value().deref().raw()));
					return zv::Val();
				}
				zv::Val conditionHolders = pt_type_call(holderEntry.value().deref().asObject(), PT_LC("getconditionexpressiontypeholders"), 0, NULL);
				if (UNEXPECTED(conditionHolders.isUndef())) return zv::Val();
				bool allUsed = true;
				for (auto conditionEntry : zv::ArrRef(conditionHolders.raw())) {
					zend_string *holderExprString = conditionEntry.stringKeyOrNull();
					if (holderExprString == NULL || !zend_hash_exists(useVariableNames.table(), holderExprString)) {
						allUsed = false;
						break;
					}
				}
				if (!allUsed) continue;
				filteredHolders.push(holderEntry.value().deref());
			}
			if (zend_hash_num_elements(filteredHolders.table()) == 0) continue;
			filteredConditionalExpressions.set(conditionalExprString, zv::Val(std::move(filteredHolders)));
		}

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		zv::Val constantTypes = getConstantTypes();
		if (UNEXPECTED(constantTypes.isUndef())) return zv::Val();
		PT_MS_ARG_CREATE(a, CreateArgs::EXPRESSION_TYPES, arrayMerge(Z_ARRVAL_P(constantTypes.raw()), expressionTypes.table()));
		zv::Val nativeConstantTypes = getNativeConstantTypes();
		if (UNEXPECTED(nativeConstantTypes.isUndef())) return zv::Val();
		PT_MS_ARG_CREATE(a, CreateArgs::NATIVE_EXPRESSION_TYPES, arrayMerge(Z_ARRVAL_P(nativeConstantTypes.raw()), nativeTypes.table()));
		a.setOwned(CreateArgs::CONDITIONAL_EXPRESSIONS, zv::Val(std::move(filteredConditionalExpressions)));
		zval closureTypeZv;
		if (UNEXPECTED(!pt_closure_type_new(&closureTypeZv))) return zv::Val();
		a.setOwned(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, zv::Val::adopt(closureTypeZv));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.setBool(CreateArgs::AFTER_EXTRACT_CALL, false);
		a.set(CreateArgs::PARENT_SCOPE, zv::Ref(thisZval()));
		return scopeFactoryCreate(a);
	}

	/* private (twin 2741); false = pending exception */
	[[nodiscard]] bool expressionTypeIsUnchangeable(zv::Ref typeHolder, bool &out)
	{
		out = false;
		zv::Val expr = holderExpr(typeHolder);
		if (UNEXPECTED(expr.isUndef())) return false;
		bool isFuncCall;
		if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_FUNC_CALL, isFuncCall))) return false;
		if (!isFuncCall) return true;
		zend_object *call = expr.ref().asObject();
		zv::Val firstClassCallable = pt_type_call(call, PT_LC("isfirstclasscallable"), 0, NULL);
		if (UNEXPECTED(firstClassCallable.isUndef())) return false;
		if (zend_is_true(firstClassCallable.raw())) return true;
		zv::Ref name = nodeProp(call, PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return false;
		bool isFullyQualified;
		if (UNEXPECTED(!isInstance(name.deref(), PT_CLASS_FULLY_QUALIFIED, isFullyQualified))) return false;
		if (!isFullyQualified) return true;
		zv::Val lower = pt_type_call(name.deref().asObject(), PT_LC("tolowerstring"), 0, NULL);
		if (UNEXPECTED(lower.isUndef())) return false;
		static const char *const existenceChecks[] = {
			"class_exists", "interface_exists", "trait_exists", "enum_exists", "function_exists",
		};
		bool isExistenceCheck = false;
		if (lower.ref().isString()) {
			for (const char *candidate : existenceChecks) {
				if (zend_string_equals_cstr(lower.ref().asString(), candidate, strlen(candidate))) {
					isExistenceCheck = true;
					break;
				}
			}
		}
		if (!isExistenceCheck) return true;
		zv::Val args = pt_type_call(call, PT_LC("getargs"), 0, NULL);
		if (UNEXPECTED(args.isUndef())) return false;
		zval *firstArg = zend_hash_index_find(Z_ARRVAL_P(args.raw()), 0);
		if (firstArg == NULL || Z_TYPE_P(firstArg) == IS_NULL) return true;
		zv::Ref value = nodeProp(Z_OBJ_P(firstArg), PT_LC("value"));
		if (UNEXPECTED(value.raw() == NULL)) return false;
		if (UNEXPECTED(!value.deref().isObject())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getScopeStateType(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(value.deref().raw()));
			return false;
		}
		zv::Val argType = getScopeStateType(value.deref().asObject());
		if (UNEXPECTED(argType.isUndef())) return false;
		if (UNEXPECTED(!argType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getConstantStrings() on %s", zend_zval_value_name(argType.raw()));
			return false;
		}
		zv::Val constantStrings = pt_type_call(argType.ref().asObject(), PT_LC("getconstantstrings"), 0, NULL);
		if (UNEXPECTED(constantStrings.isUndef())) return false;
		if (Z_TYPE_P(constantStrings.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) != 1) return true;
		zv::Val type = holderType(typeHolder);
		if (UNEXPECTED(type.isUndef())) return false;
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(type.raw()));
			return false;
		}
		zv::Val isTrue = pt_type_call(type.ref().asObject(), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue.isUndef())) return false;
		out = pt_type_trinary_value(isTrue.raw()) == PT_TRI_YES;
		return EXPECTED(EG(exception) == NULL);
	}

	/* private (twin 2769) */
	zv::Val invalidateStaticExpressions(HashTable *expressionTypes)
	{
		StaticExprCtx ctx;
		memset(&ctx, 0, sizeof(ctx));
		ctx.staticCall = pt_class(PT_CLASS_STATIC_CALL);
		ctx.staticPropertyFetch = pt_class(PT_CLASS_STATIC_PROPERTY_FETCH);
		if (UNEXPECTED(ctx.staticCall == NULL || ctx.staticPropertyFetch == NULL)) return zv::Val();
		zv::Arr filtered = zv::Arr::create(zend_hash_num_elements(expressionTypes));
		for (auto entry : zv::TableRef(expressionTypes)) {
			zv::Val expr = holderExpr(entry.value());
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			if (UNEXPECTED(!expr.ref().isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: ExpressionTypeHolder::getExpr() must return an object");
				return zv::Val();
			}
			ctx.base.failed = false;
			zend_object *staticExpression = pt_find_first_recursive(expr.ref().asObject(), staticExprMatcher, &ctx);
			if (UNEXPECTED(ctx.base.failed)) return zv::Val();
			if (staticExpression != NULL) continue;
			zval copy;
			ZVAL_COPY(&copy, entry.value().deref().raw());
			pt_ht_update(filtered.table(), entry.stringKeyOrNull(), entry.indexKey(), &copy);
		}
		return zv::Val(std::move(filtered));
	}

	/** @api (twin 2791) */
	zv::Val enterArrowFunction(zend_object *arrowFunction, zval *callableParameters, zval *nativeCallableParameters)
	{
		zv::Val closureTypeResolver = containerGetByType(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver"));
		if (UNEXPECTED(closureTypeResolver.isUndef())) return zv::Val();
		zend_object *resolverObject = requireObject(closureTypeResolver, "getClosureType");
		if (UNEXPECTED(resolverObject == NULL)) return zv::Val();
		zv::Val storage = thisGetCurrentExpressionResultStorage();
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		zv::Args resolverArgs{thisZval(), arrowFunction, true, storage.raw()};
		zv::Val anonymousFunctionReflection = pt_type_call(resolverObject, PT_LC("getclosuretype"), 4, resolverArgs);
		if (UNEXPECTED(anonymousFunctionReflection.isUndef())) return zv::Val();

		zv::Val scope = thisEnterArrowFunctionWithoutReflection(arrowFunction, callableParameters, nativeCallableParameters);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope, "isDeclareStrictTypes");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		return createForFunctionEntry(scopeObject, std::move(anonymousFunctionReflection), true);
	}

	/* (twin 2823) */
	zv::Val enterArrowFunctionWithoutReflection(zend_object *arrowFunction, zval *callableParameters, zval *nativeCallableParameters)
	{
		zv::Val arrowFunctionScope = self_();
		zv::Ref params = nodeProp(arrowFunction, PT_LC("params"));
		if (UNEXPECTED(params.raw() == NULL)) return zv::Val();
		for (auto entry : zv::ArrRef(params.deref().raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val parameterType, nativeParameterType;
			if (UNEXPECTED(!parameterTypes(parameter.asObject(), callableParameters, nativeCallableParameters, (zend_long) entry.indexKey(), parameterType, nativeParameterType))) {
				return zv::Val();
			}
			zend_string *name = parameterVariableName(parameter.asObject());
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zval assignArgs[5];
			ZVAL_STR(&assignArgs[0], name);
			ZVAL_COPY_VALUE(&assignArgs[1], parameterType.raw());
			ZVAL_COPY_VALUE(&assignArgs[2], nativeParameterType.raw());
			ZVAL_COPY_VALUE(&assignArgs[3], pt_trinary_singleton(PT_TRI_YES));
			ZVAL_EMPTY_ARRAY(&assignArgs[4]);
			zend_object *scopeObject = requireObject(arrowFunctionScope, "assignVariable");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			arrowFunctionScope = pt_type_call(scopeObject, PT_LC("assignvariable"), 5, assignArgs);
			if (UNEXPECTED(arrowFunctionScope.isUndef())) return zv::Val();
		}

		zv::Ref isStatic = nodeProp(arrowFunction, PT_LC("static"));
		if (UNEXPECTED(isStatic.raw() == NULL)) return zv::Val();
		if (zend_is_true(isStatic.deref().raw())) {
			zv::Val thisVariable = newVariable(PT_LC("this"));
			if (UNEXPECTED(thisVariable.isUndef())) return zv::Val();
			zend_object *scopeObject = requireObject(arrowFunctionScope, "invalidateExpression");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			arrowFunctionScope = pt_type_call(scopeObject, PT_LC("invalidateexpression"), 1, thisVariable.raw());
			if (UNEXPECTED(arrowFunctionScope.isUndef())) return zv::Val();
		}

		zend_object *scopeObject = requireObject(arrowFunctionScope, "getFunction");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zval *context = otherProp(scopeObject, PT_MS_PROP_CONTEXT, PT_LC("context"));
		zval *scopeExpressionTypes = otherProp(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		zval *scopeNativeExpressionTypes = otherProp(scopeObject, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		zval *scopeConditionalExpressions = otherProp(scopeObject, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		zval *scopeInClosureBindScopeClasses = otherProp(scopeObject, PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, PT_LC("inClosureBindScopeClasses"));
		zval *scopeAfterExtractCall = otherProp(scopeObject, PT_MS_PROP_AFTER_EXTRACT_CALL, PT_LC("afterExtractCall"));
		zval *scopeParentScope = otherProp(scopeObject, PT_MS_PROP_PARENT_SCOPE, PT_LC("parentScope"));
		if (UNEXPECTED(context == NULL || scopeExpressionTypes == NULL || scopeNativeExpressionTypes == NULL
			|| scopeConditionalExpressions == NULL || scopeInClosureBindScopeClasses == NULL
			|| scopeAfterExtractCall == NULL || scopeParentScope == NULL)) {
			return zv::Val();
		}

		CreateArgs a;
		a.set(CreateArgs::CONTEXT, zv::Ref(context));
		bool declareStrictTypes;
		if (UNEXPECTED(!thisIsDeclareStrictTypes(declareStrictTypes))) return zv::Val();
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, declareStrictTypes);
		PT_MS_ARG_CREATE(a, CreateArgs::FUNCTION, pt_type_call(scopeObject, PT_LC("getfunction"), 0, NULL));
		PT_MS_ARG_CREATE(a, CreateArgs::NAMESPACE_, pt_type_call(scopeObject, PT_LC("getnamespace"), 0, NULL));
		PT_MS_ARG_CREATE(a, CreateArgs::EXPRESSION_TYPES, invalidateStaticExpressions(Z_ARRVAL_P(scopeExpressionTypes)));
		a.set(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Ref(scopeNativeExpressionTypes));
		a.set(CreateArgs::CONDITIONAL_EXPRESSIONS, zv::Ref(scopeConditionalExpressions));
		a.set(CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, zv::Ref(scopeInClosureBindScopeClasses));
		zval closureTypeZv;
		if (UNEXPECTED(!pt_closure_type_new(&closureTypeZv))) return zv::Val();
		a.setOwned(CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, zv::Val::adopt(closureTypeZv));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, true);
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		a.set(CreateArgs::AFTER_EXTRACT_CALL, zv::Ref(scopeAfterExtractCall));
		a.set(CreateArgs::PARENT_SCOPE, zv::Ref(scopeParentScope));
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, "templateArgumentConstraints"))) return zv::Val();
		a.set(CreateArgs::NATIVE_TYPES_PROMOTED, slot(PT_MS_PROP_NATIVE_TYPES_PROMOTED));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_FRAME, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME));
		a.set(CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, slot(PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS));
		return scopeFactoryCreate(a);
	}

	/* the last value of a list, NULL for an empty one (array_last()) */
	static zval *arrayLast(HashTable *table)
	{
		zval *last = NULL;
		for (auto entry : zv::TableRef(table)) {
			last = entry.value().deref().raw();
		}
		return last;
	}

	/* $parameter->getType() of a ParameterReflection; UNDEF = pending exception */
	static zv::Val parameterReflectionType(zval *parameter)
	{
		if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(parameter));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(parameter), PT_LC("gettype"), 0, NULL);
	}

	/* $parameter->isVariadic(); false with an exception pending on failure */
	[[nodiscard]] static bool parameterReflectionIsVariadic(zval *parameter, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isVariadic() on %s", zend_zval_value_name(parameter));
			return false;
		}
		return otherCallBool(Z_OBJ_P(parameter), PT_LC("isvariadic"), out);
	}

	/* private (twin 2921) */
	zv::Val getCallableParameterType(zend_object *parameter, zval *callableParameters, zend_long index)
	{
		zv::Ref variadic = nodeProp(parameter, PT_LC("variadic"));
		if (UNEXPECTED(variadic.raw() == NULL)) return zv::Val();
		if (zend_is_true(variadic.deref().raw())) return buildVariadicArrayTypeFromCallableParameters(callableParameters, index);
		HashTable *parameters = Z_ARRVAL_P(callableParameters);
		zval *atIndex = zend_hash_index_find(parameters, (zend_ulong) index);
		if (atIndex != NULL && Z_TYPE_P(atIndex) != IS_NULL) return parameterReflectionType(atIndex);
		if (zend_hash_num_elements(parameters) != 0) {
			zval *lastParameter = arrayLast(parameters);
			bool isVariadic;
			if (UNEXPECTED(!parameterReflectionIsVariadic(lastParameter, isVariadic))) return zv::Val();
			if (isVariadic) return parameterReflectionType(lastParameter);
		}
		zval mixedZv;
		if (UNEXPECTED(!pt_mixed_type_new(&mixedZv))) return zv::Val();
		return zv::Val::adopt(mixedZv);
	}

	/* private (twin 2946) */
	zv::Val buildVariadicArrayTypeFromCallableParameters(zval *callableParameters, zend_long startIndex)
	{
		HashTable *parameters = Z_ARRVAL_P(callableParameters);
		uint32_t count = zend_hash_num_elements(parameters);
		zv::Arr elementTypes = zv::Arr::create(0);
		for (zend_long j = startIndex; j < (zend_long) count; j++) {
			zval *parameter = zend_hash_index_find(parameters, (zend_ulong) j);
			if (parameter == NULL) {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, j);
				if (UNEXPECTED(EG(exception))) return zv::Val();
			}
			zval nullZv;
			ZVAL_NULL(&nullZv);
			zval *entry = parameter == NULL ? &nullZv : parameter;
			zv::Val type = parameterReflectionType(entry);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			elementTypes.push(std::move(type));
			bool isVariadic;
			if (UNEXPECTED(!parameterReflectionIsVariadic(entry, isVariadic))) return zv::Val();
			if (isVariadic) break;
		}

		if (zend_hash_num_elements(elementTypes.table()) == 0 && count > 0) {
			zval *lastParameter = arrayLast(parameters);
			bool isVariadic;
			if (UNEXPECTED(!parameterReflectionIsVariadic(lastParameter, isVariadic))) return zv::Val();
			if (isVariadic) {
				zv::Val type = parameterReflectionType(lastParameter);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				elementTypes.push(std::move(type));
			}
		}

		if (zend_hash_num_elements(elementTypes.table()) == 0) {
			zval mixedZv;
			if (UNEXPECTED(!pt_mixed_type_new(&mixedZv))) return zv::Val();
			return zv::Val::adopt(mixedZv);
		}

		uint32_t elementCount = zend_hash_num_elements(elementTypes.table());
		ALLOCA_FLAG(useHeap)
		zval *unionArgv = (zval *) do_alloca(sizeof(zval) * elementCount, useHeap);
		uint32_t i = 0;
		for (auto entry : elementTypes.arrRef()) {
			ZVAL_COPY_VALUE(&unionArgv[i++], entry.value().deref().raw());
		}
		zv::Val elementType = pt_type_combinator_union(elementCount, unionArgv);
		free_alloca(unionArgv, useHeap);
		if (UNEXPECTED(elementType.isUndef())) return zv::Val();
		bool supportsNamedArguments;
		if (UNEXPECTED(!phpVersionSupportsNamedArguments(supportsNamedArguments))) return zv::Val();
		return variadicArrayType(elementType.raw(), supportsNamedArguments);
	}

	/** @api (twin 2977, static) */
	static zv::Val intersectButNotNever(zval *nativeType, zval *inferredType)
	{
		if (UNEXPECTED(Z_TYPE_P(nativeType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(nativeType));
			return zv::Val();
		}
		zv::Val isSuperType = pt_type_op(Z_OBJ_P(nativeType), PT_OP_IS_SUPER_TYPE_OF, 1, inferredType);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!isSuperType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function no() on %s", zend_zval_value_name(isSuperType.raw()));
			return zv::Val();
		}
		zv::Val no = pt_type_call(isSuperType.ref().asObject(), PT_LC("no"), 0, NULL);
		if (UNEXPECTED(no.isUndef())) return zv::Val();
		if (zend_is_true(no.raw())) return zv::Val::copyOf(zv::Ref(nativeType));

		zv::Args args{nativeType, inferredType};
		zv::Val result = pt_type_combinator_intersect(2, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		// the inferred type says no value is ever produced - the native
		// type's nullability must not resurrect one
		if (zv::Ref(result.raw()).instanceOf(pt_ce_never_type)) return result;
		bool containsNull;
		if (UNEXPECTED(!pt_type_combinator_contains_null(nativeType, containsNull))) return zv::Val();
		if (containsNull) return pt_type_combinator_add_null(result.raw());
		return result;
	}

	/* (twin 2991) */
	zv::Val enterMatch(zend_object *expr, zval *condType, zval *condNativeType)
	{
		zv::Ref cond = nodeProp(expr, PT_LC("cond"));
		if (UNEXPECTED(cond.raw() == NULL)) return zv::Val();
		bool isVariable;
		if (UNEXPECTED(!isInstance(cond.deref(), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
		if (isVariable) return self_();
		zv::Val inner;
		bool isAlwaysRemembered;
		if (UNEXPECTED(!isInstance(cond.deref(), PT_CLASS_ALWAYS_REMEMBERED_EXPR, isAlwaysRemembered))) return zv::Val();
		if (isAlwaysRemembered) {
			zv::Ref wrapped = nodeProp(cond.deref().asObject(), PT_LC("expr"));
			if (UNEXPECTED(wrapped.raw() == NULL)) return zv::Val();
			inner = zv::Val::copyOf(wrapped.deref());
		} else {
			inner = zv::Val::copyOf(cond.deref());
		}
		bool isScalar;
		if (UNEXPECTED(!isInstance(inner.ref(), PT_CLASS_SCALAR, isScalar))) return zv::Val();
		if (isScalar) return self_();

		zv::Args condArgs{inner.raw(), condType, condNativeType};
		zv::Val condExpr = pt_type_new(PT_CLASS_ALWAYS_REMEMBERED_EXPR, 3, condArgs);
		if (UNEXPECTED(condExpr.isUndef())) return zv::Val();
		zend_update_property(expr->ce, expr, PT_LC("cond"), condExpr.raw());
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return thisAssignExpression(condExpr.raw(), condType, condNativeType);
	}

	/* $originalScope->getIterableKeyType($type) / ->getIterableValueType($type) */
	static zv::Val iterableType(zend_object *originalScope, zval *iteratee, bool key)
	{
		if (key) return pt_type_call(originalScope, PT_LC("getiterablekeytype"), 1, iteratee);
		return pt_type_call(originalScope, PT_LC("getiterablevaluetype"), 1, iteratee);
	}

	/* $scope->assignExpression($expr, $type, $nativeType) on any scope */
	static zv::Val otherAssignExpression(zv::Val &scope, zval *expr, zval *type, zval *nativeType)
	{
		zend_object *object = requireObject(scope, "assignExpression");
		if (UNEXPECTED(object == NULL)) return zv::Val();
		zv::Args args{expr, type, nativeType};
		return pt_type_call(object, PT_LC("assignexpression"), 3, args);
	}

	/* $scope->overwriteExpression($expr, $type, $nativeType) on any scope */
	static zv::Val otherOverwriteExpression(zv::Val &scope, zval *expr, zval *type, zval *nativeType)
	{
		zend_object *object = requireObject(scope, "overwriteExpression");
		if (UNEXPECTED(object == NULL)) return zv::Val();
		zv::Args args{expr, type, nativeType};
		return otherPrivate(object, PT_LC("overwriteexpression"), 3, args, [&](MutatingScope &other) {
			return other.overwriteExpression(expr, type, nativeType);
		});
	}

	/* (twin 3013) */
	zv::Val enterForeach(zend_object *originalScope, zval *iteratee, zval *iterateeType, zval *nativeIterateeType, zend_string *valueName, zend_string *keyName, bool valueByRef)
	{
		zv::Val valueType = iterableType(originalScope, iterateeType, false);
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zv::Val nativeValueType = iterableType(originalScope, nativeIterateeType, false);
		if (UNEXPECTED(nativeValueType.isUndef())) return zv::Val();
		zval assignArgs[5];
		ZVAL_STR(&assignArgs[0], valueName);
		ZVAL_COPY_VALUE(&assignArgs[1], valueType.raw());
		ZVAL_COPY_VALUE(&assignArgs[2], nativeValueType.raw());
		ZVAL_COPY_VALUE(&assignArgs[3], pt_trinary_singleton(PT_TRI_YES));
		ZVAL_EMPTY_ARRAY(&assignArgs[4]);
		zv::Val scope = thisAssignVariable(assignArgs);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		/* Track the original foreach value so narrowings applied to the
		 * value variable can later be projected back onto the corresponding
		 * array dim fetch without being confused by a reassignment */
		zval valueNameZv;
		ZVAL_STR(&valueNameZv, valueName);
		zv::Val originalValueExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_VALUE_EXPR, 1, &valueNameZv);
		if (UNEXPECTED(originalValueExpr.isUndef())) return zv::Val();
		scope = otherAssignExpression(scope, originalValueExpr.raw(), valueType.raw(), nativeValueType.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		bool writeThrough;
		if (UNEXPECTED(!iterateeIsNonConstantArray(iterateeType, writeThrough))) return zv::Val();
		writeThrough = writeThrough && valueByRef;
		if (writeThrough) {
			/* the write-through rebuilds the iteratee AT FOREACH ENTRY with
			 * the value variable's latest type - captured here, not read live */
			zv::Val keyTypeExpr = nativeTypeExprOf(iterableType(originalScope, iterateeType, true), iterableType(originalScope, nativeIterateeType, true));
			if (UNEXPECTED(keyTypeExpr.isUndef())) return zv::Val();
			zv::Args iterateeTypeArgs{iterateeType, nativeIterateeType};
			zv::Val iterateeTypeExpr = pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, iterateeTypeArgs);
			if (UNEXPECTED(iterateeTypeExpr.isUndef())) return zv::Val();
			zv::Val valueVariable = newVariable(ZSTR_VAL(valueName), ZSTR_LEN(valueName));
			if (UNEXPECTED(valueVariable.isUndef())) return zv::Val();
			zv::Args setArgs{iterateeTypeExpr.raw(), keyTypeExpr.raw(), valueVariable.raw()};
			zv::Val setExpr = pt_type_new(PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, 3, setArgs);
			if (UNEXPECTED(setExpr.isUndef())) return zv::Val();
			zv::Args intertwinedArgs{valueName, iteratee, setExpr.raw()};
			zv::Val intertwined = pt_type_new(PT_CLASS_INTERTWINED_VAR, 3, intertwinedArgs);
			if (UNEXPECTED(intertwined.isUndef())) return zv::Val();
			scope = otherAssignExpression(scope, intertwined.raw(), valueType.raw(), nativeValueType.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		if (keyName != NULL) {
			zend_object *scopeObject = requireObject(scope, "enterForeachKey");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			zv::Args keyArgs{originalScope, iteratee, iterateeType, nativeIterateeType, keyName};
			scope = pt_type_call(scopeObject, PT_LC("enterforeachkey"), 5, keyArgs);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();

			if (writeThrough) {
				zv::Val keyVariable = newVariable(ZSTR_VAL(keyName), ZSTR_LEN(keyName));
				if (UNEXPECTED(keyVariable.isUndef())) return zv::Val();
				zv::Args dimArgs{iteratee, keyVariable.raw()};
				zv::Val dimFetch = pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, dimArgs);
				if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
				zv::Val valueVariable = newVariable(ZSTR_VAL(valueName), ZSTR_LEN(valueName));
				if (UNEXPECTED(valueVariable.isUndef())) return zv::Val();
				zv::Args intertwinedArgs{valueName, dimFetch.raw(), valueVariable.raw()};
				zv::Val intertwined = pt_type_new(PT_CLASS_INTERTWINED_VAR, 3, intertwinedArgs);
				if (UNEXPECTED(intertwined.isUndef())) return zv::Val();
				scope = otherAssignExpression(scope, intertwined.raw(), valueType.raw(), nativeValueType.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		return scope;
	}

	/* new NativeTypeExpr($phpdocType, $nativeType) over two producers that
	 * may have thrown */
	static zv::Val nativeTypeExprOf(zv::Val phpdocType, zv::Val nativeType)
	{
		if (UNEXPECTED(phpdocType.isUndef() || nativeType.isUndef())) return zv::Val();
		zv::Args args{phpdocType.raw(), nativeType.raw()};
		return pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, args);
	}

	/* $iterateeType->isArray()->yes() && $iterateeType->isConstantArray()->no() */
	static bool iterateeIsNonConstantArray(zval *iterateeType, bool &out)
	{
		out = false;
		if (UNEXPECTED(Z_TYPE_P(iterateeType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(iterateeType));
			return false;
		}
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(iterateeType), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return false;
		if (isArray != PT_TRI_YES) return true;
		zend_long isConstantArray = pt_type_op_trinary(Z_OBJ_P(iterateeType), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(isConstantArray < 0)) return false;
		out = isConstantArray == PT_TRI_NO;
		return true;
	}

	/* (twin 3061) */
	zv::Val enterForeachKey(zend_object *originalScope, zval *iteratee, zval *iterateeType, zval *nativeIterateeType, zend_string *keyName)
	{
		zv::Val keyType = iterableType(originalScope, iterateeType, true);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val nativeKeyType = iterableType(originalScope, nativeIterateeType, true);
		if (UNEXPECTED(nativeKeyType.isUndef())) return zv::Val();
		zval assignArgs[5];
		ZVAL_STR(&assignArgs[0], keyName);
		ZVAL_COPY_VALUE(&assignArgs[1], keyType.raw());
		ZVAL_COPY_VALUE(&assignArgs[2], nativeKeyType.raw());
		ZVAL_COPY_VALUE(&assignArgs[3], pt_trinary_singleton(PT_TRI_YES));
		ZVAL_EMPTY_ARRAY(&assignArgs[4]);
		zv::Val scope = thisAssignVariable(assignArgs);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		zval keyNameZv;
		ZVAL_STR(&keyNameZv, keyName);
		zv::Val originalKeyExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_KEY_EXPR, 1, &keyNameZv);
		if (UNEXPECTED(originalKeyExpr.isUndef())) return zv::Val();
		scope = otherAssignExpression(scope, originalKeyExpr.raw(), keyType.raw(), nativeKeyType.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		if (UNEXPECTED(Z_TYPE_P(iterateeType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(iterateeType));
			return zv::Val();
		}
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(iterateeType), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) {
			zv::Val keyVariable = newVariable(ZSTR_VAL(keyName), ZSTR_LEN(keyName));
			if (UNEXPECTED(keyVariable.isUndef())) return zv::Val();
			zv::Args dimArgs{iteratee, keyVariable.raw()};
			zv::Val dimFetch = pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, dimArgs);
			if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
			zv::Val valueType = iterableType(originalScope, iterateeType, false);
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zv::Val nativeValueType = iterableType(originalScope, nativeIterateeType, false);
			if (UNEXPECTED(nativeValueType.isUndef())) return zv::Val();
			scope = otherAssignExpression(scope, dimFetch.raw(), valueType.raw(), nativeValueType.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		return scope;
	}

	/* (twin 3086) */
	zv::Val enterCatchType(zval *catchType, zend_string *variableName)
	{
		if (variableName == NULL) return self_();
		zval throwableZv;
		zv::Str throwableName = zv::Str::adopt(zend_string_init(PT_LC("Throwable"), 0));
		if (UNEXPECTED(!pt_object_type_new(&throwableZv, throwableName.get()))) return zv::Val();
		zv::Val throwableType = zv::Val::adopt(throwableZv);
		zv::Args args{catchType, throwableType.raw()};
		zv::Val intersected = pt_type_combinator_intersect(2, args);
		if (UNEXPECTED(intersected.isUndef())) return zv::Val();
		/* the twin builds the intersection twice, once per table */
		zval nativeThrowableZv;
		if (UNEXPECTED(!pt_object_type_new(&nativeThrowableZv, throwableName.get()))) return zv::Val();
		zv::Val nativeThrowableType = zv::Val::adopt(nativeThrowableZv);
		zv::Args nativeArgs{catchType, nativeThrowableType.raw()};
		zv::Val nativeIntersected = pt_type_combinator_intersect(2, nativeArgs);
		if (UNEXPECTED(nativeIntersected.isUndef())) return zv::Val();
		zval assignArgs[5];
		ZVAL_STR(&assignArgs[0], variableName);
		ZVAL_COPY_VALUE(&assignArgs[1], intersected.raw());
		ZVAL_COPY_VALUE(&assignArgs[2], nativeIntersected.raw());
		ZVAL_COPY_VALUE(&assignArgs[3], pt_trinary_singleton(PT_TRI_YES));
		ZVAL_EMPTY_ARRAY(&assignArgs[4]);
		return thisAssignVariable(assignArgs);
	}

	/* create(...) with the twin's expression-assign argument list: the two
	 * currently-* tables as given, an empty call stack, everything else
	 * from $this — and $this->resolvedTypes carried onto the result */
	zv::Val createWithCurrentlyTables(zv::Val currentlyAssignedExpressions, zv::Val currentlyAllowedUndefinedExpressions)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, true))) return zv::Val();
		a.setOwned(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS, std::move(currentlyAssignedExpressions));
		a.setOwned(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, std::move(currentlyAllowedUndefinedExpressions));
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		zv::Val scope = scopeFactoryCreate(a);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!assignResolvedTypes(scope))) return zv::Val();
		return scope;
	}

	/* (twin 3100) */
	zv::Val enterExpressionAssign(zend_object *expr, bool isPlainWrite)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions"))) {
			return zv::Val();
		}
		zv::Arr currentlyAssignedExpressions = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()));
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		currentlyAssignedExpressions.set(key.get(), zv::Val::boolean(isPlainWrite));
		return createWithCurrentlyTables(zv::Val(std::move(currentlyAssignedExpressions)), copyOfSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS));
	}

	/* (twin 3131) */
	zv::Val exitExpressionAssign(zend_object *expr)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions"))) {
			return zv::Val();
		}
		zv::Arr currentlyAssignedExpressions = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()));
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		currentlyAssignedExpressions.separate();
		zend_symtable_del(currentlyAssignedExpressions.table(), key.get());
		return createWithCurrentlyTables(zv::Val(std::move(currentlyAssignedExpressions)), copyOfSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS));
	}

	/** @api (twin 3163) */
	bool isInExpressionAssign(zend_object *expr, bool &out)
	{
		out = false;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions"))) return false;
		HashTable *assigned = Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw());
		if (zend_hash_num_elements(assigned) == 0) return true;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return false;
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		out = zend_symtable_exists(assigned, key.get());
		return true;
	}

	/* (twin 3178) */
	bool isInWriteExpressionAssign(zend_object *expr, bool &out)
	{
		out = false;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions"))) return false;
		HashTable *assigned = Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw());
		if (zend_hash_num_elements(assigned) == 0) return true;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return false;
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		zval *value = zend_symtable_find(assigned, key.get());
		out = value != NULL && Z_TYPE_P(zv::Ref(value).deref().raw()) == IS_TRUE;
		return true;
	}

	/* (twin 3188) */
	zv::Val setAllowedUndefinedExpression(zend_object *expr)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isStaticPropertyFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_STATIC_PROPERTY_FETCH, isStaticPropertyFetch))) return zv::Val();
		if (isStaticPropertyFetch) return self_();
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions"))) {
			return zv::Val();
		}
		zv::Arr allowed = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()));
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		allowed.set(key.get(), zv::Val::boolean(true));
		return createWithCurrentlyTables(copyOfSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS), zv::Val(std::move(allowed)));
	}

	/* (twin 3223) */
	zv::Val unsetAllowedUndefinedExpression(zend_object *expr)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions"))) {
			return zv::Val();
		}
		zv::Arr allowed = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()));
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		allowed.separate();
		zend_symtable_del(allowed.table(), key.get());
		return createWithCurrentlyTables(copyOfSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS), zv::Val(std::move(allowed)));
	}

	/** @api (twin 3255) */
	bool isUndefinedExpressionAllowed(zend_object *expr, bool &out)
	{
		out = false;
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions"))) return false;
		HashTable *allowed = Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw());
		if (zend_hash_num_elements(allowed) == 0) return true;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return false;
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		out = zend_symtable_exists(allowed, key.get());
		return true;
	}

	/* $scope-><table>[$key] = $holder, or unset(...) for a NULL holder — on
	 * any scope object (the twin writes into the scope the factory
	 * answered); false = pending exception */
	[[nodiscard]] static bool writeScopeTable(zend_object *object, uint32_t nativeSlot, const char *name, size_t len, zend_string *key, zval *holder)
	{
		zval *table = otherProp(object, nativeSlot, name, len);
		if (UNEXPECTED(table == NULL)) return false;
		ZVAL_DEREF(table);
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zend_throw_error(NULL, "Cannot use a scalar value as an array");
			return false;
		}
		SEPARATE_ARRAY(table);
		if (holder == NULL) {
			zend_symtable_del(Z_ARRVAL_P(table), key);
			return true;
		}
		Z_TRY_ADDREF_P(holder);
		zend_symtable_update(Z_ARRVAL_P(table), key, holder);
		return true;
	}

	/* in_array($needle, $haystack, true) over a list of strings */
	static bool listContainsString(zval *list, zend_string *needle)
	{
		for (auto entry : zv::ArrRef(list)) {
			zv::Ref value = entry.value().deref();
			if (value.isString() && zend_string_equals(value.asString(), needle)) return true;
		}
		return false;
	}

	/* private (twin 3378) */
	zv::Val resolveIntertwinedAssignedType(zend_object *scope, zval *rootType, zend_object *assignedExpr, zend_string *rootVariableName, bool native)
	{
		zval assignedZv;
		ZVAL_OBJ(&assignedZv, assignedExpr);
		bool isVariable;
		if (UNEXPECTED(!isInstance(zv::Ref(&assignedZv), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
		if (isVariable) {
			zv::Ref name = nodeProp(assignedExpr, PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
			if (name.deref().isString() && zend_string_equals(name.deref().asString(), rootVariableName)) return zv::Val::copyOf(zv::Ref(rootType));
		}

		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&assignedZv), PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		if (isArrayDimFetch) {
			zv::Ref dim = nodeProp(assignedExpr, PT_LC("dim"));
			zv::Ref var = nodeProp(assignedExpr, PT_LC("var"));
			if (UNEXPECTED(dim.raw() == NULL || var.raw() == NULL)) return zv::Val();
			if (!dim.deref().isNull()) {
				if (UNEXPECTED(!var.deref().isObject())) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				zv::Val varType = resolveIntertwinedAssignedType(scope, rootType, var.deref().asObject(), rootVariableName, native);
				if (UNEXPECTED(varType.isUndef())) return zv::Val();
				zv::Val dimType = otherGetType(scope, dim.deref().raw(), native);
				if (UNEXPECTED(dimType.isUndef())) return zv::Val();
				if (UNEXPECTED(!varType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function getOffsetValueType() on %s", zend_zval_value_name(varType.raw()));
					return zv::Val();
				}
				return pt_type_op(varType.ref().asObject(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw());
			}
		}

		bool isSetExisting;
		if (UNEXPECTED(!isInstance(zv::Ref(&assignedZv), PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, isSetExisting))) return zv::Val();
		if (isSetExisting) {
			/* the foreach-byref slot: the iteratee with its key offset set to
			 * the value variable's new type */
			zv::Val var = pt_type_call(assignedExpr, PT_LC("getvar"), 0, NULL);
			if (UNEXPECTED(var.isUndef())) return zv::Val();
			zv::Val iterateeType = otherGetType(scope, var.raw(), native);
			if (UNEXPECTED(iterateeType.isUndef())) return zv::Val();
			zv::Val dim = pt_type_call(assignedExpr, PT_LC("getdim"), 0, NULL);
			if (UNEXPECTED(dim.isUndef())) return zv::Val();
			zv::Val dimType = otherGetType(scope, dim.raw(), native);
			if (UNEXPECTED(dimType.isUndef())) return zv::Val();
			if (UNEXPECTED(!iterateeType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function setExistingOffsetValueType() on %s", zend_zval_value_name(iterateeType.raw()));
				return zv::Val();
			}
			zv::Args args{dimType.raw(), rootType};
			return pt_type_call(iterateeType.ref().asObject(), PT_LC("setexistingoffsetvaluetype"), 2, args);
		}

		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* private (twin 3403); false = pending exception */
	[[nodiscard]] bool isDimFetchPathReachable(zend_object *scope, zend_object *dimFetch, bool &out)
	{
		out = false;
		zv::Ref dim = nodeProp(dimFetch, PT_LC("dim"));
		zv::Ref var = nodeProp(dimFetch, PT_LC("var"));
		if (UNEXPECTED(dim.raw() == NULL || var.raw() == NULL)) return false;
		if (dim.deref().isNull()) return true;
		bool varIsDimFetch;
		if (UNEXPECTED(!isInstance(var.deref(), PT_CLASS_ARRAY_DIM_FETCH, varIsDimFetch))) return false;
		if (!varIsDimFetch) {
			out = true;
			return true;
		}
		zv::Val varType = otherGetType(scope, var.deref().raw(), false);
		if (UNEXPECTED(varType.isUndef())) return false;
		zv::Val dimType = otherGetType(scope, dim.deref().raw(), false);
		if (UNEXPECTED(dimType.isUndef())) return false;
		if (UNEXPECTED(!varType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function hasOffsetValueType() on %s", zend_zval_value_name(varType.raw()));
			return false;
		}
		zend_long has = pt_type_op_trinary(varType.ref().asObject(), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, dimType.raw());
		if (UNEXPECTED(has < 0)) return false;
		if (has != PT_TRI_YES) return true;
		return isDimFetchPathReachable(scope, var.deref().asObject(), out);
	}

	/* (twin 3267) */
	zv::Val assignVariable(zend_string *variableName, zval *type, zval *nativeType, zval *certainty, zval *intertwinedPropagatedFrom)
	{
		zv::Val node = newVariable(ZSTR_VAL(variableName), ZSTR_LEN(variableName));
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		zv::Val scope = thisAssignExpression(node.raw(), type, nativeType);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_long certaintyValue = pt_type_trinary_value(certainty);
		if (UNEXPECTED(certaintyValue < 0)) return zv::Val();
		if (certaintyValue == PT_TRI_NO) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zend_object *scopeObject = requireObject(scope, "hasExpressionType");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zv::Str variableKey = zv::Str::adopt(dollarName(variableName));
		if (certaintyValue != PT_TRI_YES) {
			zval holder;
			pt_holder_create(&holder, node.raw(), type, certaintyValue);
			zv::Val ownedHolder = zv::Val::adopt(holder);
			if (UNEXPECTED(!writeScopeTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), variableKey.get(), ownedHolder.raw()))) {
				return zv::Val();
			}
			zval nativeHolder;
			pt_holder_create(&nativeHolder, node.raw(), nativeType, certaintyValue);
			zv::Val ownedNativeHolder = zv::Val::adopt(nativeHolder);
			if (UNEXPECTED(!writeScopeTable(scopeObject, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"), variableKey.get(), ownedNativeHolder.raw()))) {
				return zv::Val();
			}
		}

		/* foreach ($scope->expressionTypes as ...) — over the table as it
		 * stands here; PHP's by-value iteration sees neither the unsets
		 * below nor the tables of the scopes the loop moves on to */
		zval *initialTable = otherProp(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(initialTable == NULL)) return zv::Val();
		zv::Val snapshot = zv::Val::copyOf(zv::Ref(initialTable).deref());
		for (auto entry : zv::ArrRef(snapshot.raw())) {
			zend_string *exprString = entry.stringKeyOrNull();
			zv::Val holderExprVal = holderExpr(entry.value());
			if (UNEXPECTED(holderExprVal.isUndef())) return zv::Val();
			bool isIntertwined;
			if (UNEXPECTED(!isInstance(holderExprVal.ref(), PT_CLASS_INTERTWINED_VAR, isIntertwined))) return zv::Val();
			if (!isIntertwined) continue;
			zend_long holderCertaintyValue = holderCertainty(entry.value());
			if (UNEXPECTED(holderCertaintyValue < 0)) return zv::Val();
			if (holderCertaintyValue != PT_TRI_YES) continue;
			zend_object *intertwined = holderExprVal.ref().asObject();
			zv::Val intertwinedVariableName = pt_type_call(intertwined, PT_LC("getvariablename"), 0, NULL);
			if (UNEXPECTED(intertwinedVariableName.isUndef())) return zv::Val();
			if (!intertwinedVariableName.ref().isString() || !zend_string_equals(intertwinedVariableName.ref().asString(), variableName)) continue;

			zv::Val assignedExpr = pt_type_call(intertwined, PT_LC("getassignedexpr"), 0, NULL);
			if (UNEXPECTED(assignedExpr.isUndef())) return zv::Val();
			if (UNEXPECTED(!assignedExpr.ref().isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_object *assignedExprObject = assignedExpr.ref().asObject();
			scopeObject = requireObject(scope, "hasExpressionType");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			bool assignedIsDimFetch;
			if (UNEXPECTED(!isInstance(assignedExpr.ref(), PT_CLASS_ARRAY_DIM_FETCH, assignedIsDimFetch))) return zv::Val();
			if (assignedIsDimFetch) {
				bool reachable;
				if (UNEXPECTED(!isDimFetchPathReachable(scopeObject, assignedExprObject, reachable))) return zv::Val();
				if (!reachable) {
					if (exprString == NULL) continue;
					if (UNEXPECTED(!writeScopeTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), exprString, NULL)
						|| !writeScopeTable(scopeObject, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"), exprString, NULL))) {
						return zv::Val();
					}
					continue;
				}
			}

			/* When the byref's dim is non-constant AND not enumerable as a
			 * finite set of scalars, the just-performed write to the array
			 * might or might not have hit the byref's slot */
			bool unionWithOld = false;
			if (assignedIsDimFetch) {
				zv::Ref dim = nodeProp(assignedExprObject, PT_LC("dim"));
				if (UNEXPECTED(dim.raw() == NULL)) return zv::Val();
				if (!dim.deref().isNull()) {
					zv::Val dimType = otherGetType(scopeObject, dim.deref().raw(), false);
					if (UNEXPECTED(dimType.isUndef())) return zv::Val();
					if (UNEXPECTED(!dimType.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function getConstantScalarValues() on %s", zend_zval_value_name(dimType.raw()));
						return zv::Val();
					}
					zv::Val constantScalarValues = pt_type_op(dimType.ref().asObject(), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
					if (UNEXPECTED(constantScalarValues.isUndef())) return zv::Val();
					zv::Val finiteTypes = pt_type_call(dimType.ref().asObject(), PT_LC("getfinitetypes"), 0, NULL);
					if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
					unionWithOld = zend_hash_num_elements(Z_ARRVAL_P(constantScalarValues.raw())) != 1
						&& zend_hash_num_elements(Z_ARRVAL_P(finiteTypes.raw())) == 0;
				}
			}

			/* Resolve the byref slot's new value directly from the
			 * just-assigned root variable's type */
			zv::Val assignedType = resolveIntertwinedAssignedType(scopeObject, type, assignedExprObject, variableName, false);
			if (UNEXPECTED(assignedType.isUndef())) return zv::Val();
			zv::Val assignedNativeType = resolveIntertwinedAssignedType(scopeObject, nativeType, assignedExprObject, variableName, true);
			if (UNEXPECTED(assignedNativeType.isUndef())) return zv::Val();

			zv::Val target = pt_type_call(intertwined, PT_LC("getexpr"), 0, NULL);
			if (UNEXPECTED(target.isUndef())) return zv::Val();
			if (UNEXPECTED(!target.ref().isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val has = pt_type_call(scopeObject, PT_LC("hasexpressiontype"), 1, target.raw());
			if (UNEXPECTED(has.isUndef())) return zv::Val();
			zend_long hasValue = pt_type_trinary_value(has.raw());
			if (UNEXPECTED(hasValue < 0)) return zv::Val();
			bool targetIsVariable;
			if (UNEXPECTED(!isInstance(target.ref(), PT_CLASS_VARIABLE, targetIsVariable))) return zv::Val();
			zend_string *targetVarName = NULL;
			if (targetIsVariable) {
				zv::Ref targetName = nodeProp(target.ref().asObject(), PT_LC("name"));
				if (UNEXPECTED(targetName.raw() == NULL)) return zv::Val();
				if (targetName.deref().isString()) {
					targetVarName = targetName.deref().asString();
				}
			}

			if (targetVarName != NULL && hasValue != PT_TRI_NO) {
				if (listContainsString(intertwinedPropagatedFrom, targetVarName)) continue;
				if (unionWithOld) {
					zv::Val targetVarNode = newVariable(ZSTR_VAL(targetVarName), ZSTR_LEN(targetVarName));
					zv::Val rootVarNode = newVariable(ZSTR_VAL(variableName), ZSTR_LEN(variableName));
					if (UNEXPECTED(targetVarNode.isUndef() || rootVarNode.isUndef())) return zv::Val();
					if (UNEXPECTED(!unionAssignedWithOld(assignedType, rootVarNode.raw(), targetVarNode.raw(), assignedExprObject, variableName, scopeObject, false))) {
						return zv::Val();
					}
					if (UNEXPECTED(!unionAssignedWithOld(assignedNativeType, rootVarNode.raw(), targetVarNode.raw(), assignedExprObject, variableName, scopeObject, true))) {
						return zv::Val();
					}
				}
				zv::Arr propagated = zv::Arr::copyOfTable(Z_ARRVAL_P(intertwinedPropagatedFrom));
				propagated.push(zv::Val::string(variableName));
				zv::Args assignArgs{targetVarName, assignedType.raw(), assignedNativeType.raw(), has.raw(), propagated.raw()};
				scope = pt_type_call(scopeObject, PT_LC("assignvariable"), 5, assignArgs);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			} else {
				zv::Val targetRootVar = pt_scope_ops_intertwined_ref_root_variable_name(target.ref().asObject());
				if (UNEXPECTED(targetRootVar.isUndef())) return zv::Val();
				if (targetRootVar.ref().isString() && listContainsString(intertwinedPropagatedFrom, targetRootVar.ref().asString())) continue;
				scope = otherOverwriteExpression(scope, target.raw(), assignedType.raw(), assignedNativeType.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		return scope;
	}

	/* TypeCombinator::union($assigned, $this->resolveIntertwinedAssignedType($this, <root type>, ...), $scope-><read>($targetVarNode));
	 * in place on $assigned; false = pending exception */
	[[nodiscard]] bool unionAssignedWithOld(zv::Val &assigned, zval *rootVarNode, zval *targetVarNode, zend_object *assignedExpr, zend_string *variableName, zend_object *scopeObject, bool native)
	{
		zv::Val rootType = native ? thisGetNativeType(rootVarNode) : thisGetType(rootVarNode);
		if (UNEXPECTED(rootType.isUndef())) return false;
		zv::Val fromRoot = resolveIntertwinedAssignedType(self, rootType.raw(), assignedExpr, variableName, native);
		if (UNEXPECTED(fromRoot.isUndef())) return false;
		zv::Val targetType = otherGetType(scopeObject, targetVarNode, native);
		if (UNEXPECTED(targetType.isUndef())) return false;
		zv::Args args{assigned.raw(), fromRoot.raw(), targetType.raw()};
		zv::Val united = pt_type_combinator_union(3, args);
		if (UNEXPECTED(united.isUndef())) return false;
		assigned = std::move(united);
		return true;
	}

	/* private (twin 3423); its only caller is applySpecifiedTypes() */
	zv::Val unsetExpression(zend_object *expr)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val scope = unsetOffsetOfDimFetch(expr);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope, "invalidateExpression");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		return pt_type_call(scopeObject, PT_LC("invalidateexpression"), 1, &exprZv);
	}

	/* the ArrayDimFetch arm of unsetExpression(): the scope carrying the
	 * unset offset (and the invalidated count()/sizeof() calls), $this for
	 * anything else */
	zv::Val unsetOffsetOfDimFetch(zend_object *expr)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		if (!isArrayDimFetch) return self_();
		zv::Ref dim = nodeProp(expr, PT_LC("dim"));
		zv::Ref var = nodeProp(expr, PT_LC("var"));
		if (UNEXPECTED(dim.raw() == NULL || var.raw() == NULL)) return zv::Val();
		if (dim.deref().isNull()) return self_();
		if (UNEXPECTED(!var.deref().isObject())) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val exprVarType = getScopeStateType(var.deref().asObject());
		if (UNEXPECTED(exprVarType.isUndef())) return zv::Val();
		zv::Val dimType = thisGetType(dim.deref().raw());
		if (UNEXPECTED(dimType.isUndef())) return zv::Val();
		zv::Val unsetType = unsetOffset(exprVarType, dimType.raw());
		if (UNEXPECTED(unsetType.isUndef())) return zv::Val();
		zv::Val exprVarNativeType = getScopeStateNativeType(var.deref().asObject());
		if (UNEXPECTED(exprVarNativeType.isUndef())) return zv::Val();
		zv::Val dimNativeType = thisGetNativeType(dim.deref().raw());
		if (UNEXPECTED(dimNativeType.isUndef())) return zv::Val();
		zv::Val unsetNativeType = unsetOffset(exprVarNativeType, dimNativeType.raw());
		if (UNEXPECTED(unsetNativeType.isUndef())) return zv::Val();
		zv::Val thisScope = self_();
		zv::Val scope = otherAssignExpression(thisScope, var.deref().raw(), unsetType.raw(), unsetNativeType.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		static const struct { const char *name; bool fullyQualified; } countCalls[] = {
			{"count", true}, {"sizeof", true}, {"count", false}, {"sizeof", false},
		};
		for (const auto &countCall : countCalls) {
			zv::Val call = newCountCall(countCall.name, countCall.fullyQualified, var.deref().raw());
			if (UNEXPECTED(call.isUndef())) return zv::Val();
			zend_object *scopeObject = requireObject(scope, "invalidateExpression");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			scope = pt_type_call(scopeObject, PT_LC("invalidateexpression"), 1, call.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		bool varIsArrayDimFetch;
		if (UNEXPECTED(!isInstance(var.deref(), PT_CLASS_ARRAY_DIM_FETCH, varIsArrayDimFetch))) return zv::Val();
		if (!varIsArrayDimFetch) return scope;
		zend_object *varObject = var.deref().asObject();
		zv::Ref varDim = nodeProp(varObject, PT_LC("dim"));
		zv::Ref varVar = nodeProp(varObject, PT_LC("var"));
		if (UNEXPECTED(varDim.raw() == NULL || varVar.raw() == NULL)) return zv::Val();
		if (varDim.deref().isNull()) return scope;
		zend_object *scopeObject = requireObject(scope, "getType");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zv::Val outer = setOffsetFromScopeState(varVar.deref().raw(), varDim.deref().raw(), varObject, scopeObject, false);
		if (UNEXPECTED(outer.isUndef())) return zv::Val();
		zv::Val outerNative = setOffsetFromScopeState(varVar.deref().raw(), varDim.deref().raw(), varObject, scopeObject, true);
		if (UNEXPECTED(outerNative.isUndef())) return zv::Val();
		return otherAssignExpression(scope, varVar.deref().raw(), outer.raw(), outerNative.raw());
	}

	/* $this-><read>($outerVar)->setOffsetValueType($scope-><read>($outerDim), $scope->getScopeState<flavour>Type($inner)) */
	zv::Val setOffsetFromScopeState(zval *outerVar, zval *outerDim, zend_object *inner, zend_object *scopeObject, bool native)
	{
		zv::Val outerType = native ? thisGetNativeType(outerVar) : thisGetType(outerVar);
		if (UNEXPECTED(outerType.isUndef())) return zv::Val();
		zv::Val dimType = otherGetType(scopeObject, outerDim, native);
		if (UNEXPECTED(dimType.isUndef())) return zv::Val();
		zval innerZv;
		ZVAL_OBJ(&innerZv, inner);
		auto readState = [&](MutatingScope &other) { return native ? other.getScopeStateNativeType(inner) : other.getScopeStateType(inner); };
		zv::Val innerType = native
			? otherPrivate(scopeObject, PT_LC("getscopestatenativetype"), 1, &innerZv, readState)
			: otherPrivate(scopeObject, PT_LC("getscopestatetype"), 1, &innerZv, readState);
		if (UNEXPECTED(innerType.isUndef())) return zv::Val();
		if (UNEXPECTED(!outerType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setOffsetValueType() on %s", zend_zval_value_name(outerType.raw()));
			return zv::Val();
		}
		zv::Args args{dimType.raw(), innerType.raw()};
		return pt_type_call(outerType.ref().asObject(), PT_LC("setoffsetvaluetype"), 2, args);
	}

	/* $type->unsetOffset($dimType) */
	static zv::Val unsetOffset(zv::Val &type, zval *dimType)
	{
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function unsetOffset() on %s", zend_zval_value_name(type.raw()));
			return zv::Val();
		}
		return pt_type_call(type.ref().asObject(), PT_LC("unsetoffset"), 1, dimType);
	}

	/* new FuncCall(new FullyQualified|Name($name), [new Arg($var)]) */
	static zv::Val newCountCall(const char *name, bool fullyQualified, zval *var)
	{
		zv::Val nameVal = zv::Val::string(name, strlen(name));
		zv::Val nameNode = fullyQualified
			? pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, nameVal.raw())
			: pt_type_new(PT_CLASS_NAME, 1, nameVal.raw());
		if (UNEXPECTED(nameNode.isUndef())) return zv::Val();
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, var);
		if (UNEXPECTED(arg.isUndef())) return zv::Val();
		zv::Arr args = zv::Arr::create(1);
		args.push(arg.ref());
		zv::Args callArgs{nameNode.raw(), args.raw()};
		return pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgs);
	}

	/* (twin 3468) */
	zv::Val getStateType(zend_object *expr) { return resolveScopeStateType(expr, slotBool(PT_MS_PROP_NATIVE_TYPES_PROMOTED)); }

	/* private (twin 3478) */
	zv::Val getScopeStateNativeType(zend_object *expr) { return resolveScopeStateType(expr, true); }

	/* (twin 3625) */
	zv::Val specifyExpressionType(zend_object *expr, zval *type, zval *nativeType, zval *certainty)
	{
		bool noop;
		if (UNEXPECTED(!isSpecifyExpressionTypeNoop(expr, type, noop))) return zv::Val();
		if (noop) return self_();
		zv::Val scope = openSpecificationScope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope, "specifyExpressionTypeInPlace");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		if (UNEXPECTED(!otherSpecifyInPlace(scopeObject, expr, type, nativeType, certainty))) return zv::Val();
		return scope;
	}

	/* $scope->specifyExpressionTypeInPlace(...) on any scope object */
	static bool otherSpecifyInPlace(zend_object *scopeObject, zend_object *expr, zval *type, zval *nativeType, zval *certainty)
	{
		zv::Args args{expr, type, nativeType, certainty};
		zv::Val result = otherPrivate(scopeObject, PT_LC("specifyexpressiontypeinplace"), 4, args, [&](MutatingScope &other) {
			if (UNEXPECTED(!other.specifyExpressionTypeInPlace(expr, type, nativeType, certainty))) return zv::Val();
			return zv::Val::null();
		});
		return EXPECTED(!result.isUndef());
	}

	/* private (twin 3637) — an unpublished copy of this scope that in-place
	 * specification may mutate */
	zv::Val openSpecificationScope()
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		/* the twin passes $this->inFirstLevelStatement, not the getter */
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		return scopeFactoryCreate(a);
	}

	/* private (twin 3660); false = pending exception */
	[[nodiscard]] bool isSpecifyExpressionTypeNoop(zend_object *expr, zval *type, bool &out)
	{
		out = false;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isScalar;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_SCALAR, isScalar))) return false;
		if (isScalar) {
			out = true;
			return true;
		}
		bool isConstFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_CONST_FETCH, isConstFetch))) return false;
		if (isConstFetch) {
			zv::Ref name = nodeProp(expr, PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) return false;
			if (UNEXPECTED(!name.deref().isObject())) {
				zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(name.deref().raw()));
				return false;
			}
			zv::Val nameString = pt_type_call(name.deref().asObject(), PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(nameString.isUndef())) return false;
			zend_string *raw = zval_get_string(nameString.raw());
			if (UNEXPECTED(raw == NULL)) return false;
			zend_string *lowered = zend_string_tolower(raw);
			zend_string_release(raw);
			bool isConstant = zend_string_equals_literal(lowered, "true")
				|| zend_string_equals_literal(lowered, "false")
				|| zend_string_equals_literal(lowered, "null");
			zend_string_release(lowered);
			if (isConstant) {
				out = true;
				return true;
			}
		}

		bool isFuncCall;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_FUNC_CALL, isFuncCall))) return false;
		if (!isFuncCall) return true;
		zv::Ref name = nodeProp(expr, PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return false;
		bool isName;
		if (UNEXPECTED(!isInstance(name.deref(), PT_CLASS_NAME, isName))) return false;
		if (!isName) return true;
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isFalse() on %s", zend_zval_value_name(type));
			return false;
		}
		zv::Val isFalse = pt_type_call(Z_OBJ_P(type), PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(isFalse.isUndef())) return false;
		if (pt_type_trinary_value(isFalse.raw()) != PT_TRI_YES) return EXPECTED(EG(exception) == NULL);
		zv::Ref provider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
		if (UNEXPECTED(!provider.isObject())) {
			(void) uninitializedProperty("reflectionProvider");
			return false;
		}
		zv::Args resolveArgs{name.deref().raw(), thisZval()};
		zv::Val functionName = pt_type_call(provider.asObject(), PT_LC("resolvefunctionname"), 2, resolveArgs);
		if (UNEXPECTED(functionName.isUndef())) return false;
		if (functionName.ref().isNull()) return true;
		zend_string *raw = zval_get_string(functionName.raw());
		if (UNEXPECTED(raw == NULL)) return false;
		zend_string *lowered = zend_string_tolower(raw);
		zend_string_release(raw);
		out = zend_string_equals_literal(lowered, "is_dir")
			|| zend_string_equals_literal(lowered, "is_file")
			|| zend_string_equals_literal(lowered, "file_exists");
		zend_string_release(lowered);
		return true;
	}

	/* private (twin 3692) — writes straight into this scope's holder maps;
	 * only to be called on an unpublished scope (openSpecificationScope());
	 * false = pending exception */
	[[nodiscard]] bool specifyExpressionTypeInPlace(zend_object *expr, zval *type, zval *nativeType, zval *certainty)
	{
		bool noop;
		if (UNEXPECTED(!isSpecifyExpressionTypeNoop(expr, type, noop))) return false;
		if (noop) return true;

		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return false;
		if (isArrayDimFetch) {
			zv::Ref dim = nodeProp(expr, PT_LC("dim"));
			zv::Ref var = nodeProp(expr, PT_LC("var"));
			if (UNEXPECTED(dim.raw() == NULL || var.raw() == NULL)) return false;
			bool incDec = false;
			if (!dim.deref().isNull()) {
				for (int classIdx : { PT_CLASS_PRE_INC, PT_CLASS_PRE_DEC, PT_CLASS_POST_DEC, PT_CLASS_POST_INC }) {
					bool is;
					if (UNEXPECTED(!isInstance(dim.deref(), classIdx, is))) return false;
					if (is) {
						incDec = true;
						break;
					}
				}
			}
			if (!dim.deref().isNull() && !incDec) {
				if (UNEXPECTED(!dim.deref().isObject() || !var.deref().isObject())) {
					pt_throw_should_not_happen();
					return false;
				}
				if (UNEXPECTED(!specifyArrayDimVarInPlace(dim.deref().asObject(), var.deref().asObject(), type, certainty))) return false;
			}
		}

		if (pt_type_trinary_value(certainty) == PT_TRI_NO) {
			pt_throw_should_not_happen();
			return false;
		}
		if (UNEXPECTED(EG(exception))) return false;

		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return false;
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return false;
		zend_long certaintyValue = pt_type_trinary_value(certainty);
		if (UNEXPECTED(certaintyValue < 0)) return false;
		zval holder;
		pt_holder_create(&holder, &exprZv, type, certaintyValue);
		zv::Val ownedHolder = zv::Val::adopt(holder);
		zval nativeHolder;
		pt_holder_create(&nativeHolder, &exprZv, nativeType, certaintyValue);
		zv::Val ownedNativeHolder = zv::Val::adopt(nativeHolder);
		if (UNEXPECTED(!writeScopeTable(self, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), key.get(), ownedHolder.raw())
			|| !writeScopeTable(self, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"), key.get(), ownedNativeHolder.raw()))) {
			return false;
		}

		bool isAlwaysRemembered;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_ALWAYS_REMEMBERED_EXPR, isAlwaysRemembered))) return false;
		if (!isAlwaysRemembered) return true;
		zv::Ref inner = nodeProp(expr, PT_LC("expr"));
		if (UNEXPECTED(inner.raw() == NULL)) return false;
		if (UNEXPECTED(!inner.deref().isObject())) {
			pt_throw_should_not_happen();
			return false;
		}
		return specifyExpressionTypeInPlace(inner.deref().asObject(), type, nativeType, certainty);
	}

	/* the ArrayDimFetch arm of specifyExpressionTypeInPlace(): the var's
	 * offset-accessible narrowing, applied in place */
	bool specifyArrayDimVarInPlace(zend_object *dim, zend_object *var, zval *type, zval *certainty)
	{
		zv::Val rawDimType = getScopeStateType(dim);
		if (UNEXPECTED(rawDimType.isUndef())) return false;
		if (UNEXPECTED(!rawDimType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function toArrayKey() on %s", zend_zval_value_name(rawDimType.raw()));
			return false;
		}
		zv::Val dimType = pt_type_op(rawDimType.ref().asObject(), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(dimType.isUndef())) return false;
		if (UNEXPECTED(!dimType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isInteger() on %s", zend_zval_value_name(dimType.raw()));
			return false;
		}
		zend_long dimIsInteger = pt_type_op_trinary(dimType.ref().asObject(), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(dimIsInteger < 0)) return false;
		if (dimIsInteger != PT_TRI_YES) {
			zend_long dimIsString = pt_type_op_trinary(dimType.ref().asObject(), PT_OP_IS_STRING, 0, NULL);
			if (UNEXPECTED(dimIsString < 0)) return false;
			if (dimIsString != PT_TRI_YES) return true;
		}

		zv::Val exprVarType = getScopeStateType(var);
		if (UNEXPECTED(exprVarType.isUndef())) return false;
		if (UNEXPECTED(!exprVarType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(exprVarType.raw()));
			return false;
		}
		zend_long isArray = pt_type_op_trinary(exprVarType.ref().asObject(), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return false;
		bool isMixed = pt_ce_mixed_type != NULL && instanceof_function(exprVarType.ref().asObject()->ce, pt_ce_mixed_type);
		if (isMixed || isArray == PT_TRI_NO) return true;

		zv::Val varType = zv::Val::copyOf(exprVarType.ref());
		if (isArray != PT_TRI_YES) {
			zv::Val accessible = dimIsInteger == PT_TRI_YES
				? pt_static_type_factory_int_offset_accessible()
				: pt_static_type_factory_general_offset_accessible();
			if (UNEXPECTED(accessible.isUndef())) return false;
			zv::Args args{exprVarType.raw(), accessible.raw()};
			varType = pt_type_combinator_intersect(2, args);
			if (UNEXPECTED(varType.isUndef())) return false;
		}

		zend_class_entry *dimCe = dimType.ref().asObject()->ce;
		if ((pt_ce_constant_integer_type != NULL && instanceof_function(dimCe, pt_ce_constant_integer_type))
			|| (pt_ce_constant_string_type != NULL && instanceof_function(dimCe, pt_ce_constant_string_type))) {
			bool complex_;
			if (UNEXPECTED(!isComplexUnionType(varType.raw(), complex_))) return false;
			if (!complex_) {
				zval hasOffsetZv;
				if (UNEXPECTED(!pt_has_offset_value_type_new(&hasOffsetZv, dimType.raw(), type))) return false;
				zv::Val hasOffset = zv::Val::adopt(hasOffsetZv);
				zv::Args args{varType.raw(), hasOffset.raw()};
				zv::Val narrowed = pt_type_combinator_intersect(2, args);
				if (UNEXPECTED(narrowed.isUndef())) return false;
				varType = std::move(narrowed);
			}
		}

		zv::Val varNativeType = getScopeStateNativeType(var);
		if (UNEXPECTED(varNativeType.isUndef())) return false;
		return specifyExpressionTypeInPlace(var, varType.raw(), varNativeType.raw(), certainty);
	}

	/* (twin 3757) */
	zv::Val assignExpression(zend_object *expr, zval *type, zval *nativeType)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val scope = self_();
		bool isPropertyFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_PROPERTY_FETCH, isPropertyFetch))) return zv::Val();
		if (isPropertyFetch) {
			scope = thisInvalidateExpression(&exprZv, false, NULL, false);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zend_object *scopeObject = requireObject(scope, "invalidateMethodsOnExpression");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			zv::Ref var = nodeProp(expr, PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
			if (UNEXPECTED(!var.deref().isObject())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_object *varObject = var.deref().asObject();
			zval varZv;
			ZVAL_OBJ(&varZv, varObject);
			scope = otherPrivate(scopeObject, PT_LC("invalidatemethodsonexpression"), 1, &varZv, [&](MutatingScope &other) {
				return other.invalidateMethodsOnExpression(varObject);
			});
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		} else {
			bool invalidate;
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_STATIC_PROPERTY_FETCH, invalidate))) return zv::Val();
			if (!invalidate) {
				if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_VARIABLE, invalidate))) return zv::Val();
			}
			if (invalidate) {
				scope = thisInvalidateExpression(&exprZv, false, NULL, false);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		zend_object *scopeObject = requireObject(scope, "specifyExpressionType");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zv::Args args{expr, type, nativeType, pt_trinary_singleton(PT_TRI_YES)};
		return pt_type_call(scopeObject, PT_LC("specifyexpressiontype"), 4, args);
	}

	/* private (twin 3382): assignExpression() for a value that overwrites
	 * what an already existing offset holds - a byref alias write or a
	 * setAlwaysOverwriteTypes() specification. An ArrayDimFetch gets the
	 * value written into the containing array (setExistingOffsetValueType()
	 * all the way up) instead of the parent being narrowed with
	 * HasOffsetValueType(dim, value), which collapses to never when the
	 * parent still holds the offset's previous constant value. */
	zv::Val overwriteExpression(zval *expr, zval *type, zval *nativeType)
	{
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(expr), PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		zv::Ref dim(NULL);
		if (isArrayDimFetch) {
			dim = nodeProp(Z_OBJ_P(expr), PT_LC("dim"));
			if (UNEXPECTED(dim.raw() == NULL)) return zv::Val();
		}
		if (!isArrayDimFetch || dim.deref().isNull()) return thisAssignExpression(expr, type, nativeType);

		zv::Ref var = nodeProp(Z_OBJ_P(expr), PT_LC("var"));
		if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
		zv::Val dimType = thisGetType(dim.deref().raw());
		if (UNEXPECTED(dimType.isUndef())) return zv::Val();

		zv::Val varType = thisGetType(var.deref().raw());
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		if (UNEXPECTED(!varType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setExistingOffsetValueType() on %s", zend_zval_value_name(varType.raw()));
			return zv::Val();
		}
		zv::Args typeArgs{dimType.raw(), type};
		zv::Val newVarType = pt_type_call(varType.ref().asObject(), PT_LC("setexistingoffsetvaluetype"), 2, typeArgs);
		if (UNEXPECTED(newVarType.isUndef())) return zv::Val();

		zv::Val varNativeType = thisGetNativeType(var.deref().raw());
		if (UNEXPECTED(varNativeType.isUndef())) return zv::Val();
		if (UNEXPECTED(!varNativeType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setExistingOffsetValueType() on %s", zend_zval_value_name(varNativeType.raw()));
			return zv::Val();
		}
		zv::Args nativeTypeArgs{dimType.raw(), nativeType};
		zv::Val newVarNativeType = pt_type_call(varNativeType.ref().asObject(), PT_LC("setexistingoffsetvaluetype"), 2, nativeTypeArgs);
		if (UNEXPECTED(newVarNativeType.isUndef())) return zv::Val();

		zv::Val scope = overwriteExpression(var.deref().raw(), newVarType.raw(), newVarNativeType.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope, "specifyExpressionType");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zv::Args specifyArgs{expr, type, nativeType, pt_trinary_singleton(PT_TRI_YES)};
		return pt_type_call(scopeObject, PT_LC("specifyexpressiontype"), 4, specifyArgs);
	}

	/* (twin 3785) */
	zv::Val assignInitializedProperty(zval *fetchedOnType, zend_string *propertyName)
	{
		bool inClass;
		if (UNEXPECTED(!thisIsInClass(inClass))) return zv::Val();
		if (!inClass) return self_();
		zv::Val thisType = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("findthistype"), 1, fetchedOnType);
		if (UNEXPECTED(thisType.isUndef())) return zv::Val();
		if (thisType.ref().isNull()) return self_();
		zv::Args propertyArgs{fetchedOnType, propertyName};
		zv::Val propertyReflection = thisCallByName(PT_LC("getinstancepropertyreflection"), 2, propertyArgs);
		if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();
		if (propertyReflection.ref().isNull()) return self_();
		if (UNEXPECTED(!propertyReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getDeclaringClass() on %s", zend_zval_value_name(propertyReflection.raw()));
			return zv::Val();
		}
		zv::Val declaringClass = pt_type_call(propertyReflection.ref().asObject(), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!declaringClass.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(declaringClass.raw()));
			return zv::Val();
		}
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!classReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
			return zv::Val();
		}
		zv::Val ownName = pt_class_reflection_get_name(classReflection.ref().asObject());
		zv::Val declaringName = pt_class_reflection_get_name(declaringClass.ref().asObject());
		if (UNEXPECTED(ownName.isUndef() || declaringName.isUndef())) return zv::Val();
		if (!ownName.ref().isString() || !declaringName.ref().isString() || !zend_string_equals(ownName.ref().asString(), declaringName.ref().asString())) {
			return self_();
		}
		zval propertyNameZv;
		ZVAL_STR(&propertyNameZv, propertyName);
		zv::Val hasNativeProperty = pt_type_call(declaringClass.ref().asObject(), PT_LC("hasnativeproperty"), 1, &propertyNameZv);
		if (UNEXPECTED(hasNativeProperty.isUndef())) return zv::Val();
		if (!zend_is_true(hasNativeProperty.raw())) return self_();

		zv::Val initializationExpr = pt_type_new(PT_CLASS_PROPERTY_INITIALIZATION_EXPR, 1, &propertyNameZv);
		if (UNEXPECTED(initializationExpr.isUndef())) return zv::Val();
		zval mixedZv, nativeMixedZv;
		if (UNEXPECTED(!pt_mixed_type_new(&mixedZv) || !pt_mixed_type_new(&nativeMixedZv))) return zv::Val();
		zv::Val mixedType = zv::Val::adopt(mixedZv);
		zv::Val nativeMixedType = zv::Val::adopt(nativeMixedZv);
		zv::Val scope = thisAssignExpression(initializationExpr.raw(), mixedType.raw(), nativeMixedType.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		zend_object *scopeObject = requireObject(scope, "getFunction");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zv::Val function = pt_type_call(scopeObject, PT_LC("getfunction"), 0, NULL);
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		bool isMethod;
		if (UNEXPECTED(!isInstance(function.ref(), PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
		if (!isMethod) return scope;
		zv::Val functionName = pt_type_call(function.ref().asObject(), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(functionName.isUndef())) return zv::Val();
		zend_string *raw = zval_get_string(functionName.raw());
		if (UNEXPECTED(raw == NULL)) return zv::Val();
		zend_string *lowered = zend_string_tolower(raw);
		zend_string_release(raw);
		bool isClone = zend_string_equals_literal(lowered, "__clone");
		zend_string_release(lowered);
		if (!isClone) return scope;
		zval *phpVersion = otherProp(scopeObject, PT_MS_PROP_PHP_VERSION, PT_LC("phpVersion"));
		if (UNEXPECTED(phpVersion == NULL)) return zv::Val();
		ZVAL_DEREF(phpVersion);
		if (UNEXPECTED(Z_TYPE_P(phpVersion) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function supportsReadonlyPropertyReinitializationOnClone() on %s", zend_zval_value_name(phpVersion));
			return zv::Val();
		}
		bool supports;
		if (UNEXPECTED(!otherCallBool(Z_OBJ_P(phpVersion), PT_LC("supportsreadonlypropertyreinitializationonclone"), supports))) return zv::Val();
		if (!supports) return scope;
		zv::Val reinitializationExpr = pt_type_new(PT_CLASS_CLONE_REINITIALIZATION_EXPR, 1, &propertyNameZv);
		if (UNEXPECTED(reinitializationExpr.isUndef())) return zv::Val();
		zval cloneMixedZv, cloneNativeMixedZv;
		if (UNEXPECTED(!pt_mixed_type_new(&cloneMixedZv) || !pt_mixed_type_new(&cloneNativeMixedZv))) return zv::Val();
		zv::Val cloneMixed = zv::Val::adopt(cloneMixedZv);
		zv::Val cloneNativeMixed = zv::Val::adopt(cloneNativeMixedZv);
		return otherAssignExpression(scope, reinitializationExpr.raw(), cloneMixed.raw(), cloneNativeMixed.raw());
	}

	/* (twin 3813) */
	zv::Val invalidateExpression(zval *expressionToInvalidate, bool requireMoreCharacters, zval *invalidatingClass, bool keepPropertyFetches)
	{
		zv::Val exprString = thisGetNodeKey(expressionToInvalidate);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		zv::Ref exprPrinter = slot(PT_MS_PROP_EXPR_PRINTER);
		if (UNEXPECTED(!exprPrinter.isObject())) return uninitializedProperty("exprPrinter");
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) {
			return zv::Val();
		}
		zv::Val result = pt_scope_ops_invalidate_expression_entries(
			thisZval(),
			exprPrinter.raw(),
			key.get(),
			expressionToInvalidate,
			requireMoreCharacters,
			invalidatingClass,
			Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()),
			keepPropertyFetches);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (result.ref().isNull()) return self_();
		zval *expressionTypes = zend_hash_index_find(Z_ARRVAL_P(result.raw()), 0);
		zval *nativeExpressionTypes = zend_hash_index_find(Z_ARRVAL_P(result.raw()), 1);
		zval *conditionalExpressions = zend_hash_index_find(Z_ARRVAL_P(result.raw()), 2);
		if (UNEXPECTED(expressionTypes == NULL || nativeExpressionTypes == NULL || conditionalExpressions == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: ScopeOps::invalidateExpressionEntries() answered an unexpected shape");
			return zv::Val();
		}
		return scopeWith(Z_ARRVAL_P(expressionTypes), Z_ARRVAL_P(nativeExpressionTypes), Z_ARRVAL_P(conditionalExpressions), true);
	}

	/* ScopeOps::scopeWith($this, ...) with the twin's argument list: the
	 * three tables as given, an empty call stack, the rest from $this */
	zv::Val scopeWith(HashTable *expressionTypes, HashTable *nativeExpressionTypes, HashTable *conditionalExpressions, bool emptyCallStack)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions")
			|| !requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) {
			return zv::Val();
		}
		return pt_scope_ops_scope_with(
			thisZval(),
			expressionTypes,
			nativeExpressionTypes,
			conditionalExpressions,
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()),
			emptyCallStack ? (HashTable *) &zend_empty_array : Z_ARRVAL_P(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw()),
			slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT),
			slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL));
	}

	/** @internal called by ScopeOps (twin 3846); false = pending exception */
	[[nodiscard]] bool isPrivatePropertyOfDifferentClass(zend_object *expr, zval *invalidatingClass, bool &out)
	{
		out = false;
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_STATIC_PROPERTY_FETCH, isFetch))) return false;
		if (!isFetch) {
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_PROPERTY_FETCH, isFetch))) return false;
		}
		if (!isFetch) return true;
		zv::Ref finder = slot(PT_MS_PROP_PROPERTY_REFLECTION_FINDER);
		if (UNEXPECTED(!finder.isObject())) {
			(void) uninitializedProperty("propertyReflectionFinder");
			return false;
		}
		zv::Args args{&exprZv, thisZval()};
		zv::Val propertyReflection = pt_type_call(finder.asObject(), PT_LC("findpropertyreflectionfromnode"), 2, args);
		if (UNEXPECTED(propertyReflection.isUndef())) return false;
		if (propertyReflection.ref().isNull()) return true;
		if (UNEXPECTED(!propertyReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isPrivate() on %s", zend_zval_value_name(propertyReflection.raw()));
			return false;
		}
		bool isPrivate;
		if (UNEXPECTED(!otherCallBool(propertyReflection.ref().asObject(), PT_LC("isprivate"), isPrivate))) return false;
		if (!isPrivate) return true;
		zv::Val declaringClass = pt_type_call(propertyReflection.ref().asObject(), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		if (UNEXPECTED(!declaringClass.ref().isObject() || Z_TYPE_P(invalidatingClass) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(declaringClass.raw()));
			return false;
		}
		zv::Val declaringName = pt_class_reflection_get_name(declaringClass.ref().asObject());
		zv::Val invalidatingName = pt_class_reflection_get_name(Z_OBJ_P(invalidatingClass));
		if (UNEXPECTED(declaringName.isUndef() || invalidatingName.isUndef())) return false;
		out = !(declaringName.ref().isString() && invalidatingName.ref().isString()
			&& zend_string_equals(declaringName.ref().asString(), invalidatingName.ref().asString()));
		return true;
	}

	/* private (twin 3863) */
	zv::Val invalidateMethodsOnExpression(zend_object *expressionToInvalidate)
	{
		zv::Ref exprPrinter = slot(PT_MS_PROP_EXPR_PRINTER);
		if (UNEXPECTED(!exprPrinter.isObject())) return uninitializedProperty("exprPrinter");
		zval exprZv;
		ZVAL_OBJ(&exprZv, expressionToInvalidate);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		zv::Val result = pt_scope_ops_invalidate_methods_on_expression(
			exprPrinter.raw(),
			key.get(),
			Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()));
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (result.ref().isNull()) return self_();
		zval *expressionTypes = zend_hash_index_find(Z_ARRVAL_P(result.raw()), 0);
		zval *nativeExpressionTypes = zend_hash_index_find(Z_ARRVAL_P(result.raw()), 1);
		if (UNEXPECTED(expressionTypes == NULL || nativeExpressionTypes == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: ScopeOps::invalidateMethodsOnExpression() answered an unexpected shape");
			return zv::Val();
		}
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) return zv::Val();
		return scopeWith(Z_ARRVAL_P(expressionTypes), Z_ARRVAL_P(nativeExpressionTypes), Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()), true);
	}

	/* private (twin 3893); its only caller is applySpecifiedTypes() */
	zv::Val setExpressionCertaintyKeepingType(zend_object *expr, zval *certainty)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zv::Val exprString = thisGetNodeKey(&exprZv);
		if (UNEXPECTED(exprString.isUndef())) return zv::Val();
		zv::Str key = zv::Str::adopt(zval_get_string(exprString.raw()));
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		zval *holder = zend_symtable_find(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), key.get());
		if (holder == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val exprType = holderType(zv::Ref(holder));
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		zval *nativeHolder = zend_symtable_find(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()), key.get());
		zv::Val nativeType;
		if (nativeHolder != NULL) {
			nativeType = holderType(zv::Ref(nativeHolder));
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		} else {
			nativeType = zv::Val::copyOf(exprType.ref());
		}
		return thisSpecifyExpressionType(&exprZv, exprType.raw(), nativeType.raw(), certainty);
	}

	/* private (twin 3916) — a large union whose intersection members carry
	 * HasOffsetValueType; false = pending exception */
	[[nodiscard]] bool isComplexUnionType(zval *type, bool &out)
	{
		out = false;
		bool isUnion;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_union_type, isUnion))) return false;
		if (!isUnion) return true;
		zv::Val types = pt_type_op(Z_OBJ_P(type), PT_OP_GET_TYPES, 0, NULL);
		if (UNEXPECTED(types.isUndef())) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(types.raw())) <= PT_MS_COMPLEX_UNION_TYPE_MEMBER_LIMIT) return true;
		for (auto entry : zv::ArrRef(types.raw())) {
			bool isIntersection;
			if (UNEXPECTED(!pt_type_instanceof_ce(entry.value().deref().raw(), pt_ce_intersection_type, isIntersection))) return false;
			if (!isIntersection) continue;
			zv::Val innerTypes = pt_type_op(entry.value().deref().asObject(), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(innerTypes.isUndef())) return false;
			for (auto innerEntry : zv::ArrRef(innerTypes.raw())) {
				bool hasOffsetValue;
				if (UNEXPECTED(!pt_type_instanceof_ce(innerEntry.value().deref().raw(), pt_ce_has_offset_value_type, hasOffsetValue))) return false;
				if (hasOffsetValue) {
					out = true;
					return true;
				}
			}
		}
		return true;
	}

	/* (twin 3930) */
	zv::Val addTypeToExpression(zend_object *expr, zval *type)
	{
		zv::Val originalExprType = getScopeStateType(expr);
		if (UNEXPECTED(originalExprType.isUndef())) return zv::Val();
		bool complex_;
		if (UNEXPECTED(!isComplexUnionType(originalExprType.raw(), complex_))) return zv::Val();
		if (complex_) return self_();
		zv::Val nativeType = getScopeStateNativeType(expr);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		if (UNEXPECTED(!originalExprType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(originalExprType.raw()));
			return zv::Val();
		}
		zv::Val equals = pt_type_op(originalExprType.ref().asObject(), PT_OP_EQUALS, 1, nativeType.raw());
		if (UNEXPECTED(equals.isUndef())) return zv::Val();
		zv::Args intersectArgs{type, originalExprType.raw()};
		zv::Val newType = pt_type_combinator_intersect(2, intersectArgs);
		if (UNEXPECTED(newType.isUndef())) return zv::Val();
		if (zend_is_true(equals.raw())) return thisSpecifyExpressionType(&exprZv, newType.raw(), newType.raw(), pt_trinary_singleton(PT_TRI_YES));
		zv::Args nativeIntersectArgs{type, nativeType.raw()};
		zv::Val newNativeType = pt_type_combinator_intersect(2, nativeIntersectArgs);
		if (UNEXPECTED(newNativeType.isUndef())) return zv::Val();
		return thisSpecifyExpressionType(&exprZv, newType.raw(), newNativeType.raw(), pt_trinary_singleton(PT_TRI_YES));
	}

	/* (twin 3950) */
	zv::Val removeTypeFromExpression(zend_object *expr, zval *typeToRemove)
	{
		bool isNever;
		if (UNEXPECTED(!pt_type_instanceof_ce(typeToRemove, pt_ce_never_type, isNever))) return zv::Val();
		if (isNever) return self_();
		zv::Val exprType = getScopeStateType(expr);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_type_instanceof_ce(exprType.raw(), pt_ce_never_type, isNever))) return zv::Val();
		if (isNever) return self_();
		bool complex_;
		if (UNEXPECTED(!isComplexUnionType(exprType.raw(), complex_))) return zv::Val();
		if (complex_) return self_();
		zv::Val removed = pt_type_combinator_remove(exprType.raw(), typeToRemove);
		if (UNEXPECTED(removed.isUndef())) return zv::Val();
		zv::Val nativeType = getScopeStateNativeType(expr);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zv::Val removedNative = pt_type_combinator_remove(nativeType.raw(), typeToRemove);
		if (UNEXPECTED(removedNative.isUndef())) return zv::Val();
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		return thisSpecifyExpressionType(&exprZv, removed.raw(), removedNative.raw(), pt_trinary_singleton(PT_TRI_YES));
	}

	/* }}} */

	/* {{{ twin 3993-4773: the narrowing application, the
	 * conditional-expression bookkeeping and the scope merges */

	/* a table property of another scope object as a HashTable; NULL with an
	 * exception pending when the property is missing or unwritten */
	static HashTable *otherTable(zend_object *object, uint32_t nativeSlot, const char *name, size_t len)
	{
		zval *value = otherProp(object, nativeSlot, name, len);
		if (UNEXPECTED(value == NULL)) return NULL;
		ZVAL_DEREF(value);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(object->ce->name), name);
			return NULL;
		}
		return Z_ARRVAL_P(value);
	}

	/* a bool property of another scope object; false = pending exception */
	[[nodiscard]] static bool otherBool(zend_object *object, uint32_t nativeSlot, const char *name, size_t len, bool &out)
	{
		zval *value = otherProp(object, nativeSlot, name, len);
		if (UNEXPECTED(value == NULL)) return false;
		ZVAL_DEREF(value);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(object->ce->name), name);
			return false;
		}
		out = zend_is_true(value);
		return true;
	}

	/* $scope->scopeFactory->create(...) on any scope object */
	static zv::Val otherScopeFactoryCreate(zend_object *scopeObject, CreateArgs &args)
	{
		zval *factory = otherProp(scopeObject, PT_MS_PROP_SCOPE_FACTORY, PT_LC("scopeFactory"));
		if (UNEXPECTED(factory == NULL)) return zv::Val();
		ZVAL_DEREF(factory);
		if (UNEXPECTED(Z_TYPE_P(factory) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function create() on %s", zend_zval_value_name(factory));
			return zv::Val();
		}
		return factoryCreate(Z_OBJ_P(factory), args);
	}

	/* $a->isSuperTypeOf($b)->no(); false = pending exception */
	[[nodiscard]] static bool isSuperTypeOfNo(zend_object *a, zval *b, bool &out)
	{
		zv::Val result = pt_type_op(a, PT_OP_IS_SUPER_TYPE_OF, 1, b);
		if (UNEXPECTED(result.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function no() on %s", zend_zval_value_name(result.raw()));
			return false;
		}
		zv::Val no = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("no"), 0, NULL);
		if (UNEXPECTED(no.isUndef())) return false;
		out = zend_is_true(no.raw());
		return true;
	}

	/* TypeCombinator::intersect($a, $b) */
	static zv::Val intersectTypes(zval *a, zval *b)
	{
		zv::Args args{a, b};
		return pt_type_combinator_intersect(2, args);
	}

	/* TypeCombinator::union($a, $b) */
	static zv::Val unionTypes(zval *a, zval *b)
	{
		zv::Args args{a, b};
		return pt_type_combinator_union(2, args);
	}

	/* $holder->getKey() of a ConditionalExpressionHolder: the native key
	 * builder for a native holder, the method otherwise; NULL = pending
	 * exception */
	static zend_string *conditionalHolderKey(zv::Ref holder)
	{
		holder = holder.deref();
		if (UNEXPECTED(!holder.isObject())) {
			zend_throw_error(NULL, "Call to a member function getKey() on %s", zend_zval_value_name(holder.raw()));
			return NULL;
		}
		if (EXPECTED(holder.asObject()->ce == pt_ce_cond_expr_holder)) {
			zv::ObjRef object(holder.asObject());
			zv::Ref conditions = object.propAt(PT_CEH_PROP_CONDS).deref();
			zv::Ref typeHolder = object.propAt(PT_CEH_PROP_TYPEHOLDER).deref();
			if (UNEXPECTED(!conditions.isArray() || !typeHolder.isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: ConditionalExpressionHolder is not initialized");
				return NULL;
			}
			return pt_ceh_key_build(conditions.asArrayTable(), typeHolder.raw());
		}
		zv::Val key = pt_type_call(holder.asObject(), PT_LC("getkey"), 0, NULL);
		if (UNEXPECTED(key.isUndef())) return NULL;
		return zval_get_string(key.raw());
	}

	/* $holder->getTypeHolder() / ->getConditionExpressionTypeHolders() of a
	 * ConditionalExpressionHolder: its slots when it is the native class,
	 * its methods otherwise */
	static zv::Val conditionalHolderRead(zv::Ref holder, uint32_t slot, const char *lcname, size_t len)
	{
		holder = holder.deref();
		if (UNEXPECTED(!holder.isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(holder.raw()));
			return zv::Val();
		}
		if (EXPECTED(holder.asObject()->ce == pt_ce_cond_expr_holder)) return zv::Val::copyOf(zv::ObjRef(holder.asObject()).propAt(slot).deref());
		return pt_type_call(holder.asObject(), lcname, len, 0, NULL);
	}

	static zv::Val conditionalTypeHolder(zv::Ref holder) { return conditionalHolderRead(holder, PT_CEH_PROP_TYPEHOLDER, PT_LC("gettypeholder")); }
	static zv::Val conditionalConditions(zv::Ref holder) { return conditionalHolderRead(holder, PT_CEH_PROP_CONDS, PT_LC("getconditionexpressiontypeholders")); }

	/* new ConditionalExpressionHolder($conditions, $typeHolder) — the
	 * shadowed class through its own class entry (rule 4) */
	static zv::Val newConditionalExpressionHolder(zv::Ref conditions, zv::Ref typeHolder)
	{
		zval raw;
		object_init_ex(&raw, pt_ce_cond_expr_holder);
		zv::Val holder = zv::Val::adopt(raw);
		zv::ObjRef object(holder.ref().asObject());
		object.propAtWrite(PT_CEH_PROP_CONDS, zv::Val::copyOf(conditions.deref()));
		object.propAtWrite(PT_CEH_PROP_TYPEHOLDER, zv::Val::copyOf(typeHolder.deref()));
		return holder;
	}

	/* (twin 3993 / 4003) */
	zv::Val filterByValue(zend_object *expr, bool truthy)
	{
		zv::Ref typeSpecifier = slot(PT_MS_PROP_TYPE_SPECIFIER);
		if (UNEXPECTED(!typeSpecifier.isObject())) return uninitializedProperty("typeSpecifier");
		/* the singleton, borrowed: the context registry holds it */
		zend_object *context = truthy ? pt_type_specifier_context_create_truthy() : pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(context == NULL)) return zv::Val();
		zv::Val specifiedTypes = pt_type_specifier_specify_types_in_condition(typeSpecifier.asObject(), thisZval(), expr, context);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();

		/* if ($specifiedTypes->isEquality() && $this->getType($expr)->isBoolean()->yes()) {
		 *     $specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
		 *         $expr, new ConstantBooleanType($truthy), TypeSpecifierContext::createTrue(), $this)); } */
		bool isEquality;
		if (UNEXPECTED(!pt_specified_types_is_equality(specifiedTypes.raw(), isEquality))) return zv::Val();
		if (isEquality) {
			zval exprValue;
			ZVAL_OBJ(&exprValue, expr);
			zv::Val exprType = thisGetType(&exprValue);
			if (UNEXPECTED(exprType.isUndef())) return zv::Val();
			zend_long isBoolean = pt_type_op_trinary(Z_OBJ_P(exprType.raw()), PT_OP_IS_BOOLEAN, 0, NULL);
			if (UNEXPECTED(isBoolean < 0)) return zv::Val();
			if (isBoolean == PT_TRI_YES) {
				zval constantBoolean;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&constantBoolean, truthy))) return zv::Val();
				zv::Val booleanType = zv::Val::adopt(constantBoolean);
				zend_object *trueContext = pt_type_specifier_context_create_true();
				if (UNEXPECTED(trueContext == NULL)) return zv::Val();
				zval createArgs[4];
				ZVAL_OBJ(&createArgs[0], expr);
				ZVAL_COPY_VALUE(&createArgs[1], booleanType.raw());
				ZVAL_OBJ(&createArgs[2], trueContext);
				ZVAL_COPY_VALUE(&createArgs[3], thisZval());
				zv::Val equalityTypes = pt_type_call(typeSpecifier.asObject(), PT_LC("create"), 4, createArgs);
				if (UNEXPECTED(equalityTypes.isUndef())) return zv::Val();
				specifiedTypes = pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), equalityTypes.raw());
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
			}
		}

		return thisApplySpecifiedTypes(specifiedTypes.raw());
	}

	/* one entry of applySpecifiedTypes()' sorted batch; exprString is
	 * borrowed from the batch's key owner, expr / type / terms from the
	 * SpecifiedTypes tables the caller holds */
	struct TypeSpecification
	{
		bool sure;
		zend_string *exprString;
		zval *expr;
		zval *type;
		zval *terms;
	};

	/* an owned copy of an array key, kept alive by $owner */
	static zend_string *ownedKey(zv::Arr &owner, zend_string *skey, zend_ulong idx)
	{
		zend_string *key = skey != NULL ? zend_string_copy(skey) : zend_long_to_str((zend_long) idx);
		zval value;
		ZVAL_STR(&value, key);
		owner.push(zv::Val::adopt(value));
		return key;
	}

	/* $expr instanceof Node\Scalar || $expr instanceof Array_ || $expr
	 * instanceof Expr\UnaryMinus && $expr->expr instanceof Node\Scalar */
	static bool isNeverSpecifiedExpr(zval *expr, bool &out)
	{
		out = false;
		bool is;
		if (UNEXPECTED(!isInstance(zv::Ref(expr), PT_CLASS_SCALAR, is))) return false;
		if (is) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!isInstance(zv::Ref(expr), PT_CLASS_ARRAY_EXPR, is))) return false;
		if (is) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!isInstance(zv::Ref(expr), PT_CLASS_UNARY_MINUS, is))) return false;
		if (!is) return true;
		zv::Ref inner = nodeProp(Z_OBJ_P(expr), PT_LC("expr"));
		if (UNEXPECTED(inner.raw() == NULL)) return false;
		return isInstance(inner.deref(), PT_CLASS_SCALAR, out);
	}

	/* one of the three SpecifiedTypes tables into the batch */
	static bool collectTypeSpecifications(zval *table, bool sure, bool alternative, std::vector<TypeSpecification> &out, zv::Arr &keyOwner)
	{
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: SpecifiedTypes did not answer with an array");
			return false;
		}
		for (auto entry : zv::ArrRef(table)) {
			zv::Ref pair = entry.value().deref();
			if (UNEXPECTED(!pair.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: a SpecifiedTypes entry is not an array");
				return false;
			}
			zval *expr = zend_hash_index_find(pair.asArrayTable(), 0);
			zval *second = zend_hash_index_find(pair.asArrayTable(), 1);
			if (UNEXPECTED(expr == NULL || second == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: a SpecifiedTypes entry has an unexpected shape");
				return false;
			}
			ZVAL_DEREF(expr);
			ZVAL_DEREF(second);
			if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
				zend_throw_error(NULL, "phpstan_turbo: a SpecifiedTypes entry has no expression");
				return false;
			}
			bool skip;
			if (UNEXPECTED(!isNeverSpecifiedExpr(expr, skip))) return false;
			if (skip) continue;
			TypeSpecification spec;
			spec.sure = sure;
			spec.exprString = ownedKey(keyOwner, entry.stringKeyOrNull(), entry.indexKey());
			spec.expr = expr;
			spec.type = alternative ? NULL : second;
			spec.terms = alternative ? second : NULL;
			out.push_back(spec);
		}
		return true;
	}

	/* $specifiedTypes->shouldOverwrite(); false = pending exception */
	[[nodiscard]] static bool shouldOverwrite(zend_object *specifiedTypes, bool &out)
	{
		return pt_specified_types_should_overwrite(specifiedTypes, out);
	}

	/* $scope->isComplexUnionType($type) on any scope object */
	static bool otherIsComplexUnionType(zend_object *scopeObject, zval *type, bool &out)
	{
		zv::Val result = otherPrivate(scopeObject, PT_LC("iscomplexuniontype"), 1, type, [&](MutatingScope &other) {
			bool value;
			if (UNEXPECTED(!other.isComplexUnionType(type, value))) return zv::Val();
			return zv::Val::boolean(value);
		});
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/*
	 * applySpecifiedTypes()' alternative-form evaluator: the union over the
	 * entry's terms of `(sure ?? current) minus subtract`. $current may be
	 * NULL; $isNull is the twin's null answer (a current-type-dependent term
	 * with no known current type). false = pending exception.
	 */
	[[nodiscard]] static bool evaluateAlternativeTerms(zval *terms, zval *current, zv::Val &out, bool &isNull)
	{
		isNull = false;
		if (UNEXPECTED(Z_TYPE_P(terms) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: an alternative SpecifiedTypes entry has no terms");
			return false;
		}
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(terms));
		std::vector<zval> argv;
		std::vector<zv::Val> owned;
		argv.reserve(count);
		owned.reserve(count);
		for (auto entry : zv::ArrRef(terms)) {
			zv::Ref term = entry.value().deref();
			if (UNEXPECTED(!term.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: an alternative term is not an array");
				return false;
			}
			zval *sure = zend_hash_index_find(term.asArrayTable(), 0);
			zval *subtract = zend_hash_index_find(term.asArrayTable(), 1);
			if (UNEXPECTED(sure == NULL || subtract == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: an alternative term has an unexpected shape");
				return false;
			}
			ZVAL_DEREF(sure);
			ZVAL_DEREF(subtract);
			zval *base = Z_TYPE_P(sure) != IS_NULL ? sure : current;
			if (base == NULL || Z_TYPE_P(base) == IS_NULL) {
				isNull = true;
				return true;
			}
			if (Z_TYPE_P(subtract) != IS_NULL) {
				zv::Val removed = pt_type_combinator_remove(base, subtract);
				if (UNEXPECTED(removed.isUndef())) return false;
				argv.push_back(*removed.raw());
				owned.push_back(std::move(removed));
			} else {
				argv.push_back(*base);
			}
		}
		out = pt_type_combinator_union((uint32_t) argv.size(), argv.data());
		return EXPECTED(!out.isUndef());
	}

	/* applySpecifiedTypes()' in-place specification step: the batch's one
	 * unpublished working copy opens on the first specification and takes
	 * every later one. false = pending exception */
	[[nodiscard]] bool specifyInBatch(zend_object *expr, zval *newType, zval *newNativeType, zv::Val &scope, zend_object *&scopeObject, bool &scopeIsWorkingCopy)
	{
		bool noop;
		if (UNEXPECTED(!isSpecifyExpressionTypeNoop(expr, newType, noop))) return false;
		if (noop) return true;
		if (!scopeIsWorkingCopy) {
			zv::Val opened = otherPrivate(scopeObject, PT_LC("openspecificationscope"), 0, NULL, [&](MutatingScope &other) { return other.openSpecificationScope(); });
			if (UNEXPECTED(opened.isUndef())) return false;
			zend_object *openedObject = requireObject(opened, "specifyExpressionTypeInPlace");
			if (UNEXPECTED(openedObject == NULL)) return false;
			scope = std::move(opened);
			scopeObject = openedObject;
			scopeIsWorkingCopy = true;
		}
		return otherSpecifyInPlace(scopeObject, expr, newType, newNativeType, pt_trinary_singleton(PT_TRI_YES));
	}

	/* $specifiedExpressions[$exprString] = ExpressionTypeHolder::createYes($expr, $holderType) */
	static bool recordSpecifiedExpression(zv::Arr &specifiedExpressions, zend_object *scopeObject, zend_string *exprString, zend_object *expr, zval *fallbackType)
	{
		HashTable *table = otherTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(table == NULL)) return false;
		zval *existing = zend_symtable_find(table, exprString);
		zv::Val trackedType;
		if (existing != NULL) {
			trackedType = holderType(zv::Ref(existing));
			if (UNEXPECTED(trackedType.isUndef())) return false;
		} else {
			trackedType = zv::Val::copyOf(zv::Ref(fallbackType));
		}
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		zval holder;
		pt_holder_create(&holder, &exprZv, trackedType.raw(), PT_TRI_YES);
		specifiedExpressions.set(exprString, zv::Val::adopt(holder));
		return true;
	}

	/* (twin 4020) */
	zv::Val applySpecifiedTypes(zval *specifiedTypesArg)
	{
		if (UNEXPECTED(Z_TYPE_P(specifiedTypesArg) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getDeferredAugments() on %s", zend_zval_value_name(specifiedTypesArg));
			return zv::Val();
		}
		zv::Val specifiedTypes = zv::Val::copyOf(zv::Ref(specifiedTypesArg));

		/* the deferred augments see this scope's pre-application state — the
		 * application point of the narrowing; their entries join this batch */
		zv::Val augments = pt_specified_types_get_deferred_augments(Z_OBJ_P(specifiedTypes.raw()));
		if (UNEXPECTED(augments.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(augments.raw()) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: getDeferredAugments() did not answer with an array");
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(augments.raw())) > 0) {
			zv::Arr pending = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(augments.raw())));
			for (auto entry : zv::ArrRef(augments.raw())) {
				pending.push(entry.value().deref());
			}
			for (zend_ulong cursor = 0;; cursor++) {
				zval *slotZv = zend_hash_index_find(pending.table(), cursor);
				if (slotZv == NULL) break;
				zv::Val augment = zv::Val::copyOf(zv::Ref(slotZv).deref());
				if (UNEXPECTED(Z_TYPE_P(augment.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function evaluate() on %s", zend_zval_value_name(augment.raw()));
					return zv::Val();
				}
				zend_object *augmentObject = Z_OBJ_P(augment.raw());
				zv::Val augmentTypes = augmentObject->ce == pt_ce_disjunction_holder_projection_augment
					? pt_disjunction_holder_projection_augment_evaluate(augmentObject, thisZval())
					: pt_disjunction_branch_union_augment_evaluate(augmentObject, thisZval());
				if (UNEXPECTED(augmentTypes.isUndef())) return zv::Val();
				if (Z_TYPE_P(augmentTypes.raw()) == IS_NULL) continue;
				if (UNEXPECTED(Z_TYPE_P(augmentTypes.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getDeferredAugments() on %s", zend_zval_value_name(augmentTypes.raw()));
					return zv::Val();
				}
				zv::Val nested = pt_specified_types_get_deferred_augments(Z_OBJ_P(augmentTypes.raw()));
				if (UNEXPECTED(nested.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(nested.raw()) != IS_ARRAY)) {
					zend_throw_error(NULL, "phpstan_turbo: getDeferredAugments() did not answer with an array");
					return zv::Val();
				}
				for (auto entry : zv::ArrRef(nested.raw())) {
					pending.push(entry.value().deref());
				}
				zv::Val united = pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), augmentTypes.raw());
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(united.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "phpstan_turbo: unionWith() did not answer with an object");
					return zv::Val();
				}
				specifiedTypes = std::move(united);
			}
		}

		zend_object *specifiedTypesObject = Z_OBJ_P(specifiedTypes.raw());
		zv::Val sureTypes = pt_specified_types_get_sure_types(specifiedTypesObject);
		if (UNEXPECTED(sureTypes.isUndef())) return zv::Val();
		zv::Val sureNotTypes = pt_specified_types_get_sure_not_types(specifiedTypesObject);
		if (UNEXPECTED(sureNotTypes.isUndef())) return zv::Val();
		zv::Val alternativeTypes = pt_specified_types_get_alternative_types(specifiedTypesObject);
		if (UNEXPECTED(alternativeTypes.isUndef())) return zv::Val();

		std::vector<TypeSpecification> typeSpecifications;
		zv::Arr keyOwner = zv::Arr::create(8);
		if (UNEXPECTED(!collectTypeSpecifications(sureTypes.raw(), true, false, typeSpecifications, keyOwner)
			|| !collectTypeSpecifications(sureNotTypes.raw(), false, false, typeSpecifications, keyOwner)
			|| !collectTypeSpecifications(alternativeTypes.raw(), true, true, typeSpecifications, keyOwner))) {
			return zv::Val();
		}

		/* the twin's usort(): shorter keys first, sure specifications before
		 * sure-not ones; PHP's sort is stable */
		std::stable_sort(typeSpecifications.begin(), typeSpecifications.end(), [](const TypeSpecification &a, const TypeSpecification &b) {
			if (ZSTR_LEN(a.exprString) != ZSTR_LEN(b.exprString)) return ZSTR_LEN(a.exprString) < ZSTR_LEN(b.exprString);
			return a.sure && !b.sure;
		});

		zv::Val scope = self_();
		zend_object *scopeObject = self;
		/* one unpublished working copy takes all in-place specifications of
		 * the batch; operations that go through other scope derivations
		 * publish it and a fresh copy opens on the next specification */
		bool scopeIsWorkingCopy = false;
		zv::Arr specifiedExpressions = zv::Arr::create((uint32_t) typeSpecifications.size());

		for (const TypeSpecification &specification : typeSpecifications) {
			zend_object *expr = Z_OBJ_P(specification.expr);
			zend_string *exprString = specification.exprString;

			bool isIssetExpr;
			if (UNEXPECTED(!isInstance(zv::Ref(specification.expr), PT_CLASS_ISSET_EXPR, isIssetExpr))) return zv::Val();
			if (isIssetExpr) {
				zv::Val inner = pt_type_call(expr, PT_LC("getexpr"), 0, NULL);
				if (UNEXPECTED(inner.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(inner.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "phpstan_turbo: IssetExpr::getExpr() did not answer with an object");
					return zv::Val();
				}
				zv::Val next;
				if (specification.sure) {
					zv::Args args{inner.raw(), pt_trinary_singleton(PT_TRI_MAYBE)};
					next = otherPrivate(scopeObject, PT_LC("setexpressioncertaintykeepingtype"), 2, args, [&](MutatingScope &other) {
						return other.setExpressionCertaintyKeepingType(Z_OBJ_P(inner.raw()), &args[1]);
					});
				} else {
					zval arg;
					ZVAL_COPY_VALUE(&arg, inner.raw());
					next = otherPrivate(scopeObject, PT_LC("unsetexpression"), 1, &arg, [&](MutatingScope &other) {
						return other.unsetExpression(Z_OBJ_P(inner.raw()));
					});
				}
				if (UNEXPECTED(next.isUndef())) return zv::Val();
				zend_object *nextObject = requireObject(next, "applySpecifiedTypes");
				if (UNEXPECTED(nextObject == NULL)) return zv::Val();
				scope = std::move(next);
				scopeObject = nextObject;
				scopeIsWorkingCopy = false;
				continue;
			}

			if (!specification.sure) {
				/* removing type from a certainly-undefined variable cannot
				 * make it defined; a sure specification still can */
				bool isVariable;
				if (UNEXPECTED(!isInstance(zv::Ref(specification.expr), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
				if (isVariable) {
					zv::Ref nameProp = nodeProp(expr, PT_LC("name"));
					if (UNEXPECTED(nameProp.raw() == NULL)) return zv::Val();
					zv::Ref name = nameProp.deref();
					if (name.isString()) {
						zval nameZv;
						ZVAL_STR(&nameZv, name.asString());
						zv::Val has = pt_type_call(scopeObject, PT_LC("hasvariabletype"), 1, &nameZv);
						if (UNEXPECTED(has.isUndef())) return zv::Val();
						zend_long value = pt_type_trinary_value(has.raw());
						if (UNEXPECTED(value < 0)) return zv::Val();
						if (value == PT_TRI_NO) continue;
					}
				}
			}

			/* only Yes-certainty holders hold the current type of the
			 * expression */
			zv::Val trackedType, trackedNativeType;
			bool hasTracked = false, hasTrackedNative = false;
			{
				HashTable *table = otherTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
				if (UNEXPECTED(table == NULL)) return zv::Val();
				zval *holder = zend_symtable_find(table, exprString);
				if (holder != NULL) {
					zend_long certainty = holderCertainty(zv::Ref(holder));
					if (UNEXPECTED(certainty < 0)) return zv::Val();
					if (certainty == PT_TRI_YES) {
						trackedType = holderType(zv::Ref(holder));
						if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
						hasTracked = true;
					}
				}
			}
			{
				HashTable *table = otherTable(scopeObject, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
				if (UNEXPECTED(table == NULL)) return zv::Val();
				zval *holder = zend_symtable_find(table, exprString);
				if (holder != NULL) {
					zend_long certainty = holderCertainty(zv::Ref(holder));
					if (UNEXPECTED(certainty < 0)) return zv::Val();
					if (certainty == PT_TRI_YES) {
						trackedNativeType = holderType(zv::Ref(holder));
						if (UNEXPECTED(trackedNativeType.isUndef())) return zv::Val();
						hasTrackedNative = true;
					}
				}
			}
			bool skipSpecification = false;
			if (!hasTracked) {
				zval exprZv;
				ZVAL_OBJ(&exprZv, expr);
				zv::Val currentTypes = otherPrivate(scopeObject, PT_LC("getcurrenttypesofspecifiedexpr"), 1, &exprZv, [&](MutatingScope &other) {
					return other.getCurrentTypesOfSpecifiedExpr(expr);
				});
				if (UNEXPECTED(currentTypes.isUndef())) return zv::Val();
				if (Z_TYPE_P(currentTypes.raw()) != IS_NULL) {
					if (UNEXPECTED(Z_TYPE_P(currentTypes.raw()) != IS_ARRAY)) {
						zend_throw_error(NULL, "phpstan_turbo: getCurrentTypesOfSpecifiedExpr() answered an unexpected shape");
						return zv::Val();
					}
					zval *phpDoc = zend_hash_index_find(Z_ARRVAL_P(currentTypes.raw()), 0);
					zval *native = zend_hash_index_find(Z_ARRVAL_P(currentTypes.raw()), 1);
					if (UNEXPECTED(phpDoc == NULL || native == NULL)) {
						zend_throw_error(NULL, "phpstan_turbo: getCurrentTypesOfSpecifiedExpr() answered an unexpected shape");
						return zv::Val();
					}
					bool complexUnion;
					if (UNEXPECTED(!otherIsComplexUnionType(scopeObject, phpDoc, complexUnion))) return zv::Val();
					if (complexUnion) continue;
					trackedType = zv::Val::copyOf(zv::Ref(phpDoc));
					hasTracked = true;
					if (!hasTrackedNative) {
						trackedNativeType = zv::Val::copyOf(zv::Ref(native));
						hasTrackedNative = true;
					}
				}
			} else {
				bool overwrite;
				if (UNEXPECTED(!shouldOverwrite(specifiedTypesObject, overwrite))) return zv::Val();
				if (!overwrite) {
					/* mirrors addTypeToExpression()/removeTypeFromExpression() */
					bool complexUnion;
					if (UNEXPECTED(!otherIsComplexUnionType(scopeObject, trackedType.raw(), complexUnion))) return zv::Val();
					if (complexUnion) {
						skipSpecification = true;
					}
				}
			}
			if (skipSpecification) continue;

			if (specification.terms != NULL) {
				/* an alternative-form entry: the union over its terms of
				 * `(sure ?? current) minus subtract`, evaluated here at the
				 * application point */
				zv::Val evaluated;
				bool isNull;
				if (UNEXPECTED(!evaluateAlternativeTerms(specification.terms, hasTracked ? trackedType.raw() : NULL, evaluated, isNull))) return zv::Val();
				if (isNull) continue;
				zval *nativeCurrent = hasTrackedNative ? trackedNativeType.raw() : (hasTracked ? trackedType.raw() : NULL);
				zv::Val evaluatedNative;
				bool nativeIsNull;
				if (UNEXPECTED(!evaluateAlternativeTerms(specification.terms, nativeCurrent, evaluatedNative, nativeIsNull))) return zv::Val();
				if (nativeIsNull) {
					evaluatedNative = zv::Val::copyOf(evaluated.ref());
				}
				zv::Val newType = hasTracked ? intersectTypes(evaluated.raw(), trackedType.raw()) : zv::Val::copyOf(evaluated.ref());
				if (UNEXPECTED(newType.isUndef())) return zv::Val();
				zv::Val newNativeType = hasTrackedNative ? intersectTypes(evaluatedNative.raw(), trackedNativeType.raw()) : std::move(evaluatedNative);
				if (UNEXPECTED(newNativeType.isUndef())) return zv::Val();
				if (UNEXPECTED(!specifyInBatch(expr, newType.raw(), newNativeType.raw(), scope, scopeObject, scopeIsWorkingCopy))) return zv::Val();
				if (UNEXPECTED(!recordSpecifiedExpression(specifiedExpressions, scopeObject, exprString, expr, newType.raw()))) return zv::Val();
				continue;
			}

			zval *type = specification.type;
			if (specification.sure) {
				bool overwrite;
				if (UNEXPECTED(!shouldOverwrite(specifiedTypesObject, overwrite))) return zv::Val();
				if (overwrite) {
					zval exprZv;
					ZVAL_OBJ(&exprZv, expr);
					zv::Val assigned = otherOverwriteExpression(scope, &exprZv, type, type);
					if (UNEXPECTED(assigned.isUndef())) return zv::Val();
					zend_object *assignedObject = requireObject(assigned, "applySpecifiedTypes");
					if (UNEXPECTED(assignedObject == NULL)) return zv::Val();
					scope = std::move(assigned);
					scopeObject = assignedObject;
					scopeIsWorkingCopy = false;
				} else {
					zv::Val newType = hasTracked ? intersectTypes(type, trackedType.raw()) : zv::Val::copyOf(zv::Ref(type));
					if (UNEXPECTED(newType.isUndef())) return zv::Val();
					zv::Val newNativeType = hasTrackedNative ? intersectTypes(type, trackedNativeType.raw()) : zv::Val::copyOf(zv::Ref(type));
					if (UNEXPECTED(newNativeType.isUndef())) return zv::Val();
					if (UNEXPECTED(!specifyInBatch(expr, newType.raw(), newNativeType.raw(), scope, scopeObject, scopeIsWorkingCopy))) return zv::Val();
				}
			} else {
				bool isNever;
				if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_never_type, isNever))) return zv::Val();
				if (!isNever && hasTracked) {
					if (UNEXPECTED(!pt_type_instanceof_ce(trackedType.raw(), pt_ce_never_type, isNever))) return zv::Val();
				}
				if (isNever) continue;
				if (!hasTracked) {
					/* the expression is not tracked - there is nothing to
					 * subtract from */
					continue;
				}
				zv::Val newType = pt_type_combinator_remove(trackedType.raw(), type);
				if (UNEXPECTED(newType.isUndef())) return zv::Val();
				zv::Val newNativeType = hasTrackedNative ? pt_type_combinator_remove(trackedNativeType.raw(), type) : zv::Val::copyOf(newType.ref());
				if (UNEXPECTED(newNativeType.isUndef())) return zv::Val();
				if (UNEXPECTED(!specifyInBatch(expr, newType.raw(), newNativeType.raw(), scope, scopeObject, scopeIsWorkingCopy))) return zv::Val();
			}

			if (UNEXPECTED(!recordSpecifiedExpression(specifiedExpressions, scopeObject, exprString, expr, type))) return zv::Val();
		}

		{
			zval specifiedExpressionsZv;
			ZVAL_COPY_VALUE(&specifiedExpressionsZv, specifiedExpressions.raw());
			zv::Val processed = otherPrivate(scopeObject, PT_LC("processconditionalexpressionsafterspecifying"), 1, &specifiedExpressionsZv, [&](MutatingScope &other) {
				return other.processConditionalExpressionsAfterSpecifying(specifiedExpressions.table());
			});
			if (UNEXPECTED(processed.isUndef())) return zv::Val();
			zend_object *processedObject = requireObject(processed, "applySpecifiedTypes");
			if (UNEXPECTED(processedObject == NULL)) return zv::Val();
			scope = std::move(processed);
			scopeObject = processedObject;
		}

		zv::Val newHolders = pt_specified_types_get_new_conditional_expression_holders(specifiedTypesObject);
		if (UNEXPECTED(newHolders.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(newHolders.raw()) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: getNewConditionalExpressionHolders() did not answer with an array");
			return zv::Val();
		}
		zv::Arr newConditionalExpressionHolders = zv::Arr::copyOfTable(Z_ARRVAL_P(newHolders.raw()));
		zv::Val recipes = pt_specified_types_get_conditional_expression_holder_recipes(specifiedTypesObject);
		if (UNEXPECTED(recipes.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(recipes.raw()) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: getConditionalExpressionHolderRecipes() did not answer with an array");
			return zv::Val();
		}
		for (auto recipeEntry : zv::ArrRef(recipes.raw())) {
			zv::Ref recipe = recipeEntry.value().deref();
			if (UNEXPECTED(!recipe.isObject())) {
				zend_throw_error(NULL, "Call to a member function evaluate() on %s", zend_zval_value_name(recipe.raw()));
				return zv::Val();
			}
			/* the recipes' state-dependent math runs here, against this
			 * scope's pre-application state */
			zv::Val evaluated = pt_conditional_expression_holder_recipe_evaluate(recipe.asObject(), thisZval());
			if (UNEXPECTED(evaluated.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(evaluated.raw()) != IS_ARRAY)) {
				zend_throw_error(NULL, "phpstan_turbo: a ConditionalExpressionHolderRecipe did not answer with an array");
				return zv::Val();
			}
			for (auto entry : zv::ArrRef(evaluated.raw())) {
				zv::Ref recipeHolders = entry.value().deref();
				if (UNEXPECTED(!recipeHolders.isArray())) {
					zend_throw_error(NULL, "phpstan_turbo: a recipe entry is not an array");
					return zv::Val();
				}
				newConditionalExpressionHolders.separate();
				zend_string *key = entry.stringKeyOrNull();
				zend_ulong index = entry.indexKey();
				zval *inner = pt_ht_find(newConditionalExpressionHolders.table(), key, index);
				if (inner == NULL) {
					zval fresh;
					array_init(&fresh);
					pt_ht_add_new(newConditionalExpressionHolders.table(), key, index, &fresh);
					inner = pt_ht_find(newConditionalExpressionHolders.table(), key, index);
				} else {
					ZVAL_DEREF(inner);
					if (UNEXPECTED(Z_TYPE_P(inner) != IS_ARRAY)) {
						zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
						return zv::Val();
					}
					SEPARATE_ARRAY(inner);
				}
				for (auto holderEntry : zv::TableRef(recipeHolders.asArrayTable())) {
					zval copy;
					ZVAL_COPY(&copy, holderEntry.value().deref().raw());
					pt_ht_update(Z_ARRVAL_P(inner), holderEntry.stringKeyOrNull(), holderEntry.indexKey(), &copy);
				}
			}
		}

		CreateArgs a;
		if (UNEXPECTED(!fillCreateArgsFromOther(a, scopeObject))) return zv::Val();
		HashTable *scopeConditionalExpressions = otherTable(scopeObject, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		if (UNEXPECTED(scopeConditionalExpressions == NULL)) return zv::Val();
		PT_MS_ARG_CREATE(a, CreateArgs::CONDITIONAL_EXPRESSIONS, mergeConditionalExpressions(newConditionalExpressionHolders.table(), scopeConditionalExpressions));
		return otherScopeFactoryCreate(scopeObject, a);
	}

	/* the create() argument list every narrowing-application site builds
	 * out of another scope: its properties as the twin spells them, its dispatched
	 * isDeclareStrictTypes() / getFunction() / getNamespace() */
	static bool fillCreateArgsFromOther(CreateArgs &a, zend_object *scopeObject)
	{
		struct
		{
			uint32_t arg;
			uint32_t slot;
			const char *name;
			size_t len;
		} properties[] = {
			{ CreateArgs::CONTEXT, PT_MS_PROP_CONTEXT, PT_LC("context") },
			{ CreateArgs::EXPRESSION_TYPES, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes") },
			{ CreateArgs::NATIVE_EXPRESSION_TYPES, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes") },
			{ CreateArgs::CONDITIONAL_EXPRESSIONS, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions") },
			{ CreateArgs::IN_CLOSURE_BIND_SCOPE_CLASSES, PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES, PT_LC("inClosureBindScopeClasses") },
			{ CreateArgs::ANONYMOUS_FUNCTION_REFLECTION, PT_MS_PROP_ANONYMOUS_FUNCTION_REFLECTION, PT_LC("anonymousFunctionReflection") },
			{ CreateArgs::IN_FIRST_LEVEL_STATEMENT, PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, PT_LC("inFirstLevelStatement") },
			{ CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS, PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, PT_LC("currentlyAssignedExpressions") },
			{ CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, PT_LC("currentlyAllowedUndefinedExpressions") },
			{ CreateArgs::IN_FUNCTION_CALLS_STACK, PT_MS_PROP_IN_FUNCTION_CALLS_STACK, PT_LC("inFunctionCallsStack") },
			{ CreateArgs::AFTER_EXTRACT_CALL, PT_MS_PROP_AFTER_EXTRACT_CALL, PT_LC("afterExtractCall") },
			{ CreateArgs::PARENT_SCOPE, PT_MS_PROP_PARENT_SCOPE, PT_LC("parentScope") },
			{ CreateArgs::NATIVE_TYPES_PROMOTED, PT_MS_PROP_NATIVE_TYPES_PROMOTED, PT_LC("nativeTypesPromoted") },
			{ CreateArgs::TEMPLATE_ARGUMENT_FRAME, PT_MS_PROP_TEMPLATE_ARGUMENT_FRAME, PT_LC("templateArgumentFrame") },
			{ CreateArgs::TEMPLATE_ARGUMENT_CONSTRAINTS, PT_MS_PROP_TEMPLATE_ARGUMENT_CONSTRAINTS, PT_LC("templateArgumentConstraints") },
		};
		for (auto &property : properties) {
			zval *value = otherProp(scopeObject, property.slot, property.name, property.len);
			if (UNEXPECTED(value == NULL)) return false;
			ZVAL_DEREF(value);
			if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
				zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(scopeObject->ce->name), property.name);
				return false;
			}
			a.set(property.arg, zv::Ref(value));
		}
		zv::Val declareStrictTypes = pt_type_call(scopeObject, PT_LC("isdeclarestricttypes"), 0, NULL);
		if (UNEXPECTED(declareStrictTypes.isUndef())) return false;
		a.setBool(CreateArgs::DECLARE_STRICT_TYPES, zend_is_true(declareStrictTypes.raw()));
		zv::Val function = pt_type_call(scopeObject, PT_LC("getfunction"), 0, NULL);
		if (UNEXPECTED(function.isUndef())) return false;
		a.setOwned(CreateArgs::FUNCTION, std::move(function));
		zv::Val namespace_ = pt_type_call(scopeObject, PT_LC("getnamespace"), 0, NULL);
		if (UNEXPECTED(namespace_.isUndef())) return false;
		a.setOwned(CreateArgs::NAMESPACE_, std::move(namespace_));
		return true;
	}

	/* $a->equalTypes($b): the native holders' body when both are native, the
	 * method of anything else; false = pending exception */
	[[nodiscard]] static bool holderEqualTypes(zv::Ref a, zv::Ref b, bool &out)
	{
		a = a.deref();
		b = b.deref();
		if (UNEXPECTED(!a.isObject())) {
			zend_throw_error(NULL, "Call to a member function equalTypes() on %s", zend_zval_value_name(a.raw()));
			return false;
		}
		if (EXPECTED(a.asObject()->ce == pt_ce_expr_type_holder && b.isObject() && b.asObject()->ce == pt_ce_expr_type_holder)) {
			return pt_holder_equal_types(a.raw(), b.raw(), &out);
		}
		zv::Val result = pt_type_call(a.asObject(), PT_LC("equaltypes"), 1, b.raw());
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $table[$exprKey][$key] = $holder, vivifying the inner array like PHP */
	static bool appendConditionalHolder(zv::Arr &result, zend_string *exprKey, zend_ulong exprIdx, zend_string *key, zend_ulong keyIdx, zv::Ref holder)
	{
		result.separate();
		zval *inner = pt_ht_find(result.table(), exprKey, exprIdx);
		if (inner == NULL) {
			zval fresh;
			array_init(&fresh);
			pt_ht_add_new(result.table(), exprKey, exprIdx, &fresh);
			inner = pt_ht_find(result.table(), exprKey, exprIdx);
		} else {
			ZVAL_DEREF(inner);
			if (UNEXPECTED(Z_TYPE_P(inner) != IS_ARRAY)) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return false;
			}
			SEPARATE_ARRAY(inner);
		}
		zval copy;
		ZVAL_COPY(&copy, holder.deref().raw());
		pt_ht_update(Z_ARRVAL_P(inner), key, keyIdx, &copy);
		return true;
	}

	/* isset($table[$exprKey][$key]) */
	static bool conditionalHolderIsSet(zv::Arr &table, zend_string *exprKey, zend_ulong exprIdx, zend_string *key, zend_ulong keyIdx)
	{
		zval *inner = pt_ht_find(table.table(), exprKey, exprIdx);
		if (inner == NULL) return false;
		ZVAL_DEREF(inner);
		if (Z_TYPE_P(inner) != IS_ARRAY) return false;
		zval *found = pt_ht_find(Z_ARRVAL_P(inner), key, keyIdx);
		return found != NULL && Z_TYPE_P(found) != IS_NULL;
	}

	/*
	 * private (twin 4283) — matches the registered conditional expressions
	 * against the just-specified holders and applies the consequences.
	 * Mutates and returns $this.
	 */
	zv::Val processConditionalExpressionsAfterSpecifying(HashTable *specifiedExpressions)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) {
			return zv::Val();
		}
		zv::Val matched = pt_scope_ops_match_conditional_expressions(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()), specifiedExpressions);
		if (UNEXPECTED(matched.isUndef())) return zv::Val();
		zval *conditions = zend_hash_index_find(Z_ARRVAL_P(matched.raw()), 0);
		if (UNEXPECTED(conditions == NULL || Z_TYPE_P(conditions) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: ScopeOps::matchConditionalExpressions() answered an unexpected shape");
			return zv::Val();
		}
		zv::Arr matchedConditions = zv::Arr::copyOfTable(Z_ARRVAL_P(conditions));
		for (auto entry : zv::TableRef(matchedConditions.table())) {
			zend_string *skey = entry.stringKeyOrNull();
			zv::Str conditionalExprString = zv::Str::adopt(skey != NULL ? zend_string_copy(skey) : zend_long_to_str((zend_long) entry.indexKey()));
			zv::Ref expressions = entry.value().deref();
			if (UNEXPECTED(!expressions.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: a matched conditional expressions entry is not an array");
				return zv::Val();
			}
			HashTable *holders = expressions.asArrayTable();

			/* TrinaryLogic::lazyExtremeIdentity(): the operands' value when
			 * they all agree, Maybe when any differs */
			zend_long certainty = 0;
			bool first = true, differs = false;
			for (auto holderEntry : zv::TableRef(holders)) {
				zv::Val typeHolder = conditionalTypeHolder(holderEntry.value());
				if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
				zend_long value = holderCertainty(typeHolder.ref());
				if (UNEXPECTED(value < 0)) return zv::Val();
				if (first) {
					certainty = value;
					first = false;
					continue;
				}
				if (certainty != value) {
					differs = true;
					break;
				}
			}
			if (UNEXPECTED(first)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			if (differs) {
				certainty = PT_TRI_MAYBE;
			}

			if (certainty == PT_TRI_NO) {
				if (UNEXPECTED(!writeScopeTable(self, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), conditionalExprString.get(), NULL))) {
					return zv::Val();
				}
				continue;
			}

			zval *existing = zend_symtable_find(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), conditionalExprString.get());
			if (existing == NULL) {
				zv::Val typeHolder = conditionalTypeHolder((*zv::TableRef(holders).begin()).value());
				if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
				if (UNEXPECTED(!writeScopeTable(self, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), conditionalExprString.get(), typeHolder.raw()))) {
					return zv::Val();
				}
				continue;
			}

			zv::Val type;
			for (auto holderEntry : zv::TableRef(holders)) {
				zv::Val typeHolder = conditionalTypeHolder(holderEntry.value());
				if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
				zv::Val holderTypeValue = holderType(typeHolder.ref());
				if (UNEXPECTED(holderTypeValue.isUndef())) return zv::Val();
				if (type.isUndef()) {
					type = std::move(holderTypeValue);
					continue;
				}
				zv::Val intersected = intersectTypes(type.raw(), holderTypeValue.raw());
				if (UNEXPECTED(intersected.isUndef())) return zv::Val();
				type = std::move(intersected);
			}
			zv::Val existingExpr = holderExpr(zv::Ref(existing));
			zv::Val existingType = holderType(zv::Ref(existing));
			if (UNEXPECTED(existingExpr.isUndef() || existingType.isUndef())) return zv::Val();
			zend_long existingCertainty = holderCertainty(zv::Ref(existing));
			if (UNEXPECTED(existingCertainty < 0)) return zv::Val();
			zv::Val intersected = intersectTypes(existingType.raw(), type.raw());
			if (UNEXPECTED(intersected.isUndef())) return zv::Val();
			/* TrinaryLogic::maxMin() */
			zend_long maxMin = ((existingCertainty | certainty) == PT_TRI_YES) ? PT_TRI_YES : (existingCertainty & certainty);
			zval holder;
			pt_holder_create(&holder, existingExpr.raw(), intersected.raw(), maxMin);
			zv::Val holderValue = zv::Val::adopt(holder);
			if (UNEXPECTED(!writeScopeTable(self, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), conditionalExprString.get(), holderValue.raw()))) {
				return zv::Val();
			}
		}

		return self_();
	}

	/* (twin 4316) */
	zv::Val getConditionalExpressions()
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) return zv::Val();
		return copyOfSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS);
	}

	/* ScopeOps::scopeWith($this, ...) with the twin's full argument list */
	zv::Val scopeWithTables(HashTable *expressionTypes, HashTable *nativeExpressionTypes, HashTable *conditionalExpressions, HashTable *currentlyAssignedExpressions, HashTable *currentlyAllowedUndefinedExpressions, HashTable *inFunctionCallsStack, bool inFirstLevelStatement, bool afterExtractCall)
	{
		return pt_scope_ops_scope_with(thisZval(), expressionTypes, nativeExpressionTypes, conditionalExpressions, currentlyAssignedExpressions, currentlyAllowedUndefinedExpressions, inFunctionCallsStack, inFirstLevelStatement, afterExtractCall);
	}

	/* (twin 4324) */
	zv::Val addConditionalExpressions(zend_string *exprString, HashTable *conditionalExpressionHolders)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions")
			|| !requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) {
			return zv::Val();
		}
		zv::Arr conditionalExpressions = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()));
		zval *entry = zend_symtable_find(conditionalExpressions.table(), exprString);
		if (entry != NULL) {
			ZVAL_DEREF(entry);
		}
		/* Merge rather than overwrite: holder keys disambiguate identical
		 * entries so the earlier bindings survive */
		zv::Arr existing = (entry != NULL && Z_TYPE_P(entry) == IS_ARRAY)
			? zv::Arr::copyOfTable(Z_ARRVAL_P(entry))
			: zv::Arr::create(zend_hash_num_elements(conditionalExpressionHolders));
		for (auto holderEntry : zv::TableRef(conditionalExpressionHolders)) {
			zend_string *key = conditionalHolderKey(holderEntry.value());
			if (UNEXPECTED(key == NULL)) return zv::Val();
			zv::Str keyString = zv::Str::adopt(key);
			existing.separate();
			zval copy;
			ZVAL_COPY(&copy, holderEntry.value().deref().raw());
			zend_hash_update(existing.table(), keyString.get(), &copy);
		}
		conditionalExpressions.set(exprString, zv::Val(std::move(existing)));

		return scopeWithTables(
			Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()),
			conditionalExpressions.table(),
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw()),
			slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT),
			slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL));
	}

	/* (twin 4354) */
	zv::Val exitFirstLevelStatements()
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, "inFirstLevelStatement"))) return zv::Val();
		if (!slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT)) return self_();
		zv::Ref memo = slot(PT_MS_PROP_SCOPE_OUT_OF_FIRST_LEVEL_STATEMENT);
		if (!memo.isUndef() && Z_TYPE_P(memo.raw()) != IS_NULL) return zv::Val::copyOf(memo);
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions")
			|| !requireSlot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK, "inFunctionCallsStack"))) {
			return zv::Val();
		}
		zv::Val scope = scopeWithTables(
			Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()),
			Z_ARRVAL_P(slot(PT_MS_PROP_IN_FUNCTION_CALLS_STACK).raw()),
			false,
			slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL));
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!assignResolvedTypes(scope))) return zv::Val();
		writeSlot(PT_MS_PROP_SCOPE_OUT_OF_FIRST_LEVEL_STATEMENT, zv::Val::copyOf(scope.ref()));
		return scope;
	}

	/* (twin 4388) */
	zv::Val mergeWith(zval *otherScope, bool preserveVacuousConditionals)
	{
		zv::Val merged = mergeWithVariableState(otherScope, preserveVacuousConditionals);
		if (UNEXPECTED(merged.isUndef())) return zv::Val();
		zend_object *mergedObject = requireObject(merged, "addTemplateArgumentConstraints");
		if (UNEXPECTED(mergedObject == NULL)) return zv::Val();
		zv::Val constraints;
		if (otherScope != NULL && Z_TYPE_P(otherScope) == IS_OBJECT) {
			constraints = pt_type_call(Z_OBJ_P(otherScope), PT_LC("gettemplateargumentconstraints"), 0, NULL);
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		} else {
			constraints = zv::Val::null();
		}
		return pt_type_call(mergedObject, PT_LC("addtemplateargumentconstraints"), 1, constraints.raw());
	}

	/* private (twin 4393) */
	zv::Val mergeWithVariableState(zval *otherScopeZval, bool preserveVacuousConditionals)
	{
		if (otherScopeZval == NULL || Z_TYPE_P(otherScopeZval) != IS_OBJECT) return self_();
		zend_object *otherScope = Z_OBJ_P(otherScopeZval);
		if (otherScope == self) return self_();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions")
			|| !requireSlot(PT_MS_PROP_AFTER_EXTRACT_CALL, "afterExtractCall")
			|| !requireSlot(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT, "inFirstLevelStatement"))) {
			return zv::Val();
		}
		HashTable *theirExpressionTypesTable = otherTable(otherScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		HashTable *theirNativeExpressionTypesTable = theirExpressionTypesTable == NULL ? NULL : otherTable(otherScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		HashTable *theirConditionalExpressionsTable = theirNativeExpressionTypesTable == NULL ? NULL : otherTable(otherScope, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		if (UNEXPECTED(theirConditionalExpressionsTable == NULL)) return zv::Val();
		bool theirAfterExtractCall;
		if (UNEXPECTED(!otherBool(otherScope, PT_MS_PROP_AFTER_EXTRACT_CALL, PT_LC("afterExtractCall"), theirAfterExtractCall))) return zv::Val();
		/* every table is held for the whole merge: the ScopeOps bodies read
		 * them repeatedly and a foreign scope's slot may be rewritten */
		zv::Arr ourExpressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()));
		zv::Arr theirExpressionTypes = zv::Arr::copyOfTable(theirExpressionTypesTable);
		zv::Arr ourNativeExpressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()));
		zv::Arr theirNativeExpressionTypes = zv::Arr::copyOfTable(theirNativeExpressionTypesTable);
		zv::Arr ourConditionalExpressions = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()));
		zv::Arr theirConditionalExpressions = zv::Arr::copyOfTable(theirConditionalExpressionsTable);

		zv::Arr differingExpressionKeys = zv::Arr::create(8);
		zv::Val mergedExpressionTypes = pt_scope_ops_merge_variable_holders(ourExpressionTypes.table(), theirExpressionTypes.table(), differingExpressionKeys.table());
		if (UNEXPECTED(mergedExpressionTypes.isUndef())) return zv::Val();
		zv::Val differing = withoutPreciseClassConstantFetches(differingExpressionKeys.table(), ourExpressionTypes.table(), theirExpressionTypes.table());
		if (UNEXPECTED(differing.isUndef())) return zv::Val();
		zv::Val conditionalExpressions = pt_scope_ops_intersect_conditional_expressions(ourConditionalExpressions.table(), theirConditionalExpressions.table());
		if (UNEXPECTED(conditionalExpressions.isUndef())) return zv::Val();
		if (preserveVacuousConditionals) {
			zv::Val preserved = preserveVacuousConditionalExpressions(Z_ARRVAL_P(conditionalExpressions.raw()), ourConditionalExpressions.table(), theirExpressionTypes.table());
			if (UNEXPECTED(preserved.isUndef())) return zv::Val();
			conditionalExpressions = std::move(preserved);
			zv::Val preservedTheirs = preserveVacuousConditionalExpressions(Z_ARRVAL_P(conditionalExpressions.raw()), theirConditionalExpressions.table(), ourExpressionTypes.table());
			if (UNEXPECTED(preservedTheirs.isUndef())) return zv::Val();
			conditionalExpressions = std::move(preservedTheirs);
		}
		zv::Val sameGuard = mergeSameGuardConditionalExpressions(Z_ARRVAL_P(conditionalExpressions.raw()), ourConditionalExpressions.table(), theirConditionalExpressions.table());
		if (UNEXPECTED(sameGuard.isUndef())) return zv::Val();
		conditionalExpressions = std::move(sameGuard);
		zv::Val created = pt_scope_ops_create_conditional_expressions(Z_ARRVAL_P(conditionalExpressions.raw()), ourExpressionTypes.table(), theirExpressionTypes.table(), Z_ARRVAL_P(mergedExpressionTypes.raw()), Z_ARRVAL_P(differing.raw()));
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		conditionalExpressions = std::move(created);
		zv::Val createdReversed = pt_scope_ops_create_conditional_expressions(Z_ARRVAL_P(conditionalExpressions.raw()), theirExpressionTypes.table(), ourExpressionTypes.table(), Z_ARRVAL_P(mergedExpressionTypes.raw()), Z_ARRVAL_P(differing.raw()));
		if (UNEXPECTED(createdReversed.isUndef())) return zv::Val();
		conditionalExpressions = std::move(createdReversed);

		zv::Val finished = pt_scope_ops_finish_merge(Z_ARRVAL_P(mergedExpressionTypes.raw()), ourExpressionTypes.table(), theirExpressionTypes.table(), ourNativeExpressionTypes.table(), theirNativeExpressionTypes.table());
		if (UNEXPECTED(finished.isUndef())) return zv::Val();
		zval *mergedTypes = zend_hash_index_find(Z_ARRVAL_P(finished.raw()), 0);
		zval *mergedNativeTypes = zend_hash_index_find(Z_ARRVAL_P(finished.raw()), 1);
		if (UNEXPECTED(mergedTypes == NULL || mergedNativeTypes == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: ScopeOps::finishMerge() answered an unexpected shape");
			return zv::Val();
		}

		return scopeWithTables(
			Z_ARRVAL_P(mergedTypes),
			Z_ARRVAL_P(mergedNativeTypes),
			Z_ARRVAL_P(conditionalExpressions.raw()),
			(HashTable *) &zend_empty_array,
			(HashTable *) &zend_empty_array,
			(HashTable *) &zend_empty_array,
			slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT),
			slotBool(PT_MS_PROP_AFTER_EXTRACT_CALL) && theirAfterExtractCall);
	}

	/* private (twin 4476) — drops the keys of class-constant fetches that
	 * resolve to their declared value */
	zv::Val withoutPreciseClassConstantFetches(HashTable *differingExpressionKeys, HashTable *ourExpressionTypes, HashTable *theirExpressionTypes)
	{
		zv::Arr result = zv::Arr::copyOfTable(differingExpressionKeys);
		zv::Arr keys = zv::Arr::create(zend_hash_num_elements(differingExpressionKeys));
		for (auto entry : zv::TableRef(differingExpressionKeys)) {
			zval key;
			if (entry.stringKeyOrNull() != NULL) {
				ZVAL_STR_COPY(&key, entry.stringKeyOrNull());
			} else {
				ZVAL_LONG(&key, (zend_long) entry.indexKey());
			}
			keys.push(zv::Val::adopt(key));
		}
		for (auto keyEntry : zv::TableRef(keys.table())) {
			zv::Ref keyValue = keyEntry.value().deref();
			zend_string *skey = keyValue.isString() ? keyValue.asString() : NULL;
			zend_ulong index = keyValue.isString() ? 0 : (zend_ulong) Z_LVAL_P(keyValue.raw());
			zval *holder = pt_ht_find(ourExpressionTypes, skey, index);
			if (holder == NULL) {
				holder = pt_ht_find(theirExpressionTypes, skey, index);
			}
			if (holder == NULL) continue;
			zv::Val expr = holderExpr(zv::Ref(holder));
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			bool is;
			if (UNEXPECTED(!isInstance(expr.ref(), PT_CLASS_CLASS_CONST_FETCH, is))) return zv::Val();
			if (!is) continue;
			zv::Ref classNode = nodeProp(Z_OBJ_P(expr.raw()), PT_LC("class"));
			zv::Ref nameNode = nodeProp(Z_OBJ_P(expr.raw()), PT_LC("name"));
			if (UNEXPECTED(classNode.raw() == NULL || nameNode.raw() == NULL)) return zv::Val();
			if (UNEXPECTED(!isInstance(classNode.deref(), PT_CLASS_NAME, is))) return zv::Val();
			if (!is) continue;
			if (UNEXPECTED(!isInstance(nameNode.deref(), PT_CLASS_IDENTIFIER, is))) return zv::Val();
			if (!is) continue;
			/* static::CONST is late-bound */
			zv::Val lowerClassName = pt_type_call(classNode.deref().asObject(), PT_LC("tolowerstring"), 0, NULL);
			if (UNEXPECTED(lowerClassName.isUndef())) return zv::Val();
			if (Z_TYPE_P(lowerClassName.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(lowerClassName.raw()), "static")) continue;
			zv::Val className = thisResolveName(classNode.deref().raw());
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Val constantName = pt_type_call(nameNode.deref().asObject(), PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(constantName.isUndef())) return zv::Val();
			zv::Ref constantResolver = slot(PT_MS_PROP_CONSTANT_RESOLVER);
			if (UNEXPECTED(!constantResolver.isObject())) {
				(void) uninitializedProperty("constantResolver");
				return zv::Val();
			}
			zv::Args args{className.raw(), constantName.raw()};
			zv::Val dynamic = pt_type_call(constantResolver.asObject(), PT_LC("isdynamicclassconstant"), 2, args);
			if (UNEXPECTED(dynamic.isUndef())) return zv::Val();
			if (zend_is_true(dynamic.raw())) continue;
			result.separate();
			pt_ht_del(result.table(), skey, index);
		}

		return zv::Val(std::move(result));
	}

	/* private (twin 4527) — rescues one-sided conditional holders across an
	 * if-merge */
	static zv::Val preserveVacuousConditionalExpressions(HashTable *currentConditionalExpressions, HashTable *sourceConditionalExpressions, HashTable *otherExpressionTypes)
	{
		zv::Arr result = zv::Arr::copyOfTable(currentConditionalExpressions);
		for (auto sourceEntry : zv::TableRef(sourceConditionalExpressions)) {
			zend_string *exprKey = sourceEntry.stringKeyOrNull();
			zend_ulong exprIndex = sourceEntry.indexKey();
			zv::Ref holders = sourceEntry.value().deref();
			if (UNEXPECTED(!holders.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return zv::Val();
			}
			for (auto holderEntry : zv::TableRef(holders.asArrayTable())) {
				zend_string *key = holderEntry.stringKeyOrNull();
				zend_ulong keyIndex = holderEntry.indexKey();
				if (conditionalHolderIsSet(result, exprKey, exprIndex, key, keyIndex)) continue;
				zv::Ref holder = holderEntry.value().deref();
				zv::Val typeHolder = conditionalTypeHolder(holder);
				if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
				zend_long certainty = holderCertainty(typeHolder.ref());
				if (UNEXPECTED(certainty < 0)) return zv::Val();
				if (certainty == PT_TRI_NO) {
					zv::Val typeHolderExpr = holderExpr(typeHolder.ref());
					if (UNEXPECTED(typeHolderExpr.isUndef())) return zv::Val();
					bool isVariable;
					if (UNEXPECTED(!isInstance(typeHolderExpr.ref(), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
					if (!isVariable) continue;
				}

				zv::Val conditions = conditionalConditions(holder);
				if (UNEXPECTED(conditions.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(conditions.raw()) != IS_ARRAY)) {
					zend_throw_error(NULL, "phpstan_turbo: a ConditionalExpressionHolder has no conditions");
					return zv::Val();
				}
				bool vacuous = false;
				for (auto guardEntry : zv::ArrRef(conditions.raw())) {
					zval *otherHolder = pt_ht_find(otherExpressionTypes, guardEntry.stringKeyOrNull(), guardEntry.indexKey());
					if (otherHolder == NULL) continue;
					zv::Val otherType = holderType(zv::Ref(otherHolder));
					zv::Val guardType = holderType(guardEntry.value());
					if (UNEXPECTED(otherType.isUndef() || guardType.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(otherType.raw()) != IS_OBJECT)) {
						zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(otherType.raw()));
						return zv::Val();
					}
					bool no;
					if (UNEXPECTED(!isSuperTypeOfNo(Z_OBJ_P(otherType.raw()), guardType.raw(), no))) return zv::Val();
					if (no) {
						vacuous = true;
						break;
					}
				}
				if (vacuous) {
					if (UNEXPECTED(!appendConditionalHolder(result, exprKey, exprIndex, key, keyIndex, holder))) return zv::Val();
					continue;
				}

				if (certainty == PT_TRI_NO) continue;
				zval *otherTargetHolder = pt_ht_find(otherExpressionTypes, exprKey, exprIndex);
				if (otherTargetHolder == NULL) continue;
				zend_long otherTargetCertainty = holderCertainty(zv::Ref(otherTargetHolder));
				if (UNEXPECTED(otherTargetCertainty < 0)) return zv::Val();
				if (otherTargetCertainty != PT_TRI_YES && otherTargetCertainty != certainty) continue;
				zv::Val typeHolderType = holderType(typeHolder.ref());
				zv::Val otherTargetType = holderType(zv::Ref(otherTargetHolder));
				if (UNEXPECTED(typeHolderType.isUndef() || otherTargetType.isUndef())) return zv::Val();
				/* an ErrorType consequent or other-branch state would look
				 * "already satisfied" and hide the underlying error */
				bool isError;
				if (UNEXPECTED(!pt_type_instanceof_ce(typeHolderType.raw(), pt_ce_error_type, isError))) return zv::Val();
				if (!isError) {
					if (UNEXPECTED(!pt_type_instanceof_ce(otherTargetType.raw(), pt_ce_error_type, isError))) return zv::Val();
				}
				if (isError) continue;
				if (UNEXPECTED(Z_TYPE_P(typeHolderType.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(typeHolderType.raw()));
					return zv::Val();
				}
				bool yes;
				if (UNEXPECTED(!isSuperTypeOfYes(Z_OBJ_P(typeHolderType.raw()), otherTargetType.raw(), yes))) return zv::Val();
				if (!yes) continue;
				if (UNEXPECTED(!appendConditionalHolder(result, exprKey, exprIndex, key, keyIndex, holder))) return zv::Val();
			}
		}

		return zv::Val(std::move(result));
	}

	/* private (twin 4599) — merges one-sided holders that share a target and
	 * an identical guard set */
	static zv::Val mergeSameGuardConditionalExpressions(HashTable *currentConditionalExpressions, HashTable *ourConditionalExpressions, HashTable *theirConditionalExpressions)
	{
		zv::Arr result = zv::Arr::copyOfTable(currentConditionalExpressions);
		for (auto ourEntry : zv::TableRef(ourConditionalExpressions)) {
			zend_string *exprKey = ourEntry.stringKeyOrNull();
			zend_ulong exprIndex = ourEntry.indexKey();
			zval *theirSlot = pt_ht_find(theirConditionalExpressions, exprKey, exprIndex);
			if (theirSlot == NULL) continue;
			ZVAL_DEREF(theirSlot);
			zv::Ref ourHolders = ourEntry.value().deref();
			if (UNEXPECTED(!ourHolders.isArray() || Z_TYPE_P(theirSlot) != IS_ARRAY)) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return zv::Val();
			}
			zv::Arr theirHolders = zv::Arr::copyOfTable(Z_ARRVAL_P(theirSlot));
			for (auto ourHolderEntry : zv::TableRef(ourHolders.asArrayTable())) {
				if (conditionalHolderIsSet(result, exprKey, exprIndex, ourHolderEntry.stringKeyOrNull(), ourHolderEntry.indexKey())) continue;
				zv::Ref ourHolder = ourHolderEntry.value().deref();
				zv::Val ourTypeHolder = conditionalTypeHolder(ourHolder);
				if (UNEXPECTED(ourTypeHolder.isUndef())) return zv::Val();
				zend_long ourCertainty = holderCertainty(ourTypeHolder.ref());
				if (UNEXPECTED(ourCertainty < 0)) return zv::Val();
				if (ourCertainty == PT_TRI_NO) continue;
				zv::Val ourGuards = conditionalConditions(ourHolder);
				if (UNEXPECTED(ourGuards.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(ourGuards.raw()) != IS_ARRAY)) {
					zend_throw_error(NULL, "phpstan_turbo: a ConditionalExpressionHolder has no conditions");
					return zv::Val();
				}
				for (auto theirHolderEntry : zv::TableRef(theirHolders.table())) {
					if (conditionalHolderIsSet(result, exprKey, exprIndex, theirHolderEntry.stringKeyOrNull(), theirHolderEntry.indexKey())) continue;
					zv::Ref theirHolder = theirHolderEntry.value().deref();
					zv::Val theirTypeHolder = conditionalTypeHolder(theirHolder);
					if (UNEXPECTED(theirTypeHolder.isUndef())) return zv::Val();
					zend_long theirCertainty = holderCertainty(theirTypeHolder.ref());
					if (UNEXPECTED(theirCertainty < 0)) return zv::Val();
					if (theirCertainty != ourCertainty) continue;
					zv::Val theirGuards = conditionalConditions(theirHolder);
					if (UNEXPECTED(theirGuards.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(theirGuards.raw()) != IS_ARRAY)) {
						zend_throw_error(NULL, "phpstan_turbo: a ConditionalExpressionHolder has no conditions");
						return zv::Val();
					}
					if (zend_hash_num_elements(Z_ARRVAL_P(ourGuards.raw())) != zend_hash_num_elements(Z_ARRVAL_P(theirGuards.raw()))) continue;
					bool sameGuards = true;
					for (auto guardEntry : zv::ArrRef(ourGuards.raw())) {
						zval *theirGuard = pt_ht_find(Z_ARRVAL_P(theirGuards.raw()), guardEntry.stringKeyOrNull(), guardEntry.indexKey());
						if (theirGuard == NULL) {
							sameGuards = false;
							break;
						}
						bool equal;
						if (UNEXPECTED(!holderEquals(guardEntry.value(), zv::Ref(theirGuard), equal))) return zv::Val();
						if (!equal) {
							sameGuards = false;
							break;
						}
					}
					if (!sameGuards) continue;
					zv::Val ourType = holderType(ourTypeHolder.ref());
					zv::Val theirType = holderType(theirTypeHolder.ref());
					zv::Val ourExpr = holderExpr(ourTypeHolder.ref());
					if (UNEXPECTED(ourType.isUndef() || theirType.isUndef() || ourExpr.isUndef())) return zv::Val();
					zv::Val united = unionTypes(ourType.raw(), theirType.raw());
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					zval typeHolder;
					pt_holder_create(&typeHolder, ourExpr.raw(), united.raw(), ourCertainty);
					zv::Val typeHolderValue = zv::Val::adopt(typeHolder);
					zv::Val unionHolder = newConditionalExpressionHolder(ourGuards.ref(), typeHolderValue.ref());
					zend_string *unionKey = conditionalHolderKey(unionHolder.ref());
					if (UNEXPECTED(unionKey == NULL)) return zv::Val();
					zv::Str unionKeyString = zv::Str::adopt(unionKey);
					if (UNEXPECTED(!appendConditionalHolder(result, exprKey, exprIndex, unionKeyString.get(), 0, unionHolder.ref()))) return zv::Val();
				}
			}
		}

		return zv::Val(std::move(result));
	}

	/* private (twin 4667) */
	static zv::Val mergeConditionalExpressions(HashTable *newConditionalExpressions, HashTable *existingConditionalExpressions)
	{
		zv::Arr result = zv::Arr::copyOfTable(existingConditionalExpressions);
		for (auto entry : zv::TableRef(newConditionalExpressions)) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			zv::Ref holders = entry.value().deref();
			result.separate();
			zval *existing = pt_ht_find(result.table(), key, index);
			if (existing == NULL) {
				zval copy;
				ZVAL_COPY(&copy, holders.raw());
				pt_ht_add_new(result.table(), key, index, &copy);
				continue;
			}
			ZVAL_DEREF(existing);
			if (UNEXPECTED(Z_TYPE_P(existing) != IS_ARRAY || !holders.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return zv::Val();
			}
			zv::Val merged = arrayMerge(Z_ARRVAL_P(existing), holders.asArrayTable());
			zval mergedZv = merged.take();
			pt_ht_update(result.table(), key, index, &mergedZv);
		}

		return zv::Val(std::move(result));
	}

	/* (twin 4681) */
	zv::Val mergeInitializedProperties(zend_object *calledMethodScope)
	{
		static const char prefix[] = "__phpstanPropertyInitialization(";
		zv::Val scope = self_();
		zend_object *scopeObject = self;
		HashTable *calledTable = otherTable(calledMethodScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(calledTable == NULL)) return zv::Val();
		zv::Arr calledExpressionTypes = zv::Arr::copyOfTable(calledTable);
		for (auto entry : zv::TableRef(calledExpressionTypes.table())) {
			zend_string *skey = entry.stringKeyOrNull();
			zv::Str exprString = zv::Str::adopt(skey != NULL ? zend_string_copy(skey) : zend_long_to_str((zend_long) entry.indexKey()));
			if (ZSTR_LEN(exprString.get()) < sizeof(prefix) - 1
				|| memcmp(ZSTR_VAL(exprString.get()), prefix, sizeof(prefix) - 1) != 0) {
				continue;
			}
			size_t start = sizeof(prefix) - 1;
			size_t length = ZSTR_LEN(exprString.get()) > start ? ZSTR_LEN(exprString.get()) - start - 1 : 0;
			zv::Str propertyName = zv::Str::adopt(zend_string_init(ZSTR_VAL(exprString.get()) + start, length, 0));
			zval propertyNameZv;
			ZVAL_STR(&propertyNameZv, propertyName.get());
			zv::Val propertyExpr = pt_type_new(PT_CLASS_PROPERTY_INITIALIZATION_EXPR, 1, &propertyNameZv);
			if (UNEXPECTED(propertyExpr.isUndef())) return zv::Val();
			HashTable *scopeTable = otherTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
			if (UNEXPECTED(scopeTable == NULL)) return zv::Val();
			zval *existing = zend_symtable_find(scopeTable, exprString.get());
			zend_long certainty = 0;
			if (existing != NULL) {
				certainty = holderCertainty(zv::Ref(existing));
				if (UNEXPECTED(certainty < 0)) return zv::Val();
			}
			zval mixedZv, nativeMixedZv;
			if (UNEXPECTED(!pt_mixed_type_new(&mixedZv) || !pt_mixed_type_new(&nativeMixedZv))) return zv::Val();
			zv::Val mixed = zv::Val::adopt(mixedZv);
			zv::Val nativeMixed = zv::Val::adopt(nativeMixedZv);
			zv::Val assigned = otherAssignExpression(scope, propertyExpr.raw(), mixed.raw(), nativeMixed.raw());
			if (UNEXPECTED(assigned.isUndef())) return zv::Val();
			zend_object *assignedObject = requireObject(assigned, "mergeInitializedProperties");
			if (UNEXPECTED(assignedObject == NULL)) return zv::Val();
			scope = std::move(assigned);
			scopeObject = assignedObject;
			if (existing == NULL) {
				zval holder;
				ZVAL_COPY_VALUE(&holder, entry.value().deref().raw());
				if (UNEXPECTED(!writeScopeTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), exprString.get(), &holder))) {
					return zv::Val();
				}
				continue;
			}
			zv::Val theirExpr = holderExpr(entry.value());
			zv::Val theirType = holderType(entry.value());
			if (UNEXPECTED(theirExpr.isUndef() || theirType.isUndef())) return zv::Val();
			zend_long theirCertainty = holderCertainty(entry.value());
			if (UNEXPECTED(theirCertainty < 0)) return zv::Val();
			zval mergedHolder;
			pt_holder_create(&mergedHolder, theirExpr.raw(), theirType.raw(), theirCertainty | certainty);
			zv::Val mergedHolderValue = zv::Val::adopt(mergedHolder);
			if (UNEXPECTED(!writeScopeTable(scopeObject, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"), exprString.get(), mergedHolderValue.raw()))) {
				return zv::Val();
			}
		}

		return scope;
	}

	/* (twin 4709) */
	zv::Val processFinallyScope(zend_object *finallyScope, zend_object *originalFinallyScope)
	{
		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		HashTable *finallyTypes = otherTable(finallyScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		HashTable *originalTypes = finallyTypes == NULL ? NULL : otherTable(originalFinallyScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		HashTable *finallyNativeTypes = originalTypes == NULL ? NULL : otherTable(finallyScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		HashTable *originalNativeTypes = finallyNativeTypes == NULL ? NULL : otherTable(originalFinallyScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		HashTable *finallyConditional = originalNativeTypes == NULL ? NULL : otherTable(finallyScope, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		if (UNEXPECTED(finallyConditional == NULL)) return zv::Val();
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) {
			return zv::Val();
		}
		PT_MS_ARG_CREATE(a, CreateArgs::EXPRESSION_TYPES, processFinallyScopeVariableTypeHolders(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), finallyTypes, originalTypes));
		PT_MS_ARG_CREATE(a, CreateArgs::NATIVE_EXPRESSION_TYPES, processFinallyScopeVariableTypeHolders(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()), finallyNativeTypes, originalNativeTypes));
		PT_MS_ARG_CREATE(a, CreateArgs::CONDITIONAL_EXPRESSIONS, pt_scope_ops_intersect_conditional_expressions(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()), finallyConditional));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		return scopeFactoryCreate(a);
	}

	/* private (twin 4747) */
	static zv::Val processFinallyScopeVariableTypeHolders(HashTable *ourVariableTypeHolders, HashTable *finallyVariableTypeHolders, HashTable *originalVariableTypeHolders)
	{
		zv::Arr result = zv::Arr::copyOfTable(ourVariableTypeHolders);
		for (auto entry : zv::TableRef(finallyVariableTypeHolders)) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			zval *original = pt_ht_find(originalVariableTypeHolders, key, index);
			if (original != NULL && Z_TYPE_P(original) != IS_NULL) {
				bool equal;
				if (UNEXPECTED(!holderEqualTypes(zv::Ref(original), entry.value(), equal))) return zv::Val();
				if (equal) continue;
			}
			result.separate();
			zval copy;
			ZVAL_COPY(&copy, entry.value().deref().raw());
			pt_ht_update(result.table(), key, index, &copy);
		}

		return zv::Val(std::move(result));
	}

	/* }}} */

	/* {{{ twin 4775-5884: the closure and loop scopes, the
	 * generalization, the scope comparison, the member-access queries and
	 * the remaining readers */

	/* $a->equals($b); false = pending exception */
	[[nodiscard]] static bool typeEquals(zval *a, zval *b, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(a));
			return false;
		}
		return pt_type_op_bool(Z_OBJ_P(a), PT_OP_EQUALS, 1, b, out);
	}

	/* TypeCombinator::union(...$first, ...$second) over two list tables
	 * ($second may be NULL for the one-list spelling) */
	static zv::Val unionOfLists(HashTable *first, HashTable *second)
	{
		std::vector<zval> args;
		args.reserve(zend_hash_num_elements(first) + (second != NULL ? zend_hash_num_elements(second) : 0));
		for (auto entry : zv::TableRef(first)) {
			args.push_back(*entry.value().deref().raw());
		}
		if (second != NULL) {
			for (auto entry : zv::TableRef(second)) {
				args.push_back(*entry.value().deref().raw());
			}
		}
		return pt_type_combinator_union((uint32_t) args.size(), args.empty() ? NULL : args.data());
	}

	static zv::Val unionOfList(HashTable *list) { return unionOfLists(list, NULL); }

	/* $type->generalize(GeneralizePrecision::moreSpecific()) */
	static zv::Val generalizeMoreSpecific(zval *type)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function generalize() on %s", zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
		if (UNEXPECTED(precision.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(type), PT_LC("generalize"), 1, precision.raw());
	}

	/* the PT_TRI_* value of a TrinaryLogic-returning op; -1 = pending exception */
	[[nodiscard]] static zend_long typeOpTrinary(zend_object *type, pt_type_op_id op, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_op(type, op, argc, argv);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_trinary_value(result.raw());
	}

	/* an array key as the string PHP's `foreach ($a as $k => ...)` would
	 * hand a `string $k` parameter */
	static zv::Str entryKey(zend_string *key, zend_ulong index)
	{
		return zv::Str::adopt(key != NULL ? zend_string_copy(key) : zend_long_to_str((zend_long) index));
	}

	/* (twin 4775) */
	zv::Val processClosureScope(zend_object *closureScope, zval *prevScope, HashTable *byRefUses)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) {
			return zv::Val();
		}
		zv::Arr nativeExpressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()));
		zv::Arr expressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()));
		if (zend_hash_num_elements(byRefUses) == 0) return self_();

		for (auto entry : zv::TableRef(byRefUses)) {
			zv::Ref use = entry.value().deref();
			if (UNEXPECTED(!use.isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: a by-ref use is not a node");
				return zv::Val();
			}
			zv::Ref var = nodeProp(use.asObject(), PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
			var = var.deref();
			if (UNEXPECTED(!var.isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: a by-ref use has no variable node");
				return zv::Val();
			}
			zv::Ref name = nodeProp(var.asObject(), PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
			name = name.deref();
			if (!name.isString()) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_string *variableName = name.asString();
			zv::Str variableExprString = zv::Str::adopt(dollarName(variableName));

			zval variableNameZv;
			ZVAL_STR(&variableNameZv, variableName);
			zend_long hasVariableType = pt_type_call_trinary(closureScope, PT_LC("hasvariabletype"), 1, &variableNameZv);
			if (UNEXPECTED(hasVariableType < 0)) return zv::Val();
			if (hasVariableType != PT_TRI_YES) {
				zval nullTypeZv;
				if (UNEXPECTED(!pt_null_type_new(&nullTypeZv))) return zv::Val();
				zv::Val nullType = zv::Val::adopt(nullTypeZv);
				zval holderZv;
				pt_holder_create(&holderZv, var.raw(), nullType.raw(), PT_TRI_YES);
				zv::Val holder = zv::Val::adopt(holderZv);
				expressionTypes.set(variableExprString.get(), zv::Val::copyOf(holder.ref()));
				nativeExpressionTypes.set(variableExprString.get(), std::move(holder));
				continue;
			}

			zv::Val variableType = pt_type_call(closureScope, PT_LC("getvariabletype"), 1, &variableNameZv);
			if (UNEXPECTED(variableType.isUndef())) return zv::Val();
			if (prevScope != NULL) {
				zv::Val prevVariableType = pt_type_call(Z_OBJ_P(prevScope), PT_LC("getvariabletype"), 1, &variableNameZv);
				if (UNEXPECTED(prevVariableType.isUndef())) return zv::Val();
				bool equal;
				if (UNEXPECTED(!typeEquals(variableType.raw(), prevVariableType.raw(), equal))) return zv::Val();
				if (!equal) {
					zv::Val united = unionTypes(variableType.raw(), prevVariableType.raw());
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					variableType = generalizeType(united.raw(), prevVariableType.raw(), 0);
					if (UNEXPECTED(variableType.isUndef())) return zv::Val();
				}
			}

			zval holderZv;
			pt_holder_create(&holderZv, var.raw(), variableType.raw(), PT_TRI_YES);
			zv::Val holder = zv::Val::adopt(holderZv);
			expressionTypes.set(variableExprString.get(), zv::Val::copyOf(holder.ref()));
			nativeExpressionTypes.set(variableExprString.get(), std::move(holder));
		}

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(expressionTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(nativeExpressionTypes)));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		return scopeFactoryCreate(a);
	}

	/* (twin 4839) */
	zv::Val processAlwaysIterableForeachScopeWithoutPollute(zend_object *finalScope)
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		zv::Arr expressionTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()));
		HashTable *finalExpressionTypes = otherTable(finalScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(finalExpressionTypes == NULL)) return zv::Val();
		if (UNEXPECTED(!mergeForeachHolders(expressionTypes, finalExpressionTypes))) return zv::Val();

		if (UNEXPECTED(!requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes"))) return zv::Val();
		zv::Arr nativeTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()));
		HashTable *finalNativeTypes = otherTable(finalScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		if (UNEXPECTED(finalNativeTypes == NULL)) return zv::Val();
		if (UNEXPECTED(!mergeForeachHolders(nativeTypes, finalNativeTypes))) return zv::Val();

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, zv::Val(std::move(expressionTypes)));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, zv::Val(std::move(nativeTypes)));
		HashTable *finalConditional = otherTable(finalScope, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		if (UNEXPECTED(finalConditional == NULL || !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) return zv::Val();
		PT_MS_ARG_CREATE(a, CreateArgs::CONDITIONAL_EXPRESSIONS, pt_scope_ops_intersect_conditional_expressions(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()), finalConditional));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		return scopeFactoryCreate(a);
	}

	/* one of the two identical loops of
	 * processAlwaysIterableForeachScopeWithoutPollute(); false = pending
	 * exception */
	[[nodiscard]] static bool mergeForeachHolders(zv::Arr &ours, HashTable *theirs)
	{
		for (auto entry : zv::TableRef(theirs)) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			zv::Val expr = holderExpr(entry.value());
			zv::Val type = holderType(entry.value());
			if (UNEXPECTED(expr.isUndef() || type.isUndef())) return false;
			zval *existing = pt_ht_find(ours.table(), key, index);
			zend_long certainty;
			if (existing == NULL || Z_TYPE_P(existing) == IS_NULL) {
				certainty = PT_TRI_MAYBE;
			} else {
				zend_long theirCertainty = holderCertainty(entry.value());
				zend_long ourCertainty = holderCertainty(zv::Ref(existing));
				if (UNEXPECTED(theirCertainty < 0 || ourCertainty < 0)) return false;
				/* TrinaryLogic::and(): YES = 3, MAYBE = 1, NO = 0 */
				certainty = theirCertainty & ourCertainty;
			}
			zval holderZv;
			pt_holder_create(&holderZv, expr.raw(), type.raw(), certainty);
			ours.separate();
			pt_ht_update(ours.table(), key, index, &holderZv);
		}

		return true;
	}

	/* (twin 4893) */
	zv::Val generalizeWith(zend_object *otherScope, HashTable *writableVariableNames)
	{
		zv::Val generalized = generalizeWithVariableState(otherScope, writableVariableNames);
		if (UNEXPECTED(generalized.isUndef())) return zv::Val();
		zend_object *generalizedObject = requireObject(generalized, "addTemplateArgumentConstraints");
		if (UNEXPECTED(generalizedObject == NULL)) return zv::Val();
		zv::Val constraints = pt_type_call(otherScope, PT_LC("gettemplateargumentconstraints"), 0, NULL);
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		return pt_type_call(generalizedObject, PT_LC("addtemplateargumentconstraints"), 1, constraints.raw());
	}

	/* private (twin 4901) */
	zv::Val generalizeWithVariableState(zend_object *otherScope, HashTable *writableVariableNames)
	{
		HashTable *otherExpressionTypes = otherTable(otherScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(otherExpressionTypes == NULL || !requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return zv::Val();
		HashTable *ourExpressionTypes = Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw());

		zv::Arr writable;
		if (writableVariableNames != NULL) {
			/* a reference created before the loop lets the loop write a
			 * variable it does not name */
			writable = zv::Arr::copyOfTable(writableVariableNames);
			HashTable *tables[2] = { ourExpressionTypes, otherExpressionTypes };
			for (uint32_t i = 0; i < 2; i++) {
				for (auto entry : zv::TableRef(tables[i])) {
					zv::Val intertwinedExpr = holderExpr(entry.value());
					if (UNEXPECTED(intertwinedExpr.isUndef())) return zv::Val();
					bool isIntertwined;
					if (UNEXPECTED(!isInstance(intertwinedExpr.ref(), PT_CLASS_INTERTWINED_VAR, isIntertwined))) return zv::Val();
					if (!isIntertwined) continue;
					zend_object *intertwined = Z_OBJ_P(intertwinedExpr.raw());
					zv::Val variableName = pt_type_call(intertwined, PT_LC("getvariablename"), 0, NULL);
					if (UNEXPECTED(variableName.isUndef())) return zv::Val();
					zend_string *variableNameStr = zval_get_string(variableName.raw());
					writable.set(variableNameStr, zv::Val::boolean(true));
					zend_string_release(variableNameStr);
					zv::Val aliasedExprs[2];
					aliasedExprs[0] = pt_type_call(intertwined, PT_LC("getexpr"), 0, NULL);
					if (UNEXPECTED(aliasedExprs[0].isUndef())) return zv::Val();
					aliasedExprs[1] = pt_type_call(intertwined, PT_LC("getassignedexpr"), 0, NULL);
					if (UNEXPECTED(aliasedExprs[1].isUndef())) return zv::Val();
					for (uint32_t j = 0; j < 2; j++) {
						if (UNEXPECTED(Z_TYPE_P(aliasedExprs[j].raw()) != IS_OBJECT)) {
							zend_throw_error(NULL, "phpstan_turbo: an intertwined expression is not a node");
							return zv::Val();
						}
						zv::Val aliasedVariableName = pt_scope_ops_intertwined_ref_root_variable_name(Z_OBJ_P(aliasedExprs[j].raw()));
						if (UNEXPECTED(aliasedVariableName.isUndef())) return zv::Val();
						if (aliasedVariableName.isNull()) continue;
						zend_string *aliasedStr = zval_get_string(aliasedVariableName.raw());
						writable.set(aliasedStr, zv::Val::boolean(true));
						zend_string_release(aliasedStr);
					}
				}
			}
		}

		HashTable *writableTable = writableVariableNames == NULL ? NULL : writable.table();
		zv::Val variableTypeHolders = generalizeVariableTypeHolders(ourExpressionTypes, otherExpressionTypes, writableTable);
		if (UNEXPECTED(variableTypeHolders.isUndef())) return zv::Val();
		HashTable *otherNativeTypes = otherTable(otherScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		if (UNEXPECTED(otherNativeTypes == NULL || !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes"))) return zv::Val();
		zv::Val nativeTypes = generalizeVariableTypeHolders(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()), otherNativeTypes, writableTable);
		if (UNEXPECTED(nativeTypes.isUndef())) return zv::Val();

		CreateArgs a;
		if (UNEXPECTED(!fillFromSlots(a))) return zv::Val();
		if (UNEXPECTED(!fillDispatched(a, true, false))) return zv::Val();
		a.setOwned(CreateArgs::EXPRESSION_TYPES, std::move(variableTypeHolders));
		a.setOwned(CreateArgs::NATIVE_EXPRESSION_TYPES, std::move(nativeTypes));
		a.setBool(CreateArgs::IN_FIRST_LEVEL_STATEMENT, slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT));
		a.setEmptyArray(CreateArgs::CURRENTLY_ASSIGNED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS);
		a.setEmptyArray(CreateArgs::IN_FUNCTION_CALLS_STACK);
		return scopeFactoryCreate(a);
	}

	/* private (twin 4961) */
	zv::Val generalizeVariableTypeHolders(HashTable *variableTypeHolders, HashTable *otherVariableTypeHolders, HashTable *writableVariableNames)
	{
		/* uksort(fn ($a, $b) => strlen($a) <=> strlen($b)) — PHP's sort is
		 * stable, so equal lengths keep their insertion order */
		struct SortedEntry
		{
			zend_string *key;
			zend_ulong index;
			zval *value;
			size_t length;
		};
		std::vector<SortedEntry> sorted;
		sorted.reserve(zend_hash_num_elements(variableTypeHolders));
		for (auto entry : zv::TableRef(variableTypeHolders)) {
			zend_string *key = entry.stringKeyOrNull();
			size_t length;
			if (key != NULL) {
				length = ZSTR_LEN(key);
			} else {
				zend_string *rendered = zend_long_to_str((zend_long) entry.indexKey());
				length = ZSTR_LEN(rendered);
				zend_string_release(rendered);
			}
			sorted.push_back({ key, entry.indexKey(), entry.value().deref().raw(), length });
		}
		std::stable_sort(sorted.begin(), sorted.end(), [](const SortedEntry &x, const SortedEntry &y) {
			return x.length < y.length;
		});

		zv::Arr generalizedExpressions = zv::Arr::create(0);
		zv::Arr newVariableTypeHolders = zv::Arr::create(0);
		zv::Ref exprPrinter = slot(PT_MS_PROP_EXPR_PRINTER);
		if (UNEXPECTED(!exprPrinter.isObject())) return uninitializedProperty("exprPrinter");
		for (const SortedEntry &entry : sorted) {
			zv::Str variableExprString = entryKey(entry.key, entry.index);
			zv::Val variableExpr = holderExpr(zv::Ref(entry.value));
			if (UNEXPECTED(variableExpr.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(variableExpr.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "phpstan_turbo: an expression type holder has no expression");
				return zv::Val();
			}
			bool invalidated = false;
			for (auto generalized : zv::TableRef(generalizedExpressions.table())) {
				zv::Str generalizedExprString = entryKey(generalized.stringKeyOrNull(), generalized.indexKey());
				bool failed = false;
				bool should = pt_scope_ops_should_invalidate_expression(
					thisZval(),
					exprPrinter.raw(),
					generalizedExprString.get(),
					generalized.value().deref().raw(),
					Z_OBJ_P(variableExpr.raw()),
					variableExprString.get(),
					false,
					NULL,
					false,
					&failed);
				if (UNEXPECTED(failed)) return zv::Val();
				if (!should) continue;
				invalidated = true;
				break;
			}
			if (invalidated) continue;

			zval *otherHolder = pt_ht_find(otherVariableTypeHolders, entry.key, entry.index);
			if (otherHolder == NULL || Z_TYPE_P(otherHolder) == IS_NULL) {
				newVariableTypeHolders.set(variableExprString.get(), zv::Val::copyOf(zv::Ref(entry.value)));
				continue;
			}

			bool byNarrowingOnly = false;
			if (writableVariableNames != NULL) {
				bool isVariable;
				if (UNEXPECTED(!isInstance(variableExpr.ref(), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
				if (isVariable) {
					zv::Ref name = nodeProp(Z_OBJ_P(variableExpr.raw()), PT_LC("name"));
					if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
					name = name.deref();
					if (name.isString() && zend_symtable_find(writableVariableNames, name.asString()) == NULL) {
						byNarrowingOnly = true;
					}
				}
			}

			zv::Val ourType = holderType(zv::Ref(entry.value));
			zv::Val theirType = holderType(zv::Ref(otherHolder));
			if (UNEXPECTED(ourType.isUndef() || theirType.isUndef())) return zv::Val();
			/* the loop does not write this variable, its types differ between
			 * passes only by narrowing */
			zv::Val generalizedType = byNarrowingOnly
				? unionTypes(ourType.raw(), theirType.raw())
				: generalizeType(ourType.raw(), theirType.raw(), 0);
			if (UNEXPECTED(generalizedType.isUndef())) return zv::Val();
			bool equal;
			if (UNEXPECTED(!typeEquals(generalizedType.raw(), ourType.raw(), equal))) return zv::Val();
			if (!equal) {
				generalizedExpressions.set(variableExprString.get(), zv::Val::copyOf(variableExpr.ref()));
			}
			zend_long certainty = holderCertainty(zv::Ref(entry.value));
			if (UNEXPECTED(certainty < 0)) return zv::Val();
			zval holderZv;
			pt_holder_create(&holderZv, variableExpr.raw(), generalizedType.raw(), certainty);
			newVariableTypeHolders.set(variableExprString.get(), zv::Val::adopt(holderZv));
		}

		return zv::Val(std::move(newVariableTypeHolders));
	}

	/* the 'a' / 'b' pair of type lists generalizeType() sorts its inputs
	 * into (the twin's `['a' => [], 'b' => []]`) */
	struct GeneralizeBucket
	{
		zv::Arr list[2];

		GeneralizeBucket()
		{
			list[0] = zv::Arr::create(0);
			list[1] = zv::Arr::create(0);
		}

		HashTable *table(int side) { return list[side].table(); }
		uint32_t count(int side) { return zend_hash_num_elements(list[side].table()); }
	};

	/* $type->getArraySize()->getGreaterOrEqualType($this->phpVersion)->isSuperTypeOf($other->getArraySize())->yes();
	 * false = pending exception */
	[[nodiscard]] bool arraySizeGreaterOrEqual(zval *type, zval *other, bool &out)
	{
		zv::Val size = pt_type_call(Z_OBJ_P(type), PT_LC("getarraysize"), 0, NULL);
		if (UNEXPECTED(size.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(size.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getGreaterOrEqualType() on %s", zend_zval_value_name(size.raw()));
			return false;
		}
		zv::Ref phpVersion = slot(PT_MS_PROP_PHP_VERSION);
		if (UNEXPECTED(!phpVersion.isObject())) {
			(void) uninitializedProperty("phpVersion");
			return false;
		}
		zv::Val greaterOrEqual = pt_type_call(Z_OBJ_P(size.raw()), PT_LC("getgreaterorequaltype"), 1, phpVersion.raw());
		if (UNEXPECTED(greaterOrEqual.isUndef())) return false;
		zv::Val otherSize = pt_type_call(Z_OBJ_P(other), PT_LC("getarraysize"), 0, NULL);
		if (UNEXPECTED(otherSize.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(greaterOrEqual.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(greaterOrEqual.raw()));
			return false;
		}
		return isSuperTypeOfYes(Z_OBJ_P(greaterOrEqual.raw()), otherSize.raw(), out);
	}

	/* private flattenUnionForGeneralization(): a union's members, flattened
	 * recursively, any other type as the only element — the shapes stay
	 * whole (TypeUtils::flattenTypes() would expand optional keys into every
	 * variant only for generalizeType() to merge them back); false = pending
	 * exception */
	[[nodiscard]] static bool flattenUnionForGeneralization(zval *type, zv::Arr &out)
	{
		if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
			out.push(zv::Ref(type));
			return true;
		}
		zv::Val innerTypes = pt_type_call(Z_OBJ_P(type), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(innerTypes.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(innerTypes.raw()) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: UnionType::getTypes() did not answer with an array");
			return false;
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(innerTypes.raw()))) {
			if (UNEXPECTED(!flattenUnionForGeneralization(entry.value().deref().raw(), out))) return false;
		}
		return true;
	}

	/* private (twin 5011) */
	zv::Val generalizeType(zval *a, zval *b, zend_long depth)
	{
		bool equal;
		if (UNEXPECTED(!typeEquals(a, b, equal))) return zv::Val();
		if (equal) return zv::Val::copyOf(zv::Ref(a));

		/* Track whether either input carries a BenevolentUnion so the result
		 * can be re-wrapped at the end (see the twin's comment) */
		bool wrapBenevolent = zv::Ref(a).instanceOf(pt_ce_benevolent_union_type) || zv::Ref(b).instanceOf(pt_ce_benevolent_union_type);

		GeneralizeBucket constantIntegers, constantFloats, constantBooleans, constantStrings, constantArrays, generalArrays, integerRanges;
		zv::Arr otherTypes = zv::Arr::create(0);

		for (int side = 0; side < 2; side++) {
			zv::Arr flattenedTypes = zv::Arr::create(4);
			if (UNEXPECTED(!flattenUnionForGeneralization(side == 0 ? a : b, flattenedTypes))) return zv::Val();
			zv::Val flattened(std::move(flattenedTypes));
			for (auto entry : zv::TableRef(Z_ARRVAL_P(flattened.raw()))) {
				zv::Ref type = entry.value().deref();
				if (UNEXPECTED(!type.isObject())) {
					zend_throw_error(NULL, "phpstan_turbo: a flattened type is not a Type");
					return zv::Val();
				}
				zend_class_entry *ce = type.asObject()->ce;
				if (instanceof_function(ce, pt_ce_constant_integer_type)) {
					constantIntegers.list[side].push(type);
					continue;
				}
				if (instanceof_function(ce, pt_ce_constant_float_type)) {
					constantFloats.list[side].push(type);
					continue;
				}
				if (instanceof_function(ce, pt_ce_constant_boolean_type)) {
					constantBooleans.list[side].push(type);
					continue;
				}
				if (instanceof_function(ce, pt_ce_constant_string_type)) {
					constantStrings.list[side].push(type);
					continue;
				}
				zend_long isConstantArray = typeOpTrinary(type.asObject(), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
				if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
				if (isConstantArray == PT_TRI_YES) {
					constantArrays.list[side].push(type);
					continue;
				}
				zend_long isArray = typeOpTrinary(type.asObject(), PT_OP_IS_ARRAY, 0, NULL);
				if (UNEXPECTED(isArray < 0)) return zv::Val();
				if (isArray == PT_TRI_YES) {
					generalArrays.list[side].push(type);
					continue;
				}
				if (instanceof_function(ce, pt_ce_integer_range_type)) {
					integerRanges.list[side].push(type);
					continue;
				}

				otherTypes.push(type);
			}
		}

		zv::Arr resultTypes = zv::Arr::create(0);
		GeneralizeBucket *scalarBuckets[3] = { &constantFloats, &constantBooleans, &constantStrings };
		for (uint32_t i = 0; i < 3; i++) {
			GeneralizeBucket &bucket = *scalarBuckets[i];
			if (bucket.count(0) == 0) {
				if (bucket.count(1) > 0) {
					zv::Val united = unionOfList(bucket.table(1));
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					resultTypes.push(std::move(united));
				}
				continue;
			} else if (bucket.count(1) == 0) {
				zv::Val united = unionOfList(bucket.table(0));
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				resultTypes.push(std::move(united));
				continue;
			}

			zv::Val aTypes = unionOfList(bucket.table(0));
			if (UNEXPECTED(aTypes.isUndef())) return zv::Val();
			zv::Val bTypes = unionOfList(bucket.table(1));
			if (UNEXPECTED(bTypes.isUndef())) return zv::Val();
			bool sameTypes;
			if (UNEXPECTED(!typeEquals(aTypes.raw(), bTypes.raw(), sameTypes))) return zv::Val();
			if (sameTypes) {
				resultTypes.push(std::move(aTypes));
				continue;
			}

			zv::Val both = unionOfLists(bucket.table(0), bucket.table(1));
			if (UNEXPECTED(both.isUndef())) return zv::Val();
			zv::Val generalized = generalizeMoreSpecific(both.raw());
			if (UNEXPECTED(generalized.isUndef())) return zv::Val();
			resultTypes.push(std::move(generalized));
		}

		if (constantArrays.count(0) > 0) {
			if (constantArrays.count(1) == 0) {
				zv::Val united = unionOfList(constantArrays.table(0));
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				resultTypes.push(std::move(united));
			} else {
				zv::Val constantArraysA = unionOfList(constantArrays.table(0));
				if (UNEXPECTED(constantArraysA.isUndef())) return zv::Val();
				zv::Val constantArraysB = unionOfList(constantArrays.table(1));
				if (UNEXPECTED(constantArraysB.isUndef())) return zv::Val();
				zv::Val keyTypeA = pt_type_op(Z_OBJ_P(constantArraysA.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
				if (UNEXPECTED(keyTypeA.isUndef())) return zv::Val();
				zv::Val keyTypeB = pt_type_op(Z_OBJ_P(constantArraysB.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
				if (UNEXPECTED(keyTypeB.isUndef())) return zv::Val();
				bool sameKeys;
				if (UNEXPECTED(!typeEquals(keyTypeA.raw(), keyTypeB.raw(), sameKeys))) return zv::Val();
				bool sizeGreaterOrEqual = false;
				if (sameKeys && UNEXPECTED(!arraySizeGreaterOrEqual(constantArraysA.raw(), constantArraysB.raw(), sizeGreaterOrEqual))) return zv::Val();
				if (sameKeys && sizeGreaterOrEqual) {
					zv::Val builder = pt_constant_array_type_builder_create_empty();
					if (UNEXPECTED(builder.isUndef())) return zv::Val();
					zv::Val flattenedKeys = pt_type_utils_flatten_types(keyTypeA.raw());
					if (UNEXPECTED(flattenedKeys.isUndef() || Z_TYPE_P(flattenedKeys.raw()) != IS_ARRAY)) return zv::Val();
					for (auto keyEntry : zv::TableRef(Z_ARRVAL_P(flattenedKeys.raw()))) {
						zv::Ref keyType = keyEntry.value().deref();
						zv::Val valueA = pt_type_op(Z_OBJ_P(constantArraysA.raw()), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType.raw());
						if (UNEXPECTED(valueA.isUndef())) return zv::Val();
						zv::Val valueB = pt_type_op(Z_OBJ_P(constantArraysB.raw()), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType.raw());
						if (UNEXPECTED(valueB.isUndef())) return zv::Val();
						zv::Val generalizedValue = generalizeType(valueA.raw(), valueB.raw(), depth + 1);
						if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
						zend_long hasA = typeOpTrinary(Z_OBJ_P(constantArraysA.raw()), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, keyType.raw());
						if (UNEXPECTED(hasA < 0)) return zv::Val();
						zend_long hasB = typeOpTrinary(Z_OBJ_P(constantArraysB.raw()), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, keyType.raw());
						if (UNEXPECTED(hasB < 0)) return zv::Val();
						/* !$hasA->and($hasB)->negate()->no() — negate()->no()
						 * holds exactly when the and() is Yes */
						bool optional = (hasA & hasB) != PT_TRI_YES;
						if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), keyType.raw(), generalizedValue.raw(), optional))) {
							return zv::Val();
						}
					}
					zv::Val resultArray = pt_constant_array_type_builder_get_array(builder.raw());
					if (UNEXPECTED(resultArray.isUndef())) return zv::Val();
					resultTypes.push(std::move(resultArray));
				} else {
					/* Both inputs are sealed constant array shapes — see the
					 * twin's comment: keep the literal union instead of
					 * widening the keys and values */
					bool bothSealed = true;
					for (uint32_t side = 0; side < 2 && bothSealed; side++) {
						for (auto checkEntry : zv::TableRef(constantArrays.table((int) side))) {
							zv::Val constantArrayInstances = pt_type_op(checkEntry.value().deref().asObject(), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
							if (UNEXPECTED(constantArrayInstances.isUndef() || Z_TYPE_P(constantArrayInstances.raw()) != IS_ARRAY)) return zv::Val();
							for (auto instance : zv::TableRef(Z_ARRVAL_P(constantArrayInstances.raw()))) {
								zend_long isSealed = pt_type_call_trinary(instance.value().deref().asObject(), PT_LC("issealed"), 0, NULL);
								if (UNEXPECTED(isSealed < 0)) return zv::Val();
								if (isSealed != PT_TRI_YES) {
									bothSealed = false;
									break;
								}
							}
							if (!bothSealed) break;
						}
					}

					zv::Val valueTypeA = pt_type_op(Z_OBJ_P(constantArraysA.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
					if (UNEXPECTED(valueTypeA.isUndef())) return zv::Val();
					zv::Val valueTypeB = pt_type_op(Z_OBJ_P(constantArraysB.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
					if (UNEXPECTED(valueTypeB.isUndef())) return zv::Val();
					zv::Val resultKeyType, resultValueType;
					if (bothSealed) {
						resultKeyType = unionTypes(keyTypeA.raw(), keyTypeB.raw());
						if (UNEXPECTED(resultKeyType.isUndef())) return zv::Val();
						resultValueType = unionTypes(valueTypeA.raw(), valueTypeB.raw());
						if (UNEXPECTED(resultValueType.isUndef())) return zv::Val();
						zend_long isOversized = pt_type_call_trinary(Z_OBJ_P(resultValueType.raw()), PT_LC("isoversizedarray"), 0, NULL);
						if (UNEXPECTED(isOversized < 0)) return zv::Val();
						if (isOversized == PT_TRI_YES) {
							/* the literal value union outgrew the shape limit */
							zv::Val generalizedValue = generalizeType(valueTypeA.raw(), valueTypeB.raw(), depth + 1);
							if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
							resultValueType = pt_type_combinator_union(1, generalizedValue.raw());
							if (UNEXPECTED(resultValueType.isUndef())) return zv::Val();
						}
					} else {
						zv::Val generalizedKey = generalizeType(keyTypeA.raw(), keyTypeB.raw(), depth + 1);
						if (UNEXPECTED(generalizedKey.isUndef())) return zv::Val();
						resultKeyType = pt_type_combinator_union(1, generalizedKey.raw());
						if (UNEXPECTED(resultKeyType.isUndef())) return zv::Val();
						zv::Val generalizedValue = generalizeType(valueTypeA.raw(), valueTypeB.raw(), depth + 1);
						if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
						resultValueType = pt_type_combinator_union(1, generalizedValue.raw());
						if (UNEXPECTED(resultValueType.isUndef())) return zv::Val();
					}

					zval resultTypeZv;
					if (UNEXPECTED(!pt_array_type_new(&resultTypeZv, resultKeyType.raw(), resultValueType.raw()))) return zv::Val();
					zv::Val resultType = zv::Val::adopt(resultTypeZv);

					zv::Arr accessories = zv::Arr::create(2);
					zend_long iterableA = typeOpTrinary(Z_OBJ_P(constantArraysA.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
					if (UNEXPECTED(iterableA < 0)) return zv::Val();
					bool nonEmpty = iterableA == PT_TRI_YES;
					if (nonEmpty) {
						zend_long iterableB = typeOpTrinary(Z_OBJ_P(constantArraysB.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
						if (UNEXPECTED(iterableB < 0)) return zv::Val();
						nonEmpty = iterableB == PT_TRI_YES;
					}
					if (nonEmpty) {
						bool greaterOrEqual;
						if (UNEXPECTED(!arraySizeGreaterOrEqual(constantArraysA.raw(), constantArraysB.raw(), greaterOrEqual))) return zv::Val();
						nonEmpty = greaterOrEqual;
					}
					if (nonEmpty) {
						zval nonEmptyZv;
						if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmptyZv))) return zv::Val();
						accessories.push(zv::Val::adopt(nonEmptyZv));
					}
					zend_long listA = typeOpTrinary(Z_OBJ_P(constantArraysA.raw()), PT_OP_IS_LIST, 0, NULL);
					if (UNEXPECTED(listA < 0)) return zv::Val();
					if (listA == PT_TRI_YES) {
						zend_long listB = typeOpTrinary(Z_OBJ_P(constantArraysB.raw()), PT_OP_IS_LIST, 0, NULL);
						if (UNEXPECTED(listB < 0)) return zv::Val();
						if (listB == PT_TRI_YES) {
							zval listZv;
							if (UNEXPECTED(!pt_accessory_array_list_type_new(&listZv))) return zv::Val();
							accessories.push(zv::Val::adopt(listZv));
						}
					}

					if (zend_hash_num_elements(accessories.table()) == 0) {
						resultTypes.push(std::move(resultType));
					} else {
						zv::Val intersected = intersectWithAccessories(resultType.raw(), accessories.table());
						if (UNEXPECTED(intersected.isUndef())) return zv::Val();
						resultTypes.push(std::move(intersected));
					}
				}
			}
		} else if (constantArrays.count(1) > 0) {
			zv::Val united = unionOfList(constantArrays.table(1));
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			resultTypes.push(std::move(united));
		}

		if (generalArrays.count(0) > 0) {
			if (generalArrays.count(1) == 0) {
				zv::Val united = unionOfList(generalArrays.table(0));
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				resultTypes.push(std::move(united));
			} else {
				zv::Val generalArraysA = unionOfList(generalArrays.table(0));
				if (UNEXPECTED(generalArraysA.isUndef())) return zv::Val();
				zv::Val generalArraysB = unionOfList(generalArrays.table(1));
				if (UNEXPECTED(generalArraysB.isUndef())) return zv::Val();
				zv::Val aValueType = pt_type_op(Z_OBJ_P(generalArraysA.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
				if (UNEXPECTED(aValueType.isUndef())) return zv::Val();
				zv::Val bValueType = pt_type_op(Z_OBJ_P(generalArraysB.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
				if (UNEXPECTED(bValueType.isUndef())) return zv::Val();
				bool nestedArrays;
				if (UNEXPECTED(!isNonConstantArray(aValueType.raw(), nestedArrays))) return zv::Val();
				if (nestedArrays && UNEXPECTED(!isNonConstantArray(bValueType.raw(), nestedArrays))) return zv::Val();
				if (nestedArrays) {
					zend_long aDepth, bDepth;
					if (UNEXPECTED(!getArrayDepth(aValueType.raw(), aDepth) || !getArrayDepth(bValueType.raw(), bDepth))) return zv::Val();
					aDepth += depth;
					bDepth += depth;
					if ((aDepth > 2 || bDepth > 2) && aDepth != bDepth) {
						zval aMixed, bMixed;
						if (UNEXPECTED(!pt_mixed_type_new(&aMixed))) return zv::Val();
						aValueType = zv::Val::adopt(aMixed);
						if (UNEXPECTED(!pt_mixed_type_new(&bMixed))) return zv::Val();
						bValueType = zv::Val::adopt(bMixed);
					}
				}

				zv::Val keyTypeA = pt_type_op(Z_OBJ_P(generalArraysA.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
				if (UNEXPECTED(keyTypeA.isUndef())) return zv::Val();
				zv::Val keyTypeB = pt_type_op(Z_OBJ_P(generalArraysB.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
				if (UNEXPECTED(keyTypeB.isUndef())) return zv::Val();
				zv::Val generalizedKey = generalizeType(keyTypeA.raw(), keyTypeB.raw(), depth + 1);
				if (UNEXPECTED(generalizedKey.isUndef())) return zv::Val();
				zv::Val resultKeyType = pt_type_combinator_union(1, generalizedKey.raw());
				if (UNEXPECTED(resultKeyType.isUndef())) return zv::Val();
				zv::Val generalizedValue = generalizeType(aValueType.raw(), bValueType.raw(), depth + 1);
				if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
				zv::Val resultValueType = pt_type_combinator_union(1, generalizedValue.raw());
				if (UNEXPECTED(resultValueType.isUndef())) return zv::Val();
				zval resultTypeZv;
				if (UNEXPECTED(!pt_array_type_new(&resultTypeZv, resultKeyType.raw(), resultValueType.raw()))) return zv::Val();
				zv::Val resultType = zv::Val::adopt(resultTypeZv);

				zv::Arr accessories = zv::Arr::create(3);
				bool both;
				if (UNEXPECTED(!bothYes(generalArraysA.raw(), generalArraysB.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, both))) return zv::Val();
				if (both) {
					zval nonEmptyZv;
					if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmptyZv))) return zv::Val();
					accessories.push(zv::Val::adopt(nonEmptyZv));
				}
				if (UNEXPECTED(!bothYes(generalArraysA.raw(), generalArraysB.raw(), PT_OP_IS_LIST, both))) return zv::Val();
				if (both) {
					zval listZv;
					if (UNEXPECTED(!pt_accessory_array_list_type_new(&listZv))) return zv::Val();
					accessories.push(zv::Val::adopt(listZv));
				}
				zend_long oversizedA = pt_type_call_trinary(Z_OBJ_P(generalArraysA.raw()), PT_LC("isoversizedarray"), 0, NULL);
				if (UNEXPECTED(oversizedA < 0)) return zv::Val();
				if (oversizedA == PT_TRI_YES) {
					zend_long oversizedB = pt_type_call_trinary(Z_OBJ_P(generalArraysB.raw()), PT_LC("isoversizedarray"), 0, NULL);
					if (UNEXPECTED(oversizedB < 0)) return zv::Val();
					if (oversizedB == PT_TRI_YES) {
						zval oversizedZv;
						if (UNEXPECTED(!pt_oversized_array_type_new(&oversizedZv))) return zv::Val();
						accessories.push(zv::Val::adopt(oversizedZv));
					}
				}

				if (zend_hash_num_elements(accessories.table()) == 0) {
					resultTypes.push(std::move(resultType));
				} else {
					zv::Val intersected = intersectWithAccessories(resultType.raw(), accessories.table());
					if (UNEXPECTED(intersected.isUndef())) return zv::Val();
					resultTypes.push(std::move(intersected));
				}
			}
		} else if (generalArrays.count(1) > 0) {
			zv::Val united = unionOfList(generalArrays.table(1));
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			resultTypes.push(std::move(united));
		}

		if (constantIntegers.count(0) > 0) {
			if (constantIntegers.count(1) == 0) {
				zv::Val united = unionOfList(constantIntegers.table(0));
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				resultTypes.push(std::move(united));
			} else {
				zv::Val constantIntegersA = unionOfList(constantIntegers.table(0));
				if (UNEXPECTED(constantIntegersA.isUndef())) return zv::Val();
				zv::Val constantIntegersB = unionOfList(constantIntegers.table(1));
				if (UNEXPECTED(constantIntegersB.isUndef())) return zv::Val();
				bool same;
				if (UNEXPECTED(!typeEquals(constantIntegersA.raw(), constantIntegersB.raw(), same))) return zv::Val();
				if (same) {
					resultTypes.push(std::move(constantIntegersA));
				} else {
					bool hasMin = false, hasMax = false;
					zend_long min = 0, max = 0;
					for (auto intEntry : zv::TableRef(constantIntegers.table(0))) {
						zend_long value;
						if (UNEXPECTED(!pt_constant_integer_get_value(intEntry.value().deref().asObject(), value))) return zv::Val();
						if (!hasMin || value < min) {
							min = value;
							hasMin = true;
						}
						if (hasMax && value <= max) continue;
						max = value;
						hasMax = true;
					}

					zend_long newMin = min, newMax = max;
					for (auto intEntry : zv::TableRef(constantIntegers.table(1))) {
						zend_long value;
						if (UNEXPECTED(!pt_constant_integer_get_value(intEntry.value().deref().asObject(), value))) return zv::Val();
						if (value > newMax) {
							newMax = value;
						}
						if (value >= newMin) continue;
						newMin = value;
					}

					if (newMax > max && newMin < min) {
						zv::Val range = pt_integer_range_from_interval(phpstanturbo::NullableLong::of(newMin), phpstanturbo::NullableLong::of(newMax), 0);
						if (UNEXPECTED(range.isUndef())) return zv::Val();
						resultTypes.push(std::move(range));
					} else if (newMax > max) {
						zv::Val range = pt_integer_range_from_interval(phpstanturbo::NullableLong::of(min), phpstanturbo::NullableLong::null(), 0);
						if (UNEXPECTED(range.isUndef())) return zv::Val();
						resultTypes.push(std::move(range));
					} else if (newMin < min) {
						zv::Val range = pt_integer_range_from_interval(phpstanturbo::NullableLong::null(), phpstanturbo::NullableLong::of(max), 0);
						if (UNEXPECTED(range.isUndef())) return zv::Val();
						resultTypes.push(std::move(range));
					} else {
						zv::Val united = unionTypes(constantIntegersA.raw(), constantIntegersB.raw());
						if (UNEXPECTED(united.isUndef())) return zv::Val();
						resultTypes.push(std::move(united));
					}
				}
			}
		} else if (constantIntegers.count(1) > 0) {
			zv::Val united = unionOfList(constantIntegers.table(1));
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			resultTypes.push(std::move(united));
		}

		if (integerRanges.count(0) > 0) {
			if (integerRanges.count(1) == 0) {
				zv::Val united = unionOfList(integerRanges.table(0));
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				resultTypes.push(std::move(united));
			} else {
				zv::Val integerRangesA = unionOfList(integerRanges.table(0));
				if (UNEXPECTED(integerRangesA.isUndef())) return zv::Val();
				zv::Val integerRangesB = unionOfList(integerRanges.table(1));
				if (UNEXPECTED(integerRangesB.isUndef())) return zv::Val();
				bool same;
				if (UNEXPECTED(!typeEquals(integerRangesA.raw(), integerRangesB.raw(), same))) return zv::Val();
				if (same) {
					resultTypes.push(std::move(integerRangesA));
				} else {
					bool hasMin = false, hasMax = false;
					zend_long min = 0, max = 0;
					for (auto rangeEntry : zv::TableRef(integerRanges.table(0))) {
						zend_long rangeMin, rangeMax;
						if (UNEXPECTED(!rangeBounds(rangeEntry.value().deref().asObject(), rangeMin, rangeMax))) return zv::Val();
						if (!hasMin || rangeMin < min) {
							min = rangeMin;
							hasMin = true;
						}
						if (hasMax && rangeMax <= max) continue;
						max = rangeMax;
						hasMax = true;
					}

					zend_long newMin = min, newMax = max;
					for (auto rangeEntry : zv::TableRef(integerRanges.table(1))) {
						zend_long rangeMin, rangeMax;
						if (UNEXPECTED(!rangeBounds(rangeEntry.value().deref().asObject(), rangeMin, rangeMax))) return zv::Val();
						if (rangeMax > newMax) {
							newMax = rangeMax;
						}
						if (rangeMin >= newMin) continue;
						newMin = rangeMin;
					}

					bool gotGreater = newMax > max;
					bool gotSmaller = newMin < min;
					phpstanturbo::NullableLong minValue = min == ZEND_LONG_MIN ? phpstanturbo::NullableLong::null() : phpstanturbo::NullableLong::of(min);
					phpstanturbo::NullableLong maxValue = max == ZEND_LONG_MAX ? phpstanturbo::NullableLong::null() : phpstanturbo::NullableLong::of(max);
					phpstanturbo::NullableLong newMinValue = newMin == ZEND_LONG_MIN ? phpstanturbo::NullableLong::null() : phpstanturbo::NullableLong::of(newMin);
					phpstanturbo::NullableLong newMaxValue = newMax == ZEND_LONG_MAX ? phpstanturbo::NullableLong::null() : phpstanturbo::NullableLong::of(newMax);

					zv::Val result;
					if (gotGreater && gotSmaller) {
						result = pt_integer_range_from_interval(newMinValue, newMaxValue, 0);
					} else if (gotGreater) {
						result = pt_integer_range_from_interval(minValue, phpstanturbo::NullableLong::null(), 0);
					} else if (gotSmaller) {
						result = pt_integer_range_from_interval(phpstanturbo::NullableLong::null(), maxValue, 0);
					} else {
						result = unionTypes(integerRangesA.raw(), integerRangesB.raw());
					}
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					resultTypes.push(std::move(result));
				}
			}
		} else if (integerRanges.count(1) > 0) {
			zv::Val united = unionOfList(integerRanges.table(1));
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			resultTypes.push(std::move(united));
		}

		zv::Val accessoryTypes = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("getaccessorytypes"), 1, a);
		if (UNEXPECTED(accessoryTypes.isUndef() || Z_TYPE_P(accessoryTypes.raw()) != IS_ARRAY)) return zv::Val();
		zv::Arr generalizedAccessories = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(accessoryTypes.raw())));
		for (auto accessoryEntry : zv::TableRef(Z_ARRVAL_P(accessoryTypes.raw()))) {
			zv::Val generalized = generalizeMoreSpecific(accessoryEntry.value().deref().raw());
			if (UNEXPECTED(generalized.isUndef())) return zv::Val();
			generalizedAccessories.push(std::move(generalized));
		}

		zv::Val combined = unionOfLists(resultTypes.table(), otherTypes.table());
		if (UNEXPECTED(combined.isUndef())) return zv::Val();
		if (wrapBenevolent) {
			combined = pt_union_to_benevolent(combined.raw());
			if (UNEXPECTED(combined.isUndef())) return zv::Val();
		}

		zv::Val intersected = intersectWithAccessories(combined.raw(), generalizedAccessories.table());
		if (UNEXPECTED(intersected.isUndef())) return zv::Val();
		zv::Arr head = zv::Arr::create(1);
		head.push(intersected.ref());
		return unionOfLists(head.table(), otherTypes.table());
	}

	/* TypeCombinator::intersect($type, ...$accessories) */
	static zv::Val intersectWithAccessories(zval *type, HashTable *accessories)
	{
		std::vector<zval> args;
		args.reserve(zend_hash_num_elements(accessories) + 1);
		args.push_back(*type);
		for (auto entry : zv::TableRef(accessories)) {
			args.push_back(*entry.value().deref().raw());
		}
		return pt_type_combinator_intersect((uint32_t) args.size(), args.data());
	}

	/* $a-><op>()->yes() && $b-><op>()->yes(); false = pending exception */
	[[nodiscard]] static bool bothYes(zval *a, zval *b, pt_type_op_id op, bool &out)
	{
		out = false;
		zend_long first = typeOpTrinary(Z_OBJ_P(a), op, 0, NULL);
		if (UNEXPECTED(first < 0)) return false;
		if (first != PT_TRI_YES) return true;
		zend_long second = typeOpTrinary(Z_OBJ_P(b), op, 0, NULL);
		if (UNEXPECTED(second < 0)) return false;
		out = second == PT_TRI_YES;
		return true;
	}

	/* $type->isArray()->yes() && $type->isConstantArray()->no(); false =
	 * pending exception */
	static bool isNonConstantArray(zval *type, bool &out)
	{
		out = false;
		zend_long isArray = typeOpTrinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return false;
		if (isArray != PT_TRI_YES) return true;
		zend_long isConstantArray = typeOpTrinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(isConstantArray < 0)) return false;
		out = isConstantArray == PT_TRI_NO;
		return true;
	}

	/* $range->getMin() / getMax() with PHP_INT_MIN / PHP_INT_MAX for null,
	 * as the integer-range arm of generalizeType() spells it; false =
	 * pending exception */
	static bool rangeBounds(zend_object *range, zend_long &min, zend_long &max)
	{
		phpstanturbo::NullableLong rangeMin, rangeMax;
		if (UNEXPECTED(!pt_integer_range_bounds(range, rangeMin, rangeMax))) return false;
		min = rangeMin.isNull ? ZEND_LONG_MIN : rangeMin.value;
		max = rangeMax.isNull ? ZEND_LONG_MAX : rangeMax.value;
		return true;
	}

	/* private static (twin 5393) */
	static bool getArrayDepth(zval *type, zend_long &out)
	{
		zend_long depth = 0;
		zv::Val current = zv::Val::copyOf(zv::Ref(type));
		for (;;) {
			zv::Val benevolent = pt_union_to_benevolent(current.raw());
			if (UNEXPECTED(benevolent.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(benevolent.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getArrays() on %s", zend_zval_value_name(benevolent.raw()));
				return false;
			}
			zv::Val arrays = pt_type_call(Z_OBJ_P(benevolent.raw()), PT_LC("getarrays"), 0, NULL);
			if (UNEXPECTED(arrays.isUndef())) return false;
			if (Z_TYPE_P(arrays.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(arrays.raw())) == 0) break;
			zv::Val next = pt_type_op(Z_OBJ_P(current.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
			if (UNEXPECTED(next.isUndef())) return false;
			current = std::move(next);
			depth++;
		}
		out = depth;
		return true;
	}

	/* (twin 5407) */
	bool equals(zend_object *otherScope, bool &out)
	{
		out = false;
		zv::Ref context = slot(PT_MS_PROP_CONTEXT);
		if (UNEXPECTED(!context.isObject())) {
			(void) uninitializedProperty("context");
			return false;
		}
		zval *otherContext = otherProp(otherScope, PT_MS_PROP_CONTEXT, PT_LC("context"));
		if (UNEXPECTED(otherContext == NULL)) return false;
		ZVAL_DEREF(otherContext);
		zv::Val contextsEqual = pt_type_call(context.asObject(), PT_LC("equals"), 1, otherContext);
		if (UNEXPECTED(contextsEqual.isUndef())) return false;
		if (!zend_is_true(contextsEqual.raw())) return true;

		HashTable *otherExpressionTypes = otherTable(otherScope, PT_MS_PROP_EXPRESSION_TYPES, PT_LC("expressionTypes"));
		if (UNEXPECTED(otherExpressionTypes == NULL || !requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes"))) return false;
		bool same;
		if (UNEXPECTED(!compareVariableTypeHolders(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), otherExpressionTypes, same))) return false;
		if (!same) return true;

		HashTable *otherNativeTypes = otherTable(otherScope, PT_MS_PROP_NATIVE_EXPRESSION_TYPES, PT_LC("nativeExpressionTypes"));
		if (UNEXPECTED(otherNativeTypes == NULL || !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes"))) return false;
		if (UNEXPECTED(!compareVariableTypeHolders(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()), otherNativeTypes, same))) return false;
		if (!same) return true;

		HashTable *otherConditional = otherTable(otherScope, PT_MS_PROP_CONDITIONAL_EXPRESSIONS, PT_LC("conditionalExpressions"));
		if (UNEXPECTED(otherConditional == NULL || !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) return false;
		return compareConditionalExpressions(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()), otherConditional, out);
	}

	/* private (twin 5427) */
	static bool compareConditionalExpressions(HashTable *conditionalExpressions, HashTable *otherConditionalExpressions, bool &out)
	{
		out = false;
		if (zend_hash_num_elements(conditionalExpressions) != zend_hash_num_elements(otherConditionalExpressions)) return true;
		for (auto entry : zv::TableRef(conditionalExpressions)) {
			zval *otherHolders = pt_ht_find(otherConditionalExpressions, entry.stringKeyOrNull(), entry.indexKey());
			if (otherHolders == NULL || Z_TYPE_P(otherHolders) == IS_NULL) return true;
			ZVAL_DEREF(otherHolders);
			zv::Ref holders = entry.value().deref();
			if (UNEXPECTED(!holders.isArray() || Z_TYPE_P(otherHolders) != IS_ARRAY)) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return false;
			}
			if (zend_hash_num_elements(holders.asArrayTable()) != zend_hash_num_elements(Z_ARRVAL_P(otherHolders))) return true;
			for (auto holderEntry : zv::TableRef(holders.asArrayTable())) {
				zval *otherHolder = pt_ht_find(Z_ARRVAL_P(otherHolders), holderEntry.stringKeyOrNull(), holderEntry.indexKey());
				if (otherHolder == NULL || Z_TYPE_P(otherHolder) == IS_NULL) return true;
				zv::Val typeHolder = conditionalTypeHolder(holderEntry.value());
				zv::Val otherTypeHolder = conditionalTypeHolder(zv::Ref(otherHolder));
				if (UNEXPECTED(typeHolder.isUndef() || otherTypeHolder.isUndef())) return false;
				bool equal;
				if (UNEXPECTED(!holderEquals(typeHolder.ref(), otherTypeHolder.ref(), equal))) return false;
				if (!equal) return true;
				zv::Val conditions = conditionalConditions(holderEntry.value());
				zv::Val otherConditions = conditionalConditions(zv::Ref(otherHolder));
				if (UNEXPECTED(conditions.isUndef() || otherConditions.isUndef())) return false;
				if (UNEXPECTED(Z_TYPE_P(conditions.raw()) != IS_ARRAY || Z_TYPE_P(otherConditions.raw()) != IS_ARRAY)) {
					zend_throw_error(NULL, "phpstan_turbo: a conditional expression holder has no conditions");
					return false;
				}
				if (zend_hash_num_elements(Z_ARRVAL_P(conditions.raw())) != zend_hash_num_elements(Z_ARRVAL_P(otherConditions.raw()))) return true;
				for (auto conditionEntry : zv::TableRef(Z_ARRVAL_P(conditions.raw()))) {
					zval *otherCondition = pt_ht_find(Z_ARRVAL_P(otherConditions.raw()), conditionEntry.stringKeyOrNull(), conditionEntry.indexKey());
					if (otherCondition == NULL || Z_TYPE_P(otherCondition) == IS_NULL) return true;
					if (UNEXPECTED(!holderEquals(conditionEntry.value(), zv::Ref(otherCondition), equal))) return false;
					if (!equal) return true;
				}
			}
		}

		out = true;
		return true;
	}

	/* private (twin 5471) */
	static bool compareVariableTypeHolders(HashTable *variableTypeHolders, HashTable *otherVariableTypeHolders, bool &out)
	{
		out = false;
		if (zend_hash_num_elements(variableTypeHolders) != zend_hash_num_elements(otherVariableTypeHolders)) return true;
		for (auto entry : zv::TableRef(variableTypeHolders)) {
			zval *otherHolder = pt_ht_find(otherVariableTypeHolders, entry.stringKeyOrNull(), entry.indexKey());
			if (otherHolder == NULL || Z_TYPE_P(otherHolder) == IS_NULL) return true;
			zend_long certainty = holderCertainty(entry.value());
			zend_long otherCertainty = holderCertainty(zv::Ref(otherHolder));
			if (UNEXPECTED(certainty < 0 || otherCertainty < 0)) return false;
			if (certainty != otherCertainty) return true;
			bool equalTypes;
			if (UNEXPECTED(!holderEqualTypes(entry.value(), zv::Ref(otherHolder), equalTypes))) return false;
			if (!equalTypes) return true;
		}

		out = true;
		return true;
	}

	/**
	 * @api
	 * @deprecated Use canReadProperty() or canWriteProperty()
	 * (twin 5497)
	 */
	bool canAccessProperty(zend_object *propertyReflection, bool &out) { return canAccessClassMember(propertyReflection, out); }

	/** @api (twin 5503) */
	bool canReadProperty(zend_object *propertyReflection, bool &out) { return canAccessClassMember(propertyReflection, out); }

	/** @api (twin 5509) */
	bool canWriteProperty(zend_object *propertyReflection, bool &out)
	{
		zv::Val isPrivateSet = pt_type_call(propertyReflection, PT_LC("isprivateset"), 0, NULL);
		if (UNEXPECTED(isPrivateSet.isUndef())) return false;
		if (!zend_is_true(isPrivateSet.raw())) {
			zv::Val isProtectedSet = pt_type_call(propertyReflection, PT_LC("isprotectedset"), 0, NULL);
			if (UNEXPECTED(isProtectedSet.isUndef())) return false;
			if (!zend_is_true(isProtectedSet.raw())) return canAccessClassMember(propertyReflection, out);
		}

		zv::Ref phpVersion = slot(PT_MS_PROP_PHP_VERSION);
		if (UNEXPECTED(!phpVersion.isObject())) {
			(void) uninitializedProperty("phpVersion");
			return false;
		}
		zv::Val supportsAsymmetricVisibility = pt_type_call(phpVersion.asObject(), PT_LC("supportsasymmetricvisibility"), 0, NULL);
		if (UNEXPECTED(supportsAsymmetricVisibility.isUndef())) return false;
		if (!zend_is_true(supportsAsymmetricVisibility.raw())) return canAccessClassMember(propertyReflection, out);

		return memberAccessibleFromScope(propertyReflection, PT_LC("isprivateset"), out);
	}

	/** @api (twin 5555) */
	bool canCallMethod(zend_object *methodReflection, bool &out)
	{
		if (UNEXPECTED(!canAccessClassMember(methodReflection, out))) return false;
		if (out) return true;
		zv::Val prototype = pt_type_call(methodReflection, PT_LC("getprototype"), 0, NULL);
		if (UNEXPECTED(prototype.isUndef())) return false;
		zend_object *prototypeObject = requireObject(prototype, "canAccessClassMember");
		if (UNEXPECTED(prototypeObject == NULL)) return false;
		return canAccessClassMember(prototypeObject, out);
	}

	/** @api (twin 5565) */
	bool canAccessConstant(zend_object *constantReflection, bool &out) { return canAccessClassMember(constantReflection, out); }

	/* private (twin 5570) */
	bool canAccessClassMember(zend_object *classMemberReflection, bool &out)
	{
		zv::Val isPublic = pt_type_call(classMemberReflection, PT_LC("ispublic"), 0, NULL);
		if (UNEXPECTED(isPublic.isUndef())) return false;
		if (zend_is_true(isPublic.raw())) {
			out = true;
			return true;
		}

		return memberAccessibleFromScope(classMemberReflection, PT_LC("isprivate"), out);
	}

	/* the `$canAccessClassMember` closure of canAccessClassMember() and
	 * canWriteProperty() run over the closure-bind classes and the scope's
	 * own class; $privateLcName picks isPrivate() or isPrivateSet() */
	bool memberAccessibleFromScope(zend_object *memberReflection, const char *privateLcName, size_t privateLen, bool &out)
	{
		out = false;
		zv::Val declaringClass = pt_type_call(memberReflection, PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		zend_object *declaringClassObject = requireObject(declaringClass, "getName");
		if (UNEXPECTED(declaringClassObject == NULL)) return false;

		zv::Ref inClosureBindScopeClasses = slot(PT_MS_PROP_IN_CLOSURE_BIND_SCOPE_CLASSES);
		if (UNEXPECTED(!inClosureBindScopeClasses.isArray())) {
			(void) uninitializedProperty("inClosureBindScopeClasses");
			return false;
		}
		zv::Ref reflectionProvider = slot(PT_MS_PROP_REFLECTION_PROVIDER);
		if (UNEXPECTED(!reflectionProvider.isObject())) {
			(void) uninitializedProperty("reflectionProvider");
			return false;
		}
		for (auto entry : zv::TableRef(inClosureBindScopeClasses.asArrayTable())) {
			zval *className = entry.value().deref().raw();
			bool hasClass;
			if (UNEXPECTED(!pt_reflection_provider_has_class(reflectionProvider.asObject(), className, hasClass))) return false;
			if (!hasClass) continue;
			zv::Val classReflection = pt_reflection_provider_get_class(reflectionProvider.asObject(), className);
			if (UNEXPECTED(classReflection.isUndef())) return false;
			zend_object *classReflectionObject = requireObject(classReflection, "getName");
			if (UNEXPECTED(classReflectionObject == NULL)) return false;
			bool accessible;
			if (UNEXPECTED(!memberAccessibleFrom(classReflectionObject, memberReflection, declaringClassObject, privateLcName, privateLen, accessible))) {
				return false;
			}
			if (accessible) {
				out = true;
				return true;
			}
		}

		bool isInClass;
		if (UNEXPECTED(!thisIsInClass(isInClass))) return false;
		if (!isInClass) return true;
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return false;
		zend_object *classReflectionObject = requireObject(classReflection, "getName");
		if (UNEXPECTED(classReflectionObject == NULL)) return false;
		return memberAccessibleFrom(classReflectionObject, memberReflection, declaringClassObject, privateLcName, privateLen, out);
	}

	/* one evaluation of that closure for one ClassReflection */
	static bool memberAccessibleFrom(zend_object *classReflection, zend_object *memberReflection, zend_object *declaringClass, const char *privateLcName, size_t privateLen, bool &out)
	{
		out = false;
		zv::Val isPrivate = pt_type_call(memberReflection, privateLcName, privateLen, 0, NULL);
		if (UNEXPECTED(isPrivate.isUndef())) return false;
		zv::Val className = pt_class_reflection_get_name(classReflection);
		if (UNEXPECTED(className.isUndef())) return false;
		zv::Val declaringClassName = pt_class_reflection_get_name(declaringClass);
		if (UNEXPECTED(declaringClassName.isUndef())) return false;
		bool sameName = Z_TYPE_P(className.raw()) == IS_STRING
			&& Z_TYPE_P(declaringClassName.raw()) == IS_STRING
			&& zend_string_equals(Z_STR_P(className.raw()), Z_STR_P(declaringClassName.raw()));
		if (zend_is_true(isPrivate.raw())) {
			out = sameName;
			return true;
		}

		/* protected */
		if (sameName) {
			out = true;
			return true;
		}
		zv::Val withoutFinalOverride = pt_type_call(declaringClass, PT_LC("removefinalkeywordoverride"), 0, NULL);
		if (UNEXPECTED(withoutFinalOverride.isUndef())) return false;
		zv::Val isSubclass = pt_type_call(classReflection, PT_LC("issubclassofclass"), 1, withoutFinalOverride.raw());
		if (UNEXPECTED(isSubclass.isUndef())) return false;
		if (zend_is_true(isSubclass.raw())) {
			out = true;
			return true;
		}

		zv::Val memberDeclaringClass = pt_type_call(memberReflection, PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(memberDeclaringClass.isUndef())) return false;
		zend_object *memberDeclaringClassObject = requireObject(memberDeclaringClass, "isSubclassOfClass");
		if (UNEXPECTED(memberDeclaringClassObject == NULL)) return false;
		zval classReflectionZv;
		ZVAL_OBJ(&classReflectionZv, classReflection);
		zv::Val result = pt_type_call(memberDeclaringClassObject, PT_LC("issubclassofclass"), 1, &classReflectionZv);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* TrinaryLogic::describe() — the twin's final class, its three labels */
	static const char *certaintyLabel(zend_long certainty)
	{
		return certainty == PT_TRI_YES ? "Yes" : (certainty == PT_TRI_MAYBE ? "Maybe" : "No");
	}

	/* (twin 5614) */
	zv::Val debug()
	{
		if (UNEXPECTED(!requireSlot(PT_MS_PROP_EXPRESSION_TYPES, "expressionTypes")
			|| !requireSlot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES, "nativeExpressionTypes")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS, "currentlyAssignedExpressions")
			|| !requireSlot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS, "currentlyAllowedUndefinedExpressions")
			|| !requireSlot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS, "conditionalExpressions"))) {
			return zv::Val();
		}
		zv::Arr descriptions = zv::Arr::create(0);
		for (auto entry : zv::TableRef(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()))) {
			if (UNEXPECTED(!describeHolder(descriptions, entry, NULL))) return zv::Val();
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(slot(PT_MS_PROP_NATIVE_EXPRESSION_TYPES).raw()))) {
			if (UNEXPECTED(!describeHolder(descriptions, entry, "native "))) return zv::Val();
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ASSIGNED_EXPRESSIONS).raw()))) {
			zv::Str exprString = entryKey(entry.stringKeyOrNull(), entry.indexKey());
			zv::Str key = zv::Str::adopt(zend_strpprintf(0, "currently assigned %s", ZSTR_VAL(exprString.get())));
			descriptions.set(key.get(), zv::Val::string("true", 4));
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(slot(PT_MS_PROP_CURRENTLY_ALLOWED_UNDEFINED_EXPRESSIONS).raw()))) {
			zv::Str exprString = entryKey(entry.stringKeyOrNull(), entry.indexKey());
			zv::Str key = zv::Str::adopt(zend_strpprintf(0, "currently allowed undefined %s", ZSTR_VAL(exprString.get())));
			descriptions.set(key.get(), zv::Val::string("true", 4));
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(slot(PT_MS_PROP_CONDITIONAL_EXPRESSIONS).raw()))) {
			zv::Str exprString = entryKey(entry.stringKeyOrNull(), entry.indexKey());
			zv::Ref holders = entry.value().deref();
			if (UNEXPECTED(!holders.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: a conditional expressions entry is not an array");
				return zv::Val();
			}
			zend_long index = 0;
			for (auto holderEntry : zv::TableRef(holders.asArrayTable())) {
				zv::Str key = zv::Str::adopt(zend_strpprintf(0, "condition about %s #" ZEND_LONG_FMT, ZSTR_VAL(exprString.get()), index + 1));
				index++;
				zv::Val conditions = conditionalConditions(holderEntry.value());
				if (UNEXPECTED(conditions.isUndef() || Z_TYPE_P(conditions.raw()) != IS_ARRAY)) {
					zend_throw_error(NULL, "phpstan_turbo: a conditional expression holder has no conditions");
					return zv::Val();
				}
				smart_str condition = {};
				bool first = true;
				for (auto conditionEntry : zv::TableRef(Z_ARRVAL_P(conditions.raw()))) {
					if (!first) {
						smart_str_appendl(&condition, " && ", 4);
					}
					first = false;
					zv::Str conditionalExprString = entryKey(conditionEntry.stringKeyOrNull(), conditionEntry.indexKey());
					smart_str_append(&condition, conditionalExprString.get());
					smart_str_appendc(&condition, '=');
					zv::Val conditionType = holderType(conditionEntry.value());
					zval described;
					if (UNEXPECTED(conditionType.isUndef() || !pt_type_describe_precise(conditionType.raw(), &described))) {
						smart_str_free(&condition);
						return zv::Val();
					}
					zv::Val describedValue = zv::Val::adopt(described);
					if (EXPECTED(Z_TYPE_P(describedValue.raw()) == IS_STRING)) {
						smart_str_append(&condition, Z_STR_P(describedValue.raw()));
					}
				}
				zv::Str conditionString = zv::Str::adopt(smart_str_extract(&condition));

				zv::Val typeHolder = conditionalTypeHolder(holderEntry.value());
				if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
				zv::Val type = holderType(typeHolder.ref());
				zend_long certainty = holderCertainty(typeHolder.ref());
				if (UNEXPECTED(type.isUndef() || certainty < 0)) return zv::Val();
				zval described;
				if (UNEXPECTED(!pt_type_describe_precise(type.raw(), &described))) return zv::Val();
				zv::Val describedValue = zv::Val::adopt(described);
				zend_string *describedString = zval_get_string(describedValue.raw());
				zv::Str value = zv::Str::adopt(zend_strpprintf(
					0,
					"if %s then %s is %s (%s)",
					ZSTR_VAL(conditionString.get()),
					ZSTR_VAL(exprString.get()),
					ZSTR_VAL(describedString),
					certaintyLabel(certainty)));
				zend_string_release(describedString);
				descriptions.set(key.get(), zv::Val::string(value.get()));
			}
		}

		return zv::Val(std::move(descriptions));
	}

	/* one `$name (certainty) => description` line of debug() */
	static bool describeHolder(zv::Arr &descriptions, zv::ArrayEntry entry, const char *prefix)
	{
		zv::Str name = entryKey(entry.stringKeyOrNull(), entry.indexKey());
		zend_long certainty = holderCertainty(entry.value());
		if (UNEXPECTED(certainty < 0)) return false;
		zv::Str key = zv::Str::adopt(prefix == NULL
			? zend_strpprintf(0, "%s (%s)", ZSTR_VAL(name.get()), certaintyLabel(certainty))
			: zend_strpprintf(0, "%s%s (%s)", prefix, ZSTR_VAL(name.get()), certaintyLabel(certainty)));
		zv::Val type = holderType(entry.value());
		if (UNEXPECTED(type.isUndef())) return false;
		zval described;
		if (UNEXPECTED(!pt_type_describe_precise(type.raw(), &described))) return false;
		descriptions.set(key.get(), zv::Val::adopt(described));
		return true;
	}

	/* the predicates the filterTypes() sites pass: state0 is the member name
	 * (null where the predicate takes none), state1 the lowercase method to
	 * ask of every inner type */
	static void filterPredicate(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1 || Z_TYPE_P(argv) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: a type filter was called without a Type");
			return;
		}
		zend_long value = Z_TYPE_P(state0) == IS_STRING
			? pt_type_call_trinary(Z_OBJ_P(argv), Z_STRVAL_P(state1), Z_STRLEN_P(state1), 1, state0)
			: pt_type_call_trinary(Z_OBJ_P(argv), Z_STRVAL_P(state1), Z_STRLEN_P(state1), 0, NULL);
		if (UNEXPECTED(value < 0)) return;
		ZVAL_BOOL(return_value, value == PT_TRI_YES);
	}

	/* $type->filterTypes(static fn (Type $innerType) => $innerType-><method>($name)->yes()) */
	static zv::Val filterUnionTypes(zval *type, const char *lcname, size_t len, zval *memberName)
	{
		zval method;
		ZVAL_STRINGL(&method, lcname, len);
		zv::Val methodValue = zv::Val::adopt(method);
		zval noName;
		ZVAL_NULL(&noName);
		zv::Val callback = pt_type_native_callback(filterPredicate, memberName != NULL ? memberName : &noName, methodValue.raw());
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(type), PT_LC("filtertypes"), 1, callback.raw());
	}

	/* (twin 5655) */
	zv::Val filterTypeWithMethod(zval *typeWithMethod, zend_string *methodName)
	{
		zval methodNameZv;
		ZVAL_STR(&methodNameZv, methodName);
		if (zv::Ref(typeWithMethod).instanceOf(pt_ce_union_type)) {
			zv::Val filtered = filterUnionTypes(typeWithMethod, PT_LC("hasmethod"), &methodNameZv);
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (filtered.ref().instanceOf(pt_ce_never_type)) return zv::Val::null();
			return filtered;
		}

		zend_long hasMethod = typeOpTrinary(Z_OBJ_P(typeWithMethod), PT_OP_HAS_METHOD, 1, &methodNameZv);
		if (UNEXPECTED(hasMethod < 0)) return zv::Val();
		if (hasMethod != PT_TRI_YES) return zv::Val::null();

		return zv::Val::copyOf(zv::Ref(typeWithMethod));
	}

	/** @api (twin 5670) */
	zv::Val getMethodReflection(zval *typeWithMethod, zend_string *methodName)
	{
		zval methodNameZv;
		ZVAL_STR(&methodNameZv, methodName);
		zv::Val type = thisFilterTypeWithMethod(typeWithMethod, &methodNameZv);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) return zv::Val::null();
		zv::Args args{methodName, self};
		return pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getmethod"), 2, args);
	}

	/* (twin 5680) */
	zv::Val getNakedMethod(zval *typeWithMethod, zend_string *methodName)
	{
		zval methodNameZv;
		ZVAL_STR(&methodNameZv, methodName);
		zv::Val type = thisFilterTypeWithMethod(typeWithMethod, &methodNameZv);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) return zv::Val::null();
		zv::Args args{methodName, self};
		zv::Val prototype = pt_type_op(Z_OBJ_P(type.raw()), PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, 2, args);
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		zend_object *prototypeObject = requireObject(prototype, "getNakedMethod");
		if (UNEXPECTED(prototypeObject == NULL)) return zv::Val();
		return pt_type_call(prototypeObject, PT_LC("getnakedmethod"), 0, NULL);
	}

	/**
	 * @api
	 * @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead
	 * (twin 5694)
	 */
	zv::Val getPropertyReflection(zval *typeWithProperty, zend_string *propertyName)
	{
		zval propertyNameZv;
		ZVAL_STR(&propertyNameZv, propertyName);
		zv::Val filtered;
		zval *type = typeWithProperty;
		if (zv::Ref(typeWithProperty).instanceOf(pt_ce_union_type)) {
			filtered = filterUnionTypes(typeWithProperty, PT_LC("hasproperty"), &propertyNameZv);
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (filtered.ref().instanceOf(pt_ce_never_type)) return zv::Val::null();
			type = filtered.raw();
		} else {
			zend_long hasProperty = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasproperty"), 1, &propertyNameZv);
			if (UNEXPECTED(hasProperty < 0)) return zv::Val();
			if (hasProperty != PT_TRI_YES) return zv::Val::null();
		}

		zv::Args args{propertyName, self};
		return pt_type_call(Z_OBJ_P(type), PT_LC("getproperty"), 2, args);
	}

	/** @api (twin 5709) */
	zv::Val getInstancePropertyReflection(zval *typeWithProperty, zend_string *propertyName)
	{
		return propertyReflectionOf(typeWithProperty, propertyName, PT_LC("hasinstanceproperty"), PT_LC("getinstanceproperty"));
	}

	/** @api (twin 5725) */
	zv::Val getStaticPropertyReflection(zval *typeWithProperty, zend_string *propertyName)
	{
		return propertyReflectionOf(typeWithProperty, propertyName, PT_LC("hasstaticproperty"), PT_LC("getstaticproperty"));
	}

	/* the shared body of getInstancePropertyReflection() and
	 * getStaticPropertyReflection(): the union filter, then the same
	 * has*()/get*() pair on whatever came out */
	zv::Val propertyReflectionOf(zval *typeWithProperty, zend_string *propertyName, const char *hasLcName, size_t hasLen, const char *getLcName, size_t getLen)
	{
		zval propertyNameZv;
		ZVAL_STR(&propertyNameZv, propertyName);
		zv::Val filtered;
		zval *type = typeWithProperty;
		if (zv::Ref(typeWithProperty).instanceOf(pt_ce_union_type)) {
			filtered = filterUnionTypes(typeWithProperty, hasLcName, hasLen, &propertyNameZv);
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (filtered.ref().instanceOf(pt_ce_never_type)) return zv::Val::null();
			type = filtered.raw();
		}

		zend_long hasProperty = pt_type_call_trinary(Z_OBJ_P(type), hasLcName, hasLen, 1, &propertyNameZv);
		if (UNEXPECTED(hasProperty < 0)) return zv::Val();
		if (hasProperty != PT_TRI_YES) return zv::Val::null();

		zv::Args args{propertyName, self};
		return pt_type_call(Z_OBJ_P(type), getLcName, getLen, 2, args);
	}

	/* (twin 5740) */
	zv::Val getConstantReflection(zval *typeWithConstant, zend_string *constantName)
	{
		zval constantNameZv;
		ZVAL_STR(&constantNameZv, constantName);
		zv::Val filtered;
		zval *type = typeWithConstant;
		if (zv::Ref(typeWithConstant).instanceOf(pt_ce_union_type)) {
			filtered = filterUnionTypes(typeWithConstant, PT_LC("hasconstant"), &constantNameZv);
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (filtered.ref().instanceOf(pt_ce_never_type)) return zv::Val::null();
			type = filtered.raw();
		} else {
			zend_long hasConstant = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasconstant"), 1, &constantNameZv);
			if (UNEXPECTED(hasConstant < 0)) return zv::Val();
			if (hasConstant != PT_TRI_YES) return zv::Val::null();
		}

		return pt_type_call(Z_OBJ_P(type), PT_LC("getconstant"), 1, &constantNameZv);
	}

	/* (twin 5755) */
	zv::Val getConstantExplicitTypeFromConfig(zend_string *constantName, zval *constantType)
	{
		zv::Ref constantResolver = slot(PT_MS_PROP_CONSTANT_RESOLVER);
		if (UNEXPECTED(!constantResolver.isObject())) return uninitializedProperty("constantResolver");
		zv::Args args{constantName, constantType};
		return pt_type_call(constantResolver.asObject(), PT_LC("resolveconstanttype"), 2, args);
	}

	/* (twin 5811 / 5823) */
	zv::Val getIterableKeyType(zval *iteratee) { return iterableTypeOf(iteratee, PT_OP_GET_ITERABLE_KEY_TYPE); }
	zv::Val getIterableValueType(zval *iteratee) { return iterableTypeOf(iteratee, PT_OP_GET_ITERABLE_VALUE_TYPE); }

	zv::Val iterableTypeOf(zval *iteratee, pt_type_op_id op)
	{
		zv::Val filtered;
		zval *type = iteratee;
		if (zv::Ref(iteratee).instanceOf(pt_ce_union_type)) {
			filtered = filterUnionTypes(iteratee, PT_LC("isiterable"), NULL);
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (!filtered.ref().instanceOf(pt_ce_never_type)) {
				type = filtered.raw();
			}
		}

		return pt_type_op(Z_OBJ_P(type), op, 0, NULL);
	}

	/* (twin 5858) */
	bool invokeNodeCallback(zend_object *node)
	{
		zv::Ref nodeCallback = slot(PT_MS_PROP_NODE_CALLBACK);
		if (UNEXPECTED(nodeCallback.isUndef())) {
			(void) uninitializedProperty("nodeCallback");
			return false;
		}
		if (nodeCallback.isNull()) {
			throwNodeCallbackMissing();
			return false;
		}
		zv::Args args{node, self};
		zv::Val result = pt_type_call_callable(nodeCallback.raw(), 2, args);
		return !result.isUndef();
	}

	/* (twin 5874) */
	bool emitCollectedData(zend_string *collectorType, zval *data)
	{
		zv::Ref nodeCallback = slot(PT_MS_PROP_NODE_CALLBACK);
		if (UNEXPECTED(nodeCallback.isUndef())) {
			(void) uninitializedProperty("nodeCallback");
			return false;
		}
		if (nodeCallback.isNull()) {
			throwNodeCallbackMissing();
			return false;
		}
		zv::Args nodeArgs{collectorType, data};
		zv::Val node = pt_type_new(PT_CLASS_EMIT_COLLECTED_DATA_NODE, 2, nodeArgs);
		if (UNEXPECTED(node.isUndef())) return false;
		zv::Args args{node.raw(), self};
		zv::Val result = pt_type_call_callable(nodeCallback.raw(), 2, args);
		return !result.isUndef();
	}

	static void throwNodeCallbackMissing()
	{
		zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (ce == NULL) return; /* error already thrown */
		zend_throw_exception(ce, "Node callback is not present in this scope", 0);
	}

	/* }}} */

	/* {{{ out of the twin's file order: getNodeKey() 1093, getExprPrinter() 1099,
	 * hasExpressionType() 1814, getTrackedExpressionType() 1827,
	 * isInFirstLevelStatement() 4383, getGlobalConstantType() 5776 */

	zv::Val getNodeKey(zend_object *node)
	{
		zv::Ref exprPrinter = slot(PT_MS_PROP_EXPR_PRINTER);
		if (UNEXPECTED(!exprPrinter.isObject())) return uninitializedProperty("exprPrinter");
		zend_string *key = pt_node_key(node, exprPrinter.raw());
		if (UNEXPECTED(key == NULL)) return zv::Val();
		return zv::Val::adoptString(key);
	}

	/** @internal */
	zv::Val getExprPrinter() const { return copyOfSlot(PT_MS_PROP_EXPR_PRINTER); }

	zv::Val hasExpressionType(zend_object *node)
	{
		zv::Ref exprPrinter = slot(PT_MS_PROP_EXPR_PRINTER);
		if (UNEXPECTED(!exprPrinter.isObject())) return uninitializedProperty("exprPrinter");
		return pt_scope_ops_has_expression_type(thisZval(), node, exprPrinter.raw());
	}

	/** @internal */
	zv::Val getTrackedExpressionType(zend_object *node)
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		zv::Val key = thisGetNodeKey(&nodeZv);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		zend_string *keyStr = keyString(key);
		if (UNEXPECTED(keyStr == NULL)) return zv::Val();
		zval *holder = zend_symtable_find(Z_ARRVAL_P(slot(PT_MS_PROP_EXPRESSION_TYPES).raw()), keyStr);
		if (holder == NULL) {
			/* the twin's undefined-offset read: the warning, then the Error
			 * of the method call on null */
			zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(keyStr));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zend_throw_error(NULL, "Call to a member function getType() on null");
			return zv::Val();
		}
		return holderType(zv::Ref(holder));
	}

	/* {{{ out of the twin's file order: getScopeStateType() 3473 and
	 * resolveScopeStateType() 3490 (private; getKeepVoidType() and
	 * getCurrentTypesOfSpecifiedExpr() call them) */

	zv::Val getScopeStateType(zend_object *expr) { return resolveScopeStateType(expr, false); }

	/* Reads a narrowable expression's current type from the scope's
	 * tracked state (recursing into its operands), instead of routing
	 * through the stored ExpressionResult callbacks. */
	zv::Val resolveScopeStateType(zend_object *expr, bool native)
	{
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		bool isVariable;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_VARIABLE, isVariable))) return zv::Val();
		if (!isVariable) {
			zv::Val has = thisHasExpressionType(&exprZv);
			if (UNEXPECTED(has.isUndef())) return zv::Val();
			zend_long certainty = pt_type_trinary_value(has.raw());
			if (UNEXPECTED(certainty < 0)) return zv::Val();
			if (certainty == PT_TRI_YES) {
				/* mirror resolveType()'s tracked-holder lookup without
				 * pricing the node - the tracked type IS scope state */
				zv::Val askScope = native ? thisDoNotTreatPhpDocTypesAsCertain() : self_();
				if (UNEXPECTED(askScope.isUndef())) return zv::Val();
				zend_object *askObject = requireObject(askScope, "getNodeKey");
				if (UNEXPECTED(askObject == NULL)) return zv::Val();
				zv::Val key = MutatingScope(askObject).thisGetNodeKey(&exprZv);
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				zend_string *keyStr = keyString(key);
				if (UNEXPECTED(keyStr == NULL)) return zv::Val();
				zv::Val trackedType = pt_scope_ops_expression_type_by_key(askScope.raw(), expr, keyStr);
				if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
				if (!trackedType.isNull()) return pt_type_utils_resolve_late_resolvable_types(trackedType.raw());

				return native ? thisGetNativeType(&exprZv) : thisGetType(&exprZv);
			}
		}

		if (isVariable) {
			zv::Ref name = nodeProp(expr, PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
			name = name.deref();
			if (name.isString()) {
				zv::Val scope = native ? thisDoNotTreatPhpDocTypesAsCertain() : self_();
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
				zend_object *scopeObject = requireObject(scope, "hasVariableType");
				if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
				MutatingScope asked(scopeObject);
				zv::Val has = asked.thisHasVariableType(name.raw());
				if (UNEXPECTED(has.isUndef())) return zv::Val();
				zend_long certainty = pt_type_trinary_value(has.raw());
				if (UNEXPECTED(certainty < 0)) return zv::Val();
				if (certainty == PT_TRI_NO) return pt_type_new_error_type();
				return asked.thisGetVariableType(name.raw());
			}
		}

		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		if (isArrayDimFetch) {
			zv::Ref dim = nodeProp(expr, PT_LC("dim"));
			if (UNEXPECTED(dim.raw() == NULL)) return zv::Val();
			dim = dim.deref();
			if (!dim.isNull()) {
				zv::Ref var = nodeProp(expr, PT_LC("var"));
				if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
				zv::Val varStateType = resolveScopeStateType(Z_OBJ_P(var.deref().raw()), native);
				if (UNEXPECTED(varStateType.isUndef())) return zv::Val();
				bool isNever;
				pt_type_instanceof_ce(varStateType.raw(), pt_ce_never_type, isNever);
				if (isNever) {
					/* real pricing of an offset read on never yields ErrorType
					 * (a benevolent mixed), never NeverType - mirror it */
					return pt_type_new_error_type();
				}
				zv::Val dimStateType = resolveScopeStateType(Z_OBJ_P(dim.raw()), native);
				if (UNEXPECTED(dimStateType.isUndef())) return zv::Val();
				zend_object *varTypeObject = requireObject(varStateType, "getOffsetValueType");
				if (UNEXPECTED(varTypeObject == NULL)) return zv::Val();
				return pt_type_op(varTypeObject, PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimStateType.raw());
			}
		}

		bool isPropertyFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_PROPERTY_FETCH, isPropertyFetch))) return zv::Val();
		if (isPropertyFetch) {
			bool nameIsIdentifier;
			if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return zv::Val();
			if (nameIsIdentifier) {
				zv::Val propertyReflection = memberReflectionOfFetch(expr, native, PT_LC("getinstancepropertyreflection"));
				if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();
				return propertyStateType(propertyReflection, native);
			}
		}

		bool isStaticPropertyFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_STATIC_PROPERTY_FETCH, isStaticPropertyFetch))) return zv::Val();
		if (isStaticPropertyFetch) {
			bool nameIsVarLike;
			if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_VAR_LIKE_IDENTIFIER, nameIsVarLike))) return zv::Val();
			if (nameIsVarLike) {
				zv::Ref classNode = nodeProp(expr, PT_LC("class"));
				if (UNEXPECTED(classNode.raw() == NULL)) return zv::Val();
				classNode = classNode.deref();
				bool classIsName;
				if (UNEXPECTED(!isInstance(classNode, PT_CLASS_NAME, classIsName))) return zv::Val();
				zv::Val fetchedOnType;
				if (classIsName) {
					fetchedOnType = thisResolveTypeByName(classNode.raw());
				} else {
					if (UNEXPECTED(!classNode.isObject())) {
						zend_type_error("PHPStan\\Analyser\\MutatingScope::resolveScopeStateType(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(classNode.raw()));
						return zv::Val();
					}
					zv::Val classStateType = resolveScopeStateType(classNode.asObject(), native);
					if (UNEXPECTED(classStateType.isUndef())) return zv::Val();
					zv::Val withoutNull = pt_type_combinator_remove_null(classStateType.raw());
					if (UNEXPECTED(withoutNull.isUndef())) return zv::Val();
					zend_object *withoutNullObject = requireObject(withoutNull, "getObjectTypeOrClassStringObjectType");
					if (UNEXPECTED(withoutNullObject == NULL)) return zv::Val();
					fetchedOnType = pt_type_call(withoutNullObject, PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL);
				}
				if (UNEXPECTED(fetchedOnType.isUndef())) return zv::Val();
				zv::Val nameString = memberNameString(expr);
				if (UNEXPECTED(nameString.isUndef())) return zv::Val();
				zv::Args args{fetchedOnType.raw(), nameString.raw()};
				zv::Val propertyReflection = thisCallByName(PT_LC("getstaticpropertyreflection"), 2, args);
				if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();
				return propertyStateType(propertyReflection, native);
			}
		}

		/* a nullsafe link of a chain being ensured non-null ahead of its
		 * walk: the plain link's state on the receiver's state, plus the
		 * short-circuit null */
		bool isNullsafePropertyFetch;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_NULLSAFE_PROPERTY_FETCH, isNullsafePropertyFetch))) return zv::Val();
		if (isNullsafePropertyFetch) {
			bool nameIsIdentifier;
			if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return zv::Val();
			if (nameIsIdentifier) {
				zv::Ref var = nodeProp(expr, PT_LC("var"));
				zv::Ref name = nodeProp(expr, PT_LC("name"));
				if (UNEXPECTED(var.raw() == NULL || name.raw() == NULL)) return zv::Val();
				zv::Args args{var.deref().raw(), name.deref().raw()};
				zv::Val plainFetch = pt_type_new(PT_CLASS_PROPERTY_FETCH, 2, args);
				if (UNEXPECTED(plainFetch.isUndef())) return zv::Val();
				zv::Val plainType = resolveScopeStateType(Z_OBJ_P(plainFetch.raw()), native);
				if (UNEXPECTED(plainType.isUndef())) return zv::Val();
				return pt_type_combinator_add_null(plainType.raw());
			}
		}
		bool isNullsafeMethodCall;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_NULLSAFE_METHOD_CALL, isNullsafeMethodCall))) return zv::Val();
		if (isNullsafeMethodCall) {
			bool nameIsIdentifier;
			if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return zv::Val();
			if (nameIsIdentifier) {
				bool argumentLess;
				if (UNEXPECTED(!isArgumentLessPlainCall(expr, argumentLess))) return zv::Val();
				if (argumentLess) {
					/* new Expr\MethodCall($expr->var, $expr->name, attributes: $expr->getAttributes()) */
					zv::Ref var = nodeProp(expr, PT_LC("var"));
					zv::Ref name = nodeProp(expr, PT_LC("name"));
					if (UNEXPECTED(var.raw() == NULL || name.raw() == NULL)) return zv::Val();
					zv::Val attributes = pt_type_call(expr, PT_LC("getattributes"), 0, NULL);
					if (UNEXPECTED(attributes.isUndef())) return zv::Val();
					zval args[4];
					ZVAL_COPY_VALUE(&args[0], var.deref().raw());
					ZVAL_COPY_VALUE(&args[1], name.deref().raw());
					ZVAL_EMPTY_ARRAY(&args[2]);
					ZVAL_COPY_VALUE(&args[3], attributes.raw());
					zv::Val plainCall = pt_type_new(PT_CLASS_METHOD_CALL, 4, args);
					if (UNEXPECTED(plainCall.isUndef())) return zv::Val();
					zv::Val plainType = resolveScopeStateType(Z_OBJ_P(plainCall.raw()), native);
					if (UNEXPECTED(plainType.isUndef())) return zv::Val();
					return pt_type_combinator_add_null(plainType.raw());
				}
			}
		}

		/* an argument-less instance call - the shape @phpstan-assert
		 * subjects take: its declared return type on the receiver's state
		 * is the narrowing base, derived from reflection instead of walking
		 * the synthetic node. A call the walk did store answers from that
		 * result. */
		bool isMethodCall;
		if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_METHOD_CALL, isMethodCall))) return zv::Val();
		if (isMethodCall) {
			bool nameIsIdentifier;
			if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return zv::Val();
			if (nameIsIdentifier) {
				bool argumentLess;
				if (UNEXPECTED(!isArgumentLessPlainCall(expr, argumentLess))) return zv::Val();
				if (argumentLess) {
					zv::Val storage = currentStorage();
					if (UNEXPECTED(storage.isUndef())) return zv::Val();
					if (!storage.isNull()) {
						zv::Val stored = storageFind(storage, expr);
						if (UNEXPECTED(stored.isUndef())) return zv::Val();
						if (!stored.isNull()) return native ? thisGetNativeType(&exprZv) : thisGetType(&exprZv);
					}

					zv::Val methodReflection = memberReflectionOfFetch(expr, native, PT_LC("getmethodreflection"));
					if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
					if (methodReflection.isNull()) return pt_type_new_error_type();
					zend_object *methodObject = requireObject(methodReflection, "getVariants");
					if (UNEXPECTED(methodObject == NULL)) return zv::Val();

					/* resolved against the (empty) argument list so a template
					 * inferred from an omitted parameter's default resolves the
					 * way a walk resolves it */
					zv::Val variants = pt_type_call(methodObject, PT_LC("getvariants"), 0, NULL);
					if (UNEXPECTED(variants.isUndef())) return zv::Val();
					zv::Val namedArgumentsVariants = pt_type_call(methodObject, PT_LC("getnamedargumentsvariants"), 0, NULL);
					if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
					zval selectArgs[4];
					ZVAL_OBJ(&selectArgs[0], self);
					ZVAL_EMPTY_ARRAY(&selectArgs[1]);
					ZVAL_COPY_VALUE(&selectArgs[2], variants.raw());
					ZVAL_COPY_VALUE(&selectArgs[3], namedArgumentsVariants.raw());
					zv::Val variant = pt_type_call_static(PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR, PT_LC("selectfromargs"), 4, selectArgs);
					if (UNEXPECTED(variant.isUndef())) return zv::Val();

					if (native) {
						bool isExtended;
						if (UNEXPECTED(!isInstance(variant.ref(), PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR, isExtended))) return zv::Val();
						if (isExtended) return pt_type_call(Z_OBJ_P(variant.raw()), PT_LC("getnativereturntype"), 0, NULL);
					}
					zval selfZv, siteZv;
					ZVAL_OBJ(&selfZv, self);
					ZVAL_OBJ(&siteZv, expr);
					return pt_template_argument_frame_return_type_of_call(variant.raw(), &selfZv, &siteZv, 1);
				}
			}
		}

		/* position-independent constant expressions are priced without
		 * walking the node */
		bool isConstantExpr = false;
		static const int constantClasses[] = { PT_CLASS_SCALAR_STRING, PT_CLASS_SCALAR_INT, PT_CLASS_SCALAR_FLOAT };
		for (int classIdx : constantClasses) {
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), classIdx, isConstantExpr))) return zv::Val();
			if (isConstantExpr) break;
		}
		if (!isConstantExpr) {
			bool isClassConstFetch;
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_CLASS_CONST_FETCH, isClassConstFetch))) return zv::Val();
			if (isClassConstFetch) {
				zv::Ref classNode = nodeProp(expr, PT_LC("class"));
				if (UNEXPECTED(classNode.raw() == NULL)) return zv::Val();
				bool classIsName;
				if (UNEXPECTED(!isInstance(classNode.deref(), PT_CLASS_NAME, classIsName))) return zv::Val();
				if (classIsName) {
					if (UNEXPECTED(!nodeNameIs(expr, PT_CLASS_IDENTIFIER, isConstantExpr))) return zv::Val();
				}
			}
		}
		if (!isConstantExpr) {
			if (UNEXPECTED(!isInstance(zv::Ref(&exprZv), PT_CLASS_CONST_FETCH, isConstantExpr))) return zv::Val();
		}
		if (isConstantExpr) {
			zv::Ref resolver = slot(PT_MS_PROP_INITIALIZER_EXPR_TYPE_RESOLVER);
			if (UNEXPECTED(!resolver.isObject())) return uninitializedProperty("initializerExprTypeResolver");
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromscope"), 1, &selfZv);
			if (UNEXPECTED(context.isUndef())) return zv::Val();
			zv::Args args{expr, context.raw()};
			return pt_type_call(resolver.asObject(), PT_LC("gettype"), 2, args);
		}

		/* genuinely non-narrowed expressions (calls, ...) have no
		 * variable-callback hazard, so read them normally */
		return native ? thisGetNativeType(&exprZv) : thisGetType(&exprZv);
	}

	/* $this-><method>($this->resolveScopeStateType($expr->var, $native), $expr->name->toString()) */
	zv::Val memberReflectionOfFetch(zend_object *expr, bool native, const char *lcname, size_t len)
	{
		zv::Ref var = nodeProp(expr, PT_LC("var"));
		if (UNEXPECTED(var.raw() == NULL)) return zv::Val();
		var = var.deref();
		if (UNEXPECTED(!var.isObject())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::resolveScopeStateType(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(var.raw()));
			return zv::Val();
		}
		zv::Val varStateType = resolveScopeStateType(var.asObject(), native);
		if (UNEXPECTED(varStateType.isUndef())) return zv::Val();
		zv::Val nameString = memberNameString(expr);
		if (UNEXPECTED(nameString.isUndef())) return zv::Val();
		zv::Args args{varStateType.raw(), nameString.raw()};
		return thisCallByName(lcname, len, 2, args);
	}

	/* $expr->name->toString() */
	static zv::Val memberNameString(zend_object *expr)
	{
		zv::Ref name = nodeProp(expr, PT_LC("name"));
		if (UNEXPECTED(name.raw() == NULL)) return zv::Val();
		name = name.deref();
		if (UNEXPECTED(!name.isObject())) {
			zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(name.raw()));
			return zv::Val();
		}
		return pt_type_call(name.asObject(), PT_LC("tostring"), 0, NULL);
	}

	/* the (readable / native) type of a property reflection lookup, an
	 * ErrorType for none */
	static zv::Val propertyStateType(zv::Val &propertyReflection, bool native)
	{
		if (propertyReflection.isNull()) return pt_type_new_error_type();
		zend_object *reflection = requireObject(propertyReflection, native ? "hasNativeType" : "getReadableType");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		if (native) {
			zv::Val hasNativeType = pt_type_call(reflection, PT_LC("hasnativetype"), 0, NULL);
			if (UNEXPECTED(hasNativeType.isUndef())) return zv::Val();
			if (zend_is_true(hasNativeType.raw())) return pt_type_call(reflection, PT_LC("getnativetype"), 0, NULL);
			return pt_type_new_mixed_type();
		}
		return pt_type_call(reflection, PT_LC("getreadabletype"), 0, NULL);
	}

	/* }}} */

	bool isInFirstLevelStatement() const { return slotBool(PT_MS_PROP_IN_FIRST_LEVEL_STATEMENT); }

	/* private; null when no fetch is tracked, UNDEF = pending exception */
	zv::Val getGlobalConstantType(zend_object *name)
	{
		/* the namespace only takes part for a name that is not already fully qualified */
		zv::Val isFullyQualified = pt_type_call(name, PT_LC("isfullyqualified"), 0, NULL);
		if (UNEXPECTED(isFullyQualified.isUndef())) return zv::Val();
		zv::Val ns = zv::Val::null();
		if (!zend_is_true(isFullyQualified.raw())) {
			ns = thisGetNamespace();
			if (UNEXPECTED(ns.isUndef())) return zv::Val();
		}

		zv::Val nameString = pt_type_call(name, PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(nameString.isUndef())) return zv::Val();
		if (UNEXPECTED(!nameString.ref().isString())) {
			zend_throw_error(NULL, "phpstan_turbo: Name::toString() did not return a string");
			return zv::Val();
		}

		zv::Str cacheKey = globalConstantFetchCacheKey(name->ce->name, nameString.ref().asString(), ns.ref());
		zval *keysTable = globalConstantFetchKeysTable();
		if (UNEXPECTED(keysTable == NULL)) return zv::Val();

		zv::Val exprStrings;
		zval *memo = zend_hash_find(Z_ARRVAL_P(keysTable), cacheKey.get());
		if (memo != NULL) {
			zv::Ref memoRef = zv::Ref(memo).deref();
			if (UNEXPECTED(!memoRef.isArray())) {
				zend_throw_error(NULL, "phpstan_turbo: globalConstantFetchKeys entry is not an array");
				return zv::Val();
			}
			exprStrings = zv::Val::copyOf(memoRef);
		} else {
			zv::Val fetches[3];
			uint32_t count = 0;
			if (UNEXPECTED(!createGlobalConstantFetches(name, nameString.ref().asString(), ns.ref(), fetches, count))) return zv::Val();

			zv::Arr keys = zv::Arr::create(count);
			for (uint32_t i = 0; i < count; i++) {
				zv::Val key = thisGetNodeKey(fetches[i].raw());
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				keys.push(std::move(key));
			}

			if (zend_hash_num_elements(Z_ARRVAL_P(keysTable)) < PT_MS_GLOBAL_CONSTANT_FETCH_KEYS_LIMIT) {
				SEPARATE_ARRAY(keysTable);
				zval stored;
				ZVAL_COPY(&stored, keys.raw());
				zend_hash_update(Z_ARRVAL_P(keysTable), cacheKey.get(), &stored);
			}
			exprStrings = std::move(keys);
		}

		zv::Ref expressionTypes = slot(PT_MS_PROP_EXPRESSION_TYPES);
		if (UNEXPECTED(!expressionTypes.isArray())) return uninitializedProperty("expressionTypes");

		uint32_t i = 0;
		for (auto entry : zv::ArrRef(exprStrings.raw())) {
			/* hasExpressionType() looks at nothing but the key for a node that is
			 * not a Variable, and the key is exactly what is memoized above */
			zv::Ref exprString = entry.value().deref();
			if (UNEXPECTED(!exprString.isString())) {
				zend_throw_error(NULL, "phpstan_turbo: globalConstantFetchKeys entry is not a string");
				return zv::Val();
			}
			zval *holder = zend_symtable_find(Z_ARRVAL_P(expressionTypes.raw()), exprString.asString());
			if (holder != NULL) {
				zend_long certainty = holderCertainty(zv::Ref(holder).deref());
				if (UNEXPECTED(certainty < 0)) return zv::Val();
				if (certainty == PT_TRI_YES) {
					zv::Val fetches[3];
					uint32_t count = 0;
					if (UNEXPECTED(!createGlobalConstantFetches(name, nameString.ref().asString(), ns.ref(), fetches, count))) return zv::Val();
					return thisGetType(fetches[i].raw());
				}
			}
			i++;
		}

		return zv::Val::null();
	}

	/*
	 * The nodes a global constant name is looked up as, in priority order: the
	 * current namespace's constant, the global one, then the name as written.
	 *
	 * Fresh nodes on every call by design - everything that keys on node identity
	 * (ExpressionResultStorage, NodeScopeResolver's processed-node guards) must
	 * keep seeing a node of its own; only the keys they print as are memoized.
	 *
	 * false = pending exception
	 */
	[[nodiscard]] static bool createGlobalConstantFetches(zend_object *name, zend_string *nameString, zv::Ref ns, zv::Val (&fetches)[3], uint32_t &count)
	{
		count = 0;

		if (!ns.isNull()) {
			zv::Arr parts = zv::Arr::create(2);
			parts.push(ns);
			parts.push(zv::Val::string(nameString));
			zv::Val fetch = newConstFetchOfFullyQualified(parts.raw());
			if (UNEXPECTED(fetch.isUndef())) return false;
			fetches[count++] = std::move(fetch);
		}

		zval nameStringZv;
		ZVAL_STR(&nameStringZv, nameString);
		zv::Val fetch = newConstFetchOfFullyQualified(&nameStringZv);
		if (UNEXPECTED(fetch.isUndef())) return false;
		fetches[count++] = std::move(fetch);

		zval nameZv;
		ZVAL_OBJ(&nameZv, name);
		fetch = pt_type_new(PT_CLASS_CONST_FETCH, 1, &nameZv);
		if (UNEXPECTED(fetch.isUndef())) return false;
		fetches[count++] = std::move(fetch);

		return true;
	}

	/* the twin's get_class($name) . "\0" . $name->toString() . "\0" . ($namespace ?? "\0") */
	static zv::Str globalConstantFetchCacheKey(zend_string *nameClass, zend_string *nameString, zv::Ref ns)
	{
		smart_str key = {};
		smart_str_append(&key, nameClass);
		smart_str_appendc(&key, '\0');
		smart_str_append(&key, nameString);
		smart_str_appendc(&key, '\0');
		if (ns.isNull()) {
			smart_str_appendc(&key, '\0');
		} else {
			smart_str_append(&key, ns.asString());
		}
		return zv::Str::adopt(smart_str_extract(&key));
	}

	/* MutatingScope::$globalConstantFetchKeys - the same static property the twin
	 * memoizes into; NULL = pending exception */
	[[nodiscard]] static zval *globalConstantFetchKeysTable()
	{
		if (UNEXPECTED(pt_ce_mutating_scope == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: MutatingScope class entry is not available");
			return NULL;
		}
		zval *table = zend_read_static_property(pt_ce_mutating_scope, PT_LC("globalConstantFetchKeys"), 0);
		if (UNEXPECTED(table == NULL)) return NULL;
		ZVAL_DEREF(table);
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: MutatingScope::$globalConstantFetchKeys is not an array");
			return NULL;
		}
		return table;
	}

	/* new ConstFetch(new FullyQualified($nameOrParts)) */
	static zv::Val newConstFetchOfFullyQualified(zval *nameOrParts)
	{
		zv::Val fullyQualified = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, nameOrParts);
		if (UNEXPECTED(fullyQualified.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_FETCH, 1, fullyQualified.raw());
	}

	/* }}} */

private:
	zend_object *self;
	zval selfZval;
};

} // namespace phpstanturbo

using phpstanturbo::MutatingScope;

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS MutatingScope(Z_OBJ_P(ZEND_THIS))

/* a `bool method(bool &out)` body into return_value */
#define PT_MS_RETURN_BOOL(expr) \
	do { \
		bool out_; \
		if (UNEXPECTED(!(expr))) { \
			RETURN_THROWS(); \
		} \
		RETURN_BOOL(out_); \
	} while (0)

namespace pt_ms {
/* the twin's parameter and return class names (persistent literals) */
inline constexpr const char *self = "PHPStan\\Analyser\\MutatingScope";
inline constexpr const char *container = "PHPStan\\DependencyInjection\\Container";
inline constexpr const char *internalScopeFactory = "PHPStan\\Analyser\\InternalScopeFactory";
inline constexpr const char *reflectionProvider = "PHPStan\\Reflection\\ReflectionProvider";
inline constexpr const char *initializerExprTypeResolver = "PHPStan\\Reflection\\InitializerExprTypeResolver";
inline constexpr const char *extensionsCollection = "PHPStan\\DependencyInjection\\ExtensionsCollection";
inline constexpr const char *exprPrinter = "PHPStan\\Node\\Printer\\ExprPrinter";
inline constexpr const char *typeSpecifier = "PHPStan\\Analyser\\TypeSpecifier";
inline constexpr const char *propertyReflectionFinder = "PHPStan\\Rules\\Properties\\PropertyReflectionFinder";
inline constexpr const char *parser = "PHPStan\\Parser\\Parser";
inline constexpr const char *constantResolver = "PHPStan\\Analyser\\ConstantResolver";
inline constexpr const char *expressionResultStorageStack = "PHPStan\\Analyser\\ExpressionResultStorageStack";
inline constexpr const char *scopeContext = "PHPStan\\Analyser\\ScopeContext";
inline constexpr const char *phpVersion = "PHPStan\\Php\\PhpVersion";
inline constexpr const char *attributeReflectionFactory = "PHPStan\\Reflection\\AttributeReflectionFactory";
inline constexpr const char *configuredPhpVersionRangeHelper = "PHPStan\\Php\\ConfiguredPhpVersionRangeHelper";
inline constexpr const char *phpFunctionFromParserNodeReflection = "PHPStan\\Reflection\\Php\\PhpFunctionFromParserNodeReflection";
inline constexpr const char *closureType = "PHPStan\\Type\\ClosureType";
inline constexpr const char *templateArgumentFrame = "PHPStan\\Analyser\\Generics\\TemplateArgumentFrame";
inline constexpr const char *templateArgumentConstraints = "PHPStan\\Analyser\\Generics\\TemplateArgumentConstraints";
inline constexpr const char *classReflection = "PHPStan\\Reflection\\ClassReflection";
inline constexpr const char *expr = "PhpParser\\Node\\Expr";
inline constexpr const char *name = "PhpParser\\Node\\Name";
inline constexpr const char *trinaryLogic = "PHPStan\\TrinaryLogic";
inline constexpr const char *type = "PHPStan\\Type\\Type";
inline constexpr const char *specifiedTypes = "PHPStan\\Analyser\\SpecifiedTypes";
inline constexpr const char *parameterReflection = "PHPStan\\Reflection\\ParameterReflection";
inline constexpr const char *phpVersions = "PHPStan\\Php\\PhpVersions";
inline constexpr const char *param = "PhpParser\\Node\\Param";
inline constexpr const char *classMethodNode = "PhpParser\\Node\\Stmt\\ClassMethod";
inline constexpr const char *functionNode = "PhpParser\\Node\\Stmt\\Function_";
inline constexpr const char *propertyHook = "PhpParser\\Node\\PropertyHook";
inline constexpr const char *identifierOrNameOrComplexType = "PhpParser\\Node\\Identifier|PhpParser\\Node\\Name|PhpParser\\Node\\ComplexType";
inline constexpr const char *templateTypeMap = "PHPStan\\Type\\Generic\\TemplateTypeMap";
inline constexpr const char *assertions = "PHPStan\\Reflection\\Assertions";
inline constexpr const char *resolvedPhpDocBlock = "PHPStan\\PhpDoc\\ResolvedPhpDocBlock";
inline constexpr const char *closureNode = "PhpParser\\Node\\Expr\\Closure";
inline constexpr const char *arrowFunctionNode = "PhpParser\\Node\\Expr\\ArrowFunction";
inline constexpr const char *node = "PhpParser\\Node";
inline constexpr const char *propertyReflection = "PHPStan\\Reflection\\PropertyReflection";
inline constexpr const char *methodReflection = "PHPStan\\Reflection\\MethodReflection";

/* `?bool $x` — reg::boolArg() is never nullable */
constexpr reg::Arg nullableBool(const char *name)
{
	return { name, MAY_BE_BOOL | MAY_BE_NULL | reg::detail::flagBits(false, false), nullptr };
}

inline constexpr reg::Arg returnsSelf = reg::obj("", self);
} // namespace pt_ms

/* the handlers the $this-dispatch fast paths identify */
static void ZEND_FASTCALL msGetFile(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getFile());
}

static void ZEND_FASTCALL msIsDeclareStrictTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_BOOL(PT_THIS.isDeclareStrictTypes());
}

static void ZEND_FASTCALL msIsReadonlyPropertyFetch(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *expr;
	bool allowOnlyOnThis;
	zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
	if (UNEXPECTED(propertyFetchCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_OBJECT_OF_CLASS(expr, propertyFetchCe)
		Z_PARAM_BOOL(allowOnlyOnThis)
	ZEND_PARSE_PARAMETERS_END();
	PT_MS_RETURN_BOOL(PT_THIS.isReadonlyPropertyFetch(Z_OBJ_P(expr), allowOnlyOnThis, out_));
}

static void ZEND_FASTCALL msIsInClass(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_MS_RETURN_BOOL(PT_THIS.isInClass(out_));
}

static void ZEND_FASTCALL msGetClassReflection(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getClassReflection());
}

static void ZEND_FASTCALL msGetFunction(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getFunction());
}

static void ZEND_FASTCALL msGetNamespace(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getNamespace());
}

static void ZEND_FASTCALL msCanAnyVariableExist(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_MS_RETURN_BOOL(PT_THIS.canAnyVariableExist(out_));
}

static void ZEND_FASTCALL msHasVariableType(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *variableName;
	if (!zp::parse<zp::Str>(execute_data, variableName)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.hasVariableType(variableName));
}

static void ZEND_FASTCALL msIsInAnonymousFunction(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_BOOL(PT_THIS.isInAnonymousFunction());
}

static void ZEND_FASTCALL msGetNodeKey(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *node;
	if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.getNodeKey(Z_OBJ_P(node)));
}

static void ZEND_FASTCALL msHasExpressionType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *node;
	if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.hasExpressionType(Z_OBJ_P(node)));
}

static void ZEND_FASTCALL msIsInFirstLevelStatement(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_BOOL(PT_THIS.isInFirstLevelStatement());
}

static void ZEND_FASTCALL msToWalkScope(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.toWalkScope());
}

static void ZEND_FASTCALL msGetVariableType(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *variableName;
	if (!zp::parse<zp::Str>(execute_data, variableName)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.getVariableType(variableName));
}

/* the one Expr argument of getType() & co. */
/* a nullable object / string / array parameter as a zval the handle class takes */
#define PT_MS_OBJ_ZVAL(name) \
	zval name##Zv; \
	if ((name) == NULL) { \
		ZVAL_NULL(&name##Zv); \
		(name) = &name##Zv; \
	}

#define PT_MS_STR_ZVAL(name) \
	zval name##Zv; \
	if ((name) == NULL) { \
		ZVAL_NULL(&name##Zv); \
	} else { \
		ZVAL_STR(&name##Zv, (name)); \
	}

#define PT_MS_ARRAY_ZVAL(name) \
	zval name##Zv; \
	if ((name) == NULL) { \
		ZVAL_EMPTY_ARRAY(&name##Zv); \
		(name) = &name##Zv; \
	}

/* The parameter is declared as PHPStan\Reflection\ClassReflection, so the
 * check resolves that NAME: in production it is the shadowing class itself,
 * under the prefixed differential activation the PHP twin (the native class
 * lives beside it as PHPStanTurbo\ClassReflection). Resolved once and
 * cached; the class is loaded by the time a scope is entered. */
static zend_class_entry *msClassReflectionCe()
{
	static zend_class_entry *cached = NULL;
	if (EXPECTED(cached != NULL)) return cached;
	zend_string *name = zend_string_init("PHPStan\\Reflection\\ClassReflection", sizeof("PHPStan\\Reflection\\ClassReflection") - 1, 0);
	cached = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(name);
	return cached;
}

#define PT_MS_PARSE_CLASS_REFLECTION(var) \
	zval *var; \
	do { \
		zend_class_entry *classReflectionCe_ = msClassReflectionCe(); \
		if (UNEXPECTED(classReflectionCe_ == NULL)) { \
			zend_throw_error(NULL, "phpstan_turbo: PHPStan\\Reflection\\ClassReflection is not loaded"); \
			RETURN_THROWS(); \
		} \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT_OF_CLASS(var, classReflectionCe_) \
		ZEND_PARSE_PARAMETERS_END(); \
	} while (0)

#define PT_MS_PARSE_EXPR(var) \
	zval *var; \
	do { \
		zend_class_entry *exprCe_ = pt_class(PT_CLASS_EXPR); \
		if (UNEXPECTED(exprCe_ == NULL)) { \
			RETURN_THROWS(); \
		} \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT_OF_CLASS(var, exprCe_) \
		ZEND_PARSE_PARAMETERS_END(); \
	} while (0)

static void ZEND_FASTCALL msGetType(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(node);
	PT_RETURN_VAL(PT_THIS.getType(Z_OBJ_P(node)));
}

static void ZEND_FASTCALL msDuplicateWith(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *expressionTypes, *nativeExpressionTypes, *conditionalExpressions, *currentlyAssignedExpressions, *currentlyAllowedUndefinedExpressions, *inFunctionCallsStack;
	bool inFirstLevelStatement, afterExtractCall;
	ZEND_PARSE_PARAMETERS_START(8, 8)
		Z_PARAM_ARRAY(expressionTypes)
		Z_PARAM_ARRAY(nativeExpressionTypes)
		Z_PARAM_ARRAY(conditionalExpressions)
		Z_PARAM_ARRAY(currentlyAssignedExpressions)
		Z_PARAM_ARRAY(currentlyAllowedUndefinedExpressions)
		Z_PARAM_ARRAY(inFunctionCallsStack)
		Z_PARAM_BOOL(inFirstLevelStatement)
		Z_PARAM_BOOL(afterExtractCall)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.duplicateWith(expressionTypes, nativeExpressionTypes, conditionalExpressions, currentlyAssignedExpressions, currentlyAllowedUndefinedExpressions, inFunctionCallsStack, inFirstLevelStatement, afterExtractCall));
}

static void ZEND_FASTCALL msObtainResultForNode(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(node);
	PT_RETURN_VAL(PT_THIS.obtainResultForNode(Z_OBJ_P(node)));
}

static void ZEND_FASTCALL msWithTemplateArgumentConstraints(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *constraints;
	if (!zp::parse<zp::ObjOrNull>(execute_data, constraints)) RETURN_THROWS();
	zval nullZv;
	if (constraints == NULL) {
		ZVAL_NULL(&nullZv);
		constraints = &nullZv;
	}
	PT_RETURN_VAL(PT_THIS.withTemplateArgumentConstraints(constraints));
}

static void ZEND_FASTCALL msWithoutMemoizedTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.withoutMemoizedTypes());
}

static void ZEND_FASTCALL msGetNativeType(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(expr);
	PT_RETURN_VAL(PT_THIS.getNativeType(expr));
}

static void ZEND_FASTCALL msDoNotTreatPhpDocTypesAsCertain(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.doNotTreatPhpDocTypesAsCertain());
}

/* the one Name argument of resolveName() / resolveTypeByName() */
#define PT_MS_PARSE_NAME(var) \
	zval *var; \
	do { \
		zend_class_entry *nameCe_ = pt_class(PT_CLASS_NAME); \
		if (UNEXPECTED(nameCe_ == NULL)) { \
			RETURN_THROWS(); \
		} \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT_OF_CLASS(var, nameCe_) \
		ZEND_PARSE_PARAMETERS_END(); \
	} while (0)

static void ZEND_FASTCALL msResolveName(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_NAME(name);
	PT_RETURN_VAL(PT_THIS.resolveName(Z_OBJ_P(name)));
}

static void ZEND_FASTCALL msResolveTypeByName(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_NAME(name);
	PT_RETURN_VAL(PT_THIS.resolveTypeByName(Z_OBJ_P(name)));
}

static void ZEND_FASTCALL msGetParentScope(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getParentScope());
}

static void ZEND_FASTCALL msPushInFunctionCall(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *reflection, *parameter;
	bool rememberTypes;
	if (!zp::parse<zp::Zval, zp::ObjOrNull, zp::Bool>(execute_data, reflection, parameter, rememberTypes)) RETURN_THROWS();
	ZVAL_DEREF(reflection);
	zval nullZv;
	if (parameter == NULL) {
		ZVAL_NULL(&nullZv);
		parameter = &nullZv;
	}
	PT_RETURN_VAL(PT_THIS.pushInFunctionCall(reflection, parameter, rememberTypes));
}

static void ZEND_FASTCALL msPopInFunctionCall(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.popInFunctionCall());
}

static void ZEND_FASTCALL msGetPhpVersion(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getPhpVersion());
}

static void ZEND_FASTCALL msIsParameterValueNullable(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *parameter;
	zend_class_entry *paramCe = pt_class(PT_CLASS_PARAM);
	if (UNEXPECTED(paramCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(parameter, paramCe)
	ZEND_PARSE_PARAMETERS_END();
	PT_MS_RETURN_BOOL(PT_THIS.isParameterValueNullable(Z_OBJ_P(parameter), out_));
}

static void ZEND_FASTCALL msGetFunctionType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	bool isNullable, isVariadic;
	if (!zp::parse<zp::Zval, zp::Bool, zp::Bool>(execute_data, type, isNullable, isVariadic)) RETURN_THROWS();
	ZVAL_DEREF(type);
	PT_RETURN_VAL(PT_THIS.getFunctionType(type, isNullable, isVariadic));
}

static void ZEND_FASTCALL msGetCurrentExpressionResultStorage(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getCurrentExpressionResultStorage());
}

/* the ($functionLike, ?array, ?array) argument list of the two
 * *WithoutReflection() entries */
#define PT_MS_PARSE_FUNCTION_ENTRY(var, classIdx) \
	zval *var, *callableParameters = NULL, *nativeCallableParameters = NULL; \
	do { \
		zend_class_entry *ce_ = pt_class(classIdx); \
		if (UNEXPECTED(ce_ == NULL)) { \
			RETURN_THROWS(); \
		} \
		ZEND_PARSE_PARAMETERS_START(3, 3) \
			Z_PARAM_OBJECT_OF_CLASS(var, ce_) \
			Z_PARAM_ARRAY_OR_NULL(callableParameters) \
			Z_PARAM_ARRAY_OR_NULL(nativeCallableParameters) \
		ZEND_PARSE_PARAMETERS_END(); \
	} while (0)

static void ZEND_FASTCALL msEnterAnonymousFunctionWithoutReflection(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_FUNCTION_ENTRY(closure, PT_CLASS_CLOSURE_EXPR);
	PT_RETURN_VAL(PT_THIS.enterAnonymousFunctionWithoutReflection(Z_OBJ_P(closure), callableParameters, nativeCallableParameters));
}

static void ZEND_FASTCALL msEnterArrowFunctionWithoutReflection(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_FUNCTION_ENTRY(arrowFunction, PT_CLASS_ARROW_FUNCTION);
	PT_RETURN_VAL(PT_THIS.enterArrowFunctionWithoutReflection(Z_OBJ_P(arrowFunction), callableParameters, nativeCallableParameters));
}

static void ZEND_FASTCALL msAssignVariable(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *variableName;
	zval *type, *nativeType, *certainty, *intertwinedPropagatedFrom = NULL;
	if (!zp::parse<zp::Str, zp::Obj, zp::Obj, zp::Obj, zp::Opt<zp::Arr>>(execute_data, variableName, type, nativeType, certainty, intertwinedPropagatedFrom)) RETURN_THROWS();
	PT_MS_ARRAY_ZVAL(intertwinedPropagatedFrom);
	PT_RETURN_VAL(PT_THIS.assignVariable(variableName, type, nativeType, certainty, intertwinedPropagatedFrom));
}

static void ZEND_FASTCALL msAssignExpression(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *expr, *type, *nativeType;
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(3, 3)
		Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
		Z_PARAM_OBJECT(type)
		Z_PARAM_OBJECT(nativeType)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.assignExpression(Z_OBJ_P(expr), type, nativeType));
}

static void ZEND_FASTCALL msSpecifyExpressionType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *expr, *type, *nativeType, *certainty;
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(4, 4)
		Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
		Z_PARAM_OBJECT(type)
		Z_PARAM_OBJECT(nativeType)
		Z_PARAM_OBJECT(certainty)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.specifyExpressionType(Z_OBJ_P(expr), type, nativeType, certainty));
}

static void ZEND_FASTCALL msInvalidateExpression(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *expressionToInvalidate, *invalidatingClass = NULL;
	bool requireMoreCharacters = false, keepPropertyFetches = false;
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(1, 4)
		Z_PARAM_OBJECT_OF_CLASS(expressionToInvalidate, exprCe)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(requireMoreCharacters)
		Z_PARAM_OBJECT_OR_NULL(invalidatingClass)
		Z_PARAM_BOOL(keepPropertyFetches)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.invalidateExpression(expressionToInvalidate, requireMoreCharacters, invalidatingClass, keepPropertyFetches));
}

static void ZEND_FASTCALL msFilterByTruthyValue(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(expr);
	PT_RETURN_VAL(PT_THIS.filterByValue(Z_OBJ_P(expr), true));
}

static void ZEND_FASTCALL msFilterByFalseyValue(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(expr);
	PT_RETURN_VAL(PT_THIS.filterByValue(Z_OBJ_P(expr), false));
}

static void ZEND_FASTCALL msApplySpecifiedTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *specifiedTypes;
	if (!zp::parse<zp::Obj>(execute_data, specifiedTypes)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.applySpecifiedTypes(specifiedTypes));
}

static void ZEND_FASTCALL msFilterTypeWithMethod(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *typeWithMethod;
	zend_string *methodName;
	if (!zp::parse<zp::Obj, zp::Str>(execute_data, typeWithMethod, methodName)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.filterTypeWithMethod(typeWithMethod, methodName));
}

/* named handlers: the direct entries below identify the native bodies by
 * them */
static void ZEND_FASTCALL msIsInExpressionAssign(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(expr);
	PT_MS_RETURN_BOOL(PT_THIS.isInExpressionAssign(Z_OBJ_P(expr), out_));
}

static void ZEND_FASTCALL msIsInTrait(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_MS_RETURN_BOOL(PT_THIS.isInTrait(out_));
}

/* {{{ direct entries for native callers (ClassStatementsGatherer.cpp): the
 * native body while the scope's method is still the native handler (a
 * MutatingScope, or a subclass not overriding it such as NodeCallbackScope),
 * the method by name otherwise */

static bool msCallBool(zend_object *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_type_call(scope, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_mutating_scope_is_in_expression_assign(zend_object *scope, zend_object *expr, bool &out)
{
	if (EXPECTED(pt_type_method_is(scope, PT_LC("isinexpressionassign"), msIsInExpressionAssign))) return MutatingScope(scope).isInExpressionAssign(expr, out);
	zval exprZv;
	ZVAL_OBJ(&exprZv, expr);
	return msCallBool(scope, PT_LC("isinexpressionassign"), 1, &exprZv, out);
}

bool pt_mutating_scope_is_in_trait(zend_object *scope, bool &out)
{
	if (EXPECTED(pt_type_method_is(scope, PT_LC("isintrait"), msIsInTrait))) return MutatingScope(scope).isInTrait(out);
	return msCallBool(scope, PT_LC("isintrait"), 0, NULL, out);
}

bool pt_mutating_scope_is_in_anonymous_function(zend_object *scope, bool &out)
{
	if (EXPECTED(pt_type_method_is(scope, PT_LC("isinanonymousfunction"), msIsInAnonymousFunction))) {
		out = MutatingScope(scope).isInAnonymousFunction();
		return true;
	}
	return msCallBool(scope, PT_LC("isinanonymousfunction"), 0, NULL, out);
}

zv::Val pt_mutating_scope_get_function(zend_object *scope)
{
	if (EXPECTED(pt_type_method_is(scope, PT_LC("getfunction"), msGetFunction))) return MutatingScope(scope).getFunction();
	return pt_type_call(scope, PT_LC("getfunction"), 0, NULL);
}

/* the engine ports' reads (Engine.h): exactly a MutatingScope takes the
 * native body without a lookup, a subclass inheriting the method too */
zv::Val pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(zend_object *scope)
{
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("donottreatphpdoctypesascertain"), msDoNotTreatPhpDocTypesAsCertain))) return MutatingScope(scope).doNotTreatPhpDocTypesAsCertain();
	return pt_type_call(scope, PT_LC("donottreatphpdoctypesascertain"), 0, NULL);
}

zv::Val pt_mutating_scope_has_variable_type(zend_object *scope, zend_string *variableName)
{
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("hasvariabletype"), msHasVariableType))) return MutatingScope(scope).hasVariableType(variableName);
	zval nameZv;
	ZVAL_STR(&nameZv, variableName);
	return pt_type_call(scope, PT_LC("hasvariabletype"), 1, &nameZv);
}

zv::Val pt_mutating_scope_get_variable_type(zend_object *scope, zend_string *variableName)
{
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("getvariabletype"), msGetVariableType))) return MutatingScope(scope).getVariableType(variableName);
	zval nameZv;
	ZVAL_STR(&nameZv, variableName);
	return pt_type_call(scope, PT_LC("getvariabletype"), 1, &nameZv);
}

zv::Val pt_mutating_scope_apply_specified_types(zend_object *scope, zval *specifiedTypes)
{
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("applyspecifiedtypes"), msApplySpecifiedTypes))) return MutatingScope(scope).applySpecifiedTypes(specifiedTypes);
	return pt_type_call(scope, PT_LC("applyspecifiedtypes"), 1, specifiedTypes);
}

/* the scope reads of the analyser value classes (IssetabilityDescriptor.cpp),
 * the same way ($node / $expr an Expr) */
zend_long pt_mutating_scope_has_expression_type(zend_object *scope, zval *node)
{
	/* the PT_TRI_* value straight out of the singleton the body answers with */
	zv::Val result = pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("hasexpressiontype"), msHasExpressionType, 1, node, [&]() { return MutatingScope(scope).hasExpressionType(Z_OBJ_P(node)); });
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

zv::Val pt_mutating_scope_get_type(zend_object *scope, zval *node)
{
	return pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("gettype"), msGetType, 1, node, [&]() { return MutatingScope(scope).getType(Z_OBJ_P(node)); });
}

zv::Val pt_mutating_scope_get_native_type(zend_object *scope, zval *expr)
{
	return pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("getnativetype"), msGetNativeType, 1, expr, [&]() { return MutatingScope(scope).getNativeType(expr); });
}

/* the template-inference and merge entries of the statement results
 * (InternalStatementResult.cpp): addTemplateArgumentConstraints() and
 * mergeWith() by their named handlers, getTemplateArgumentConstraints() by
 * its generated one; $constraints / $otherScope IS_NULL or NULL for null */
static void ZEND_FASTCALL msAddTemplateArgumentConstraints(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *constraints;
	if (!zp::parse<zp::ObjOrNull>(execute_data, constraints)) RETURN_THROWS();
	zval nullZv;
	if (constraints == NULL) {
		ZVAL_NULL(&nullZv);
		constraints = &nullZv;
	}
	PT_RETURN_VAL(PT_THIS.addTemplateArgumentConstraints(constraints));
}

static void ZEND_FASTCALL msMergeWith(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *otherScope;
	bool preserveVacuousConditionals = false;
	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(otherScope, pt_ce_mutating_scope)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(preserveVacuousConditionals)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.mergeWith(otherScope, preserveVacuousConditionals));
}

zv::Val pt_mutating_scope_get_template_argument_constraints(zend_object *scope)
{
	return pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("gettemplateargumentconstraints"), &reg::detail::Bound<&MutatingScope::getTemplateArgumentConstraints>::handle, 0, NULL, [&]() { return MutatingScope(scope).getTemplateArgumentConstraints(); });
}

zv::Val pt_mutating_scope_add_template_argument_constraints(zend_object *scope, zval *constraints)
{
	zval nullZv;
	if (constraints == NULL) {
		ZVAL_NULL(&nullZv);
		constraints = &nullZv;
	}
	return pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("addtemplateargumentconstraints"), msAddTemplateArgumentConstraints, 1, constraints, [&]() { return MutatingScope(scope).addTemplateArgumentConstraints(constraints); });
}

/* the template-frame reads of TemplateArgumentFrame::returnTypeOfCall()
 * (TemplateArgumentFrame.cpp) */
zv::Val pt_mutating_scope_get_current_template_argument_frame(zend_object *scope)
{
	return pt_this_call(scope, scope->ce == pt_ce_mutating_scope, PT_LC("getcurrenttemplateargumentframe"), &reg::detail::Bound<&MutatingScope::getCurrentTemplateArgumentFrame>::handle, 0, NULL, [&]() { return MutatingScope(scope).getCurrentTemplateArgumentFrame(); });
}

bool pt_mutating_scope_native_types_promoted(zend_object *scope, bool &out)
{
	return MutatingScope::scopeNativeTypesPromoted(scope, out);
}

zv::Val pt_mutating_scope_merge_with(zend_object *scope, zval *otherScope, bool preserveVacuousConditionals)
{
	if (otherScope != NULL && Z_TYPE_P(otherScope) == IS_NULL) {
		otherScope = NULL;
	}
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("mergewith"), msMergeWith))) {
		/* the handler's Z_PARAM_OBJECT_OF_CLASS_OR_NULL check */
		if (EXPECTED(otherScope == NULL || instanceof_function(Z_OBJCE_P(otherScope), pt_ce_mutating_scope))) return MutatingScope(scope).mergeWith(otherScope, preserveVacuousConditionals);
	}
	zval argv[2];
	if (otherScope != NULL) {
		ZVAL_COPY_VALUE(&argv[0], otherScope);
	} else {
		ZVAL_NULL(&argv[0]);
	}
	ZVAL_BOOL(&argv[1], preserveVacuousConditionals);
	return pt_type_call(scope, PT_LC("mergewith"), 2, argv);
}

/* the entries the helper ports call (MethodCallReturnTypeHelper.cpp,
 * MethodThrowPointHelper.cpp, the narrowing helpers and augments,
 * TypeSpecifier.cpp): the same contract, with the exact class tested before
 * the function-table probe */

static void ZEND_FASTCALL msGetStateType(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_MS_PARSE_EXPR(expr);
	PT_RETURN_VAL(PT_THIS.getStateType(Z_OBJ_P(expr)));
}

static void ZEND_FASTCALL msSpecifyTypesOfNewWorldHandlerNode(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *node, *context;
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_OBJECT_OF_CLASS(node, exprCe)
		Z_PARAM_OBJECT(context)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.specifyTypesOfNewWorldHandlerNode(Z_OBJ_P(node), context));
}

static zend_always_inline bool msNative(zend_object *scope, const char *lcname, size_t len, zif_handler handler)
{
	return EXPECTED(scope->ce == pt_ce_mutating_scope) || pt_type_method_is(scope, lcname, len, handler);
}

zv::Val pt_mutating_scope_filter_type_with_method(zend_object *scope, zval *typeWithMethod, zend_string *methodName)
{
	if (EXPECTED(Z_TYPE_P(typeWithMethod) == IS_OBJECT) && msNative(scope, PT_LC("filtertypewithmethod"), msFilterTypeWithMethod)) return MutatingScope(scope).filterTypeWithMethod(typeWithMethod, methodName);
	zv::Args args{typeWithMethod, methodName};
	return pt_type_call(scope, PT_LC("filtertypewithmethod"), 2, args);
}

zv::Val pt_mutating_scope_get_method_reflection(zend_object *scope, zval *typeWithMethod, zend_string *methodName)
{
	if (EXPECTED(Z_TYPE_P(typeWithMethod) == IS_OBJECT) && msNative(scope, PT_LC("getmethodreflection"), &reg::detail::Bound<&MutatingScope::getMethodReflection, zp::Obj, zp::Str>::handle)) return MutatingScope(scope).getMethodReflection(typeWithMethod, methodName);
	zv::Args args{typeWithMethod, methodName};
	return pt_type_call(scope, PT_LC("getmethodreflection"), 2, args);
}

zv::Val pt_mutating_scope_get_state_type(zend_object *scope, zend_object *expr)
{
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) return zv::Val();
	if (EXPECTED(instanceof_function(expr->ce, exprCe)) && msNative(scope, PT_LC("getstatetype"), msGetStateType)) return MutatingScope(scope).getStateType(expr);
	zval exprZv;
	ZVAL_OBJ(&exprZv, expr);
	return pt_type_call(scope, PT_LC("getstatetype"), 1, &exprZv);
}

zv::Val pt_mutating_scope_get_conditional_expressions(zend_object *scope)
{
	if (EXPECTED(msNative(scope, PT_LC("getconditionalexpressions"), &reg::detail::Bound<&MutatingScope::getConditionalExpressions>::handle))) return MutatingScope(scope).getConditionalExpressions();
	return pt_type_call(scope, PT_LC("getconditionalexpressions"), 0, NULL);
}

zv::Val pt_mutating_scope_get_current_expression_result_storage(zend_object *scope)
{
	if (EXPECTED(msNative(scope, PT_LC("getcurrentexpressionresultstorage"), &reg::detail::Bound<&MutatingScope::getCurrentExpressionResultStorage>::handle))) return MutatingScope(scope).getCurrentExpressionResultStorage();
	return pt_type_call(scope, PT_LC("getcurrentexpressionresultstorage"), 0, NULL);
}

zv::Val pt_mutating_scope_resolve_type_by_name(zend_object *scope, zend_object *name)
{
	zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
	if (UNEXPECTED(nameCe == NULL)) return zv::Val();
	if (EXPECTED(instanceof_function(name->ce, nameCe)) && msNative(scope, PT_LC("resolvetypebyname"), msResolveTypeByName)) return MutatingScope(scope).resolveTypeByName(name);
	zval nameZv;
	ZVAL_OBJ(&nameZv, name);
	return pt_type_call(scope, PT_LC("resolvetypebyname"), 1, &nameZv);
}

zv::Val pt_mutating_scope_specify_types_of_new_world_handler_node(zend_object *scope, zend_object *node, zval *context)
{
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) return zv::Val();
	if (EXPECTED(instanceof_function(node->ce, exprCe) && Z_TYPE_P(context) == IS_OBJECT) && msNative(scope, PT_LC("specifytypesofnewworldhandlernode"), msSpecifyTypesOfNewWorldHandlerNode)) return MutatingScope(scope).specifyTypesOfNewWorldHandlerNode(node, context);
	zv::Args args{node, context};
	return pt_type_call(scope, PT_LC("specifytypesofnewworldhandlernode"), 2, args);
}
/* }}} */

/* {{{ direct entries for the NodeScopeResolver / StatementsHandler /
 * NonNullabilityHelper ports: exactly a MutatingScope takes the native body,
 * anything else (NodeCallbackScope, a third-party subclass) the method
 * through its class entry */

namespace {

inline bool msExact(zend_object *scope)
{
	return EXPECTED(scope->ce == pt_ce_mutating_scope);
}

[[nodiscard]] bool msCallBoolResult(zend_object *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_type_call(scope, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

} // namespace

zv::Val pt_mutating_scope_to_node_callback_scope(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).toNodeCallbackScope();
	return pt_type_call(scope, PT_LC("tonodecallbackscope"), 0, NULL);
}

bool pt_mutating_scope_push_expression_result_storage(zend_object *scope, zval *storage)
{
	if (msExact(scope)) return MutatingScope(scope).pushExpressionResultStorage(storage);
	return !pt_type_call(scope, PT_LC("pushexpressionresultstorage"), 1, storage).isUndef();
}

bool pt_mutating_scope_pop_expression_result_storage(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).popExpressionResultStorage();
	return !pt_type_call(scope, PT_LC("popexpressionresultstorage"), 0, NULL).isUndef();
}

zv::Val pt_mutating_scope_exit_first_level_statements(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).exitFirstLevelStatements();
	return pt_type_call(scope, PT_LC("exitfirstlevelstatements"), 0, NULL);
}

zv::Val pt_mutating_scope_with_template_argument_frame(zend_object *scope, zval *frame)
{
	if (msExact(scope)) return MutatingScope(scope).withTemplateArgumentFrame(frame);
	return pt_type_call(scope, PT_LC("withtemplateargumentframe"), 1, frame);
}

zv::Val pt_mutating_scope_with_template_argument_constraints(zend_object *scope, zval *constraints)
{
	if (msExact(scope)) return MutatingScope(scope).withTemplateArgumentConstraints(constraints);
	return pt_type_call(scope, PT_LC("withtemplateargumentconstraints"), 1, constraints);
}

zv::Val pt_mutating_scope_get_tracked_expression_type(zend_object *scope, zend_object *expr)
{
	if (msExact(scope)) return MutatingScope(scope).getTrackedExpressionType(expr);
	zv::Args argv{expr};
	return pt_type_call(scope, PT_LC("gettrackedexpressiontype"), 1, argv);
}

bool pt_mutating_scope_equals(zend_object *scope, zend_object *otherScope, bool &out)
{
	if (msExact(scope)) return MutatingScope(scope).equals(otherScope, out);
	zv::Args argv{otherScope};
	return msCallBoolResult(scope, PT_LC("equals"), 1, argv, out);
}

zv::Val pt_mutating_scope_generalize_with(zend_object *scope, zend_object *otherScope)
{
	if (msExact(scope)) return MutatingScope(scope).generalizeWith(otherScope, NULL);
	zv::Args argv{otherScope};
	return pt_type_call(scope, PT_LC("generalizewith"), 1, argv);
}

zv::Val pt_mutating_scope_get_differing_variable_roots(zend_object *scope, zend_object *other)
{
	if (msExact(scope)) return MutatingScope(scope).getDifferingVariableRoots(other);
	zv::Args argv{other};
	return pt_type_call(scope, PT_LC("getdifferingvariableroots"), 1, argv);
}

zv::Val pt_mutating_scope_with_recorded_statement_delta(zend_object *scope, zend_object *recordedEntry, zend_object *recordedExit)
{
	if (msExact(scope)) return MutatingScope(scope).withRecordedStatementDelta(recordedEntry, recordedExit);
	zv::Args argv{recordedEntry, recordedExit};
	return pt_type_call(scope, PT_LC("withrecordedstatementdelta"), 2, argv);
}

zv::Val pt_mutating_scope_set_allowed_undefined_expression(zend_object *scope, zend_object *expr)
{
	if (msExact(scope)) return MutatingScope(scope).setAllowedUndefinedExpression(expr);
	zv::Args argv{expr};
	return pt_type_call(scope, PT_LC("setallowedundefinedexpression"), 1, argv);
}

zv::Val pt_mutating_scope_unset_allowed_undefined_expression(zend_object *scope, zend_object *expr)
{
	if (msExact(scope)) return MutatingScope(scope).unsetAllowedUndefinedExpression(expr);
	zv::Args argv{expr};
	return pt_type_call(scope, PT_LC("unsetallowedundefinedexpression"), 1, argv);
}

zv::Val pt_mutating_scope_get_anonymous_function_return_type(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).getAnonymousFunctionReturnType();
	return pt_type_call(scope, PT_LC("getanonymousfunctionreturntype"), 0, NULL);
}

bool pt_mutating_scope_is_in_class(zend_object *scope, bool &out)
{
	if (msExact(scope)) return MutatingScope(scope).isInClass(out);
	return msCallBoolResult(scope, PT_LC("isinclass"), 0, NULL, out);
}

zv::Val pt_mutating_scope_get_class_reflection(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).getClassReflection();
	return pt_type_call(scope, PT_LC("getclassreflection"), 0, NULL);
}

zv::Val pt_mutating_scope_get_trait_reflection(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).getTraitReflection();
	return pt_type_call(scope, PT_LC("gettraitreflection"), 0, NULL);
}

zv::Val pt_mutating_scope_get_file(zend_object *scope)
{
	if (msExact(scope)) return MutatingScope(scope).getFile();
	return pt_type_call(scope, PT_LC("getfile"), 0, NULL);
}

bool pt_mutating_scope_can_any_variable_exist(zend_object *scope, bool &out)
{
	if (msExact(scope)) return MutatingScope(scope).canAnyVariableExist(out);
	return msCallBoolResult(scope, PT_LC("cananyvariableexist"), 0, NULL, out);
}

zv::Val pt_mutating_scope_assign_variable(zend_object *scope, zend_string *variableName, zval *type, zval *nativeType, zval *certainty)
{
	if (msExact(scope)) {
		zval intertwinedPropagatedFrom;
		ZVAL_EMPTY_ARRAY(&intertwinedPropagatedFrom);
		return MutatingScope(scope).assignVariable(variableName, type, nativeType, certainty, &intertwinedPropagatedFrom);
	}
	zv::Args argv{variableName, type, nativeType, certainty};
	return pt_type_call(scope, PT_LC("assignvariable"), 4, argv);
}

zv::Val pt_mutating_scope_assign_expression(zend_object *scope, zend_object *expr, zval *type, zval *nativeType)
{
	if (msExact(scope)) return MutatingScope(scope).assignExpression(expr, type, nativeType);
	zv::Args argv{expr, type, nativeType};
	return pt_type_call(scope, PT_LC("assignexpression"), 3, argv);
}

zv::Val pt_mutating_scope_specify_expression_type(zend_object *scope, zend_object *expr, zval *type, zval *nativeType, zval *certainty)
{
	if (msExact(scope)) return MutatingScope(scope).specifyExpressionType(expr, type, nativeType, certainty);
	zv::Args argv{expr, type, nativeType, certainty};
	return pt_type_call(scope, PT_LC("specifyexpressiontype"), 4, argv);
}

zv::Val pt_mutating_scope_invalidate_expression(zend_object *scope, zval *expressionToInvalidate)
{
	if (msExact(scope)) return MutatingScope(scope).invalidateExpression(expressionToInvalidate, false, NULL, false);
	return pt_type_call(scope, PT_LC("invalidateexpression"), 1, expressionToInvalidate);
}

/* }}} */

void pt_register_mutating_scope()
{
	using namespace pt_ms;

	reg::Class cls("PHPStan\\Analyser\\MutatingScope");
	/* not final: NodeCallbackScope extends it in PHP (and a third party
	 * may too), so every method stays dispatched through the object's
	 * class entry */
	ptdecl::MutatingScope::declareClass(cls);

	/* {{{ the slots, in the twin's declaration order (the PT_MS_PROP_*
	 * enum): the class-body properties with their defaults, then the
	 * promoted constructor properties, uninitialized until the
	 * constructor writes them */
	cls.property("resolvedTypes", ZEND_ACC_PUBLIC, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("nodeCallbackScope", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, "self");
	cls.property("namespace", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("scopeOutOfFirstLevelStatement", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, "self");
	cls.property("scopeWithPromotedNativeTypes", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, "self");
	cls.property("container", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, container);
	cls.property("scopeFactory", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, 0, internalScopeFactory);
	cls.property("reflectionProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, reflectionProvider);
	cls.property("initializerExprTypeResolver", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, initializerExprTypeResolver);
	cls.property("expressionTypeResolverExtensions", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, extensionsCollection);
	cls.property("exprPrinter", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, exprPrinter);
	cls.property("typeSpecifier", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, typeSpecifier);
	cls.property("propertyReflectionFinder", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, propertyReflectionFinder);
	cls.property("parser", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, parser);
	cls.property("constantResolver", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, constantResolver);
	cls.property("expressionResultStorageStack", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, expressionResultStorageStack);
	cls.property("context", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, 0, scopeContext);
	cls.property("phpVersion", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, phpVersion);
	cls.property("attributeReflectionFactory", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, attributeReflectionFactory);
	cls.property("configuredPhpVersionRangeHelper", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, configuredPhpVersionRangeHelper);
	cls.property("nodeCallback", ZEND_ACC_PRIVATE, reg::PropertyKind::Null, 0);
	cls.property("declareStrictTypes", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_BOOL);
	cls.property("function", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, phpFunctionFromParserNodeReflection);
	cls.property("expressionTypes", ZEND_ACC_PUBLIC, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("nativeExpressionTypes", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("conditionalExpressions", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("inClosureBindScopeClasses", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("anonymousFunctionReflection", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, closureType);
	cls.property("inFirstLevelStatement", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_BOOL);
	cls.property("currentlyAssignedExpressions", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("currentlyAllowedUndefinedExpressions", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("inFunctionCallsStack", ZEND_ACC_PUBLIC, reg::PropertyKind::Typed, MAY_BE_ARRAY);
	cls.property("afterExtractCall", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_BOOL);
	cls.property("parentScope", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, "self");
	cls.property("nativeTypesPromoted", ZEND_ACC_PUBLIC, reg::PropertyKind::Typed, MAY_BE_BOOL);
	cls.property("templateArgumentFrame", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_NULL, templateArgumentFrame);
	cls.property("templateArgumentConstraints", ZEND_ACC_PROTECTED, reg::PropertyKind::Typed, MAY_BE_NULL, templateArgumentConstraints);
	/* the twin's `private static array $globalConstantFetchKeys = []`, which
	 * getGlobalConstantType() memoizes into on both sides. Declared after the
	 * instance properties even though the twin declares it before them: a
	 * static takes no OBJ_PROP_NUM slot, and keeping it out of the run above
	 * keeps that run a literal transcript of the PT_MS_PROP_* enum */
	cls.privateStaticTypedArrayPropertyDefaultEmpty("globalConstantFetchKeys");
	/* }}} */

	cls.method("__construct", reg::Public, 15, {
		reg::obj("container", container),
		reg::obj("scopeFactory", internalScopeFactory),
		reg::obj("reflectionProvider", reflectionProvider),
		reg::obj("initializerExprTypeResolver", initializerExprTypeResolver),
		reg::obj("expressionTypeResolverExtensions", extensionsCollection),
		reg::obj("exprPrinter", exprPrinter),
		reg::obj("typeSpecifier", typeSpecifier),
		reg::obj("propertyReflectionFinder", propertyReflectionFinder),
		reg::obj("parser", parser),
		reg::obj("constantResolver", constantResolver),
		reg::obj("expressionResultStorageStack", expressionResultStorageStack),
		reg::obj("context", scopeContext),
		reg::obj("phpVersion", phpVersion),
		reg::obj("attributeReflectionFactory", attributeReflectionFactory),
		reg::obj("configuredPhpVersionRangeHelper", configuredPhpVersionRangeHelper),
		reg::withDefault(reg::any("nodeCallback"), "null"),
		reg::withDefault(reg::boolArg("declareStrictTypes"), "false"),
		reg::withDefault(reg::obj("function", phpFunctionFromParserNodeReflection, true), "null"),
		reg::withDefault(reg::stringArg("namespace", true), "null"),
		reg::withDefault(reg::arrayArg("expressionTypes"), "[]"),
		reg::withDefault(reg::arrayArg("nativeExpressionTypes"), "[]"),
		reg::withDefault(reg::arrayArg("conditionalExpressions"), "[]"),
		reg::withDefault(reg::arrayArg("inClosureBindScopeClasses"), "[]"),
		reg::withDefault(reg::obj("anonymousFunctionReflection", closureType, true), "null"),
		reg::withDefault(reg::boolArg("inFirstLevelStatement"), "true"),
		reg::withDefault(reg::arrayArg("currentlyAssignedExpressions"), "[]"),
		reg::withDefault(reg::arrayArg("currentlyAllowedUndefinedExpressions"), "[]"),
		reg::withDefault(reg::arrayArg("inFunctionCallsStack"), "[]"),
		reg::withDefault(reg::boolArg("afterExtractCall"), "false"),
		reg::withDefault(reg::obj("parentScope", self, true), "null"),
		reg::withDefault(reg::boolArg("nativeTypesPromoted"), "false"),
		reg::withDefault(reg::obj("templateArgumentFrame", templateArgumentFrame, true), "null"),
		reg::withDefault(reg::obj("templateArgumentConstraints", templateArgumentConstraints, true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		MutatingScope::ConstructArgs a = {};
		a.inFirstLevelStatement = true;
		ZEND_PARSE_PARAMETERS_START(15, 33)
			Z_PARAM_OBJECT(a.container)
			Z_PARAM_OBJECT(a.scopeFactory)
			Z_PARAM_OBJECT(a.reflectionProvider)
			Z_PARAM_OBJECT(a.initializerExprTypeResolver)
			Z_PARAM_OBJECT(a.expressionTypeResolverExtensions)
			Z_PARAM_OBJECT(a.exprPrinter)
			Z_PARAM_OBJECT(a.typeSpecifier)
			Z_PARAM_OBJECT(a.propertyReflectionFinder)
			Z_PARAM_OBJECT(a.parser)
			Z_PARAM_OBJECT(a.constantResolver)
			Z_PARAM_OBJECT(a.expressionResultStorageStack)
			Z_PARAM_OBJECT(a.context)
			Z_PARAM_OBJECT(a.phpVersion)
			Z_PARAM_OBJECT(a.attributeReflectionFactory)
			Z_PARAM_OBJECT(a.configuredPhpVersionRangeHelper)
			Z_PARAM_OPTIONAL
			Z_PARAM_ZVAL(a.nodeCallback)
			Z_PARAM_BOOL(a.declareStrictTypes)
			Z_PARAM_OBJECT_OR_NULL(a.function)
			Z_PARAM_STR_OR_NULL(a.ns)
			Z_PARAM_ARRAY(a.expressionTypes)
			Z_PARAM_ARRAY(a.nativeExpressionTypes)
			Z_PARAM_ARRAY(a.conditionalExpressions)
			Z_PARAM_ARRAY(a.inClosureBindScopeClasses)
			Z_PARAM_OBJECT_OR_NULL(a.anonymousFunctionReflection)
			Z_PARAM_BOOL(a.inFirstLevelStatement)
			Z_PARAM_ARRAY(a.currentlyAssignedExpressions)
			Z_PARAM_ARRAY(a.currentlyAllowedUndefinedExpressions)
			Z_PARAM_ARRAY(a.inFunctionCallsStack)
			Z_PARAM_BOOL(a.afterExtractCall)
			Z_PARAM_OBJECT_OR_NULL(a.parentScope)
			Z_PARAM_BOOL(a.nativeTypesPromoted)
			Z_PARAM_OBJECT_OR_NULL(a.templateArgumentFrame)
			Z_PARAM_OBJECT_OR_NULL(a.templateArgumentConstraints)
		ZEND_PARSE_PARAMETERS_END();
		if (a.nodeCallback != NULL) {
			ZVAL_DEREF(a.nodeCallback);
		}
		PT_THIS.construct(a);
	});

	cls.method<&MutatingScope::toNodeCallbackScope>(sigs::toNodeCallbackScope);

	cls.method(sigs::toWalkScope, msToWalkScope);

	cls.method<&MutatingScope::toMutatingScope>(sigs::toMutatingScope);

	cls.method(sigs::getFile, msGetFile);

	cls.method<&MutatingScope::getFileDescription>(sigs::getFileDescription);

	cls.method(sigs::isDeclareStrictTypes, msIsDeclareStrictTypes);

	cls.method<&MutatingScope::enterDeclareStrictTypes>(sigs::enterDeclareStrictTypes);

	cls.method<&MutatingScope::rememberConstructorScope>(sigs::rememberConstructorScope);

	cls.method(sigs::isReadonlyPropertyFetch, msIsReadonlyPropertyFetch);
	cls.method(sigs::isInClass, msIsInClass);

	cls.method(sigs::isInTrait, msIsInTrait);

	cls.method(sigs::getClassReflection, msGetClassReflection);

	cls.method<&MutatingScope::getTraitReflection>(sigs::getTraitReflection);

	cls.method(sigs::getFunction, msGetFunction);

	cls.method<&MutatingScope::getFunctionName>(sigs::getFunctionName);

	cls.method(sigs::getNamespace, msGetNamespace);

	/* a named handler: NodeCallbackScope overrides it (as it does
	 * pushInFunctionCall / popInFunctionCall / filterByTruthyValue /
	 * filterByFalseyValue), so a $this-dispatch must be able to identify
	 * the native body */
	cls.method(sigs::getParentScope, msGetParentScope);

	cls.method(sigs::canAnyVariableExist, msCanAnyVariableExist);

	cls.method<&MutatingScope::afterExtractCall>(sigs::afterExtractCall);

	cls.method<&MutatingScope::afterClearstatcacheCall>(sigs::afterClearstatcacheCall);

	cls.method<&MutatingScope::afterOpenSslCall, zp::Str>(sigs::afterOpenSslCall);

	cls.method<&MutatingScope::invalidateVolatileExpressions>(sigs::invalidateVolatileExpressions);

	cls.method(sigs::invalidateExistenceCheckExpressions, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionNames;
		zend_string *declaredSymbolName;
		if (!zp::parse<zp::Arr, zp::StrOrNull>(execute_data, functionNames, declaredSymbolName)) RETURN_THROWS();
		zval declaredSymbolNameZv = {};
		if (declaredSymbolName == NULL) {
			ZVAL_NULL(&declaredSymbolNameZv);
		} else {
			ZVAL_STR(&declaredSymbolNameZv, declaredSymbolName);
		}
		PT_RETURN_VAL(PT_THIS.invalidateExistenceCheckExpressions(functionNames, &declaredSymbolNameZv));
	});

	cls.method(sigs::hasVariableType, msHasVariableType);

	cls.method(sigs::getVariableType, msGetVariableType);

	cls.method<&MutatingScope::getDefinedVariables>(sigs::getDefinedVariables);

	cls.method<&MutatingScope::getMaybeDefinedVariables>(sigs::getMaybeDefinedVariables);

	cls.method(sigs::findPossiblyImpureCallDescriptions, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprArg;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(exprArg, exprCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.findPossiblyImpureCallDescriptions(Z_OBJ_P(exprArg)));
	});

	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nameArg;
		zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
		if (UNEXPECTED(nameCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(nameArg, nameCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_RETURN_BOOL(PT_THIS.hasConstant(Z_OBJ_P(nameArg), out_));
	});

	cls.method(sigs::isInAnonymousFunction, msIsInAnonymousFunction);

	cls.method<&MutatingScope::getAnonymousFunctionReflection>(sigs::getAnonymousFunctionReflection);

	cls.method<&MutatingScope::getAnonymousFunctionReturnType>(sigs::getAnonymousFunctionReturnType);

	cls.method(sigs::withAnonymousFunctionReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *anonymousFunctionReflection;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(anonymousFunctionReflection, pt_ce_closure_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.withAnonymousFunctionReflection(anonymousFunctionReflection));
	});

	/* {{{ the type resolution core (twin 1054–1808) */

	cls.method(sigs::getType, msGetType);

	cls.method(sigs::getScopeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(exprArg);
		PT_RETURN_VAL(PT_THIS.getScopeType(exprArg));
	});

	cls.method(sigs::getScopeNativeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(exprArg);
		PT_RETURN_VAL(PT_THIS.getScopeNativeType(exprArg));
	});

	/* getNodeKey() / getExprPrinter(): registered below */

	cls.method("duplicateWith", reg::Public, 8, {
		reg::arrayArg("expressionTypes"),
		reg::arrayArg("nativeExpressionTypes"),
		reg::arrayArg("conditionalExpressions"),
		reg::arrayArg("currentlyAssignedExpressions"),
		reg::arrayArg("currentlyAllowedUndefinedExpressions"),
		reg::arrayArg("inFunctionCallsStack"),
		reg::boolArg("inFirstLevelStatement"),
		reg::boolArg("afterExtractCall"),
	}, msDuplicateWith, &returnsSelf);

	cls.method(sigs::getClosureScopeCacheKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *relevantRoots = NULL;
		if (!zp::parse<zp::Opt<zp::ArrOrNull>>(execute_data, relevantRoots)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.getClosureScopeCacheKey(relevantRoots));
	});

	cls.method(sigs::specifyTypesOfNewWorldHandlerNode, msSpecifyTypesOfNewWorldHandlerNode);

	cls.method(sigs::obtainResultForNode, msObtainResultForNode);

	cls.method(sigs::pushExpressionResultStorage, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *storage;
		if (!zp::parse<zp::Obj>(execute_data, storage)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.pushExpressionResultStorage(storage))) RETURN_THROWS();
	});

	cls.method(sigs::popExpressionResultStorage, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(!PT_THIS.popExpressionResultStorage())) RETURN_THROWS();
	});

	cls.method(sigs::findSettledStoredResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(node);
		PT_RETURN_VAL(PT_THIS.findSettledStoredResult(Z_OBJ_P(node)));
	});

	cls.method<&MutatingScope::getCurrentExpressionResultStorage>(sigs::getCurrentExpressionResultStorage);

	cls.method(sigs::withTemplateArgumentFrame, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *frame;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OR_NULL(frame)
		ZEND_PARSE_PARAMETERS_END();
		/* the twin's ?TemplateArgumentFrame parameter: the shadowing class,
		 * or the class of that name (the PHP twin next to the prefixed
		 * native classes in the differential tests) */
		if (UNEXPECTED(frame != NULL && Z_OBJCE_P(frame) != pt_ce_template_argument_frame && !zend_string_equals_literal(Z_OBJCE_P(frame)->name, "PHPStan\\Analyser\\Generics\\TemplateArgumentFrame"))) {
			zend_argument_type_error(1, "must be of type ?PHPStan\\Analyser\\Generics\\TemplateArgumentFrame, %s given", zend_zval_value_name(frame));
			RETURN_THROWS();
		}
		zval nullZv;
		if (frame == NULL) {
			ZVAL_NULL(&nullZv);
			frame = &nullZv;
		}
		PT_RETURN_VAL(PT_THIS.withTemplateArgumentFrame(frame));
	});

	cls.method<&MutatingScope::getCurrentTemplateArgumentFrame>(sigs::getCurrentTemplateArgumentFrame);

	cls.method<&MutatingScope::getTemplateArgumentConstraints>(sigs::getTemplateArgumentConstraints);

	cls.method(sigs::withTemplateArgumentConstraints, msWithTemplateArgumentConstraints);

	cls.method(sigs::addTemplateArgumentConstraints, msAddTemplateArgumentConstraints);

	cls.method(sigs::withoutMemoizedTypes, msWithoutMemoizedTypes);

	cls.method(sigs::getDifferingVariableRoots, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.getDifferingVariableRoots(Z_OBJ_P(other)));
	});

	cls.method(sigs::withRecordedStatementDelta, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *recordedEntry, *recordedExit;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(recordedEntry, pt_ce_mutating_scope)
			Z_PARAM_OBJECT_OF_CLASS(recordedExit, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.withRecordedStatementDelta(Z_OBJ_P(recordedEntry), Z_OBJ_P(recordedExit)));
	});

	cls.method(sigs::getNativeType, msGetNativeType);

	cls.method(sigs::getKeepVoidType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(node);
		PT_RETURN_VAL(PT_THIS.getKeepVoidType(Z_OBJ_P(node)));
	});

	cls.method(sigs::doNotTreatPhpDocTypesAsCertain, msDoNotTreatPhpDocTypesAsCertain);
	cls.method(sigs::resolveName, msResolveName);
	cls.method(sigs::resolveTypeByName, msResolveTypeByName);

	cls.method<&MutatingScope::getTypeFromValue, zp::Zval>(sigs::getTypeFromValue);

	/* }}} */

	/* {{{ twin 1835-2011 */

	cls.method("pushInFunctionCall", reg::Public, 3, {
		reg::any("reflection"),
		reg::obj("parameter", parameterReflection, true),
		reg::boolArg("rememberTypes"),
	}, msPushInFunctionCall, &returnsSelf);

	cls.method(sigs::popInFunctionCall, msPopInFunctionCall);

	cls.method(sigs::isInClassExists, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className;
		if (!zp::parse<zp::Str>(execute_data, className)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.isInClassExists(className, out_));
	});

	cls.method<&MutatingScope::getFunctionCallStack>(sigs::getFunctionCallStack);

	cls.method<&MutatingScope::getFunctionCallStackWithParameters>(sigs::getFunctionCallStackWithParameters);

	cls.method(sigs::isInFunctionExists, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *functionName;
		if (!zp::parse<zp::Str>(execute_data, functionName)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.isInFunctionExists(functionName, out_));
	});

	cls.method(sigs::enterClass, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_CLASS_REFLECTION(classReflectionArg);
		PT_RETURN_VAL(PT_THIS.enterClass(classReflectionArg));
	});

	cls.method(sigs::enterTrait, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_CLASS_REFLECTION(traitReflection);
		PT_RETURN_VAL(PT_THIS.enterTrait(traitReflection));
	});

	/* out of the twin's file order, for the function-like family */
	cls.method(sigs::getPhpVersion, msGetPhpVersion);

	cls.method(sigs::isParameterValueNullable, msIsParameterValueNullable);

	cls.method(sigs::getFunctionType, msGetFunctionType);

	/* {{{ twin 2012-2369: the function-like family */

	cls.method("enterClassMethod", reg::Public, 9, {
		reg::obj("classMethod", classMethodNode),
		reg::obj("templateTypeMap", templateTypeMap),
		reg::arrayArg("phpDocParameterTypes"),
		reg::obj("phpDocReturnType", type, true),
		reg::obj("throwType", type, true),
		reg::stringArg("deprecatedDescription", true),
		reg::boolArg("isDeprecated"),
		reg::boolArg("isInternal"),
		reg::boolArg("isFinal"),
		reg::withDefault(nullableBool("isPure"), "null"),
		reg::withDefault(reg::boolArg("acceptsNamedArguments"), "true"),
		reg::withDefault(reg::obj("asserts", assertions, true), "null"),
		reg::withDefault(reg::obj("selfOutType", type, true), "null"),
		reg::withDefault(reg::stringArg("phpDocComment", true), "null"),
		reg::withDefault(reg::arrayArg("parameterOutTypes"), "[]"),
		reg::withDefault(reg::arrayArg("immediatelyInvokedCallableParameters"), "[]"),
		reg::withDefault(reg::arrayArg("phpDocClosureThisTypeParameters"), "[]"),
		reg::withDefault(reg::boolArg("isConstructor"), "false"),
		reg::withDefault(reg::obj("resolvedPhpDocBlock", resolvedPhpDocBlock, true), "null"),
		reg::withDefault(reg::arrayArg("phpDocPureUnlessCallableIsImpureParameters"), "[]"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classMethod, *templateTypeMapArg, *phpDocParameterTypes, *phpDocReturnType = NULL, *throwType = NULL, *asserts = NULL, *selfOutType = NULL, *resolvedPhpDocBlock = NULL;
		zval *parameterOutTypes = NULL, *immediatelyInvokedCallableParameters = NULL, *phpDocClosureThisTypeParameters = NULL, *phpDocPureUnlessCallableIsImpureParameters = NULL;
		zend_string *deprecatedDescription = NULL, *phpDocComment = NULL;
		bool isDeprecated, isInternal, isFinal, isPure = false, isPureIsNull = true, acceptsNamedArguments = true, isConstructor = false;
		ZEND_PARSE_PARAMETERS_START(9, 20)
			Z_PARAM_OBJECT(classMethod)
			Z_PARAM_OBJECT(templateTypeMapArg)
			Z_PARAM_ARRAY(phpDocParameterTypes)
			Z_PARAM_OBJECT_OR_NULL(phpDocReturnType)
			Z_PARAM_OBJECT_OR_NULL(throwType)
			Z_PARAM_STR_OR_NULL(deprecatedDescription)
			Z_PARAM_BOOL(isDeprecated)
			Z_PARAM_BOOL(isInternal)
			Z_PARAM_BOOL(isFinal)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL_OR_NULL(isPure, isPureIsNull)
			Z_PARAM_BOOL(acceptsNamedArguments)
			Z_PARAM_OBJECT_OR_NULL(asserts)
			Z_PARAM_OBJECT_OR_NULL(selfOutType)
			Z_PARAM_STR_OR_NULL(phpDocComment)
			Z_PARAM_ARRAY(parameterOutTypes)
			Z_PARAM_ARRAY(immediatelyInvokedCallableParameters)
			Z_PARAM_ARRAY(phpDocClosureThisTypeParameters)
			Z_PARAM_BOOL(isConstructor)
			Z_PARAM_OBJECT_OR_NULL(resolvedPhpDocBlock)
			Z_PARAM_ARRAY(phpDocPureUnlessCallableIsImpureParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_OBJ_ZVAL(phpDocReturnType);
		PT_MS_OBJ_ZVAL(throwType);
		PT_MS_OBJ_ZVAL(asserts);
		PT_MS_OBJ_ZVAL(selfOutType);
		PT_MS_OBJ_ZVAL(resolvedPhpDocBlock);
		PT_MS_STR_ZVAL(deprecatedDescription);
		PT_MS_STR_ZVAL(phpDocComment);
		PT_MS_ARRAY_ZVAL(parameterOutTypes);
		PT_MS_ARRAY_ZVAL(immediatelyInvokedCallableParameters);
		PT_MS_ARRAY_ZVAL(phpDocClosureThisTypeParameters);
		PT_MS_ARRAY_ZVAL(phpDocPureUnlessCallableIsImpureParameters);
		zval isPureZv;
		if (isPureIsNull) {
			ZVAL_NULL(&isPureZv);
		} else {
			ZVAL_BOOL(&isPureZv, isPure);
		}
		PT_RETURN_VAL(PT_THIS.enterClassMethod(classMethod, templateTypeMapArg, phpDocParameterTypes, phpDocReturnType, throwType, &deprecatedDescriptionZv, isDeprecated, isInternal, isFinal, &isPureZv, acceptsNamedArguments, asserts, selfOutType, &phpDocCommentZv, parameterOutTypes, immediatelyInvokedCallableParameters, phpDocClosureThisTypeParameters, isConstructor, resolvedPhpDocBlock, phpDocPureUnlessCallableIsImpureParameters));
	}, &returnsSelf);

	cls.method("enterPropertyHook", reg::Public, 10, {
		reg::obj("hook", propertyHook),
		reg::stringArg("propertyName"),
		reg::obj("nativePropertyTypeNode", identifierOrNameOrComplexType, true),
		reg::obj("phpDocPropertyType", type, true),
		reg::arrayArg("phpDocParameterTypes"),
		reg::obj("throwType", type, true),
		reg::stringArg("deprecatedDescription", true),
		reg::boolArg("isDeprecated"),
		nullableBool("isPure"),
		reg::stringArg("phpDocComment", true),
		reg::withDefault(reg::obj("resolvedPhpDocBlock", resolvedPhpDocBlock, true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *hook, *nativePropertyTypeNode = NULL, *phpDocPropertyType = NULL, *phpDocParameterTypes, *throwType = NULL, *resolvedPhpDocBlock = NULL;
		zend_string *propertyName, *deprecatedDescription = NULL, *phpDocComment = NULL;
		bool isDeprecated, isPure = false, isPureIsNull = true;
		ZEND_PARSE_PARAMETERS_START(10, 11)
			Z_PARAM_OBJECT(hook)
			Z_PARAM_STR(propertyName)
			Z_PARAM_OBJECT_OR_NULL(nativePropertyTypeNode)
			Z_PARAM_OBJECT_OR_NULL(phpDocPropertyType)
			Z_PARAM_ARRAY(phpDocParameterTypes)
			Z_PARAM_OBJECT_OR_NULL(throwType)
			Z_PARAM_STR_OR_NULL(deprecatedDescription)
			Z_PARAM_BOOL(isDeprecated)
			Z_PARAM_BOOL_OR_NULL(isPure, isPureIsNull)
			Z_PARAM_STR_OR_NULL(phpDocComment)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(resolvedPhpDocBlock)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_OBJ_ZVAL(nativePropertyTypeNode);
		PT_MS_OBJ_ZVAL(phpDocPropertyType);
		PT_MS_OBJ_ZVAL(throwType);
		PT_MS_OBJ_ZVAL(resolvedPhpDocBlock);
		PT_MS_STR_ZVAL(deprecatedDescription);
		PT_MS_STR_ZVAL(phpDocComment);
		zval isPureZv;
		if (isPureIsNull) {
			ZVAL_NULL(&isPureZv);
		} else {
			ZVAL_BOOL(&isPureZv, isPure);
		}
		PT_RETURN_VAL(PT_THIS.enterPropertyHook(hook, propertyName, nativePropertyTypeNode, phpDocPropertyType, phpDocParameterTypes, throwType, &deprecatedDescriptionZv, isDeprecated, &isPureZv, &phpDocCommentZv, resolvedPhpDocBlock));
	}, &returnsSelf);

	cls.method("enterFunction", reg::Public, 8, {
		reg::obj("function", functionNode),
		reg::obj("templateTypeMap", templateTypeMap),
		reg::arrayArg("phpDocParameterTypes"),
		reg::obj("phpDocReturnType", type, true),
		reg::obj("throwType", type, true),
		reg::stringArg("deprecatedDescription", true),
		reg::boolArg("isDeprecated"),
		reg::boolArg("isInternal"),
		reg::withDefault(nullableBool("isPure"), "null"),
		reg::withDefault(reg::boolArg("acceptsNamedArguments"), "true"),
		reg::withDefault(reg::obj("asserts", assertions, true), "null"),
		reg::withDefault(reg::stringArg("phpDocComment", true), "null"),
		reg::withDefault(reg::arrayArg("parameterOutTypes"), "[]"),
		reg::withDefault(reg::arrayArg("immediatelyInvokedCallableParameters"), "[]"),
		reg::withDefault(reg::arrayArg("phpDocClosureThisTypeParameters"), "[]"),
		reg::withDefault(reg::arrayArg("pureUnlessCallableIsImpureParameters"), "[]"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *function = NULL, *templateTypeMapArg = NULL, *phpDocParameterTypes = NULL, *phpDocReturnType = NULL, *throwType = NULL, *asserts = NULL;
		zval *parameterOutTypes = NULL, *immediatelyInvokedCallableParameters = NULL, *phpDocClosureThisTypeParameters = NULL, *pureUnlessCallableIsImpureParameters = NULL;
		zend_string *deprecatedDescription = NULL, *phpDocComment = NULL;
		bool isDeprecated = false, isInternal = false, isPure = false, isPureIsNull = true, acceptsNamedArguments = true;
		ZEND_PARSE_PARAMETERS_START(8, 16)
			Z_PARAM_OBJECT(function)
			Z_PARAM_OBJECT(templateTypeMapArg)
			Z_PARAM_ARRAY(phpDocParameterTypes)
			Z_PARAM_OBJECT_OR_NULL(phpDocReturnType)
			Z_PARAM_OBJECT_OR_NULL(throwType)
			Z_PARAM_STR_OR_NULL(deprecatedDescription)
			Z_PARAM_BOOL(isDeprecated)
			Z_PARAM_BOOL(isInternal)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL_OR_NULL(isPure, isPureIsNull)
			Z_PARAM_BOOL(acceptsNamedArguments)
			Z_PARAM_OBJECT_OR_NULL(asserts)
			Z_PARAM_STR_OR_NULL(phpDocComment)
			Z_PARAM_ARRAY(parameterOutTypes)
			Z_PARAM_ARRAY(immediatelyInvokedCallableParameters)
			Z_PARAM_ARRAY(phpDocClosureThisTypeParameters)
			Z_PARAM_ARRAY(pureUnlessCallableIsImpureParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_OBJ_ZVAL(phpDocReturnType);
		PT_MS_OBJ_ZVAL(throwType);
		PT_MS_OBJ_ZVAL(asserts);
		PT_MS_STR_ZVAL(deprecatedDescription);
		PT_MS_STR_ZVAL(phpDocComment);
		PT_MS_ARRAY_ZVAL(parameterOutTypes);
		PT_MS_ARRAY_ZVAL(immediatelyInvokedCallableParameters);
		PT_MS_ARRAY_ZVAL(phpDocClosureThisTypeParameters);
		PT_MS_ARRAY_ZVAL(pureUnlessCallableIsImpureParameters);
		zval isPureZv;
		if (isPureIsNull) {
			ZVAL_NULL(&isPureZv);
		} else {
			ZVAL_BOOL(&isPureZv, isPure);
		}
		PT_RETURN_VAL(PT_THIS.enterFunction(function, templateTypeMapArg, phpDocParameterTypes, phpDocReturnType, throwType, &deprecatedDescriptionZv, isDeprecated, isInternal, &isPureZv, acceptsNamedArguments, asserts, &phpDocCommentZv, parameterOutTypes, immediatelyInvokedCallableParameters, phpDocClosureThisTypeParameters, pureUnlessCallableIsImpureParameters));
	}, &returnsSelf);

	cls.method<&MutatingScope::enterNamespace, zp::Str>(sigs::enterNamespace);

	/* }}} */

	/* {{{ twin 2385-2558: the closure-bind family */

	cls.method(sigs::enterClosureBind, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *thisType = NULL, *nativeThisType = NULL, *scopeClasses;
		if (!zp::parse<zp::ObjOrNull, zp::ObjOrNull, zp::Arr>(execute_data, thisType, nativeThisType, scopeClasses)) RETURN_THROWS();
		PT_MS_OBJ_ZVAL(thisType);
		PT_MS_OBJ_ZVAL(nativeThisType);
		PT_RETURN_VAL(PT_THIS.enterClosureBind(thisType, nativeThisType, scopeClasses));
	});

	cls.method(sigs::restoreOriginalScopeAfterClosureBind, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *originalScope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(originalScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.restoreOriginalScopeAfterClosureBind(Z_OBJ_P(originalScope)));
	});

	cls.method(sigs::restoreThis, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *restoreThisScope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(restoreThisScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.restoreThis(Z_OBJ_P(restoreThisScope)));
	});

	cls.method<&MutatingScope::enterClosureCall, zp::Obj, zp::Obj>(sigs::enterClosureCall);

	cls.method(sigs::isInClosureBind, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_MS_RETURN_BOOL(PT_THIS.isInClosureBind(out_));
	});

	cls.method<&MutatingScope::withClosureBindScopeClasses, zp::Arr>(sigs::withClosureBindScopeClasses);

	/* }}} */

	/* }}} */

	/* {{{ twin 2560-3990: the anonymous- and arrow-function
	 * entries, the assignment / invalidation family and the specification
	 * machinery */

	cls.method("enterAnonymousFunction", reg::Public, 2, {
		reg::obj("closure", closureNode),
		reg::arrayArg("callableParameters", true),
		reg::withDefault(reg::arrayArg("nativeCallableParameters", true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *closure, *callableParameters = NULL, *nativeCallableParameters = NULL;
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(closureCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_OBJECT_OF_CLASS(closure, closureCe)
			Z_PARAM_ARRAY_OR_NULL(callableParameters)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(nativeCallableParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterAnonymousFunction(Z_OBJ_P(closure), callableParameters, nativeCallableParameters));
	}, &returnsSelf);

	cls.method("enterAnonymousFunctionWithoutReflection", reg::Public, 3, {
		reg::obj("closure", closureNode),
		reg::arrayArg("callableParameters", true),
		reg::arrayArg("nativeCallableParameters", true),
	}, msEnterAnonymousFunctionWithoutReflection, &returnsSelf);

	cls.method("enterArrowFunction", reg::Public, 2, {
		reg::obj("arrowFunction", arrowFunctionNode),
		reg::arrayArg("callableParameters", true),
		reg::withDefault(reg::arrayArg("nativeCallableParameters", true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *arrowFunction, *callableParameters = NULL, *nativeCallableParameters = NULL;
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(arrowFunctionCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_OBJECT_OF_CLASS(arrowFunction, arrowFunctionCe)
			Z_PARAM_ARRAY_OR_NULL(callableParameters)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(nativeCallableParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterArrowFunction(Z_OBJ_P(arrowFunction), callableParameters, nativeCallableParameters));
	}, &returnsSelf);

	cls.method("enterArrowFunctionWithoutReflection", reg::Public, 3, {
		reg::obj("arrowFunction", arrowFunctionNode),
		reg::arrayArg("callableParameters", true),
		reg::arrayArg("nativeCallableParameters", true),
	}, msEnterArrowFunctionWithoutReflection, &returnsSelf);

	cls.method(sigs::intersectButNotNever, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nativeType, *inferredType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, nativeType, inferredType)) RETURN_THROWS();
		zv::Val result = MutatingScope::intersectButNotNever(nativeType, inferredType);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::enterMatch, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *condType, *condNativeType;
		zend_class_entry *matchCe = pt_class(PT_CLASS_MATCH);
		if (UNEXPECTED(matchCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(expr, matchCe)
			Z_PARAM_OBJECT(condType)
			Z_PARAM_OBJECT(condNativeType)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterMatch(Z_OBJ_P(expr), condType, condNativeType));
	});

	cls.method("enterForeach", reg::Public, 7, {
		reg::obj("originalScope", self),
		reg::obj("iteratee", expr),
		reg::obj("iterateeType", type),
		reg::obj("nativeIterateeType", type),
		reg::stringArg("valueName"),
		reg::stringArg("keyName", true),
		reg::boolArg("valueByRef"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *originalScope, *iteratee, *iterateeType, *nativeIterateeType;
		zend_string *valueName, *keyName = NULL;
		bool valueByRef;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT_OF_CLASS(originalScope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT_OF_CLASS(iteratee, exprCe)
			Z_PARAM_OBJECT(iterateeType)
			Z_PARAM_OBJECT(nativeIterateeType)
			Z_PARAM_STR(valueName)
			Z_PARAM_STR_OR_NULL(keyName)
			Z_PARAM_BOOL(valueByRef)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterForeach(Z_OBJ_P(originalScope), iteratee, iterateeType, nativeIterateeType, valueName, keyName, valueByRef));
	}, &returnsSelf);

	cls.method("enterForeachKey", reg::Public, 5, {
		reg::obj("originalScope", self),
		reg::obj("iteratee", expr),
		reg::obj("iterateeType", type),
		reg::obj("nativeIterateeType", type),
		reg::stringArg("keyName"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *originalScope, *iteratee, *iterateeType, *nativeIterateeType;
		zend_string *keyName;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT_OF_CLASS(originalScope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT_OF_CLASS(iteratee, exprCe)
			Z_PARAM_OBJECT(iterateeType)
			Z_PARAM_OBJECT(nativeIterateeType)
			Z_PARAM_STR(keyName)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterForeachKey(Z_OBJ_P(originalScope), iteratee, iterateeType, nativeIterateeType, keyName));
	}, &returnsSelf);

	cls.method(sigs::enterCatchType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *catchType;
		zend_string *variableName = NULL;
		if (!zp::parse<zp::Obj, zp::StrOrNull>(execute_data, catchType, variableName)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.enterCatchType(catchType, variableName));
	});

	cls.method(sigs::enterExpressionAssign, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		bool isPlainWrite = true;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(isPlainWrite)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.enterExpressionAssign(Z_OBJ_P(expr), isPlainWrite));
	});

	cls.method(sigs::exitExpressionAssign, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_THIS.exitExpressionAssign(Z_OBJ_P(expr)));
	});

	cls.method(sigs::isInExpressionAssign, msIsInExpressionAssign);

	cls.method(sigs::isInWriteExpressionAssign, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(expr);
		PT_MS_RETURN_BOOL(PT_THIS.isInWriteExpressionAssign(Z_OBJ_P(expr), out_));
	});

	cls.method(sigs::setAllowedUndefinedExpression, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_THIS.setAllowedUndefinedExpression(Z_OBJ_P(expr)));
	});

	cls.method(sigs::unsetAllowedUndefinedExpression, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_THIS.unsetAllowedUndefinedExpression(Z_OBJ_P(expr)));
	});

	cls.method(sigs::isUndefinedExpressionAllowed, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_MS_PARSE_EXPR(expr);
		PT_MS_RETURN_BOOL(PT_THIS.isUndefinedExpressionAllowed(Z_OBJ_P(expr), out_));
	});

	cls.method("assignVariable", reg::Public, 4, {
		reg::stringArg("variableName"),
		reg::obj("type", type),
		reg::obj("nativeType", type),
		reg::obj("certainty", trinaryLogic),
		reg::withDefault(reg::arrayArg("intertwinedPropagatedFrom"), "[]"),
	}, msAssignVariable, &returnsSelf);

	cls.method(sigs::getStateType, msGetStateType);

	cls.method("specifyExpressionType", reg::Public, 4, {
		reg::obj("expr", expr),
		reg::obj("type", type),
		reg::obj("nativeType", type),
		reg::obj("certainty", trinaryLogic),
	}, msSpecifyExpressionType, &returnsSelf);

	cls.method(sigs::assignExpression, msAssignExpression);

	cls.method<&MutatingScope::assignInitializedProperty, zp::Obj, zp::Str>(sigs::assignInitializedProperty);

	cls.method("invalidateExpression", reg::Public, 1, {
		reg::obj("expressionToInvalidate", expr),
		reg::withDefault(reg::boolArg("requireMoreCharacters"), "false"),
		reg::withDefault(reg::obj("invalidatingClass", classReflection, true), "null"),
		reg::withDefault(reg::boolArg("keepPropertyFetches"), "false"),
	}, msInvalidateExpression, &returnsSelf);

	cls.method(sigs::isPrivatePropertyOfDifferentClass, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *invalidatingClass;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		zend_class_entry *classReflectionCe = msClassReflectionCe();
		if (UNEXPECTED(exprCe == NULL || classReflectionCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
			Z_PARAM_OBJECT_OF_CLASS(invalidatingClass, classReflectionCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_RETURN_BOOL(PT_THIS.isPrivatePropertyOfDifferentClass(Z_OBJ_P(expr), invalidatingClass, out_));
	});

	cls.method(sigs::addTypeToExpression, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.addTypeToExpression(Z_OBJ_P(expr), type));
	});

	cls.method(sigs::removeTypeFromExpression, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *typeToRemove;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
			Z_PARAM_OBJECT(typeToRemove)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.removeTypeFromExpression(Z_OBJ_P(expr), typeToRemove));
	});

	/* }}} */

	/* {{{ twin 3993-4773: the narrowing application, the
	 * conditional-expression bookkeeping and the scope merges */

	cls.method(sigs::filterByTruthyValue, msFilterByTruthyValue);
	cls.method(sigs::filterByFalseyValue, msFilterByFalseyValue);
	cls.method(sigs::applySpecifiedTypes, msApplySpecifiedTypes);

	cls.method<&MutatingScope::getConditionalExpressions>(sigs::getConditionalExpressions);

	cls.method<&MutatingScope::addConditionalExpressions, zp::Str, zp::Ht>(sigs::addConditionalExpressions);

	cls.method<&MutatingScope::exitFirstLevelStatements>(sigs::exitFirstLevelStatements);

	cls.method(sigs::mergeWith, msMergeWith);

	cls.method(sigs::mergeInitializedProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *calledMethodScope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(calledMethodScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mergeInitializedProperties(Z_OBJ_P(calledMethodScope)));
	});

	cls.method(sigs::processFinallyScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *finallyScope, *originalFinallyScope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(finallyScope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT_OF_CLASS(originalFinallyScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.processFinallyScope(Z_OBJ_P(finallyScope), Z_OBJ_P(originalFinallyScope)));
	});

	/* }}} */

	/* {{{ twin 4775-5884: the closure and loop scopes, the
	 * generalization, the scope comparison, the member-access queries and
	 * the remaining readers */

	cls.method(sigs::processClosureScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *closureScope, *prevScope;
		HashTable *byRefUses;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(closureScope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(prevScope, pt_ce_mutating_scope)
			Z_PARAM_ARRAY_HT(byRefUses)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.processClosureScope(Z_OBJ_P(closureScope), prevScope, byRefUses));
	});

	cls.method(sigs::processAlwaysIterableForeachScopeWithoutPollute, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *finalScope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(finalScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.processAlwaysIterableForeachScopeWithoutPollute(Z_OBJ_P(finalScope)));
	});

	cls.method(sigs::generalizeWith, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherScope;
		HashTable *writableVariableNames = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 2)
			Z_PARAM_OBJECT_OF_CLASS(otherScope, pt_ce_mutating_scope)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_HT_OR_NULL(writableVariableNames)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.generalizeWith(Z_OBJ_P(otherScope), writableVariableNames));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherScope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(otherScope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_MS_RETURN_BOOL(PT_THIS.equals(Z_OBJ_P(otherScope), out_));
	});

	cls.method(sigs::canAccessProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection;
		if (!zp::parse<zp::Obj>(execute_data, reflection)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.canAccessProperty(Z_OBJ_P(reflection), out_));
	});

	cls.method(sigs::canReadProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection;
		if (!zp::parse<zp::Obj>(execute_data, reflection)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.canReadProperty(Z_OBJ_P(reflection), out_));
	});

	cls.method(sigs::canWriteProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection;
		if (!zp::parse<zp::Obj>(execute_data, reflection)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.canWriteProperty(Z_OBJ_P(reflection), out_));
	});

	cls.method(sigs::canCallMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection;
		if (!zp::parse<zp::Obj>(execute_data, reflection)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.canCallMethod(Z_OBJ_P(reflection), out_));
	});

	cls.method(sigs::canAccessConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection;
		if (!zp::parse<zp::Obj>(execute_data, reflection)) RETURN_THROWS();
		PT_MS_RETURN_BOOL(PT_THIS.canAccessConstant(Z_OBJ_P(reflection), out_));
	});

	cls.method<&MutatingScope::debug>(sigs::debug);

	cls.method(sigs::filterTypeWithMethod, msFilterTypeWithMethod);

	cls.method<&MutatingScope::getMethodReflection, zp::Obj, zp::Str>(sigs::getMethodReflection);

	cls.method<&MutatingScope::getNakedMethod, zp::Obj, zp::Str>(sigs::getNakedMethod);

	cls.method<&MutatingScope::getPropertyReflection, zp::Obj, zp::Str>(sigs::getPropertyReflection);

	cls.method<&MutatingScope::getInstancePropertyReflection, zp::Obj, zp::Str>(sigs::getInstancePropertyReflection);

	cls.method<&MutatingScope::getStaticPropertyReflection, zp::Obj, zp::Str>(sigs::getStaticPropertyReflection);

	cls.method<&MutatingScope::getConstantReflection, zp::Obj, zp::Str>(sigs::getConstantReflection);

	cls.method<&MutatingScope::getConstantExplicitTypeFromConfig, zp::Str, zp::Obj>(sigs::getConstantExplicitTypeFromConfig);

	cls.method<&MutatingScope::getIterableKeyType, zp::Obj>(sigs::getIterableKeyType);

	cls.method<&MutatingScope::getIterableValueType, zp::Obj>(sigs::getIterableValueType);

	cls.method(sigs::invokeNodeCallback, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeArg;
		if (!zp::parse<zp::Obj>(execute_data, nodeArg)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.invokeNodeCallback(Z_OBJ_P(nodeArg)))) RETURN_THROWS();
	});

	cls.method(sigs::emitCollectedData, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *collectorType;
		zval *data;
		if (!zp::parse<zp::Str, zp::Zval>(execute_data, collectorType, data)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.emitCollectedData(collectorType, data))) RETURN_THROWS();
	});

	/* }}} */

	/* out of the twin's file order (see the handle class) */
	cls.method(sigs::getNodeKey, msGetNodeKey);

	cls.method<&MutatingScope::getExprPrinter>(sigs::getExprPrinter);

	cls.method(sigs::hasExpressionType, msHasExpressionType);

	cls.method(sigs::getTrackedExpressionType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.getTrackedExpressionType(Z_OBJ_P(node)));
	});

	cls.method(sigs::isInFirstLevelStatement, msIsInFirstLevelStatement);

	cls.shadow(&pt_ce_mutating_scope);
}

/* }}} */

/* {{{ direct entries for the narrowing helpers (DefaultNarrowingHelper.cpp):
 * $scope->toWalkScope() — the native body while the scope's method is
 * MutatingScope's own handler (NodeCallbackScope overrides it), the method
 * by name otherwise */

zv::Val pt_mutating_scope_to_walk_scope(zend_object *scope)
{
	if (EXPECTED(scope->ce == pt_ce_mutating_scope || pt_type_method_is(scope, PT_LC("towalkscope"), msToWalkScope))) return MutatingScope(scope).toWalkScope();
	return pt_type_call(scope, PT_LC("towalkscope"), 0, NULL);
}

/* }}} */
