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
];
