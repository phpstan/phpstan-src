#include "support.h"
#include "zv.h"
#include "TypeOps.h"

#include <cstring>
#include <initializer_list>

pt_globals_t pt_globals;

zend_class_entry *pt_ce_trinary = nullptr;
zend_class_entry *pt_ce_expr_type_holder = nullptr;
zend_class_entry *pt_ce_cond_expr_holder = nullptr;

/* {{{ class map */

typedef struct {
	const char *key;
	const char *default_name;
} pt_class_template;

static const pt_class_template pt_class_templates[PT_CLASS_COUNT] = {
	/* PT_CLASS_SHOULD_NOT_HAPPEN */ {"shouldNotHappenException", "PHPStan\\ShouldNotHappenException"},
	/* PT_CLASS_VARIABLE */ {"variable", "PhpParser\\Node\\Expr\\Variable"},
	/* PT_CLASS_FUNC_CALL */ {"funcCall", "PhpParser\\Node\\Expr\\FuncCall"},
	/* PT_CLASS_VIRTUAL_NODE */ {"virtualNode", "PHPStan\\Node\\VirtualNode"},
	/* PT_CLASS_NODE */ {"node", "PhpParser\\Node"},
	/* PT_CLASS_NAME */ {"name", "PhpParser\\Node\\Name"},
	/* PT_CLASS_EXPR */ {"expr", "PhpParser\\Node\\Expr"},
	/* PT_CLASS_PROPERTY_FETCH */ {"propertyFetch", "PhpParser\\Node\\Expr\\PropertyFetch"},
	/* PT_CLASS_NULLSAFE_PROPERTY_FETCH */ {"nullsafePropertyFetch", "PhpParser\\Node\\Expr\\NullsafePropertyFetch"},
	/* PT_CLASS_IDENTIFIER */ {"identifier", "PhpParser\\Node\\Identifier"},
	/* PT_CLASS_INTERTWINED_VAR */ {"intertwinedVariableByReferenceWithExpr", "PHPStan\\Node\\Expr\\IntertwinedVariableByReferenceWithExpr"},
	/* PT_CLASS_ARRAY_DIM_FETCH */ {"arrayDimFetch", "PhpParser\\Node\\Expr\\ArrayDimFetch"},
	/* PT_CLASS_METHOD_CALL */ {"methodCall", "PhpParser\\Node\\Expr\\MethodCall"},
	/* PT_CLASS_FUNCTION_LIKE */ {"functionLike", "PhpParser\\Node\\FunctionLike"},
	/* PT_CLASS_CALL_LIKE */ {"callLike", "PhpParser\\Node\\Expr\\CallLike"},
	/* PT_CLASS_STATIC_CALL */ {"staticCall", "PhpParser\\Node\\Expr\\StaticCall"},
	/* PT_CLASS_NEW */ {"newExpr", "PhpParser\\Node\\Expr\\New_"},
	/* PT_CLASS_CLASS_STMT */ {"classStmt", "PhpParser\\Node\\Stmt\\Class_"},
	/* PT_CLASS_VARIADIC_PLACEHOLDER */ {"variadicPlaceholder", "PhpParser\\Node\\VariadicPlaceholder"},
	/* PT_CLASS_SCALAR */ {"scalar", "PhpParser\\Node\\Scalar"},
	/* PT_CLASS_ARRAY_EXPR */ {"arrayExpr", "PhpParser\\Node\\Expr\\Array_"},
	/* PT_CLASS_UNARY_MINUS */ {"unaryMinus", "PhpParser\\Node\\Expr\\UnaryMinus"},
	/* PT_CLASS_YIELD */ {"yield", "PhpParser\\Node\\Expr\\Yield_"},
	/* PT_CLASS_YIELD_FROM */ {"yieldFrom", "PhpParser\\Node\\Expr\\YieldFrom"},
	/* PT_CLASS_STMT */ {"stmt", "PhpParser\\Node\\Stmt"},
	/* PT_CLASS_NODE_VISITOR_ABSTRACT */ {"nodeVisitorAbstract", "PhpParser\\NodeVisitorAbstract"},
	/* PT_CLASS_CLOSURE_EXPR */ {"closureExpr", "PhpParser\\Node\\Expr\\Closure"},
	/* PT_CLASS_ARROW_FUNCTION */ {"arrowFunction", "PhpParser\\Node\\Expr\\ArrowFunction"},
	/* PT_CLASS_TYPE */ {"type", "PHPStan\\Type\\Type"},
	/* PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT */ {"classNameToObjectTypeResult", "PHPStan\\Type\\ClassNameToObjectTypeResult"},
	/* PT_CLASS_IDENTIFIER_TYPE_NODE */ {"identifierTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\IdentifierTypeNode"},
	/* PT_CLASS_LOOSE_COMPARISON_HELPER */ {"looseComparisonHelper", "PHPStan\\Type\\LooseComparisonHelper"},
	/* PT_CLASS_EXPONENTIATE_HELPER */ {"exponentiateHelper", "PHPStan\\Type\\ExponentiateHelper"},
	/* PT_CLASS_COMPOUND_TYPE */ {"compoundType", "PHPStan\\Type\\CompoundType"},
	/* PT_CLASS_CONSTANT_SCALAR_TYPE */ {"constantScalarType", "PHPStan\\Type\\ConstantScalarType"},
	/* PT_CLASS_INITIALIZER_EXPR_TYPE_RESOLVER */ {"initializerExprTypeResolver", "PHPStan\\Reflection\\InitializerExprTypeResolver"},
	/* PT_CLASS_GENERIC_TYPE_NODE */ {"genericTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\GenericTypeNode"},
	/* PT_CLASS_CONST_TYPE_NODE */ {"constTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ConstTypeNode"},
	/* PT_CLASS_CONST_EXPR_INTEGER_NODE */ {"constExprIntegerNode", "PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstExprIntegerNode"},
	/* PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR */ {"reflectionProviderStaticAccessor", "PHPStan\\Reflection\\ReflectionProviderStaticAccessor"},
	/* PT_CLASS_PHP_VERSION_STATIC_ACCESSOR */ {"phpVersionStaticAccessor", "PHPStan\\Reflection\\PhpVersionStaticAccessor"},
	/* PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE */ {"reportUnsafeArrayStringKeyCastingToggle", "PHPStan\\DependencyInjection\\ReportUnsafeArrayStringKeyCastingToggle"},
	/* PT_CLASS_OUT_OF_CLASS_SCOPE */ {"outOfClassScope", "PHPStan\\Analyser\\OutOfClassScope"},
	/* PT_CLASS_FUNCTION_CALLABLE_VARIANT */ {"functionCallableVariant", "PHPStan\\Reflection\\Callables\\FunctionCallableVariant"},
	/* PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR */ {"trivialParametersAcceptor", "PHPStan\\Reflection\\TrivialParametersAcceptor"},
	/* PT_CLASS_INACCESSIBLE_METHOD */ {"inaccessibleMethod", "PHPStan\\Reflection\\InaccessibleMethod"},
	/* PT_CLASS_TEMPLATE_TYPE */ {"templateType", "PHPStan\\Type\\Generic\\TemplateType"},
	/* PT_CLASS_NARROWED_SUBJECT_TYPE */ {"narrowedSubjectType", "PHPStan\\Type\\NarrowedSubjectType"},
	/* PT_CLASS_CONDITIONAL_TYPE_RESOLVER */ {"conditionalTypeResolver", "PHPStan\\Analyser\\ConditionalTypeResolver"},
	/* PT_CLASS_GENERALIZE_PRECISION */ {"generalizePrecision", "PHPStan\\Type\\GeneralizePrecision"},
	/* PT_CLASS_CONST_EXPR_STRING_NODE */ {"constExprStringNode", "PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstExprStringNode"},
	/* PT_CLASS_NETTE_STRINGS */ {"netteStrings", "Nette\\Utils\\Strings"},
	/* PT_CLASS_NETTE_REGEXP_EXCEPTION */ {"netteRegexpException", "Nette\\Utils\\RegexpException"},
	/* PT_CLASS_CONST_EXPR_FLOAT_NODE */ {"constExprFloatNode", "PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstExprFloatNode"},
	/* PT_CLASS_SUBTRACTABLE_TYPE */ {"subtractableType", "PHPStan\\Type\\SubtractableType"},
	/* PT_CLASS_DUMMY_PROPERTY_REFLECTION */ {"dummyPropertyReflection", "PHPStan\\Reflection\\Dummy\\DummyPropertyReflection"},
	/* PT_CLASS_DUMMY_METHOD_REFLECTION */ {"dummyMethodReflection", "PHPStan\\Reflection\\Dummy\\DummyMethodReflection"},
	/* PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION */ {"dummyClassConstantReflection", "PHPStan\\Reflection\\Dummy\\DummyClassConstantReflection"},
	/* PT_CLASS_TYPE_WITH_CLASS_NAME */ {"typeWithClassName", "PHPStan\\Type\\TypeWithClassName"},
	/* PT_CLASS_OBJECT_SHAPE_PROPERTY_REFLECTION */ {"objectShapePropertyReflection", "PHPStan\\Type\\ObjectShapePropertyReflection"},
	/* PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION */ {"universalObjectCratesClassReflectionExtension", "PHPStan\\Reflection\\Php\\UniversalObjectCratesClassReflectionExtension"},
	/* PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION */ {"missingPropertyFromReflectionException", "PHPStan\\Reflection\\MissingPropertyFromReflectionException"},
	/* PT_CLASS_THIS_TYPE_NODE */ {"thisTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ThisTypeNode"},
	/* PT_CLASS_OBJECT_SHAPE_NODE */ {"objectShapeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ObjectShapeNode"},
	/* PT_CLASS_OBJECT_SHAPE_ITEM_NODE */ {"objectShapeItemNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ObjectShapeItemNode"},
	/* PT_CLASS_UNSAFE_ARRAY_STRING_KEY_CASTING_TRAVERSER */ {"unsafeArrayStringKeyCastingTraverser", "PHPStan\\Type\\Traverser\\UnsafeArrayStringKeyCastingTraverser"},
	/* PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES */ {"allowedArrayKeysTypes", "PHPStan\\Rules\\Arrays\\AllowedArrayKeysTypes"},
	/* PT_CLASS_CLASS_NOT_FOUND_EXCEPTION */ {"classNotFoundException", "PHPStan\\Broker\\ClassNotFoundException"},
	/* PT_CLASS_UNION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION */ {"unionTypeUnresolvedPropertyPrototypeReflection", "PHPStan\\Reflection\\Type\\UnionTypeUnresolvedPropertyPrototypeReflection"},
	/* PT_CLASS_ENUM_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION */ {"enumUnresolvedPropertyPrototypeReflection", "PHPStan\\Reflection\\Php\\EnumUnresolvedPropertyPrototypeReflection"},
	/* PT_CLASS_ENUM_PROPERTY_REFLECTION */ {"enumPropertyReflection", "PHPStan\\Reflection\\Php\\EnumPropertyReflection"},
	/* PT_CLASS_CONST_FETCH_NODE */ {"constFetchNode", "PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstFetchNode"},
	/* PT_CLASS_CALLABLE_ASSERTIONS_HELPER */ {"callableAssertionsHelper", "PHPStan\\Type\\CallableAssertionsHelper"},
	/* PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR */ {"callableParametersAcceptor", "PHPStan\\Reflection\\Callables\\CallableParametersAcceptor"},
	/* PT_CLASS_ASSERTIONS */ {"assertions", "PHPStan\\Reflection\\Assertions"},
	/* PT_CLASS_SIMPLE_THROW_POINT */ {"simpleThrowPoint", "PHPStan\\Reflection\\Callables\\SimpleThrowPoint"},
	/* PT_CLASS_EXTENDED_PARAMETER_REFLECTION */ {"extendedParameterReflection", "PHPStan\\Reflection\\ExtendedParameterReflection"},
	/* PT_CLASS_CLOSURE_CALL_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION */ {"closureCallUnresolvedMethodPrototypeReflection", "PHPStan\\Reflection\\Php\\ClosureCallUnresolvedMethodPrototypeReflection"},
	/* PT_CLASS_PHPDOC_PRINTER */ {"phpDocPrinter", "PHPStan\\PhpDocParser\\Printer\\Printer"},
	/* PT_CLASS_CALLABLE_TYPE_NODE */ {"callableTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode"},
	/* PT_CLASS_CALLABLE_TYPE_PARAMETER_NODE */ {"callableTypeParameterNode", "PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeParameterNode"},
	/* PT_CLASS_TEMPLATE_TAG_VALUE_NODE */ {"templateTagValueNode", "PHPStan\\PhpDocParser\\Ast\\PhpDoc\\TemplateTagValueNode"},
	/* PT_CLASS_BLEEDING_EDGE_TOGGLE */ {"bleedingEdgeToggle", "PHPStan\\DependencyInjection\\BleedingEdgeToggle"},
	/* PT_CLASS_CONSTANT_ARRAY_TYPE_AND_METHOD */ {"constantArrayTypeAndMethod", "PHPStan\\Type\\Constant\\ConstantArrayTypeAndMethod"},
	/* PT_CLASS_ARRAY_SHAPE_NODE */ {"arrayShapeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeNode"},
	/* PT_CLASS_ARRAY_SHAPE_ITEM_NODE */ {"arrayShapeItemNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeItemNode"},
	/* PT_CLASS_ARRAY_SHAPE_UNSEALED_TYPE_NODE */ {"arrayShapeUnsealedTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode"},
	/* PT_CLASS_LATE_RESOLVABLE_TYPE */ {"lateResolvableType", "PHPStan\\Type\\LateResolvableType"},
	/* PT_CLASS_UNION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION */ {"unionTypeUnresolvedMethodPrototypeReflection", "PHPStan\\Reflection\\Type\\UnionTypeUnresolvedMethodPrototypeReflection"},
	/* PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION */ {"missingMethodFromReflectionException", "PHPStan\\Reflection\\MissingMethodFromReflectionException"},
	/* PT_CLASS_MISSING_CONSTANT_FROM_REFLECTION_EXCEPTION */ {"missingConstantFromReflectionException", "PHPStan\\Reflection\\MissingConstantFromReflectionException"},
	/* PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION */ {"intersectionTypeUnresolvedPropertyPrototypeReflection", "PHPStan\\Reflection\\Type\\IntersectionTypeUnresolvedPropertyPrototypeReflection"},
	/* PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION */ {"intersectionTypeUnresolvedMethodPrototypeReflection", "PHPStan\\Reflection\\Type\\IntersectionTypeUnresolvedMethodPrototypeReflection"},
	/* PT_CLASS_ACCESSORY_TYPE */ {"accessoryType", "PHPStan\\Type\\Accessory\\AccessoryType"},
	/* PT_CLASS_UNION_TYPE_NODE */ {"unionTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode"},
	/* PT_CLASS_INTERSECTION_TYPE_NODE */ {"intersectionTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode"},
	/* PT_CLASS_TYPE_TRAVERSER_CALLABLE */ {"typeTraverserCallable", "PHPStan\\Type\\TypeTraverserCallable"},
	/* PT_CLASS_LATE_RESOLVABLE_TRAVERSER */ {"lateResolvableTraverser", "PHPStan\\Type\\Traverser\\LateResolvableTraverser"},
	/* PT_CLASS_REFLECTION_UNION_TYPE */ {"reflectionUnionType", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionUnionType"},
	/* PT_CLASS_REFLECTION_INTERSECTION_TYPE */ {"reflectionIntersectionType", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionIntersectionType"},
	/* PT_CLASS_REFLECTION_NAMED_TYPE */ {"reflectionNamedType", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionNamedType"},
	/* PT_CLASS_FULLY_QUALIFIED */ {"fullyQualified", "PhpParser\\Node\\Name\\FullyQualified"},
	/* PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE */ {"parserNodeTypeToPHPStanType", "PHPStan\\Type\\ParserNodeTypeToPHPStanType"},
	/* PT_CLASS_TURBO_EXTENSION_ENABLER */ {"turboExtensionEnabler", "PHPStan\\Turbo\\TurboExtensionEnabler"},
	/* PT_CLASS_PARAMETERS_ACCEPTOR */ {"parametersAcceptor", "PHPStan\\Reflection\\ParametersAcceptor"},
	/* PT_CLASS_OFFSET_ACCESS_TYPE_NODE */ {"offsetAccessTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\OffsetAccessTypeNode"},
	/* PT_CLASS_CONDITIONAL_TYPE_NODE */ {"conditionalTypeNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ConditionalTypeNode"},
	/* PT_CLASS_CONDITIONAL_TYPE_FOR_PARAMETER_NODE */ {"conditionalTypeForParameterNode", "PHPStan\\PhpDocParser\\Ast\\Type\\ConditionalTypeForParameterNode"},
	/* PT_CLASS_REFLECTION_ENUM */ {"reflectionEnum", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnum"},
	/* PT_CLASS_MEMOIZING_REFLECTION_PROVIDER */ {"memoizingReflectionProvider", "PHPStan\\Reflection\\ReflectionProvider\\MemoizingReflectionProvider"},
	/* PT_CLASS_UNRESOLVABLE_TYPE_RESULT */ {"unresolvableTypeResult", "PHPStan\\Rules\\PhpDoc\\UnresolvableTypeResult"},
	/* PT_CLASS_EXTENDED_FUNCTION_VARIANT */ {"extendedFunctionVariant", "PHPStan\\Reflection\\ExtendedFunctionVariant"},
	/* PT_CLASS_RESOLVED_PROPERTY_REFLECTION */ {"resolvedPropertyReflection", "PHPStan\\Reflection\\ResolvedPropertyReflection"},
	/* PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION */ {"changedTypePropertyReflection", "PHPStan\\Reflection\\Dummy\\ChangedTypePropertyReflection"},
	/* PT_CLASS_UNDEFINED_VARIABLE_EXCEPTION */ {"undefinedVariableException", "PHPStan\\Analyser\\UndefinedVariableException"},
	/* PT_CLASS_NODE_CALLBACK_SCOPE */ {"nodeCallbackScope", "PHPStan\\Analyser\\NodeCallbackScope"},
	/* PT_CLASS_PROPERTY_INITIALIZATION_EXPR */ {"propertyInitializationExpr", "PHPStan\\Node\\Expr\\PropertyInitializationExpr"},
	/* PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR */ {"possiblyImpureCallExpr", "PHPStan\\Node\\Expr\\PossiblyImpureCallExpr"},
	/* PT_CLASS_CONST_FETCH */ {"constFetch", "PhpParser\\Node\\Expr\\ConstFetch"},
	/* PT_CLASS_HALT_COMPILER */ {"haltCompiler", "PhpParser\\Node\\Stmt\\HaltCompiler"},
	/* PT_CLASS_INITIALIZER_EXPR_CONTEXT */ {"initializerExprContext", "PHPStan\\Reflection\\InitializerExprContext"},
	/* PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR */ {"extendedParametersAcceptor", "PHPStan\\Reflection\\ExtendedParametersAcceptor"},
	/* PT_CLASS_MATCH */ {"match", "PhpParser\\Node\\Expr\\Match_"},
	/* PT_CLASS_NULLSAFE_METHOD_CALL */ {"nullsafeMethodCall", "PhpParser\\Node\\Expr\\NullsafeMethodCall"},
	/* PT_CLASS_STATIC_PROPERTY_FETCH */ {"staticPropertyFetch", "PhpParser\\Node\\Expr\\StaticPropertyFetch"},
	/* PT_CLASS_CLASS_CONST_FETCH */ {"classConstFetch", "PhpParser\\Node\\Expr\\ClassConstFetch"},
	/* PT_CLASS_SCALAR_STRING */ {"scalarString", "PhpParser\\Node\\Scalar\\String_"},
	/* PT_CLASS_SCALAR_INT */ {"scalarInt", "PhpParser\\Node\\Scalar\\Int_"},
	/* PT_CLASS_SCALAR_FLOAT */ {"scalarFloat", "PhpParser\\Node\\Scalar\\Float_"},
	/* PT_CLASS_VAR_LIKE_IDENTIFIER */ {"varLikeIdentifier", "PhpParser\\Node\\VarLikeIdentifier"},
	/* PT_CLASS_EXTENDED_METHOD_REFLECTION */ {"extendedMethodReflection", "PHPStan\\Reflection\\ExtendedMethodReflection"},
	/* PT_CLASS_ARG */ {"arg", "PhpParser\\Node\\Arg"},
	/* PT_CLASS_FUNCTION_REFLECTION */ {"functionReflection", "PHPStan\\Reflection\\FunctionReflection"},
	/* PT_CLASS_PHP_VERSIONS */ {"phpVersions", "PHPStan\\Php\\PhpVersions"},
	/* PT_CLASS_PARAM */ {"param", "PhpParser\\Node\\Param"},
	/* PT_CLASS_TRANSFORM_STATIC_TYPE_TRAVERSER */ {"transformStaticTypeTraverser", "PHPStan\\Analyser\\Traverser\\TransformStaticTypeTraverser"},
	/* PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION */ {"phpMethodFromParserNodeReflection", "PHPStan\\Reflection\\Php\\PhpMethodFromParserNodeReflection"},
	/* PT_CLASS_PHP_FUNCTION_FROM_PARSER_NODE_REFLECTION */ {"phpFunctionFromParserNodeReflection", "PHPStan\\Reflection\\Php\\PhpFunctionFromParserNodeReflection"},
	/* PT_CLASS_PARAMETER_VARIABLE_ORIGINAL_VALUE_EXPR */ {"parameterVariableOriginalValueExpr", "PHPStan\\Node\\Expr\\ParameterVariableOriginalValueExpr"},
	/* PT_CLASS_WRAPPED_EXTENDED_METHOD_REFLECTION */ {"wrappedExtendedMethodReflection", "PHPStan\\Reflection\\WrappedExtendedMethodReflection"},
	/* PT_CLASS_EXTENDED_PROPERTY_REFLECTION */ {"extendedPropertyReflection", "PHPStan\\Reflection\\ExtendedPropertyReflection"},
	/* PT_CLASS_WRAPPED_EXTENDED_PROPERTY_REFLECTION */ {"wrappedExtendedPropertyReflection", "PHPStan\\Reflection\\WrappedExtendedPropertyReflection"},
	/* PT_CLASS_ENUM_CASE_REFLECTION */ {"enumCaseReflection", "PHPStan\\Reflection\\EnumCaseReflection"},
	/* PT_CLASS_REFLECTION_ENUM_BACKED_CASE */ {"reflectionEnumBackedCase", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnumBackedCase"},
	/* PT_CLASS_REAL_CLASS_CLASS_CONSTANT_REFLECTION */ {"realClassClassConstantReflection", "PHPStan\\Reflection\\RealClassClassConstantReflection"},
	/* PT_CLASS_TYPE_ALIAS */ {"typeAlias", "PHPStan\\Type\\TypeAlias"},
	/* PT_CLASS_CIRCULAR_TYPE_ALIAS_DEFINITION_EXCEPTION */ {"circularTypeAliasDefinitionException", "PHPStan\\Type\\CircularTypeAliasDefinitionException"},
	/* PT_CLASS_VARIABLE_WRITE */ {"variableWrite", "PHPStan\\Node\\Variable\\VariableWrite"},
	/* PT_CLASS_VARIABLE_WRITE_OFFSET */ {"variableWriteOffset", "PHPStan\\Analyser\\VariableWriteOffset"},
	/* PT_CLASS_LIST_EXPR */ {"listExpr", "PhpParser\\Node\\Expr\\List_"},
	/* PT_CLASS_VARIABLE_WRITES_NODE */ {"variableWritesNode", "PHPStan\\Node\\VariableWritesNode"},
	/* PT_CLASS_VOID_TO_NULL_TRAVERSER */ {"voidToNullTraverser", "PHPStan\\Analyser\\Traverser\\VoidToNullTraverser"},
	/* PT_CLASS_ISSETABILITY_RESOLUTION */ {"issetabilityResolution", "PHPStan\\Analyser\\IssetabilityResolution"},
	/* PT_CLASS_ISSETABILITY_LINK_INFO */ {"issetabilityLinkInfo", "PHPStan\\Analyser\\IssetabilityLinkInfo"},
	/* PT_CLASS_ALWAYS_REMEMBERED_EXPR */ {"alwaysRememberedExpr", "PHPStan\\Node\\Expr\\AlwaysRememberedExpr"},
	/* PT_CLASS_PHP_PROPERTY_REFLECTION */ {"phpPropertyReflection", "PHPStan\\Reflection\\Php\\PhpPropertyReflection"},
	/* PT_CLASS_NATIVE_METHOD_REFLECTION */ {"nativeMethodReflection", "PHPStan\\Reflection\\Native\\NativeMethodReflection"},
	/* PT_CLASS_EXTENDED_NATIVE_PARAMETER_REFLECTION */ {"extendedNativeParameterReflection", "PHPStan\\Reflection\\Native\\ExtendedNativeParameterReflection"},
	/* PT_CLASS_ENUM_CASES_METHOD_REFLECTION */ {"enumCasesMethodReflection", "PHPStan\\Reflection\\Php\\EnumCasesMethodReflection"},
	/* PT_CLASS_PRIVATE_PROPERTY_ATTRIBUTE */ {"privatePropertyAttribute", "PHPStan\\Reflection\\Attribute\\PrivateProperty"},
	/* PT_CLASS_PROTECTED_PROPERTY_ATTRIBUTE */ {"protectedPropertyAttribute", "PHPStan\\Reflection\\Attribute\\ProtectedProperty"},
	/* PT_CLASS_ADAPTER_REFLECTION_METHOD */ {"adapterReflectionMethod", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionMethod"},
	/* PT_CLASS_EXPRESSION_STMT */ {"expressionStmt", "PhpParser\\Node\\Stmt\\Expression"},
	/* PT_CLASS_ASSIGN_EXPR */ {"assignExpr", "PhpParser\\Node\\Expr\\Assign"},
	/* PT_CLASS_NAMESPACE_STMT */ {"namespaceStmt", "PhpParser\\Node\\Stmt\\Namespace_"},
	/* PT_CLASS_DECLARE_STMT */ {"declareStmt", "PhpParser\\Node\\Stmt\\Declare_"},
	/* PT_CLASS_CLASS_METHOD_STMT */ {"classMethodStmt", "PhpParser\\Node\\Stmt\\ClassMethod"},
	/* PT_CLASS_ADAPTER_REFLECTION_CLASS */ {"adapterReflectionClass", "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass"},
	/* PT_CLASS_BETTER_REFLECTION_CLASS */ {"betterReflectionClass", "PHPStan\\BetterReflection\\Reflection\\ReflectionClass"},
	/* PT_CLASS_ORIGINAL_FOREACH_VALUE_EXPR */ {"originalForeachValueExpr", "PHPStan\\Node\\Expr\\OriginalForeachValueExpr"},
	/* PT_CLASS_ORIGINAL_FOREACH_KEY_EXPR */ {"originalForeachKeyExpr", "PHPStan\\Node\\Expr\\OriginalForeachKeyExpr"},
	/* PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR */ {"setExistingOffsetValueTypeExpr", "PHPStan\\Node\\Expr\\SetExistingOffsetValueTypeExpr"},
	/* PT_CLASS_NATIVE_TYPE_EXPR */ {"nativeTypeExpr", "PHPStan\\Node\\Expr\\NativeTypeExpr"},
	/* PT_CLASS_CLONE_REINITIALIZATION_EXPR */ {"cloneReinitializationExpr", "PHPStan\\Node\\Expr\\CloneReinitializationExpr"},
	/* PT_CLASS_METHOD_REFLECTION */ {"methodReflection", "PHPStan\\Reflection\\MethodReflection"},
	/* PT_CLASS_PRE_INC */ {"preInc", "PhpParser\\Node\\Expr\\PreInc"},
	/* PT_CLASS_PRE_DEC */ {"preDec", "PhpParser\\Node\\Expr\\PreDec"},
	/* PT_CLASS_POST_INC */ {"postInc", "PhpParser\\Node\\Expr\\PostInc"},
	/* PT_CLASS_POST_DEC */ {"postDec", "PhpParser\\Node\\Expr\\PostDec"},
	/* PT_CLASS_ISSET_EXPR */ {"issetExpr", "PHPStan\\Node\\IssetExpr"},
	/* PT_CLASS_EMIT_COLLECTED_DATA_NODE */ {"emitCollectedDataNode", "PHPStan\\Node\\EmitCollectedDataNode"},
	/* PT_CLASS_LAZY_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER */ {"lazyClassReflectionExtensionRegistryProvider", "PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider"},
	/* PT_CLASS_CLASS_REFLECTION_EXTENSION_REGISTRY */ {"classReflectionExtensionRegistry", "PHPStan\\Reflection\\ClassReflectionExtensionRegistry"},
	/* PT_CLASS_LAZY_INTERNAL_SCOPE_FACTORY */ {"lazyInternalScopeFactory", "PHPStan\\Analyser\\LazyInternalScopeFactory"},
	/* PT_CLASS_MAGIC_CONST */ {"magicConst", "PhpParser\\Node\\Scalar\\MagicConst"},
	/* PT_CLASS_ASSIGN_REF_EXPR */ {"assignRefExpr", "PhpParser\\Node\\Expr\\AssignRef"},
	/* PT_CLASS_ASSIGN_OP_EXPR */ {"assignOpExpr", "PhpParser\\Node\\Expr\\AssignOp"},
	/* PT_CLASS_TRAIT_STMT */ {"traitStmt", "PhpParser\\Node\\Stmt\\Trait_"},
	/* PT_CLASS_INLINE_HTML_STMT */ {"inlineHtmlStmt", "PhpParser\\Node\\Stmt\\InlineHTML"},
	/* PT_CLASS_INTERPOLATED_STRING */ {"interpolatedString", "PhpParser\\Node\\Scalar\\InterpolatedString"},
	/* PT_CLASS_INSTANCEOF_EXPR */ {"instanceofExpr", "PhpParser\\Node\\Expr\\Instanceof_"},
	/* PT_CLASS_TRY_CATCH_STMT */ {"tryCatchStmt", "PhpParser\\Node\\Stmt\\TryCatch"},
	/* PT_CLASS_CATCH_STMT */ {"catchStmt", "PhpParser\\Node\\Stmt\\Catch_"},
	/* PT_CLASS_CLASS_PROPERTY_NODE */ {"classPropertyNode", "PHPStan\\Node\\ClassPropertyNode"},
	/* PT_CLASS_PROPERTY_ASSIGN_NODE */ {"propertyAssignNode", "PHPStan\\Node\\PropertyAssignNode"},
	/* PT_CLASS_METHOD_RETURN_STATEMENTS_NODE */ {"methodReturnStatementsNode", "PHPStan\\Node\\MethodReturnStatementsNode"},
	/* PT_CLASS_METHOD_CALLABLE_NODE */ {"methodCallableNode", "PHPStan\\Node\\MethodCallableNode"},
	/* PT_CLASS_STATIC_METHOD_CALLABLE_NODE */ {"staticMethodCallableNode", "PHPStan\\Node\\StaticMethodCallableNode"},
	/* PT_CLASS_FUNCTION_CALLABLE_NODE */ {"functionCallableNode", "PHPStan\\Node\\FunctionCallableNode"},
	/* PT_CLASS_INSTANTIATION_CALLABLE_NODE */ {"instantiationCallableNode", "PHPStan\\Node\\InstantiationCallableNode"},
	/* PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR */ {"setOffsetValueTypeExpr", "PHPStan\\Node\\Expr\\SetOffsetValueTypeExpr"},
	/* PT_CLASS_GATHERED_METHOD_CALL */ {"gatheredMethodCall", "PHPStan\\Node\\Method\\MethodCall"},
	/* PT_CLASS_PROPERTY_READ */ {"propertyRead", "PHPStan\\Node\\Property\\PropertyRead"},
	/* PT_CLASS_PROPERTY_WRITE */ {"propertyWrite", "PHPStan\\Node\\Property\\PropertyWrite"},
	/* PT_CLASS_PROPERTY_ASSIGN */ {"propertyAssign", "PHPStan\\Node\\Property\\PropertyAssign"},
	/* PT_CLASS_GATHERED_CLASS_METHOD */ {"gatheredClassMethod", "PHPStan\\Node\\ClassMethod"},
	/* PT_CLASS_CLASS_CONSTANT_FETCH */ {"classConstantFetch", "PHPStan\\Node\\Constant\\ClassConstantFetch"},
	/* PT_CLASS_COALESCE_ASSIGN_OP_EXPR */ {"coalesceAssignOpExpr", "PhpParser\\Node\\Expr\\AssignOp\\Coalesce"},
	/* PT_CLASS_CLASS_CONST_STMT */ {"classConstStmt", "PhpParser\\Node\\Stmt\\ClassConst"},
	/* PT_CLASS_EXPR_HANDLER */ {"exprHandler", "PHPStan\\Analyser\\ExprHandler"},
	/* PT_CLASS_STMT_HANDLER */ {"stmtHandler", "PHPStan\\Analyser\\StmtHandler"},
	/* PT_CLASS_CONTINUE_STMT */ {"continueStmt", "PhpParser\\Node\\Stmt\\Continue_"},
	/* PT_CLASS_BREAK_STMT */ {"breakStmt", "PhpParser\\Node\\Stmt\\Break_"},
	/* PT_CLASS_RESOLVED_FUNCTION_VARIANT */ {"resolvedFunctionVariant", "PHPStan\\Reflection\\ResolvedFunctionVariant"},
	/* PT_CLASS_EXTENSION_CLASS_HELPER */ {"extensionClassHelper", "PHPStan\\Type\\ExtensionClassHelper"},
	/* PT_CLASS_LAZY_EXTENSIONS_COLLECTION */ {"lazyExtensionsCollection", "PHPStan\\DependencyInjection\\LazyExtensionsCollection"},
	/* PT_CLASS_BOOLEAN_AND_EXPR */ {"booleanAndExpr", "PhpParser\\Node\\Expr\\BinaryOp\\BooleanAnd"},
	/* PT_CLASS_LOGICAL_AND_EXPR */ {"logicalAndExpr", "PhpParser\\Node\\Expr\\BinaryOp\\LogicalAnd"},
	/* PT_CLASS_BOOLEAN_OR_EXPR */ {"booleanOrExpr", "PhpParser\\Node\\Expr\\BinaryOp\\BooleanOr"},
	/* PT_CLASS_LOGICAL_OR_EXPR */ {"logicalOrExpr", "PhpParser\\Node\\Expr\\BinaryOp\\LogicalOr"},
	/* PT_CLASS_PARSER_ISSET_EXPR */ {"parserIssetExpr", "PhpParser\\Node\\Expr\\Isset_"},
	/* PT_CLASS_NULLSAFE_OPERATOR_HELPER */ {"nullsafeOperatorHelper", "PHPStan\\Analyser\\NullsafeOperatorHelper"},
	/* PT_CLASS_COALESCE_EXPR */ {"coalesceExpr", "PhpParser\\Node\\Expr\\BinaryOp\\Coalesce"},
	/* PT_CLASS_TYPE_EXPR */ {"typeExpr", "PHPStan\\Node\\Expr\\TypeExpr"},
	/* PT_CLASS_IDENTICAL_EXPR */ {"identicalExpr", "PhpParser\\Node\\Expr\\BinaryOp\\Identical"},
	/* PT_CLASS_STATIC_STMT */ {"staticStmt", "PhpParser\\Node\\Stmt\\Static_"},
	/* PT_CLASS_GLOBAL_STMT */ {"globalStmt", "PhpParser\\Node\\Stmt\\Global_"},
	/* PT_CLASS_PROPERTY_STMT */ {"propertyStmt", "PhpParser\\Node\\Stmt\\Property"},
	/* PT_CLASS_CONST_STMT */ {"constStmt", "PhpParser\\Node\\Stmt\\Const_"},
	/* PT_CLASS_CLASS_LIKE_STMT */ {"classLikeStmt", "PhpParser\\Node\\Stmt\\ClassLike"},
	/* PT_CLASS_FUNCTION_STMT */ {"functionStmt", "PhpParser\\Node\\Stmt\\Function_"},
	/* PT_CLASS_ECHO_STMT */ {"echoStmt", "PhpParser\\Node\\Stmt\\Echo_"},
	/* PT_CLASS_FOREACH_STMT */ {"foreachStmt", "PhpParser\\Node\\Stmt\\Foreach_"},
	/* PT_CLASS_IF_STMT */ {"ifStmt", "PhpParser\\Node\\Stmt\\If_"},
	/* PT_CLASS_RETURN_STMT */ {"returnStmt", "PhpParser\\Node\\Stmt\\Return_"},
	/* PT_CLASS_SWITCH_STMT */ {"switchStmt", "PhpParser\\Node\\Stmt\\Switch_"},
	/* PT_CLASS_UNSET_STMT */ {"unsetStmt", "PhpParser\\Node\\Stmt\\Unset_"},
	/* PT_CLASS_WHILE_STMT */ {"whileStmt", "PhpParser\\Node\\Stmt\\While_"},
	/* PT_CLASS_DO_STMT */ {"doStmt", "PhpParser\\Node\\Stmt\\Do_"},
	/* PT_CLASS_FOR_STMT */ {"forStmt", "PhpParser\\Node\\Stmt\\For_"},
	/* PT_CLASS_LABEL_STMT */ {"labelStmt", "PhpParser\\Node\\Stmt\\Label"},
	/* PT_CLASS_NOP_STMT */ {"nopStmt", "PhpParser\\Node\\Stmt\\Nop"},
	/* PT_CLASS_GOTO_STMT */ {"gotoStmt", "PhpParser\\Node\\Stmt\\Goto_"},
	/* PT_CLASS_EVAL_EXPR */ {"evalExpr", "PhpParser\\Node\\Expr\\Eval_"},
	/* PT_CLASS_INCLUDE_EXPR */ {"includeExpr", "PhpParser\\Node\\Expr\\Include_"},
	/* PT_CLASS_DOC_COMMENT */ {"docComment", "PhpParser\\Comment\\Doc"},
	/* PT_CLASS_NODE_FINDER */ {"nodeFinder", "PhpParser\\NodeFinder"},
	/* PT_CLASS_NODE_ABSTRACT */ {"nodeAbstract", "PhpParser\\NodeAbstract"},
	/* PT_CLASS_PHP_METHOD_REFLECTION */ {"phpMethodReflection", "PHPStan\\Reflection\\Php\\PhpMethodReflection"},
	/* PT_CLASS_NOOP_NODE_CALLBACK */ {"noopNodeCallback", "PHPStan\\Analyser\\NoopNodeCallback"},
	/* PT_CLASS_FUNCTION_CALL_EXPRESSION_NODE */ {"functionCallExpressionNode", "PHPStan\\Node\\FunctionCallExpressionNode"},
	/* PT_CLASS_METHOD_CALL_EXPRESSION_NODE */ {"methodCallExpressionNode", "PHPStan\\Node\\MethodCallExpressionNode"},
	/* PT_CLASS_STATIC_METHOD_CALL_EXPRESSION_NODE */ {"staticMethodCallExpressionNode", "PHPStan\\Node\\StaticMethodCallExpressionNode"},
	/* PT_CLASS_EXECUTION_END_NODE */ {"executionEndNode", "PHPStan\\Node\\ExecutionEndNode"},
	/* PT_CLASS_UNREACHABLE_STATEMENT_NODE */ {"unreachableStatementNode", "PHPStan\\Node\\UnreachableStatementNode"},
	/* PT_CLASS_VAR_TAG_CHANGED_EXPRESSION_TYPE_NODE */ {"varTagChangedExpressionTypeNode", "PHPStan\\Node\\VarTagChangedExpressionTypeNode"},
	/* PT_CLASS_PROPERTY_HOOK_STATEMENT_NODE */ {"propertyHookStatementNode", "PHPStan\\Node\\PropertyHookStatementNode"},
	/* PT_CLASS_TEMPLATE_ARGUMENT_CONSTRAINTS */ {"templateArgumentConstraints", "PHPStan\\Analyser\\Generics\\TemplateArgumentConstraints"},
	/* PT_CLASS_TEMPLATE_ARGUMENT_STATS */ {"templateArgumentStats", "PHPStan\\Analyser\\Generics\\TemplateArgumentStats"},
	/* PT_CLASS_ENSURED_NON_NULLABILITY_RESULT */ {"ensuredNonNullabilityResult", "PHPStan\\Analyser\\EnsuredNonNullabilityResult"},
	/* PT_CLASS_ENSURED_NON_NULLABILITY_RESULT_EXPRESSION */ {"ensuredNonNullabilityResultExpression", "PHPStan\\Analyser\\EnsuredNonNullabilityResultExpression"},
	/* PT_CLASS_RESOLVED_FUNCTION_VARIANT_WITH_ORIGINAL */ {"resolvedFunctionVariantWithOriginal", "PHPStan\\Reflection\\ResolvedFunctionVariantWithOriginal"},
	/* PT_CLASS_INVALIDATE_EXPR_NODE */ {"invalidateExprNode", "PHPStan\\Node\\InvalidateExprNode"},
	/* PT_CLASS_TERNARY_EXPR */ {"ternaryExpr", "PhpParser\\Node\\Expr\\Ternary"},
	/* PT_CLASS_BINARY_OP_MINUS */ {"binaryOpMinus", "PhpParser\\Node\\Expr\\BinaryOp\\Minus"},
	/* PT_CLASS_BINARY_OP_PLUS */ {"binaryOpPlus", "PhpParser\\Node\\Expr\\BinaryOp\\Plus"},
	/* PT_CLASS_BINARY_OP_NOT_IDENTICAL */ {"binaryOpNotIdentical", "PhpParser\\Node\\Expr\\BinaryOp\\NotIdentical"},
	/* PT_CLASS_ASSIGN_OP_CONCAT */ {"assignOpConcat", "PhpParser\\Node\\Expr\\AssignOp\\Concat"},
	/* PT_CLASS_ASSIGN_OP_BITWISE_AND */ {"assignOpBitwiseAnd", "PhpParser\\Node\\Expr\\AssignOp\\BitwiseAnd"},
	/* PT_CLASS_ASSIGN_OP_BITWISE_OR */ {"assignOpBitwiseOr", "PhpParser\\Node\\Expr\\AssignOp\\BitwiseOr"},
	/* PT_CLASS_ASSIGN_OP_BITWISE_XOR */ {"assignOpBitwiseXor", "PhpParser\\Node\\Expr\\AssignOp\\BitwiseXor"},
	/* PT_CLASS_ASSIGN_OP_DIV */ {"assignOpDiv", "PhpParser\\Node\\Expr\\AssignOp\\Div"},
	/* PT_CLASS_ASSIGN_OP_MOD */ {"assignOpMod", "PhpParser\\Node\\Expr\\AssignOp\\Mod"},
	/* PT_CLASS_ASSIGN_OP_PLUS */ {"assignOpPlus", "PhpParser\\Node\\Expr\\AssignOp\\Plus"},
	/* PT_CLASS_ASSIGN_OP_MINUS */ {"assignOpMinus", "PhpParser\\Node\\Expr\\AssignOp\\Minus"},
	/* PT_CLASS_ASSIGN_OP_MUL */ {"assignOpMul", "PhpParser\\Node\\Expr\\AssignOp\\Mul"},
	/* PT_CLASS_ASSIGN_OP_POW */ {"assignOpPow", "PhpParser\\Node\\Expr\\AssignOp\\Pow"},
	/* PT_CLASS_ASSIGN_OP_SHIFT_LEFT */ {"assignOpShiftLeft", "PhpParser\\Node\\Expr\\AssignOp\\ShiftLeft"},
	/* PT_CLASS_ASSIGN_OP_SHIFT_RIGHT */ {"assignOpShiftRight", "PhpParser\\Node\\Expr\\AssignOp\\ShiftRight"},
	/* PT_CLASS_EXISTING_ARRAY_DIM_FETCH */ {"existingArrayDimFetch", "PHPStan\\Node\\Expr\\ExistingArrayDimFetch"},
	/* PT_CLASS_VARIABLE_ASSIGN_NODE */ {"variableAssignNode", "PHPStan\\Node\\VariableAssignNode"},
	/* PT_CLASS_VIRTUAL_ASSIGN_NODE_CALLBACK */ {"virtualAssignNodeCallback", "PHPStan\\Analyser\\VirtualAssignNodeCallback"},
	/* PT_CLASS_COALESCE_EXPRESSION_NODE */ {"coalesceExpressionNode", "PHPStan\\Node\\CoalesceExpressionNode"},
	/* PT_CLASS_THROW_EXPR */ {"throwExpr", "PhpParser\\Node\\Expr\\Throw_"},
	/* PT_CLASS_NOOP_EXPRESSION_NODE */ {"noopExpressionNode", "PHPStan\\Node\\NoopExpressionNode"},
	/* PT_CLASS_BLOCK_STMT */ {"blockStmt", "PhpParser\\Node\\Stmt\\Block"},
	/* PT_CLASS_INTERFACE_STMT */ {"interfaceStmt", "PhpParser\\Node\\Stmt\\Interface_"},
	/* PT_CLASS_ENUM_STMT */ {"enumStmt", "PhpParser\\Node\\Stmt\\Enum_"},
	/* PT_CLASS_NODE_TO_REFLECTION */ {"nodeToReflection", "PHPStan\\BetterReflection\\SourceLocator\\Ast\\Strategy\\NodeToReflection"},
	/* PT_CLASS_LOCATED_SOURCE */ {"locatedSource", "PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource"},
	/* PT_CLASS_BETTER_REFLECTION_ENUM */ {"betterReflectionEnum", "PHPStan\\BetterReflection\\Reflection\\ReflectionEnum"},
	/* PT_CLASS_IN_CLASS_METHOD_NODE */ {"inClassMethodNode", "PHPStan\\Node\\InClassMethodNode"},
	/* PT_CLASS_IN_FUNCTION_NODE */ {"inFunctionNode", "PHPStan\\Node\\InFunctionNode"},
	/* PT_CLASS_FUNCTION_RETURN_STATEMENTS_NODE */ {"functionReturnStatementsNode", "PHPStan\\Node\\FunctionReturnStatementsNode"},
	/* PT_CLASS_RETURN_AFTER_FINALLY_NODE */ {"returnAfterFinallyNode", "PHPStan\\Node\\ReturnAfterFinallyNode"},
	/* PT_CLASS_RETURN_STATEMENT */ {"returnStatement", "PHPStan\\Node\\ReturnStatement"},
	/* PT_CLASS_IN_CLASS_NODE */ {"inClassNode", "PHPStan\\Node\\InClassNode"},
	/* PT_CLASS_CLASS_PROPERTIES_NODE */ {"classPropertiesNode", "PHPStan\\Node\\ClassPropertiesNode"},
	/* PT_CLASS_CLASS_METHODS_NODE */ {"classMethodsNode", "PHPStan\\Node\\ClassMethodsNode"},
	/* PT_CLASS_CLASS_CONSTANTS_NODE */ {"classConstantsNode", "PHPStan\\Node\\ClassConstantsNode"},
	/* PT_CLASS_FILE_READER */ {"fileReader", "PHPStan\\File\\FileReader"},
	/* PT_CLASS_DUMMY_CONSTRUCTOR_REFLECTION */ {"dummyConstructorReflection", "PHPStan\\Reflection\\Dummy\\DummyConstructorReflection"},
	/* PT_CLASS_GENERIC_TYPE_TEMPLATE_TRAVERSER */ {"genericTypeTemplateTraverser", "PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser"},
	/* PT_CLASS_CLOSURE_HANDLER */ {"closureHandler", "PHPStan\\Analyser\\ExprHandler\\ClosureHandler"},
	/* PT_CLASS_ALLOWED_CONSTANTS_RESULT */ {"allowedConstantsResult", "PHPStan\\Reflection\\AllowedConstantsResult"},
	/* PT_CLASS_GENERIC_PARAMETERS_ACCEPTOR_RESOLVER */ {"genericParametersAcceptorResolver", "PHPStan\\Reflection\\GenericParametersAcceptorResolver"},
	/* PT_CLASS_FUNCTION_VARIANT */ {"functionVariant", "PHPStan\\Reflection\\FunctionVariant"},
	/* PT_CLASS_EXTENDED_CALLABLE_FUNCTION_VARIANT */ {"extendedCallableFunctionVariant", "PHPStan\\Reflection\\ExtendedCallableFunctionVariant"},
};

zend_class_entry *pt_class(int idx)
{
	pt_class_ref *ref = &PT_G(class_refs)[idx];
	zend_class_entry *ce;
	zend_string *name;

	if (EXPECTED(ref->ce != NULL)) return ref->ce;

	if (ref->configured != NULL) {
		name = zend_string_copy(ref->configured);
	} else if (ref->default_name != NULL) {
		name = zend_string_init(ref->default_name, strlen(ref->default_name), 0);
	} else {
		zend_throw_error(NULL, "phpstan_turbo: class for '%s' was not configured", ref->key);
		return NULL;
	}
	ce = zend_lookup_class(name);
	if (ce == NULL) {
		zend_throw_error(NULL, "phpstan_turbo: class %s not found", ZSTR_VAL(name));
		zend_string_release(name);
		return NULL;
	}
	zend_string_release(name);
	ref->ce = ce;
	return ce;
}

zend_class_entry *pt_class_loaded(int idx)
{
	pt_class_ref *ref = &PT_G(class_refs)[idx];
	zend_class_entry *ce;
	zend_string *name;

	if (EXPECTED(ref->ce != NULL)) return ref->ce;

	if (ref->configured != NULL) {
		name = zend_string_copy(ref->configured);
	} else if (ref->default_name != NULL) {
		name = zend_string_init(ref->default_name, strlen(ref->default_name), 0);
	} else {
		zend_throw_error(NULL, "phpstan_turbo: class for '%s' was not configured", ref->key);
		return NULL;
	}
	ce = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(name);
	if (ce != NULL) {
		ref->ce = ce;
	}
	return ce;
}

void pt_class_map_configure(zend_string *key, zend_string *value)
{
	for (int idx = 0; idx < PT_CLASS_COUNT; idx++) {
		if (strcmp(ZSTR_VAL(key), pt_class_templates[idx].key) == 0) {
			pt_class_ref *ref = &PT_G(class_refs)[idx];
			if (ref->configured != NULL) {
				zend_string_release(ref->configured);
			}
			ref->configured = zend_string_copy(value);
			ref->ce = NULL;
			return;
		}
	}
}

void pt_class_refs_dump(zval *return_value)
{
	array_init_size(return_value, PT_CLASS_COUNT);
	for (int idx = 0; idx < PT_CLASS_COUNT; idx++) {
		zval v;
		if (pt_class_templates[idx].default_name != NULL) {
			ZVAL_STRING(&v, pt_class_templates[idx].default_name);
		} else {
			ZVAL_NULL(&v);
		}
		zend_hash_str_add_new(Z_ARRVAL_P(return_value), pt_class_templates[idx].key, strlen(pt_class_templates[idx].key), &v);
	}
}

/* }}} */

/* {{{ lifecycle */

zend_string *pt_str_cache_printer = nullptr;
zend_string *pt_str_contains_super_global = nullptr;
zend_string *pt_str_contains_call = nullptr;
zend_string *pt_str_array_map_args = nullptr;
zend_string *pt_str_start_file_pos = nullptr;
zend_string *pt_str_end_file_pos = nullptr;
static bool pt_strs_inited = false;

static HashTable pt_node_class_cache;
static bool pt_node_class_cache_inited = false;

void pt_init_strs()
{
	if (pt_strs_inited) return;
	pt_str_cache_printer = zend_string_init("phpstan_cache_printer", sizeof("phpstan_cache_printer") - 1, 0);
	pt_str_contains_super_global = zend_string_init("containsSuperGlobal", sizeof("containsSuperGlobal") - 1, 0);
	pt_str_contains_call = zend_string_init("containsCall", sizeof("containsCall") - 1, 0);
	pt_str_array_map_args = zend_string_init("arrayMapArgs", sizeof("arrayMapArgs") - 1, 0);
	pt_str_start_file_pos = zend_string_init("startFilePos", sizeof("startFilePos") - 1, 0);
	pt_str_end_file_pos = zend_string_init("endFilePos", sizeof("endFilePos") - 1, 0);
	pt_strs_inited = true;
}

void pt_support_rinit()
{
	PT_G(trinary_inited) = false;
	ZVAL_UNDEF(&PT_G(trinary_yes));
	ZVAL_UNDEF(&PT_G(trinary_maybe));
	ZVAL_UNDEF(&PT_G(trinary_no));

	for (int i = 0; i < PT_CLASS_COUNT; i++) {
		PT_G(class_refs)[i].key = pt_class_templates[i].key;
		PT_G(class_refs)[i].default_name = pt_class_templates[i].default_name;
		PT_G(class_refs)[i].configured = NULL;
		PT_G(class_refs)[i].ce = NULL;
	}

	pt_strs_inited = false;
	pt_node_class_cache_inited = false;
	pt_native_visitor_index_reset();
}

void pt_support_rshutdown()
{
	if (PT_G(trinary_inited)) {
		zval_ptr_dtor(&PT_G(trinary_yes));
		zval_ptr_dtor(&PT_G(trinary_maybe));
		zval_ptr_dtor(&PT_G(trinary_no));
		PT_G(trinary_inited) = false;
	}
	for (int i = 0; i < PT_CLASS_COUNT; i++) {
		if (PT_G(class_refs)[i].configured != NULL) {
			zend_string_release(PT_G(class_refs)[i].configured);
			PT_G(class_refs)[i].configured = NULL;
		}
		PT_G(class_refs)[i].ce = NULL;
	}
	if (pt_strs_inited) {
		zend_string_release(pt_str_cache_printer);
		zend_string_release(pt_str_contains_super_global);
		zend_string_release(pt_str_contains_call);
		zend_string_release(pt_str_array_map_args);
		zend_string_release(pt_str_start_file_pos);
		zend_string_release(pt_str_end_file_pos);
		pt_strs_inited = false;
	}
	if (pt_node_class_cache_inited) {
		zend_hash_destroy(&pt_node_class_cache);
		pt_node_class_cache_inited = false;
	}
	pt_native_visitor_index_reset();
}

/* }}} */

/* {{{ TrinaryLogic singletons */

zval *pt_trinary_singleton(zend_long value)
{
	if (UNEXPECTED(!PT_G(trinary_inited))) {
		static const zend_long values[3] = {PT_TRI_YES, PT_TRI_MAYBE, PT_TRI_NO};
		zend_class_entry *impl = pt_ce_trinary;
		zval *slots[3];
		slots[0] = &PT_G(trinary_yes);
		slots[1] = &PT_G(trinary_maybe);
		slots[2] = &PT_G(trinary_no);
		for (int i = 0; i < 3; i++) {
			object_init_ex(slots[i], impl);
			ZVAL_LONG(OBJ_PROP_NUM(Z_OBJ_P(slots[i]), PT_TRI_PROP_VALUE), values[i]);
		}
		PT_G(trinary_inited) = true;
	}

	if (value == PT_TRI_YES) return &PT_G(trinary_yes);
	if (value == PT_TRI_MAYBE) return &PT_G(trinary_maybe);
	return &PT_G(trinary_no);
}

/* }}} */

/* {{{ userland callback helpers */

zend_function *pt_find_method(zend_class_entry *ce, const char *lcname, size_t len)
{
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", ZSTR_VAL(ce->name), lcname);
	}
	return fn;
}

bool pt_call_type_equals(zval *type_a, zval *type_b)
{
	/* $a->equals($b) — directly for a native class registering the op
	 * (TypeOps.h), through the engine otherwise */
	zv::Val ret = pt_type_op(Z_OBJ_P(type_a), PT_OP_EQUALS, 1, type_b);
	if (UNEXPECTED(ret.isUndef())) return false;
	return Z_TYPE_P(ret.raw()) == IS_TRUE;
}

bool pt_types_identical_or_equal(zval *type_a, zval *type_b)
{
	if (Z_OBJ_P(type_a) == Z_OBJ_P(type_b)) return true;
	return pt_call_type_equals(type_a, type_b);
}

bool pt_type_combinator_binary(const char *lcname, size_t len, zval *type_a, zval *type_b, zval *result)
{
	zval args[2];

	ZVAL_COPY_VALUE(&args[0], type_a);
	ZVAL_COPY_VALUE(&args[1], type_b);
	zv::Val value = pt_type_combinator_call(lcname, len, 2, args);
	if (UNEXPECTED(value.isUndef())) return false;
	*result = value.take();
	return true;
}

bool pt_type_describe_precise(zval *type, zval *result)
{
	/* the shadowing VerbosityLevel's precise() singleton (VerbosityLevel.cpp) */
	zval *precise = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_PRECISE);
	if (UNEXPECTED(precise == NULL)) return false;

	zv::Val described = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, precise);
	if (UNEXPECTED(described.isUndef())) return false;
	*result = described.take();
	return true;
}

void pt_throw_should_not_happen()
{
	zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
	if (ce == NULL) return; /* error already thrown */
	zend_throw_exception(ce, "Internal error.", 0);
}

bool pt_call_scope_bool(zval *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool *out)
{
	zend_class_entry *ce = Z_OBJCE_P(scope);
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	zval ret;

	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", ZSTR_VAL(ce->name), lcname);
		return false;
	}
	zend_call_known_function(fn, Z_OBJ_P(scope), ce, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) return false;
	*out = zend_is_true(&ret);
	zval_ptr_dtor(&ret);
	return true;
}

/* }}} */

/* {{{ node class info + attributes */

static void pt_node_class_info_free(zval *zv)
{
	pt_node_class_info *info = (pt_node_class_info *) Z_PTR_P(zv);
	if (info->subnode_offsets != NULL) {
		efree(info->subnode_offsets);
	}
	efree(info);
}

int32_t pt_instance_prop_offset(zend_class_entry *ce, const char *name, size_t len)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, name, len);
	if (info == NULL || (info->flags & ZEND_ACC_STATIC) != 0) return -1;
	return (int32_t) info->offset;
}

pt_node_class_info *pt_get_node_class_info(zend_class_entry *ce)
{
	pt_node_class_info *info;
	zend_class_entry *variable_ce;

	if (!pt_node_class_cache_inited) {
		zend_hash_init(&pt_node_class_cache, 64, NULL, pt_node_class_info_free, 0);
		pt_node_class_cache_inited = true;
	}

	info = (pt_node_class_info *) zend_hash_find_ptr(&pt_node_class_cache, ce->name);
	if (EXPECTED(info != NULL)) return info;

	info = (pt_node_class_info *) ecalloc(1, sizeof(pt_node_class_info));
	info->attributes_offset = pt_instance_prop_offset(ce, "attributes", sizeof("attributes") - 1);
	info->name_offset = pt_instance_prop_offset(ce, "name", sizeof("name") - 1);

	variable_ce = pt_class(PT_CLASS_VARIABLE);
	if (variable_ce == NULL) {
		efree(info);
		return NULL;
	}
	info->is_variable = instanceof_function(ce, variable_ce);

	zend_hash_add_ptr(&pt_node_class_cache, ce->name, info);
	return info;
}

pt_node_class_info *pt_node_class_info_for_object(zend_object *obj)
{
	zend_class_entry *ce = obj->ce;
	pt_node_class_info *info = pt_get_node_class_info(ce);
	zend_function *fn;
	zval retval;

	if (info == NULL) return NULL;
	if (info->subnode_offsets != NULL || info->subnode_count == UINT32_MAX) return info;

	fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, "getsubnodenames", sizeof("getsubnodenames") - 1);
	if (fn == NULL || (fn->common.fn_flags & ZEND_ACC_ABSTRACT) != 0) {
		info->subnode_count = UINT32_MAX;
		return info;
	}

	zend_call_known_function(fn, obj, ce, &retval, 0, NULL, NULL);
	if (EG(exception) || Z_TYPE(retval) != IS_ARRAY) {
		zval_ptr_dtor(&retval);
		info->subnode_count = UINT32_MAX;
		return info;
	}

	{
		HashTable *names = Z_ARRVAL(retval);
		uint32_t count = zend_hash_num_elements(names);
		uint32_t i = 0;
		zval *name_zv;

		info->subnode_offsets = (uint32_t *) emalloc(sizeof(uint32_t) * (count > 0 ? count : 1));
		ZEND_HASH_FOREACH_VAL(names, name_zv) {
			int32_t off;
			if (Z_TYPE_P(name_zv) != IS_STRING) continue;
			off = pt_instance_prop_offset(ce, Z_STRVAL_P(name_zv), Z_STRLEN_P(name_zv));
			if (off >= 0) {
				info->subnode_offsets[i++] = (uint32_t) off;
			}
		} ZEND_HASH_FOREACH_END();
		info->subnode_count = i;
	}
	zval_ptr_dtor(&retval);
	return info;
}

zval *pt_node_attribute(zend_object *node, zend_string *name)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *attrs;

	if (info == NULL || info->attributes_offset < 0) return NULL;
	attrs = OBJ_PROP(node, info->attributes_offset);
	ZVAL_DEREF(attrs);
	if (Z_TYPE_P(attrs) != IS_ARRAY) return NULL;
	return zend_hash_find(Z_ARRVAL_P(attrs), name);
}

/* {{{ natively dispatched node visitors */

/* the registered entries (file-statics of the visitor ports, MINIT order)
 * and the per-request index from class entry to entry */
#define PT_NATIVE_VISITORS_LIMIT 64
static const pt_native_visitor *pt_native_visitors[PT_NATIVE_VISITORS_LIMIT];
static uint32_t pt_native_visitor_count = 0;
static HashTable pt_native_visitor_index;
static bool pt_native_visitor_index_inited = false;

void pt_native_visitor_register(const pt_native_visitor *entry)
{
	if (UNEXPECTED(pt_native_visitor_count >= PT_NATIVE_VISITORS_LIMIT)) {
		zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: more than %d natively dispatched node visitors", PT_NATIVE_VISITORS_LIMIT);
	}
	pt_native_visitors[pt_native_visitor_count++] = entry;
}

void pt_native_visitor_index_reset()
{
	if (pt_native_visitor_index_inited) {
		zend_hash_destroy(&pt_native_visitor_index);
		pt_native_visitor_index_inited = false;
	}
}

const pt_native_visitor *pt_native_visitor_for(zend_class_entry *ce)
{
	if (UNEXPECTED(!pt_native_visitor_index_inited)) {
		zend_hash_init(&pt_native_visitor_index, pt_native_visitor_count, NULL, NULL, 0);
		for (uint32_t i = 0; i < pt_native_visitor_count; i++) {
			/* NULL until activation declared the shadowing class */
			zend_class_entry *declared = *pt_native_visitors[i]->ce;
			if (declared != NULL) {
				zend_hash_index_add_ptr(&pt_native_visitor_index, (zend_ulong) (uintptr_t) declared, (void *) pt_native_visitors[i]);
			}
		}
		pt_native_visitor_index_inited = true;
	}
	return (const pt_native_visitor *) zend_hash_index_find_ptr(&pt_native_visitor_index, (zend_ulong) (uintptr_t) ce);
}

/* }}} */

bool pt_node_set_attribute(zend_object *node, zend_string *name, zval *value)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *attrs;

	if (info == NULL || info->attributes_offset < 0) return false;
	attrs = OBJ_PROP(node, info->attributes_offset);
	ZVAL_DEREF(attrs);
	if (Z_TYPE_P(attrs) != IS_ARRAY) return false;
	SEPARATE_ARRAY(attrs);
	Z_TRY_ADDREF_P(value);
	zend_hash_update(Z_ARRVAL_P(attrs), name, value);
	return true;
}

/* }}} */

/* {{{ node key */

/* The printed form of the expression, without pt_node_key's attribute-derived
 * suffixes — the equivalent of a plain $exprPrinter->printExpr($node) call
 * (which caches through the printer attribute). Owned string; NULL on
 * failure with an exception pending. */
static zend_string *pt_node_printed_expr(zend_object *node, zval *expr_printer)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);

	if (info == NULL) return NULL;

	/* fast path: '$' . $node->name for Variable with a string name */
	if (info->is_variable && info->name_offset >= 0) {
		zval *name = OBJ_PROP(node, info->name_offset);
		ZVAL_DEREF(name);
		if (Z_TYPE_P(name) == IS_STRING) {
			zend_string *name_str = Z_STR_P(name);
			zend_string *key = zend_string_alloc(ZSTR_LEN(name_str) + 1, 0);
			ZSTR_VAL(key)[0] = '$';
			memcpy(ZSTR_VAL(key) + 1, ZSTR_VAL(name_str), ZSTR_LEN(name_str));
			ZSTR_VAL(key)[ZSTR_LEN(key)] = '\0';
			return key;
		}
	}

	zval *attr = pt_node_attribute(node, pt_str_cache_printer);
	if (attr != NULL && Z_TYPE_P(attr) == IS_STRING) return zend_string_copy(Z_STR_P(attr));

	return pt_expr_printer_print_uncached(expr_printer, node);
}

zend_string *pt_node_key(zend_object *node, zval *expr_printer)
{
	zend_string *key;

	pt_init_strs();

	/* the Variable fast path returns before any suffix handling below, same
	 * as the twin: a Variable node never carries the suffix attributes */
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	if (info == NULL) return NULL;
	if (info->is_variable && info->name_offset >= 0) {
		zval *name = OBJ_PROP(node, info->name_offset);
		ZVAL_DEREF(name);
		if (Z_TYPE_P(name) == IS_STRING) return pt_node_printed_expr(node, expr_printer);
	}

	key = pt_node_printed_expr(node, expr_printer);
	if (key == NULL) return NULL;

	/* FunctionLike with arrayMapArgs + startFilePos: append the array_map
	 * argument suffix exactly like MutatingScope::getNodeKey() */
	{
		zend_class_entry *fl_ce = pt_class(PT_CLASS_FUNCTION_LIKE);
		if (fl_ce == NULL) {
			zend_string_release(key);
			return NULL;
		}
		if (instanceof_function(node->ce, fl_ce)) {
			zval *map_args = pt_node_attribute(node, pt_str_array_map_args);
			zval *start_pos = pt_node_attribute(node, pt_str_start_file_pos);
			if (map_args != NULL && Z_TYPE_P(map_args) != IS_NULL
				&& start_pos != NULL && Z_TYPE_P(start_pos) != IS_NULL) {
				smart_str str = {};
				smart_str_append(&str, key);
				smart_str_appendl(&str, "/*", 2);
				if (Z_TYPE_P(start_pos) == IS_LONG) {
					smart_str_append_long(&str, Z_LVAL_P(start_pos));
				}
				if (Z_TYPE_P(map_args) == IS_ARRAY) {
					zval *arg;
					ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(map_args), arg) {
						zval *arg_deref = arg;
						zval *value_prop;
						ZVAL_DEREF(arg_deref);
						if (Z_TYPE_P(arg_deref) != IS_OBJECT) continue;
						{
							int32_t voff = pt_instance_prop_offset(Z_OBJCE_P(arg_deref), "value", sizeof("value") - 1);
							if (voff < 0) continue;
							value_prop = OBJ_PROP(Z_OBJ_P(arg_deref), voff);
							ZVAL_DEREF(value_prop);
						}
						if (Z_TYPE_P(value_prop) != IS_OBJECT) continue;
						smart_str_appendc(&str, ':');
						{
							/* plain printExpr like the twin — NOT the full node
							 * key: an argument carrying its own suffix
							 * attributes must not have them appended here */
							zend_string *arg_key = pt_node_printed_expr(Z_OBJ_P(value_prop), expr_printer);
							if (arg_key == NULL) {
								smart_str_free(&str);
								zend_string_release(key);
								return NULL;
							}
							smart_str_append(&str, arg_key);
							zend_string_release(arg_key);
						}
					} ZEND_HASH_FOREACH_END();
				}
				smart_str_appendl(&str, "*/", 2);
				zend_string_release(key);
				key = smart_str_extract(&str);
			}
		}
	}

	return key;
}

/* }}} */

/* {{{ findFirst walker + superglobal scan */

zend_object *pt_find_first_recursive(zend_object *node, pt_node_matcher matcher, void *ctx)
{
	pt_node_class_info *info;
	zend_class_entry *node_iface;
	uint32_t i;

	if (matcher(node, ctx)) return node;
	if (UNEXPECTED(((pt_find_ctx *) ctx)->failed)) return NULL;

	info = pt_node_class_info_for_object(node);
	if (info == NULL || !PT_HAS_SUBNODES(info)) return NULL;

	node_iface = pt_class(PT_CLASS_NODE);
	if (UNEXPECTED(node_iface == NULL)) {
		((pt_find_ctx *) ctx)->failed = true;
		return NULL;
	}

	for (i = 0; i < info->subnode_count; i++) {
		zval *val = OBJ_PROP(node, info->subnode_offsets[i]);
		ZVAL_DEREF(val);
		if (Z_TYPE_P(val) == IS_OBJECT) {
			if (instanceof_function(Z_OBJCE_P(val), node_iface)) {
				zend_object *found = pt_find_first_recursive(Z_OBJ_P(val), matcher, ctx);
				if (found != NULL || ((pt_find_ctx *) ctx)->failed) return found;
			}
		} else if (Z_TYPE_P(val) == IS_ARRAY) {
			zval *el;
			ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(val), el) {
				zval *el_deref = el;
				ZVAL_DEREF(el_deref);
				if (Z_TYPE_P(el_deref) == IS_OBJECT && instanceof_function(Z_OBJCE_P(el_deref), node_iface)) {
					zend_object *found = pt_find_first_recursive(Z_OBJ_P(el_deref), matcher, ctx);
					if (found != NULL || ((pt_find_ctx *) ctx)->failed) return found;
				}
			} ZEND_HASH_FOREACH_END();
		}
	}
	return NULL;
}

static const pt_superglobal_name pt_superglobals[] = {
	{"GLOBALS", 7},
	{"_SERVER", 7},
	{"_GET", 4},
	{"_POST", 5},
	{"_FILES", 6},
	{"_COOKIE", 7},
	{"_SESSION", 8},
	{"_REQUEST", 8},
	{"_ENV", 4},
};

bool pt_is_superglobal_cstr(const char *name, size_t len)
{
	for (size_t i = 0; i < sizeof(pt_superglobals) / sizeof(pt_superglobals[0]); i++) {
		if (len == pt_superglobals[i].len && memcmp(name, pt_superglobals[i].name, len) == 0) return true;
	}
	return false;
}

bool pt_is_superglobal_name(zend_string *name)
{
	return pt_is_superglobal_cstr(ZSTR_VAL(name), ZSTR_LEN(name));
}

const pt_superglobal_name *pt_superglobal_names(size_t *count)
{
	*count = sizeof(pt_superglobals) / sizeof(pt_superglobals[0]);
	return pt_superglobals;
}

/* {{{ PhpParser CallLike reads */

/* the per-request generation of the engine's caches (Engine.cpp) */
extern uint32_t pt_engine_generation;

namespace {

/* a class entry's verdict: the byte offset of its `args` slot when
 * getRawArgs() is a php-parser call class's `return $this->args;` and
 * isFirstClassCallable() / getArgs() are CallLike's own; -1 = call the
 * methods */
struct pt_call_like_class
{
	zend_class_entry *ce;
	int32_t argsOffset;
	uint32_t generation;
};

/* a handful of call classes alternate (FuncCall, MethodCall, StaticCall,
 * New_, NullsafeMethodCall): a small table scanned linearly, replaced
 * round-robin */
constexpr uint32_t PT_CALL_LIKE_CLASSES_LIMIT = 8;
pt_call_like_class pt_call_like_classes[PT_CALL_LIKE_CLASSES_LIMIT];
uint32_t pt_call_like_classes_next = 0;

/* the method's declaring class is one of the class-map classes */
bool pt_call_like_declared_by(zend_class_entry *ce, const char *lcname, size_t len, std::initializer_list<int> classIdxs)
{
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	if (fn == NULL) return false;
	for (int classIdx : classIdxs) {
		if (fn->common.scope == pt_class_loaded(classIdx)) return true;
	}
	return false;
}

int32_t pt_call_like_resolve(zend_class_entry *ce)
{
	for (pt_call_like_class &entry : pt_call_like_classes) {
		if (entry.ce == ce && entry.generation == pt_engine_generation) return entry.argsOffset;
	}
	int32_t argsOffset = -1;
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, "args", sizeof("args") - 1);
	if (info != NULL && (info->flags & ZEND_ACC_STATIC) == 0
		&& pt_call_like_declared_by(ce, PT_LC("getrawargs"), { PT_CLASS_FUNC_CALL, PT_CLASS_METHOD_CALL, PT_CLASS_NULLSAFE_METHOD_CALL, PT_CLASS_STATIC_CALL, PT_CLASS_NEW })
		&& pt_call_like_declared_by(ce, PT_LC("isfirstclasscallable"), { PT_CLASS_CALL_LIKE })
		&& pt_call_like_declared_by(ce, PT_LC("getargs"), { PT_CLASS_CALL_LIKE })) {
		argsOffset = (int32_t) info->offset;
	}
	pt_call_like_classes[pt_call_like_classes_next] = { ce, argsOffset, pt_engine_generation };
	pt_call_like_classes_next = (pt_call_like_classes_next + 1) % PT_CALL_LIKE_CLASSES_LIMIT;
	return argsOffset;
}

/* the initialized `args` slot (dereferenced), NULL when the methods must
 * answer */
zval *pt_call_like_args_slot(zend_object *call)
{
	int32_t argsOffset = pt_call_like_resolve(call->ce);
	if (UNEXPECTED(argsOffset < 0)) return NULL;
	zval *args = OBJ_PROP(call, (uint32_t) argsOffset);
	ZVAL_DEREF(args);
	return EXPECTED(Z_TYPE_P(args) == IS_ARRAY) ? args : NULL;
}

/* count($rawArgs) === 1 && current($rawArgs) instanceof VariadicPlaceholder;
 * false = pending exception */
bool pt_call_like_raw_args_are_first_class_callable(zval *rawArgs, bool &out)
{
	out = false;
	if (Z_TYPE_P(rawArgs) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(rawArgs)) != 1) return true;
	/* current($rawArgs): the array's internal pointer, like the twin */
	zval *first = zend_hash_get_current_data(Z_ARRVAL_P(rawArgs));
	if (first == NULL) return true;
	ZVAL_DEREF(first);
	if (Z_TYPE_P(first) != IS_OBJECT) return true;
	zend_class_entry *variadicPlaceholderCe = pt_class(PT_CLASS_VARIADIC_PLACEHOLDER);
	if (UNEXPECTED(variadicPlaceholderCe == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(first), variadicPlaceholderCe);
	return true;
}

/* $call->method() by name, kept in hold; NULL = pending exception */
zend_never_inline ZEND_COLD zval *pt_call_like_call(zend_object *call, const char *lcname, size_t len, zv::Val &hold)
{
	hold = pt_type_call(call, lcname, len, 0, NULL);
	return hold.isUndef() ? NULL : hold.raw();
}

} // namespace

zval *pt_call_like_raw_args(zend_object *call, zv::Val &hold)
{
	zval *args = pt_call_like_args_slot(call);
	return EXPECTED(args != NULL) ? args : pt_call_like_call(call, PT_LC("getrawargs"), hold);
}

bool pt_call_like_is_first_class_callable(zend_object *call, bool &out)
{
	zval *args = pt_call_like_args_slot(call);
	if (EXPECTED(args != NULL)) return pt_call_like_raw_args_are_first_class_callable(args, out);
	zv::Val hold;
	zval *result = pt_call_like_call(call, PT_LC("isfirstclasscallable"), hold);
	if (UNEXPECTED(result == NULL)) return false;
	out = zend_is_true(result);
	return true;
}

zval *pt_call_like_args(zend_object *call, zv::Val &hold)
{
	zval *args = pt_call_like_args_slot(call);
	if (EXPECTED(args != NULL)) {
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_raw_args_are_first_class_callable(args, firstClassCallable))) return NULL;
		if (EXPECTED(!firstClassCallable)) return args;
	}
	return pt_call_like_call(call, PT_LC("getargs"), hold);
}

/* }}} */

static bool pt_superglobal_matcher(zend_object *node, void *ctx)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *name;

	(void) ctx;
	if (info == NULL || !info->is_variable || info->name_offset < 0) return false;
	name = OBJ_PROP(node, info->name_offset);
	ZVAL_DEREF(name);
	if (Z_TYPE_P(name) != IS_STRING) return false;
	return pt_is_superglobal_name(Z_STR_P(name));
}

bool pt_expr_contains_superglobal(zend_object *expr)
{
	zval *attr;
	pt_find_ctx ctx;
	bool contains;
	zval attr_val;

	pt_init_strs();

	attr = pt_node_attribute(expr, pt_str_contains_super_global);
	if (attr != NULL && (Z_TYPE_P(attr) == IS_TRUE || Z_TYPE_P(attr) == IS_FALSE)) return Z_TYPE_P(attr) == IS_TRUE;

	memset(&ctx, 0, sizeof(ctx));
	contains = pt_find_first_recursive(expr, pt_superglobal_matcher, &ctx) != NULL;
	ZVAL_BOOL(&attr_val, contains);
	pt_node_set_attribute(expr, pt_str_contains_super_global, &attr_val);
	return contains;
}

/* }}} */

/* {{{ holder helpers */

bool pt_check_holder(zval *zv)
{
	if (UNEXPECTED(Z_TYPE_P(zv) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(zv), pt_ce_expr_type_holder))) {
		zend_type_error("phpstan_turbo: expected ExpressionTypeHolder, got %s", zend_zval_value_name(zv));
		return false;
	}
	return true;
}

void pt_holder_create(zval *result, zval *expr, zval *type, zend_long certainty)
{
	zend_class_entry *impl = pt_ce_expr_type_holder;
	zend_object *obj;
	object_init_ex(result, impl);
	obj = Z_OBJ_P(result);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_EXPR), expr);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_TYPE), type);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_CERTAINTY), pt_trinary_singleton(certainty));
}

bool pt_holder_and(zval *a, zval *b, zval *result)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);
	zval *a_type = OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE);
	zval *b_type = OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE);
	zend_long ac = pt_holder_certainty_value(ao);
	zend_long bc = pt_holder_certainty_value(bo);

	if (pt_types_identical_or_equal(a_type, b_type)) {
		if ((ac & bc) == PT_TRI_YES || ac == PT_TRI_MAYBE) {
			ZVAL_COPY(result, a);
		} else {
			ZVAL_COPY(result, b);
		}
		return true;
	}
	if (UNEXPECTED(EG(exception))) return false;
	{
		zval union_type;
		if (UNEXPECTED(!pt_type_combinator_binary("union", sizeof("union") - 1, a_type, b_type, &union_type))) return false;
		pt_holder_create(result, OBJ_PROP_NUM(ao, PT_ETH_PROP_EXPR), &union_type, ac & bc);
		zval_ptr_dtor(&union_type);
	}
	return true;
}

bool pt_holder_equals(zval *a, zval *b, bool *out)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);

	if (ao == bo) {
		*out = true;
		return true;
	}
	if (pt_holder_certainty_value(ao) != pt_holder_certainty_value(bo)) {
		*out = false;
		return true;
	}
	*out = pt_types_identical_or_equal(OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE), OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE));
	return !EG(exception);
}

bool pt_holder_equal_types(zval *a, zval *b, bool *out)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);
	if (ao == bo) {
		*out = true;
		return true;
	}
	*out = pt_types_identical_or_equal(OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE), OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE));
	return !EG(exception);
}

zend_string *pt_ceh_key_build(HashTable *conds, zval *type_holder)
{
	smart_str str = {};
	zend_string *key_str;
	zend_ulong kidx;
	zval *entry;
	bool first = true;

	ZEND_HASH_FOREACH_KEY_VAL(conds, kidx, key_str, entry) {
		zval described;
		zval *entry_deref = entry;
		ZVAL_DEREF(entry_deref);
		if (!first) {
			smart_str_appendl(&str, " && ", 4);
		}
		first = false;
		if (key_str != NULL) {
			smart_str_append(&str, key_str);
		} else {
			smart_str_append_long(&str, (zend_long) kidx);
		}
		smart_str_appendc(&str, '=');
		if (UNEXPECTED(!pt_type_describe_precise(OBJ_PROP_NUM(Z_OBJ_P(entry_deref), PT_ETH_PROP_TYPE), &described))) {
			smart_str_free(&str);
			return NULL;
		}
		if (EXPECTED(Z_TYPE(described) == IS_STRING)) {
			smart_str_append(&str, Z_STR(described));
		}
		zval_ptr_dtor(&described);
	} ZEND_HASH_FOREACH_END();

	smart_str_appendl(&str, " => ", 4);
	{
		zval described;
		if (UNEXPECTED(!pt_type_describe_precise(OBJ_PROP_NUM(Z_OBJ_P(type_holder), PT_ETH_PROP_TYPE), &described))) {
			smart_str_free(&str);
			return NULL;
		}
		if (EXPECTED(Z_TYPE(described) == IS_STRING)) {
			smart_str_append(&str, Z_STR(described));
		}
		zval_ptr_dtor(&described);
	}
	smart_str_appendl(&str, " (", 2);
	{
		zend_long certainty = pt_holder_certainty_value(Z_OBJ_P(type_holder));
		if (certainty == PT_TRI_YES) {
			smart_str_appendl(&str, "Yes", 3);
		} else if (certainty == PT_TRI_MAYBE) {
			smart_str_appendl(&str, "Maybe", 5);
		} else {
			smart_str_appendl(&str, "No", 2);
		}
	}
	smart_str_appendc(&str, ')');

	return smart_str_extract(&str);
}

/* }}} */
