/*
 * forkunsafe - a minimal model of a fork-unsafe PHP extension, the shape of
 * ext-grpc without grpc.enable_fork_support: RINIT starts a background
 * thread, MSHUTDOWN asks it to stop and waits on a condition variable until
 * the running-thread count drops to zero.
 *
 * In the process that started the thread this works. In a pcntl_fork()ed
 * child only the forking thread survives, but the copied counter still says
 * "one thread running" - so the child's module shutdown waits forever. That
 * is https://github.com/phpstan/phpstan/issues/15138: a forked worker that
 * has delivered its results and cannot exit.
 */
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include <pthread.h>
#include <unistd.h>

static pthread_t forkunsafe_thread;
static pthread_mutex_t forkunsafe_lock = PTHREAD_MUTEX_INITIALIZER;
static pthread_cond_t forkunsafe_cond = PTHREAD_COND_INITIALIZER;
static int forkunsafe_running = 0;
static int forkunsafe_stop = 0;

static void *forkunsafe_worker(void *arg)
{
	(void) arg;
	pthread_mutex_lock(&forkunsafe_lock);
	while (!forkunsafe_stop) {
		pthread_mutex_unlock(&forkunsafe_lock);
		usleep(1000);
		pthread_mutex_lock(&forkunsafe_lock);
	}
	forkunsafe_running = 0;
	pthread_cond_broadcast(&forkunsafe_cond);
	pthread_mutex_unlock(&forkunsafe_lock);
	return NULL;
}

static PHP_RINIT_FUNCTION(forkunsafe)
{
	pthread_mutex_lock(&forkunsafe_lock);
	if (!forkunsafe_running) {
		forkunsafe_stop = 0;
		forkunsafe_running = 1;
		pthread_create(&forkunsafe_thread, NULL, forkunsafe_worker, NULL);
	}
	pthread_mutex_unlock(&forkunsafe_lock);
	return SUCCESS;
}

static PHP_MSHUTDOWN_FUNCTION(forkunsafe)
{
	pthread_mutex_lock(&forkunsafe_lock);
	forkunsafe_stop = 1;
	while (forkunsafe_running) {
		/* grpc_shutdown()-style: wait for every thread to check out */
		pthread_cond_wait(&forkunsafe_cond, &forkunsafe_lock);
	}
	pthread_mutex_unlock(&forkunsafe_lock);
	return SUCCESS;
}

zend_module_entry forkunsafe_module_entry = {
	STANDARD_MODULE_HEADER,
	"forkunsafe",
	NULL,
	NULL,
	PHP_MSHUTDOWN(forkunsafe),
	PHP_RINIT(forkunsafe),
	NULL,
	NULL,
	"0.1",
	STANDARD_MODULE_PROPERTIES,
};

ZEND_GET_MODULE(forkunsafe)
