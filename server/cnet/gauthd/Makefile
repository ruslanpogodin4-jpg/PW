
TOP_SRCDIR = ..

SINGLE_THREAD = false 
DEBUG_VERSION = false

include ../mk/gcc.defs.mk

OBJS = gauthserver.o state.o stubs.o authmanager.o gmysqlclient.o gauthd.o 
DEFINES += -DUSE_EPOLL -g -ggdb

all : gauthd

gauthd : $(SHAREOBJ) $(OBJS)
	$(LD) $(LDFLAGS) -o $@ $(SHAREOBJ) $(OBJS) $(SHARE_SOBJ) -lmysqlclient

include ../mk/gcc.rules.mk

