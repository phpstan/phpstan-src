<?php declare(strict_types = 1);

/*
 * The shadowed classes whose native implementation is compiled from the PHP
 * twin by turbo-ext/bin/compile-twins.php instead of written by hand. The
 * generated file is the one the twin's ShadowedByTurboExtension attribute
 * names. Per class (FQCN =>), the class-entry variable the rest of the
 * extension uses for it, or an array with
 *
 * - ce: that variable; defineCe: whether the generated file defines it
 * - new: [function, flavor] — a `new X(...)` entry for native callers
 *   (val: zv::Val f(<constructor parameters>), out: bool f(zval *out, ...),
 *   argv: zv::Val f(uint32_t argc, zval *argv))
 * - dispatch: a reflection family's member dispatch — function, enum,
 *   prefix of its constants, receiver parameter name, fallback for members
 *   the class does not declare
 *
 * Exports with any other shape stay hand-written in CompiledSupport.cpp.
 */

return [
	'PHPStan\\TrinaryLogic' => 'pt_ce_trinary',
	'PHPStan\\Type\\AcceptsResult' => 'pt_ce_accepts_result',
	'PHPStan\\Type\\IsSuperTypeOfResult' => 'pt_ce_is_super_type_of_result',
	'PHPStan\\Analyser\\AssignTargetWalkMode' => ['ce' => 'pt_ce_assign_target_walk_mode', 'defineCe' => true, 'new' => ['pt_assign_target_walk_mode_new', 'val']],
	'PHPStan\\Analyser\\EndStatementResult' => ['ce' => 'pt_ce_end_statement_result', 'defineCe' => true, 'new' => ['pt_end_statement_result_new', 'val']],
	'PHPStan\\Analyser\\EnsuredNonNullabilityResult' => ['ce' => 'pt_ce_ensured_non_nullability_result', 'defineCe' => true, 'new' => ['pt_ensured_non_nullability_result_new', 'val']],
	'PHPStan\\Analyser\\EnsuredNonNullabilityResultExpression' => ['ce' => 'pt_ce_ensured_non_nullability_result_expression', 'defineCe' => true, 'new' => ['pt_ensured_non_nullability_result_expression_new', 'val']],
	'PHPStan\\Analyser\\ExpressionTypeHolder' => 'pt_ce_expr_type_holder',
	'PHPStan\\Analyser\\InternalEndStatementResult' => ['ce' => 'pt_ce_internal_end_statement_result', 'defineCe' => true, 'new' => ['pt_internal_end_statement_result_new', 'val']],
	'PHPStan\\Analyser\\InternalStatementExitPoint' => ['ce' => 'pt_ce_internal_statement_exit_point', 'defineCe' => true, 'new' => ['pt_internal_statement_exit_point_new', 'val']],
	'PHPStan\\Analyser\\PreparedAssignTarget' => ['ce' => 'pt_ce_prepared_assign_target', 'defineCe' => true, 'new' => ['pt_prepared_assign_target_new', 'argv']],
	'PHPStan\\Analyser\\ProcessArrowFunctionResult' => ['ce' => 'pt_ce_process_arrow_function_result', 'defineCe' => true, 'new' => ['pt_process_arrow_function_result_new', 'val']],
	'PHPStan\\Analyser\\ProcessClosureResult' => ['ce' => 'pt_ce_process_closure_result', 'defineCe' => true, 'new' => ['pt_process_closure_result_new', 'val']],
	'PHPStan\\Analyser\\StatementExitPoint' => ['ce' => 'pt_ce_statement_exit_point', 'defineCe' => true, 'new' => ['pt_statement_exit_point_new', 'val']],
	'PHPStan\\Analyser\\ParametersProcessor' => ['ce' => 'pt_ce_parameters_processor', 'defineCe' => true],
	'PHPStan\\Type\\Generic\\TemplateTypeReference' => ['ce' => 'pt_ce_template_type_reference', 'defineCe' => true, 'new' => ['pt_template_type_reference_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateTypeScope' => ['ce' => 'pt_ce_template_type_scope', 'defineCe' => true, 'new' => ['pt_template_type_scope_new', 'out']],
	'PHPStan\\Type\\Generic\\TypeProjectionHelper' => ['ce' => 'pt_ce_type_projection_helper', 'defineCe' => true],
	'PHPStan\\Type\\CircularTypeAliasErrorType' => ['ce' => 'pt_ce_circular_type_alias_error_type', 'defineCe' => true],
	'PHPStan\\Reflection\\Native\\NativeParameterReflection' => ['ce' => 'pt_ce_native_parameter_reflection', 'defineCe' => true, 'new' => ['pt_native_parameter_reflection_new', 'argv']],
	'PHPStan\\Reflection\\TrivialParametersAcceptor' => ['ce' => 'pt_ce_trivial_parameters_acceptor', 'defineCe' => true, 'new' => ['pt_trivial_parameters_acceptor_new', 'val'], 'dispatch' => ['function' => 'pt_trivial_parameters_acceptor_call', 'enum' => 'pt_parameters_acceptor_member', 'prefix' => 'PT_PA_', 'receiver' => 'acceptor', 'fallback' => 'pt_parameters_acceptor_call_method']],
	'PHPStan\\Reflection\\Dummy\\ChangedTypeMethodReflection' => ['ce' => 'pt_ce_changed_type_method_reflection', 'defineCe' => true, 'new' => ['pt_changed_type_method_reflection_new', 'val'], 'dispatch' => ['function' => 'pt_changed_type_method_reflection_call', 'enum' => 'pt_method_reflection_member', 'prefix' => 'PT_MR_', 'receiver' => 'method']],
	'PHPStan\\Reflection\\Dummy\\ChangedTypePropertyReflection' => ['ce' => 'pt_ce_changed_type_property_reflection', 'defineCe' => true, 'new' => ['pt_changed_type_property_reflection_new', 'val'], 'dispatch' => ['function' => 'pt_changed_type_property_reflection_call', 'enum' => 'pt_property_reflection_member', 'prefix' => 'PT_PROP_', 'receiver' => 'property']],
	'PHPStan\\Parser\\ArrayFilterArgVisitor' => ['ce' => 'pt_ce_array_filter_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ArrayFindArgVisitor' => ['ce' => 'pt_ce_array_find_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ArrayMapArgVisitor' => ['ce' => 'pt_ce_array_map_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ArrayOffsetNormalizingVisitor' => ['ce' => 'pt_ce_array_offset_normalizing_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ArrayWalkArgVisitor' => ['ce' => 'pt_ce_array_walk_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ArrowFunctionArgVisitor' => ['ce' => 'pt_ce_arrow_function_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ClosureArgVisitor' => ['ce' => 'pt_ce_closure_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ClosureBindArgVisitor' => ['ce' => 'pt_ce_closure_bind_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ClosureBindToVarVisitor' => ['ce' => 'pt_ce_closure_bind_to_var_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\CurlSetOptArgVisitor' => ['ce' => 'pt_ce_curl_set_opt_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\CurlSetOptArrayArgVisitor' => ['ce' => 'pt_ce_curl_set_opt_array_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\DeclarePositionVisitor' => ['ce' => 'pt_ce_declare_position_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ImmediatelyInvokedClosureVisitor' => ['ce' => 'pt_ce_immediately_invoked_closure_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ImplodeArgVisitor' => ['ce' => 'pt_ce_implode_arg_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\MagicConstantParamDefaultVisitor' => ['ce' => 'pt_ce_magic_constant_param_default_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\NewAssignedToPropertyVisitor' => ['ce' => 'pt_ce_new_assigned_to_property_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\ParentStmtTypesVisitor' => ['ce' => 'pt_ce_parent_stmt_types_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\TraitCollectingVisitor' => ['ce' => 'pt_ce_trait_collecting_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\TryCatchTypeVisitor' => ['ce' => 'pt_ce_try_catch_type_visitor', 'defineCe' => true],
	'PHPStan\\Parser\\TypeTraverserInstanceofVisitor' => ['ce' => 'pt_ce_type_traverser_instanceof_visitor', 'defineCe' => true],
	// wave 4: Type classes and helpers nothing else calls natively
	'PHPStan\\Type\\Accessory\\HasMethodType' => ['ce' => 'pt_ce_has_method_type', 'defineCe' => true, 'new' => ['pt_has_method_type_new', 'out']],
	'PHPStan\\Type\\ClassConstantAccessType' => ['ce' => 'pt_ce_class_constant_access_type', 'defineCe' => true, 'new' => ['pt_class_constant_access_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateBenevolentUnionType' => ['ce' => 'pt_ce_template_benevolent_union_type', 'defineCe' => true, 'new' => ['pt_template_benevolent_union_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateBooleanType' => ['ce' => 'pt_ce_template_boolean_type', 'defineCe' => true, 'new' => ['pt_template_boolean_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateConstantArrayType' => ['ce' => 'pt_ce_template_constant_array_type', 'defineCe' => true, 'new' => ['pt_template_constant_array_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateConstantIntegerType' => ['ce' => 'pt_ce_template_constant_integer_type', 'defineCe' => true, 'new' => ['pt_template_constant_integer_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateConstantStringType' => ['ce' => 'pt_ce_template_constant_string_type', 'defineCe' => true, 'new' => ['pt_template_constant_string_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateFloatType' => ['ce' => 'pt_ce_template_float_type', 'defineCe' => true, 'new' => ['pt_template_float_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateGenericObjectType' => ['ce' => 'pt_ce_template_generic_object_type', 'defineCe' => true, 'new' => ['pt_template_generic_object_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateIntegerType' => ['ce' => 'pt_ce_template_integer_type', 'defineCe' => true, 'new' => ['pt_template_integer_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateIntersectionType' => ['ce' => 'pt_ce_template_intersection_type', 'defineCe' => true, 'new' => ['pt_template_intersection_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateIterableType' => ['ce' => 'pt_ce_template_iterable_type', 'defineCe' => true, 'new' => ['pt_template_iterable_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateKeyOfType' => ['ce' => 'pt_ce_template_key_of_type', 'defineCe' => true, 'new' => ['pt_template_key_of_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateMixedType' => ['ce' => 'pt_ce_template_mixed_type', 'defineCe' => true, 'new' => ['pt_template_mixed_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateNullType' => ['ce' => 'pt_ce_template_null_type', 'defineCe' => true, 'new' => ['pt_template_null_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateObjectShapeType' => ['ce' => 'pt_ce_template_object_shape_type', 'defineCe' => true, 'new' => ['pt_template_object_shape_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateObjectType' => ['ce' => 'pt_ce_template_object_type', 'defineCe' => true, 'new' => ['pt_template_object_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType' => ['ce' => 'pt_ce_template_object_without_class_type', 'defineCe' => true, 'new' => ['pt_template_object_without_class_type_new', 'out']],
	'PHPStan\\Type\\Generic\\TemplateStringType' => ['ce' => 'pt_ce_template_string_type', 'defineCe' => true, 'new' => ['pt_template_string_type_new', 'out']],
	'PHPStan\\Type\\Helper\\GetTemplateTypeType' => ['ce' => 'pt_ce_get_template_type_type', 'defineCe' => true, 'new' => ['pt_get_template_type_type_new', 'val']],
	'PHPStan\\Type\\KeyOfType' => ['ce' => 'pt_ce_key_of_type', 'defineCe' => true, 'new' => ['pt_key_of_type_new', 'out']],
	'PHPStan\\Type\\NewObjectType' => ['ce' => 'pt_ce_new_object_type', 'defineCe' => true, 'new' => ['pt_new_object_type_new', 'out']],
	'PHPStan\\Type\\OffsetAccessType' => ['ce' => 'pt_ce_offset_access_type', 'defineCe' => true, 'new' => ['pt_offset_access_type_new', 'out']],
	'PHPStan\\Type\\StrictMixedType' => ['ce' => 'pt_ce_strict_mixed_type', 'defineCe' => true],
	'PHPStan\\Type\\StringAlwaysAcceptingObjectWithToStringType' => ['ce' => 'pt_ce_string_always_accepting_object_with_to_string_type', 'defineCe' => true, 'new' => ['pt_string_always_accepting_object_with_to_string_type_new', 'out']],
	'PHPStan\\Type\\StringNeverAcceptingObjectWithToStringType' => ['ce' => 'pt_ce_string_never_accepting_object_with_to_string_type', 'defineCe' => true, 'new' => ['pt_string_never_accepting_object_with_to_string_type_new', 'out']],
	'PHPStan\\Type\\ValueOfType' => ['ce' => 'pt_ce_value_of_type', 'defineCe' => true, 'new' => ['pt_value_of_type_new', 'out']],
	'PHPStan\\Reflection\\Php\\PhpParameterReflection' => ['ce' => 'pt_ce_php_parameter_reflection', 'defineCe' => true, 'dispatch' => ['function' => 'pt_php_parameter_reflection_call', 'enum' => 'pt_parameter_reflection_member', 'prefix' => 'PT_PR_', 'receiver' => 'parameter']],
	'PHPStan\\Rules\\PhpDoc\\UnresolvableTypeHelper' => ['ce' => 'pt_ce_unresolvable_type_helper', 'defineCe' => true],
];
