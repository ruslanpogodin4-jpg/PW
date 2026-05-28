
TOP_SRCDIR = ..
SINGLE_THREAD = true
DEBUG_VERSION = true

include ../mk/gcc.defs.mk

INCLUDES += -I$(TOP_SRCDIR)/gfaction/operations -I$(TOP_SRCDIR)/include
DEFINES += -g -ggdb -D_TESTCODE -DUSE_EPOLL -DUSE_HASH_MAP
LDFLAGS	+= -g -ggdb -DUSE_HASH_MAP

OBJS = vclient_if.o factionlib.o gclient.o stubs.o state.o glinkclient.o

SUBDIR = vclient
VCLIENTOBJS = vclient/*.o
SUBOBJS = $(VCLIENTOBJS)
CLEAN += $(SUBOBJS) + gclient

all : gclient

gclient : $(SHAREOBJ) $(OBJS) $(SUBDIR)
#	$(LD) $(LDFLAGS) -o $@ $(OBJS) $(SHAREOBJ) $(SHARE_SOBJ) $(SUBOBJS)
	$(LD) $(LDFLAGS) -o $@ $(SUBOBJS) $(SHARE_SOBJ) $(SHAREOBJ) $(OBJS) 

$(SUBDIR): FORCE
	cd $@; make

FORCE:

include ../mk/gcc.rules.mk

