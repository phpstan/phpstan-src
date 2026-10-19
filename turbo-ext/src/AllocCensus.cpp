/*
 * Allocation census: with -DPT_ALLOC_COUNT, support.h routes the extension's
 * own object_init_ex(), zend_new_array() and zend_array_dup() calls here, and
 * the counts by class and by call site are written at exit to the file named
 * by $PT_ALLOC_COUNT_OUT (stderr otherwise). An ordinary build compiles this
 * file to nothing.
 *
 * Escape census: each object created through object_init_ex() gets a copy of
 * its handler table whose member, method, cast and compare handlers note
 * when they run in a userland frame — the object reached PHP code. When the
 * object is freed (or at exit, while still alive), its class is counted under
 * "X created/escaped/live" with the handler that first saw it from PHP. An
 * object PHP only passes along without touching is not seen.
 */

#ifdef PT_ALLOC_COUNT

#define PT_ALLOC_COUNT_IMPL
#include "support.h"
#include "Engine.h"

#include <algorithm>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <dlfcn.h>
#include <execinfo.h>
#include <map>
#include <string>
#include <unordered_map>
#include <vector>

namespace {

std::map<std::string, unsigned long> *counts;

void dump()
{
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &entry : *counts) rows.emplace_back(entry.second, entry.first);
	std::sort(rows.rbegin(), rows.rend());
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	for (const auto &row : rows) fprintf(out, "%10lu %s\n", row.first, row.second.c_str());
	if (out != stderr) fclose(out);
}

void hit(const std::string &key)
{
	if (counts == nullptr) {
		counts = new std::map<std::string, unsigned long>();
		atexit(dump);
	}
	(*counts)[key]++;
}

std::string site(const char *file, int line)
{
	const char *slash = strrchr(file, '/');
	return std::string(slash != nullptr ? slash + 1 : file) + ":" + std::to_string(line);
}

/* the calling function, as an offset into the shared object for atos/addr2line */
std::string caller(void *address)
{
	static char *base = nullptr;
	if (base == nullptr) {
		Dl_info info;
		if (dladdr((void *) &caller, &info) == 0) return "?";
		base = (char *) info.dli_fbase;
	}
	char buffer[32];
	snprintf(buffer, sizeof(buffer), "@%lx", (unsigned long) ((char *) address - base));
	return buffer;
}

struct Census
{
	const zend_object_handlers *original;
	const char *escapedVia;
	// the class name, copied: objects still alive at exit outlive the engine's memory
	const std::string *className;
};

std::unordered_map<zend_class_entry *, std::string> *classNames;

std::unordered_map<zend_object *, Census> *tracked;
std::map<const zend_object_handlers *, zend_object_handlers *> *clones;
// a clone made by the original clone_obj copies the wrapped table: map it back
std::unordered_map<const zend_object_handlers *, const zend_object_handlers *> *originals;

struct ClassCounts
{
	unsigned long created = 0;
	unsigned long escaped = 0;
	unsigned long live = 0;
	std::map<std::string, unsigned long> via;
};

std::map<std::string, ClassCounts> *classes;

void tally(const Census &census, bool live)
{
	ClassCounts &counts = (*classes)[*census.className];
	counts.created++;
	if (live) counts.live++;
	if (census.escapedVia != nullptr) {
		counts.escaped++;
		counts.via[census.escapedVia]++;
	}
}

void dumpEscapes()
{
	for (auto &entry : *tracked) tally(entry.second, true);
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &entry : *classes) {
		std::string via;
		for (auto &channel : entry.second.via) via += " " + channel.first + "=" + std::to_string(channel.second);
		rows.emplace_back(entry.second.created, "X " + entry.first + " escaped=" + std::to_string(entry.second.escaped) + " live=" + std::to_string(entry.second.live) + via);
	}
	std::sort(rows.rbegin(), rows.rend());
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	for (const auto &row : rows) fprintf(out, "%10lu %s\n", row.first, row.second.c_str());
	if (out != stderr) fclose(out);
}

bool fromUserland()
{
	zend_execute_data *frame = EG(current_execute_data);
	return frame != nullptr && frame->func != nullptr && ZEND_USER_CODE(frame->func->type);
}

const zend_object_handlers *originalOf(zend_object *obj)
{
	auto found = tracked->find(obj);
	if (found != tracked->end()) return found->second.original;
	auto original = originals->find(obj->handlers);
	return original != originals->end() ? original->second : obj->handlers;
}

void noteEscape(zend_object *obj, const char *via)
{
	if (!fromUserland()) return;
	auto found = tracked->find(obj);
	if (found != tracked->end() && found->second.escapedVia == nullptr) found->second.escapedVia = via;
}

void censusFree(zend_object *obj)
{
	auto found = tracked->find(obj);
	if (found == tracked->end()) {
		const zend_object_handlers *original = originalOf(obj);
		obj->handlers = original;
		original->free_obj(obj);
		return;
	}
	Census census = found->second;
	tracked->erase(found);
	tally(census, false);
	obj->handlers = census.original;
	census.original->free_obj(obj);
}

zval *censusReadProperty(zend_object *obj, zend_string *name, int type, void **cache, zval *rv)
{
	noteEscape(obj, "read_property");
	return originalOf(obj)->read_property(obj, name, type, cache, rv);
}

zval *censusWriteProperty(zend_object *obj, zend_string *name, zval *value, void **cache)
{
	noteEscape(obj, "write_property");
	return originalOf(obj)->write_property(obj, name, value, cache);
}

int censusHasProperty(zend_object *obj, zend_string *name, int type, void **cache)
{
	noteEscape(obj, "has_property");
	return originalOf(obj)->has_property(obj, name, type, cache);
}

zval *censusGetPropertyPtrPtr(zend_object *obj, zend_string *name, int type, void **cache)
{
	noteEscape(obj, "get_property_ptr_ptr");
	return originalOf(obj)->get_property_ptr_ptr(obj, name, type, cache);
}

HashTable *censusGetProperties(zend_object *obj)
{
	noteEscape(obj, "get_properties");
	return originalOf(obj)->get_properties(obj);
}

zend_function *censusGetMethod(zend_object **obj, zend_string *method, const zval *key)
{
	zend_object *object = *obj;
	noteEscape(object, "get_method");
	return originalOf(object)->get_method(obj, method, key);
}

zend_result censusCastObject(zend_object *obj, zval *result, int type)
{
	noteEscape(obj, "cast_object");
	return originalOf(obj)->cast_object(obj, result, type);
}

int censusCompare(zval *a, zval *b)
{
	zend_object *obj = Z_TYPE_P(a) == IS_OBJECT ? Z_OBJ_P(a) : Z_OBJ_P(b);
	noteEscape(obj, "compare");
	if (Z_TYPE_P(a) == IS_OBJECT) noteEscape(Z_OBJ_P(a), "compare");
	if (Z_TYPE_P(b) == IS_OBJECT) noteEscape(Z_OBJ_P(b), "compare");
	return originalOf(obj)->compare(a, b);
}

void track(zend_object *obj);

zend_object *censusCloneObj(zend_object *obj)
{
	noteEscape(obj, "clone_obj");
	zend_object *clone = originalOf(obj)->clone_obj(obj);
	if (clone != nullptr) {
		auto original = originals->find(clone->handlers);
		if (original != originals->end()) clone->handlers = original->second;
		track(clone);
	}
	return clone;
}

void track(zend_object *obj)
{
	if (tracked == nullptr) {
		tracked = new std::unordered_map<zend_object *, Census>();
		clones = new std::map<const zend_object_handlers *, zend_object_handlers *>();
		classes = new std::map<std::string, ClassCounts>();
		originals = new std::unordered_map<const zend_object_handlers *, const zend_object_handlers *>();
		classNames = new std::unordered_map<zend_class_entry *, std::string>();
		atexit(dumpEscapes);
	}
	auto back = originals->find(obj->handlers);
	if (back != originals->end()) obj->handlers = back->second;
	const zend_object_handlers *original = obj->handlers;
	zend_object_handlers *&clone = (*clones)[original];
	if (clone == nullptr) {
		clone = new zend_object_handlers(*original);
		clone->free_obj = censusFree;
		clone->read_property = censusReadProperty;
		clone->write_property = censusWriteProperty;
		clone->has_property = censusHasProperty;
		clone->get_property_ptr_ptr = censusGetPropertyPtrPtr;
		clone->get_properties = censusGetProperties;
		clone->get_method = censusGetMethod;
		clone->cast_object = censusCastObject;
		clone->compare = censusCompare;
		if (original->clone_obj != nullptr) clone->clone_obj = censusCloneObj;
		(*originals)[clone] = original;
	}
	auto name = classNames->find(obj->ce);
	if (name == classNames->end()) name = classNames->emplace(obj->ce, ZSTR_VAL(obj->ce->name)).first;
	(*tracked)[obj] = Census{original, nullptr, &name->second};
	obj->handlers = clone;
}

int walkModeDepth[2];

struct WalkCounts
{
	std::string className;
	std::string file;
	long line = -1;
	long startPos = -1;
	unsigned long walks[3] = {0, 0, 0};
};

std::unordered_map<zend_object *, WalkCounts> *walkedNodes;
// $PT_WALK_TRACE=<file>:<line>: the native stacks walking a node there, by count
std::map<std::string, unsigned long> *walkStacks;
// $PT_WALK_ORIGINS: on-demand walks by the function that asked for them
std::map<std::string, unsigned long> *walkOrigins;
// the counts of nodes whose address a later node took over
std::vector<WalkCounts> *finishedNodes;

void dumpWalks()
{
	unsigned long total[3] = {0, 0, 0};
	std::map<unsigned long, unsigned long> histogram;
	std::vector<std::pair<unsigned long, const WalkCounts *>> rows;
	for (auto &entry : *walkedNodes) finishedNodes->push_back(entry.second);
	for (const WalkCounts &node : *finishedNodes) {
		unsigned long sum = 0;
		for (int i = 0; i < 3; i++) {
			total[i] += node.walks[i];
			sum += node.walks[i];
		}
		histogram[sum < 10 ? sum : (sum < 100 ? sum / 10 * 10 : 100)]++;
		rows.emplace_back(sum, &node);
	}
	std::sort(rows.begin(), rows.end(), [](const auto &a, const auto &b) { return a.first > b.first; });
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	fprintf(out, "W nodes %zu walks plain=%lu loopPass=%lu onDemand=%lu\n", finishedNodes->size(), total[2], total[0], total[1]);
	for (auto &bucket : histogram) fprintf(out, "W walked %s%lu: %lu nodes\n", bucket.first >= 10 ? ">=" : "", bucket.first, bucket.second);
	/* how the walks of a node split between plain walks, loop passes and
	 * on-demand walks, by the walks beyond the first they add up to */
	std::map<std::string, std::pair<unsigned long, unsigned long>> patterns;
	for (const WalkCounts &node : *finishedNodes) {
		char key[64];
		snprintf(key, sizeof(key), "plain %lu pass %lu ondemand %lu", node.walks[2], node.walks[0], node.walks[1]);
		auto &pattern = patterns[key];
		pattern.first++;
		pattern.second += node.walks[0] + node.walks[1] + node.walks[2] - 1;
	}
	std::vector<std::pair<unsigned long, std::string>> patternRows;
	for (auto &pattern : patterns) patternRows.emplace_back(pattern.second.second, pattern.first + " nodes=" + std::to_string(pattern.second.first));
	std::sort(patternRows.rbegin(), patternRows.rend());
	for (size_t i = 0; i < patternRows.size() && i < 30; i++) fprintf(out, "W excess %lu %s\n", patternRows[i].first, patternRows[i].second.c_str());
	for (size_t i = 0; i < rows.size() && i < 40; i++) {
		const WalkCounts *counts = rows[i].second;
		fprintf(out, "W top %lu (plain %lu pass %lu ondemand %lu) %s %s:%ld\n", rows[i].first, counts->walks[2], counts->walks[0], counts->walks[1], counts->className.c_str(), counts->file.c_str(), counts->line);
	}
	/* the source lines whose nodes are walked most, by their most walked node */
	std::map<std::string, std::pair<unsigned long, std::string>> lines;
	for (const WalkCounts &node : *finishedNodes) {
		unsigned long sum = node.walks[0] + node.walks[1] + node.walks[2];
		if (sum < 4) continue;
		std::string key = node.file + ":" + std::to_string(node.line);
		auto &line = lines[key];
		if (sum > line.first) {
			char pattern[64];
			snprintf(pattern, sizeof(pattern), "plain %lu pass %lu ondemand %lu", node.walks[2], node.walks[0], node.walks[1]);
			line = {sum, pattern};
		}
	}
	std::vector<std::pair<unsigned long, std::string>> lineRows;
	for (auto &line : lines) lineRows.emplace_back(line.second.first, line.first + " (" + line.second.second + ")");
	std::sort(lineRows.rbegin(), lineRows.rend());
	for (size_t i = 0; i < lineRows.size() && i < 80; i++) fprintf(out, "W line %lu %s\n", lineRows[i].first, lineRows[i].second.c_str());
	if (walkOrigins != nullptr) {
		std::vector<std::pair<unsigned long, std::string>> origins;
		for (auto &origin : *walkOrigins) origins.emplace_back(origin.second, origin.first);
		std::sort(origins.rbegin(), origins.rend());
		for (size_t i = 0; i < origins.size() && i < 30; i++) fprintf(out, "O %lu %s\n", origins[i].first, origins[i].second.c_str());
	}
	if (walkStacks != nullptr) {
		std::vector<std::pair<unsigned long, std::string>> stacks;
		for (auto &stack : *walkStacks) stacks.emplace_back(stack.second, stack.first);
		std::sort(stacks.rbegin(), stacks.rend());
		for (size_t i = 0; i < stacks.size() && i < 8; i++) fprintf(out, "S %lu%s\n", stacks[i].first, stacks[i].second.c_str());
	}
	if (out != stderr) fclose(out);
}

/* the caller of the outermost on-demand entry on the stack */
void traceOrigin(const WalkCounts &counts)
{
	if (walkOrigins == nullptr) walkOrigins = new std::map<std::string, unsigned long>();
	void *frames[96];
	int n = backtrace(frames, 96);
	std::string origin = "?";
	bool inOnDemand = false;
	for (int i = 1; i < n; i++) {
		Dl_info info;
		const char *name = dladdr(frames[i], &info) != 0 && info.dli_sname != nullptr ? info.dli_sname : "";
		bool onDemandFrame = strstr(name, "OnDemand") != nullptr || strstr(name, "on_demand") != nullptr
			|| strstr(name, "readScopeStateOrSyntheticType") != nullptr || strstr(name, "read_scope_state_or_synthetic_type") != nullptr
			|| strstr(name, "pt_engine_with_stack") != nullptr;
		if (onDemandFrame) {
			inOnDemand = true;
			continue;
		}
		if (inOnDemand && strstr(name, "OnDemand") == nullptr) {
			origin = name;
			inOnDemand = false;
			/* who asked the scope: the first frame past the scope's own type reads */
			for (int j = i + 1; j < n; j++) {
				Dl_info askerInfo;
				const char *asker = dladdr(frames[j], &askerInfo) != 0 && askerInfo.dli_sname != nullptr ? askerInfo.dli_sname : "?";
				if (strstr(asker, "MutatingScope") != nullptr || strstr(asker, "mutating_scope") != nullptr || strstr(asker, "pt_engine_with_stack") != nullptr
					|| strstr(asker, "NodeCallbackScope") != nullptr || strstr(asker, "node_callback_scope") != nullptr || strstr(asker, "ScopeOps") != nullptr
					|| strstr(asker, "abi_call_known_function") != nullptr || strstr(asker, "call_method") != nullptr || strstr(asker, "callMethod") != nullptr
					|| strstr(asker, "pt_type_call") != nullptr || strstr(asker, "zend_call_function") != nullptr || strstr(asker, "ZEND_DO_") != nullptr
					|| strstr(asker, "execute_ex") != nullptr || strstr(asker, "zend_execute") != nullptr) {
					continue;
				}
				origin += std::string(" <- ") + asker;
				break;
			}
		}
	}
	origin = (counts.line < 0 ? "synthetic " : "real ") + origin;
	(*walkOrigins)[origin]++;
}

void traceWalk(const WalkCounts &counts)
{
	static const char *target = getenv("PT_WALK_TRACE");
	if (target == nullptr) return;
	std::string here = counts.file + ":" + std::to_string(counts.line);
	if (here.size() < strlen(target) || here.compare(here.size() - strlen(target), strlen(target), target) != 0) return;
	if (walkStacks == nullptr) walkStacks = new std::map<std::string, unsigned long>();
	void *frames[48];
	int n = backtrace(frames, 48);
	std::string key;
	for (int i = 2; i < n; i++) {
		Dl_info info;
		if (dladdr(frames[i], &info) != 0 && info.dli_sname != nullptr && strstr(info.dli_fname, "phpstan_turbo") == nullptr) {
			key += std::string(" ") + info.dli_sname;
		} else {
			key += " " + caller(frames[i]);
		}
	}
	(*walkStacks)[key]++;
}

} // namespace

void pt_census_walk_mode(int mode, int delta)
{
	walkModeDepth[mode] += delta;
}

void pt_census_expr_walk(zend_object *expr, zend_object *scope)
{
	if (walkedNodes == nullptr) {
		walkedNodes = new std::unordered_map<zend_object *, WalkCounts>();
		finishedNodes = new std::vector<WalkCounts>();
		atexit(dumpWalks);
	}
	/* node objects are freed with their file's AST: an address reused by a
	 * node of another class or position starts over */
	zv::Val start = pt_engine_node_get_attribute(expr, "startFilePos", sizeof("startFilePos") - 1);
	long startPos = !start.isUndef() && Z_TYPE_P(start.raw()) == IS_LONG ? (long) Z_LVAL_P(start.raw()) : -1;
	/* a trait's nodes are shared by its users, each analysing them in its
	 * own file: one record per node and analysed file */
	std::string currentFile;
	zv::Val file = pt_mutating_scope_get_file(scope);
	if (!file.isUndef() && Z_TYPE_P(file.raw()) == IS_STRING) {
		const char *path = Z_STRVAL_P(file.raw());
		const char *src = strstr(path, "/src/");
		currentFile = src != nullptr ? src + 1 : path;
	}
	WalkCounts &counts = (*walkedNodes)[expr];
	if (counts.startPos != startPos || counts.className != ZSTR_VAL(expr->ce->name) || counts.file != currentFile) {
		if (!counts.className.empty()) finishedNodes->push_back(counts);
		counts = WalkCounts();
		counts.startPos = startPos;
		counts.className = ZSTR_VAL(expr->ce->name);
		counts.file = currentFile;
		zv::Val line = pt_engine_node_get_attribute(expr, "startLine", sizeof("startLine") - 1);
		counts.line = !line.isUndef() && Z_TYPE_P(line.raw()) == IS_LONG ? (long) Z_LVAL_P(line.raw()) : -1;
	}
	int mode = walkModeDepth[PT_CENSUS_MODE_ON_DEMAND] > 0 ? 1 : (walkModeDepth[PT_CENSUS_MODE_LOOP_PASS] > 0 ? 0 : 2);
	counts.walks[mode]++;
	traceWalk(counts);
	static const bool origins = getenv("PT_WALK_ORIGINS") != nullptr;
	if (origins && mode == 1) traceOrigin(counts);
}

const zend_object_handlers *pt_census_original_handlers(zend_object *obj)
{
	return tracked != nullptr ? originalOf(obj) : obj->handlers;
}

zend_result pt_count_object_init_ex(zval *arg, zend_class_entry *ce, const char *file, int line)
{
	hit(std::string("O ") + ZSTR_VAL(ce->name));
	hit(std::string("o ") + site(file, line) + " " + ZSTR_VAL(ce->name));
	hit(std::string("c ") + site(file, line) + " " + ZSTR_VAL(ce->name) + " " + caller(__builtin_return_address(0)));
	zend_result result = object_init_ex(arg, ce);
	if (result == SUCCESS) track(Z_OBJ_P(arg));
	return result;
}

HashTable *pt_count_new_array(uint32_t size, const char *file, int line)
{
	hit(std::string("A ") + site(file, line));
	hit(std::string("a ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_new_array(size);
}

HashTable *pt_count_array_dup(const HashTable *source, const char *file, int line)
{
	const char *kind = zend_hash_num_elements(source) == 0 ? "E " : "D ";
	hit(kind + site(file, line));
	hit(std::string(kind[0] == 'E' ? "e " : "d ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_array_dup(source);
}

#endif
