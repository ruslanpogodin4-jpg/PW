CPP = g++
CC  = g++ 
INCLUDES = -I./common -I./include -I../../common
DEFINES = 
CFLAGS = -g -ggdb -m32 -std=gnu++11

VCLIENTOBJS = templatedataman.o moveagent.o task.o virtualclient.o msgmanager.o playermanager.o player.o

all: $(VCLIENTOBJS)

.c.o:
	$(CC) -c $(DEFINES) $(INCLUDES) $(CFLAGS) $< -o $@

.cc.o:
	$(CPP) -c $(DEFINES) $(INCLUDES) $(CFLAGS) $< -o $@

.cxx.o:
	$(CPP) -c $(DEFINES) $(INCLUDES) $(CFLAGS) $< -o $@ 

.cpp.o:
	$(CPP) -c $(DEFINES) $(INCLUDES) $(CFLAGS) $< -o $@ 

clean:  FORCE
	rm -f *.o

FORCE:
