
execdirs :=  cnet/gdeliveryd/ cnet/glinkd/ cnet/gamedbd/ cnet/gfaction/ cnet/logservice/ cnet/gacd/  \
				cnet/uniquenamed/

skilldir :=   cskill/skill/

all: libperf subs subsskill libs gs install
clean: clean-libperf clean-subs clean-subsskill  clean-libs clean-gs

makegs: libperf subsskill libs gs install
cleangs: clean-libperf clean-subsskill clean-libs clean-gs

makecnet: libperf subs install
cleancnet: clean-libperf clean-subs

configure: setrules configure-shared configure-iolib
clean-configure: clean-shared clean-iolib

libs: libgs libcommon
clean-libs: clean-libgs clean-libcommon 

install:
	#strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cgame/gs/gs; \
	cp ./cgame/gs/gs /home/gamed; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cgame/gs/libtask.so; \
	cp ./cgame/gs/libtask.so /home/gamed; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/gfaction/gfactiond; \
	cp ./cnet/gfaction/gfactiond /home/gfactiond; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/uniquenamed/uniquenamed; \
	cp ./cnet/uniquenamed/uniquenamed /home/uniquenamed; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/gamedbd/gamedbd; \
	cp ./cnet/gamedbd/gamedbd /home/gamedbd; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/gdeliveryd/gdeliveryd; \
	cp ./cnet/gdeliveryd/gdeliveryd /home/gdeliveryd; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/glinkd/glinkd; \
	cp ./cnet/glinkd/glinkd /home/glinkd; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/gacd/gacd; \
	cp ./cnet/gacd/gacd /home/gacd; \
	strip -g -S -d --strip-debug --strip-unneeded --keep-file-symbols ./cnet/logservice/logservice; \
	cp ./cnet/logservice/logservice /home/logservice;

libperf:
	cd cnet/perf; \
	make;  \
	cd ../../

clean-libperf:
	cd cnet/perf; \
	make clean;  \
	cd ../../

libcommon:
	cd cgame/libcommon; \
	make -j8;  \
	cd ../../

clean-libcommon:
	cd cgame/libcommon; \
	make clean;  \
	cd ../../

libgs: libLogClient libgsio  libgsPro2 libdbCli
	cd cgame/libgs; \
	mkdir -p io; \
	mkdir -p gs; \
	mkdir -p db; \
	mkdir -p sk; \
	mkdir -p log; \
	make
	cd ../../

clean-libgs: clean-libgsio clean-libLogClient clean-libgsPro2 clean-libdbCli
	cd cgame/libgs; \
	make clean; \
	cd ../../

gs:
	cd cgame; \
	make clean; \
	make -j8; \
	cd ..;
	
clean-gs:
	cd cgame; \
	make clean; \
	cd ..;

.PHONY: rpcgen

setrules:
	./setrules.sh; 
	
configure-shared: clean-shared
	cd cnet; \
	ln -s ../share/common/ .; \
	ln -s ../share/io/ .; \
	ln -s ../share/perf/ .; \
	ln -s ../share/mk/ .; \
	ln -s ../share/storage/ .; \
	ln -s ../share/rpc/ .; \
	ln -s ../share/rpcgen .; \
	cd ..;
	
configure-iolib: clean-iolib
	mkdir -p iolib; \
	cd iolib; \
	mkdir -p inc; \
	cd inc; \
	ln -s ../../cnet/gamed/auctionsyslib.h; \
	ln -s ../../cnet/gamed/sysauctionlib.h; \
	ln -s ../../cnet/gdbclient/db_if.h; \
	ln -s ../../cnet/gamed/factionlib.h; \
	ln -s ../../cnet/common/glog.h; \
	ln -s ../../cnet/gamed/gsp_if.h; \
	ln -s ../../cnet/gamed/mailsyslib.h; \
	ln -s ../../cnet/gamed/privilege.hxx; \
	ln -s ../../cnet/gamed/sellpointlib.h; \
	ln -s ../../cnet/gamed/stocklib.h; \
	ln -s ../../cnet/gamed/webtradesyslib.h; \
	ln -s ../../cnet/gamed/kingelectionsyslib.h; \
	ln -s ../../cnet/gamed/pshopsyslib.h; \
	cd .. ; \
	ln -s ../cnet/io/libgsio.a; \
	ln -s ../cnet/gdbclient/libdbCli.a; \
	ln -s ../cnet/gamed/libgsPro2.a; \
	ln -s ../cnet/logclient/liblogCli.a; \
	ln -s ../cskill/skill/libskill.a; \
	cd ..;

rpcgen:
	cd cnet; \
	./rpcgen rpcalls.xml; \
	cd gfaction/operations; \
	./opgen.pl; \
	cd ../../..; 

subsskill:
	for dir in $(skilldir); do \
        $(MAKE) -C $$dir clean; \
        $(MAKE) -j8 -C $$dir; \
    done

clean-subsskill:
	for dir in $(skilldir); do \
        $(MAKE) -C $$dir clean; \
    done


subs:
	for dir in $(execdirs); do \
        $(MAKE) -C $$dir clean; \
        $(MAKE) -j8 -C $$dir; \
    done

clean-subs:
	for dir in $(execdirs); do \
        $(MAKE) -C $$dir clean; \
    done

libgsio:
	cd cnet/io; \
	make lib; \
	cd ../..;

clean-libgsio:
	cd cnet/io; \
	rm -f libgsio.a; \
	cd ../..;

libLogClient:
	cd cnet/logclient; \
	make clean; \
	make -f Makefile.gs -j8; \
	cd ../..;
	
clean-libLogClient:
	cd cnet/logclient; \
	rm -f liblogCli.a; \
	cd ../..;

libgsPro2:
	cd cnet/gamed; \
	make clean; \
	make lib -j8; \
	cd ../..;
	
clean-libgsPro2:
	cd cnet/gamed; \
	make clean; \
	cd ../..;

libdbCli:
	cd cnet/gdbclient; \
	make clean; \
	make lib -j8; \
	cd ../..;
	
clean-libdbCli:
	cd cnet/gdbclient; \
	make clean; \
	cd ../..;

clean-shared:
	cd cnet; \
	rm -f common; \
	rm -f io; \
	rm -f perf; \
	rm -f mk; \
	rm -f storage; \
	rm -f rpc; \
	rm -f rpcgen;

clean-iolib:
	cd iolib; \
	cd inc; \
	rm -f *; \
	cd .. ;\
	rm -f lib*; 