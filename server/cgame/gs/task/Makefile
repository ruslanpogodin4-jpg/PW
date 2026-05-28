MSGOBJ = TaskProcess.o TaskTempl.o TaskTemplMan.o TaskServer.o taskman.o
SOOBJ = TaskProcess.o TaskTempl.o TaskTemplMan.o TaskServer.o

DEFINES  = -DVIP

all:	$(MSGOBJ)

lib: 	$(SOOBJ)
	$(LD) -shared -fPIC $(SOOBJ) -o ../libtask.so
	
.c.o:
	$(CC) -fPIC -c $(DEFINES) $(INC) $< -o $@

.cc.o:
	$(CPP) -fPIC -c $(DEFINES) $(INC) $< -o $@

.cxx.o:
	$(CPP) -fPIC -c $(DEFINES) $(INC) $< -o $@ 

.cpp.o:
	$(CPP) -fPIC -c $(DEFINES) $(INC) $< -o $@ 

FORCE:

clean: 	FORCE
	rm -f *.o *.so;rm -f .depend 

depend: FORCE
	make dep;

include ../../Rules.make
