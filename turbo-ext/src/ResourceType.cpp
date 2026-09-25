/*
 * PHPStanTurbo\ResourceType — native implementation of
 * PHPStan\Type\ResourceType.
 *
 * Declared as PHPStan\Type\ResourceType itself at activation: not final,
 * implementing PHPStan\Type\Type, without state (the twin's constructor is
 * empty).
 *
 * The bodies are the twin's one-liners: the registration below is the
 * engine ABI glue around them. The twin makes no `$this->method()` calls
 * of its own.
 */

#include "TypeTraits.h"
#include "generated/ResourceType.h"

namespace sigs = ptdecl::ResourceType::sig;

zend_class_entry *pt_ce_resource_type = nullptr;

bool pt_resource_type_new(zval *out)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_resource_type) != SUCCESS)) return false;
	return true;
}

/* {{{ the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL rtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL rtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL rtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

/* }}} */

/* {{{ registration */

PT_MINIT_REGISTRATION(pt_register_resource_type)
{
	reg::Class cls("PHPStan\\Type\\ResourceType");
	ptdecl::ResourceType::declareClass(cls);
	ptdecl::ResourceType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("resource", sizeof("resource") - 1);
	});

	cls.method(sigs::getConstantStrings, rtEmptyArray0);
	cls.method(sigs::toNumber, rtError0);
	cls.method(sigs::toBitwiseNotType, rtError0);
	cls.method(sigs::toAbsoluteNumber, rtError0);

	cls.method(sigs::toString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new StringType() — the shadowing class */
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method(sigs::toInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new IntegerType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_integer_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new FloatType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_float_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
		 * isList: TrinaryLogic::createYes()) */
		PT_RETURN_VAL(pt_type_string_accessory_to_array(Z_OBJ_P(ZEND_THIS)));
	});

	cls.method(sigs::toArrayKey, rtError0);

	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_YES);
	});

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_NO);
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::exponentiate, rtError1);
	cls.method(sigs::getFiniteTypes, rtEmptyArray0);

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new IdentifierTypeNode('resource') */
		PT_RETURN_VAL(pt_type_new_identifier_type_node(PT_LC("resource")));
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ResourceType::registerTraits(cls);

	cls.shadow(&pt_ce_resource_type);
}

/* }}} */
