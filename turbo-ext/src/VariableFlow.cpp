/*
 * PHPStanTurbo\VariableFlow — native implementation of
 * PHPStan\Analyser\VariableFlow.
 *
 * Declared as PHPStan\Analyser\VariableFlow itself at activation (abstract,
 * like the twin): the four flow classes (VariableAccessFlow,
 * VariableSequenceFlow, VariableControlFlow, VariableInputFlow) extend it and
 * are native themselves; the static factories here construct them directly
 * (pt_variable_*_flow_new()), with every optional constructor parameter at
 * its default, so the objects are the ones the twin's `new` expressions
 * create.
 *
 * The VariableFlow handle class below mirrors the twin method for method,
 * in the same order (`switch` and `exit` carry a trailing underscore
 * natively).
 */

#include "support.h"
#include "generated/VariableFlow.h"

namespace sigs = ptdecl::VariableFlow::sig;
#include "zv.h"
#include "TypeTraits.h"

#include <cstring>

zend_class_entry *pt_ce_variable_flow;

namespace {

/* the twin's kind constants, as permanent interned strings (module startup) */
enum pt_vf_kind
{
	PT_VF_SEQUENCE,
	PT_VF_CHOICE,
	PT_VF_LOOP,
	PT_VF_TRY_CATCH,
	PT_VF_SWITCH,
	PT_VF_READ,
	PT_VF_WRITE,
	PT_VF_DEFINE,
	PT_VF_DISCARD,
	PT_VF_ESCAPE,
	PT_VF_MENTION,
	PT_VF_READ_ALL,
	PT_VF_MENTION_ALL,
	PT_VF_OPAQUE,
	PT_VF_RETURN,
	PT_VF_BREAK,
	PT_VF_CONTINUE,
	PT_VF_THROW,
	PT_VF_STOP,
	PT_VF_ARROW,
	PT_VF_LOOP_STATEMENT,
	PT_VF_KIND_COUNT,
};

const struct { const char *constant; const char *value; } pt_vf_kinds[PT_VF_KIND_COUNT] = {
	{ "SEQUENCE", "sequence" },
	{ "CHOICE", "choice" },
	{ "LOOP", "loop" },
	{ "TRY_CATCH", "try" },
	{ "SWITCH", "switch" },
	{ "READ", "read" },
	{ "WRITE", "write" },
	{ "DEFINE", "define" },
	{ "DISCARD", "discard" },
	{ "ESCAPE", "escape" },
	{ "MENTION", "mention" },
	{ "READ_ALL", "readAll" },
	{ "MENTION_ALL", "mentionAll" },
	{ "OPAQUE", "opaque" },
	{ "RETURN", "return" },
	{ "BREAK", "break" },
	{ "CONTINUE", "continue" },
	{ "THROW", "throw" },
	{ "STOP", "stop" },
	{ "ARROW", "arrow" },
	{ "LOOP_STATEMENT", "loopStatement" },
};

zend_string *pt_vf_kind_strings[PT_VF_KIND_COUNT];

/* a PHP null for the flow arguments the glue passes as NULL */
zval pt_vf_null;

zval *flowOrNull(zval *flow)
{
	return flow != NULL ? flow : &pt_vf_null;
}

/* the VariableWrite slot cache (pt_variable_write_slots_of); rinit forgets it */
pt_variable_write_slots pt_vf_write_slots = { NULL, 0, 0, 0, 0, 0, 0, 0, 0 };

/* $write->getVariableName() (an owned string in *name) and
 * $write->getParentId() === null; false = pending exception */
[[nodiscard]] bool variableWriteInfo(zval *write, zv::Val &name, bool &parentIdIsNull)
{
	bool error;
	const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (slots != NULL) {
		name = zv::Val::copyOf(zv::ObjRef(write).propAtOffset(slots->variableName));
		parentIdIsNull = Z_TYPE_P(OBJ_PROP(Z_OBJ_P(write), slots->parentId)) == IS_NULL;
		return true;
	}
	if (UNEXPECTED(error)) return false;
	zv::Val parentId = pt_type_call(Z_OBJ_P(write), PT_LC("getparentid"), 0, NULL);
	if (UNEXPECTED(parentId.isUndef())) return false;
	parentIdIsNull = Z_TYPE_P(parentId.raw()) == IS_NULL;
	name = pt_type_call(Z_OBJ_P(write), PT_LC("getvariablename"), 0, NULL);
	return !name.isUndef();
}

/* new VariableAccessFlow($kind, $name, $write, $type, $targetId, $container,
 * $offset) — NULL stands for a null argument */
zv::Val newAccessFlow(pt_vf_kind kind, zval *name, zval *write, zval *type, zval *targetId, bool container, zval *offset)
{
	return pt_variable_access_flow_new(pt_vf_kind_strings[kind], name, write, type, targetId, container, offset);
}

/* new VariableSequenceFlow($kind, $children) */
zv::Val newSequenceFlow(pt_vf_kind kind, zval *children)
{
	return pt_variable_sequence_flow_new(pt_vf_kind_strings[kind], children);
}

/* the optional constructor parameters of VariableControlFlow, NULL / the
 * twin's defaults where a factory leaves them out */
using ControlFlowArgs = pt_variable_control_flow_args;

/* new VariableControlFlow($kind, ...); $kind is the constant's value (all()
 * and exit() take it as a parameter) */
zv::Val newControlFlow(zend_string *kind, const ControlFlowArgs &a)
{
	return pt_variable_control_flow_new(kind, a);
}

zv::Val newControlFlow(pt_vf_kind kind, const ControlFlowArgs &a)
{
	return newControlFlow(pt_vf_kind_strings[kind], a);
}

/* [$a, $b, ...] of borrowed, possibly null flows */
zv::Val flowList(uint32_t count, zval *const *flows)
{
	zv::Arr list = zv::Arr::create(count);
	for (uint32_t i = 0; i < count; i++) {
		if (flows[i] != NULL) {
			list.push(zv::Ref(flows[i]));
		} else {
			list.push(zv::Val::null());
		}
	}
	return zv::Val(std::move(list));
}

} // namespace

const pt_variable_write_slots *pt_variable_write_slots_of(zend_object *object, bool &error)
{
	error = false;
	if (UNEXPECTED(object->ce != pt_vf_write_slots.ce)) {
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_VARIABLE_WRITE);
		if (ce == NULL) {
			error = EG(exception) != NULL;
			return NULL;
		}
		if (object->ce != ce) return NULL;
		int32_t offsets[8];
		static const char *const names[8] = { "variableName", "node", "id", "kind", "offsetWrite", "offset", "parentId", "replacesOffset" };
		for (int i = 0; i < 8; i++) {
			offsets[i] = pt_instance_prop_offset(ce, names[i], strlen(names[i]));
			if (offsets[i] < 0) return NULL;
		}
		pt_vf_write_slots.ce = ce;
		pt_vf_write_slots.variableName = (uint32_t) offsets[0];
		pt_vf_write_slots.node = (uint32_t) offsets[1];
		pt_vf_write_slots.id = (uint32_t) offsets[2];
		pt_vf_write_slots.kind = (uint32_t) offsets[3];
		pt_vf_write_slots.offsetWrite = (uint32_t) offsets[4];
		pt_vf_write_slots.offset = (uint32_t) offsets[5];
		pt_vf_write_slots.parentId = (uint32_t) offsets[6];
		pt_vf_write_slots.replacesOffset = (uint32_t) offsets[7];
	}
	const pt_variable_write_slots &slots = pt_vf_write_slots;
	if (Z_TYPE_P(OBJ_PROP(object, slots.variableName)) != IS_STRING
		|| Z_TYPE_P(OBJ_PROP(object, slots.node)) != IS_OBJECT
		|| Z_TYPE_P(OBJ_PROP(object, slots.id)) != IS_LONG
		|| Z_TYPE_P(OBJ_PROP(object, slots.kind)) != IS_LONG
		|| Z_TYPE_P(OBJ_PROP(object, slots.offsetWrite)) == IS_UNDEF
		|| Z_TYPE_P(OBJ_PROP(object, slots.offset)) == IS_UNDEF
		|| Z_TYPE_P(OBJ_PROP(object, slots.parentId)) == IS_UNDEF
		|| Z_TYPE_P(OBJ_PROP(object, slots.replacesOffset)) == IS_UNDEF) {
		return NULL;
	}
	return &slots;
}

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Analyser\VariableFlow. Flow arguments are borrowed zvals
 * (NULL or IS_NULL for a null flow); methods return the flow, PHP null where
 * the twin returns null, UNDEF for a pending exception.
 */
class VariableFlow
{
public:
	/* Mirrors sequence(?self ...$flows). */
	static zv::Val sequence(uint32_t argc, zval *argv)
	{
		uint32_t count = 0;
		zval *single = NULL;
		for (uint32_t i = 0; i < argc; i++) {
			if (Z_TYPE(argv[i]) == IS_NULL) continue;
			count++;
			single = &argv[i];
		}
		if (count == 0) return zv::Val::null();
		if (count == 1) return zv::Val::copyOf(zv::Ref(single));

		zv::Arr nonEmpty = zv::Arr::create(count);
		for (uint32_t i = 0; i < argc; i++) {
			if (Z_TYPE(argv[i]) == IS_NULL) continue;
			nonEmpty.push(zv::Ref(&argv[i]));
		}
		return newSequenceFlow(PT_VF_SEQUENCE, nonEmpty.raw());
	}

	/* Mirrors choice(?self ...$branches). */
	static zv::Val choice(uint32_t argc, zval *argv)
	{
		if (argc == 0) return zv::Val::null();
		if (argc == 1 || (argc == 2 && zend_is_identical(&argv[0], &argv[1]))) return zv::Val::copyOf(zv::Ref(&argv[0]));

		zv::Arr branches = zv::Arr::create(argc);
		for (uint32_t i = 0; i < argc; i++) {
			branches.push(zv::Ref(&argv[i]));
		}
		return newSequenceFlow(PT_VF_CHOICE, branches.raw());
	}

	/* Mirrors arrow(). */
	static zv::Val arrow(zval *arrow, zval *body, zval *outputs)
	{
		zval *const flows[2] = { body, outputs };
		zv::Val children = flowList(2, flows);
		ControlFlowArgs a;
		a.children = children.raw();
		a.arrow = arrow;
		return newControlFlow(PT_VF_ARROW, a);
	}

	/* Mirrors read(); $targetId and $offset NULL for null. */
	static zv::Val read(zval *name, zval *targetId, bool container, zval *offset)
	{
		if (zend_string_equals_literal(Z_STR_P(name), "this") || pt_is_superglobal_name(Z_STR_P(name))) return zv::Val::null();

		return newAccessFlow(PT_VF_READ, name, NULL, NULL, targetId, container, offset);
	}

	/* Mirrors conditional(). */
	static zv::Val conditional(zval *condition, zval *ifFlow, zval *elseFlow)
	{
		condition = flowOrNull(condition);
		ifFlow = flowOrNull(ifFlow);
		elseFlow = flowOrNull(elseFlow);
		zv::Args branchArgv{ifFlow, elseFlow};
		zv::Val branch = choice(2, branchArgv);
		if (UNEXPECTED(branch.isUndef())) return zv::Val();
		zv::Args argv{condition, branch.raw()};
		return sequence(2, argv);
	}

	/* Mirrors switch(). */
	static zv::Val switch_(zval *condition, zval *cases, bool exhaustive)
	{
		zval *const flows[1] = { condition };
		zv::Val children = flowList(1, flows);
		ControlFlowArgs a;
		a.children = children.raw();
		a.canExit = !exhaustive;
		a.cases = cases;
		return newControlFlow(PT_VF_SWITCH, a);
	}

	/* Mirrors write(); $redundantType NULL for null. */
	static zv::Val write(zval *write, zval *redundantType)
	{
		zv::Val name;
		bool parentIdIsNull;
		if (UNEXPECTED(!variableWriteInfo(write, name, parentIdIsNull))) return zv::Val();
		return newAccessFlow(parentIdIsNull ? PT_VF_WRITE : PT_VF_DEFINE, name.raw(), write, redundantType, NULL, false, NULL);
	}

	/* Mirrors discard(). */
	static zv::Val discard(zval *write)
	{
		zv::Val name;
		bool parentIdIsNull;
		if (UNEXPECTED(!variableWriteInfo(write, name, parentIdIsNull))) return zv::Val();
		return newAccessFlow(PT_VF_DISCARD, name.raw(), write, NULL, NULL, false, NULL);
	}

	/* Mirrors inputs(); $targetId NULL for null. */
	static zv::Val inputs(zend_long writeId, zval *targetId)
	{
		return pt_variable_input_flow_new(writeId, targetId);
	}

	/* Mirrors escape(). */
	static zv::Val escape(zval *name)
	{
		return newAccessFlow(PT_VF_ESCAPE, name, NULL, NULL, NULL, false, NULL);
	}

	/* Mirrors mention(). */
	static zv::Val mention(zval *name)
	{
		return newAccessFlow(PT_VF_MENTION, name, NULL, NULL, NULL, false, NULL);
	}

	/* Mirrors all(). */
	static zv::Val all(zend_string *kind)
	{
		return newControlFlow(kind, ControlFlowArgs());
	}

	/* Mirrors exit(); $name NULL for null. */
	static zv::Val exit_(zend_string *kind, zend_long level, zend_string *name)
	{
		ControlFlowArgs a;
		a.name = name;
		a.level = level;
		return newControlFlow(kind, a);
	}

	/* Mirrors throwing(). */
	static zv::Val throwing(zval *type, bool canContinue, bool canContainAnyThrowable)
	{
		ControlFlowArgs a;
		a.type = type;
		a.canExit = canContinue;
		a.canContainAnyThrowable = canContainAnyThrowable;
		return newControlFlow(PT_VF_THROW, a);
	}

	/* Mirrors loop(). */
	static zv::Val loop(zval *condition, zval *body, zval *update, bool atLeastOnce, bool canExit, bool canRepeat)
	{
		zval *const flows[3] = { condition, body, update };
		zv::Val children = flowList(3, flows);
		ControlFlowArgs a;
		a.children = children.raw();
		a.atLeastOnce = atLeastOnce;
		a.canExit = canExit;
		a.canRepeat = canRepeat;
		return newControlFlow(PT_VF_LOOP, a);
	}

	/* Mirrors loopStatement(). */
	static zv::Val loopStatement(zval *stmt, zval *flow, zval *bindings, zval *ownWrites)
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(bindings)) == 0) return zv::Val::copyOf(zv::Ref(flowOrNull(flow)));

		zval *const flows[1] = { flow };
		zv::Val children = flowList(1, flows);
		ControlFlowArgs a;
		a.children = children.raw();
		a.stmt = stmt;
		a.bindings = bindings;
		a.ownWrites = ownWrites;
		return newControlFlow(PT_VF_LOOP_STATEMENT, a);
	}

	/* Mirrors tryCatch(). */
	static zv::Val tryCatch(zval *body, zval *catches, zval *finally)
	{
		zval *const flows[2] = { body, finally };
		zv::Val children = flowList(2, flows);
		ControlFlowArgs a;
		a.children = children.raw();
		a.catches = catches;
		return newControlFlow(PT_VF_TRY_CATCH, a);
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableFlow;

void pt_variable_flow_rinit()
{
	pt_vf_write_slots.ce = NULL;
}

bool pt_variable_flow_init_readonly(zend_object *self, uint32_t index, zval *value, zend_class_entry *declaring, const char *name)
{
	zval *slot = OBJ_PROP_NUM(self, index);
	if (UNEXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) {
		zend_throw_error(NULL, "Cannot modify readonly property %s::$%s", ZSTR_VAL(declaring->name), name);
		return false;
	}
	ZVAL_COPY(slot, value);
	Z_PROP_FLAG_P(slot) = 0;
	return true;
}

zv::Val pt_variable_flow_sequence(uint32_t argc, zval *argv)
{
	return VariableFlow::sequence(argc, argv);
}

zv::Val pt_variable_flow_sequence_list(HashTable *flows)
{
	/* a hole-free packed list is already the contiguous argument vector
	 * sequence() walks; anything else is copied into one */
	uint32_t count = zend_hash_num_elements(flows);
	if (HT_IS_PACKED(flows) && flows->nNumUsed == count) return VariableFlow::sequence(count, flows->arPacked);
	zval *argv = (zval *) safe_emalloc(count, sizeof(zval), 0);
	uint32_t i = 0;
	for (auto entry : zv::TableRef(flows)) {
		ZVAL_COPY_VALUE(&argv[i++], entry.value().deref().raw());
	}
	zv::Val result = VariableFlow::sequence(i, argv);
	efree(argv);
	return result;
}

zv::Val pt_variable_flow_arrow(zval *arrow, zval *body, zval *outputs)
{
	return VariableFlow::arrow(arrow, body, outputs);
}

zv::Val pt_variable_flow_read(zend_string *name, zval *targetId, bool container, zval *offset)
{
	zval nameValue;
	ZVAL_STR(&nameValue, name);
	return VariableFlow::read(&nameValue, targetId, container, offset);
}

zv::Val pt_variable_flow_write(zval *write, zval *redundantType)
{
	return VariableFlow::write(write, redundantType);
}

zv::Val pt_variable_flow_escape(zend_string *name)
{
	zval nameValue;
	ZVAL_STR(&nameValue, name);
	return VariableFlow::escape(&nameValue);
}

zv::Val pt_variable_flow_discard(zval *write)
{
	return VariableFlow::discard(write);
}

zv::Val pt_variable_flow_mention(zend_string *name)
{
	zval nameValue;
	ZVAL_STR(&nameValue, name);
	return VariableFlow::mention(&nameValue);
}

zv::Val pt_variable_flow_all_read_all()
{
	return VariableFlow::all(pt_vf_kind_strings[PT_VF_READ_ALL]);
}

zv::Val pt_variable_flow_all_mention_all()
{
	return VariableFlow::all(pt_vf_kind_strings[PT_VF_MENTION_ALL]);
}

/* the loop and control-flow statement handlers' (GotoHandler.cpp, ...) */
zv::Val pt_variable_flow_all_opaque()
{
	return VariableFlow::all(pt_vf_kind_strings[PT_VF_OPAQUE]);
}

zv::Val pt_variable_flow_loop(zval *condition, zval *body, zval *update, bool atLeastOnce, bool canExit, bool canRepeat)
{
	return VariableFlow::loop(condition, body, update, atLeastOnce, canExit, canRepeat);
}

zv::Val pt_variable_flow_loop_statement(zval *stmt, zval *flow, zval *bindings, zval *ownWrites)
{
	return VariableFlow::loopStatement(stmt, flow, bindings, ownWrites);
}

zv::Val pt_variable_flow_switch(zval *condition, zval *cases, bool exhaustive)
{
	return VariableFlow::switch_(condition, cases, exhaustive);
}

zv::Val pt_variable_flow_try_catch(zval *body, zval *catches, zval *finally)
{
	return VariableFlow::tryCatch(body, catches, finally);
}

zv::Val pt_variable_flow_throwing(zval *type, bool canContinue, bool canContainAnyThrowable)
{
	return VariableFlow::throwing(type, canContinue, canContainAnyThrowable);
}

zv::Val pt_variable_flow_exit_stop()
{
	return VariableFlow::exit_(pt_vf_kind_strings[PT_VF_STOP], 1, NULL);
}

/* the assignment handlers' (AssignHandler.cpp, AssignOpHandler.cpp) */
zv::Val pt_variable_flow_inputs(zend_long writeId, zval *targetId)
{
	if (targetId != NULL && Z_TYPE_P(targetId) == IS_NULL) targetId = NULL;
	return VariableFlow::inputs(writeId, targetId);
}

zv::Val pt_variable_flow_choice(uint32_t argc, zval *argv)
{
	return VariableFlow::choice(argc, argv);
}

zv::Val pt_variable_flow_exit(pt_variable_flow_exit_kind kind, zend_long level, zend_string *name)
{
	static const pt_vf_kind kinds[] = { PT_VF_RETURN, PT_VF_BREAK, PT_VF_CONTINUE, PT_VF_STOP };
	return VariableFlow::exit_(pt_vf_kind_strings[kinds[kind]], level, name);
}

zv::Val pt_variable_flow_conditional(zval *condition, zval *ifFlow, zval *elseFlow)
{
	return VariableFlow::conditional(condition, ifFlow, elseFlow);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_VF_RETURN(expr) \
	do { \
		zv::Val pt_vf_result = (expr); \
		if (UNEXPECTED(pt_vf_result.isUndef())) { \
			RETURN_THROWS(); \
		} \
		pt_vf_result.intoReturnValue(return_value); \
	} while (0)

namespace {

} // namespace

PT_MINIT_REGISTRATION(pt_register_variable_flow)
{
	ZVAL_NULL(&pt_vf_null);
	for (int i = 0; i < PT_VF_KIND_COUNT; i++) {
		pt_vf_kind_strings[i] = zend_string_init_interned(pt_vf_kinds[i].value, strlen(pt_vf_kinds[i].value), 1);
	}

	reg::Class cls("PHPStan\\Analyser\\VariableFlow");
	ptdecl::VariableFlow::declareClass(cls);
	for (int i = 0; i < PT_VF_KIND_COUNT; i++) {
		cls.publicClassConstantString(pt_vf_kinds[i].constant, pt_vf_kinds[i].value);
	}
	/* slot 0: the promoted `public readonly string $kind` */
	ptdecl::VariableFlow::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		if (!zp::parse<zp::Str>(execute_data, kind)) RETURN_THROWS();
		zval value;
		ZVAL_STR(&value, kind);
		if (UNEXPECTED(!pt_variable_flow_init_readonly(Z_OBJ_P(ZEND_THIS), ptdecl::VariableFlow::slot::kind, &value, pt_ce_variable_flow, "kind"))) RETURN_THROWS();
	});

	cls.method(sigs::sequence, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *flows;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', flows, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_VF_RETURN(VariableFlow::sequence(count, flows));
	});

	cls.method(sigs::choice, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *branches;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', branches, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_VF_RETURN(VariableFlow::choice(count, branches));
	});

	cls.method(sigs::arrow, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *arrow, *body, *outputs;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::ObjOrNull>(execute_data, arrow, body, outputs)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::arrow(arrow, body, outputs));
	});

	cls.method(sigs::read, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		zval *offset = NULL;
		zend_long targetId = 0;
		bool targetIdIsNull = true;
		bool container = false;
		ZEND_PARSE_PARAMETERS_START(1, 4)
			Z_PARAM_STR(name)
			Z_PARAM_OPTIONAL
			Z_PARAM_LONG_OR_NULL(targetId, targetIdIsNull)
			Z_PARAM_BOOL(container)
			Z_PARAM_ZVAL(offset)
		ZEND_PARSE_PARAMETERS_END();
		zval nameValue, targetIdValue;
		ZVAL_STR(&nameValue, name);
		ZVAL_LONG(&targetIdValue, targetId);
		PT_VF_RETURN(VariableFlow::read(&nameValue, targetIdIsNull ? NULL : &targetIdValue, container, offset));
	});

	cls.method(sigs::conditional, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *condition, *ifFlow, *elseFlow;
		if (!zp::parse<zp::ObjOrNull, zp::ObjOrNull, zp::ObjOrNull>(execute_data, condition, ifFlow, elseFlow)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::conditional(condition, ifFlow, elseFlow));
	});

	cls.method(sigs::switch_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *condition, *cases;
		bool exhaustive;
		if (!zp::parse<zp::ObjOrNull, zp::Arr, zp::Bool>(execute_data, condition, cases, exhaustive)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::switch_(condition, cases, exhaustive));
	});

	cls.method(sigs::write, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *write, *redundantType = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, write, redundantType)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::write(write, redundantType));
	});

	cls.method(sigs::discard, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *write;
		if (!zp::parse<zp::Obj>(execute_data, write)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::discard(write));
	});

	cls.method(sigs::inputs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long writeId, targetId = 0;
		bool targetIdIsNull = true;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_LONG(writeId)
			Z_PARAM_LONG_OR_NULL(targetId, targetIdIsNull)
		ZEND_PARSE_PARAMETERS_END();
		zval targetIdValue;
		ZVAL_LONG(&targetIdValue, targetId);
		PT_VF_RETURN(VariableFlow::inputs(writeId, targetIdIsNull ? NULL : &targetIdValue));
	});

	cls.method(sigs::escape, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		PT_VF_RETURN(VariableFlow::escape(&nameValue));
	});

	cls.method(sigs::mention, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		PT_VF_RETURN(VariableFlow::mention(&nameValue));
	});

	cls.method(sigs::all, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		if (!zp::parse<zp::Str>(execute_data, kind)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::all(kind));
	});

	cls.method(sigs::exit, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind, *name = NULL;
		zend_long level = 1;
		if (!zp::parse<zp::Str, zp::Opt<zp::Long>, zp::Opt<zp::StrOrNull>>(execute_data, kind, level, name)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::exit_(kind, level, name));
	});

	cls.method(sigs::throwing, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool canContinue, canContainAnyThrowable = false;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Opt<zp::Bool>>(execute_data, type, canContinue, canContainAnyThrowable)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::throwing(type, canContinue, canContainAnyThrowable));
	});

	cls.method(sigs::loop, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *condition, *body, *update;
		bool atLeastOnce, canExit, canRepeat = true;
		if (!zp::parse<zp::ObjOrNull, zp::ObjOrNull, zp::ObjOrNull, zp::Bool, zp::Bool, zp::Opt<zp::Bool>>(execute_data, condition, body, update, atLeastOnce, canExit, canRepeat)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::loop(condition, body, update, atLeastOnce, canExit, canRepeat));
	});

	cls.method(sigs::loopStatement, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt, *flow, *bindings, *ownWrites;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::Arr, zp::Arr>(execute_data, stmt, flow, bindings, ownWrites)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::loopStatement(stmt, flow, bindings, ownWrites));
	});

	cls.method(sigs::tryCatch, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *body, *catches, *finally;
		if (!zp::parse<zp::ObjOrNull, zp::Arr, zp::ObjOrNull>(execute_data, body, catches, finally)) RETURN_THROWS();
		PT_VF_RETURN(VariableFlow::tryCatch(body, catches, finally));
	});

	cls.shadow(&pt_ce_variable_flow);
}

/* }}} */
