#!/bin/bash
GS=`echo *game`;
NET=`echo *net`;
SKILL=`echo *skill`;
echo ""
echo "=========================== setup $NET ==========================="
echo ""
cd $NET
rm common
rm io
rm perf
rm mk
rm storage
rm rpc
rm rpcgen
ln -s ../share/common/ .
ln -s ../share/io/ .
ln -s ../share/perf/ .
ln -s ../share/mk/ .
ln -s ../share/storage/ .
ln -s ../share/rpc/ .
ln -s ../share/rpcgen .
cd ..
echo ""
echo "=========================== setup iolib ==========================="
echo ""
if [ ! -d iolib ]; then
	mkdir iolib
fi;
cd iolib;
if [ ! -d inc ]; then
	mkdir inc
fi;
cd inc
rm *
ln -s ../../$NET/gamed/auctionsyslib.h
ln -s ../../$NET/gamed/sysauctionlib.h 
ln -s ../../$NET/gdbclient/db_if.h
ln -s ../../$NET/gamed/factionlib.h
ln -s ../../$NET/common/glog.h
ln -s ../../$NET/gamed/gsp_if.h
ln -s ../../$NET/gamed/mailsyslib.h
ln -s ../../$NET/gamed/privilege.hxx
ln -s ../../$NET/gamed/sellpointlib.h
ln -s ../../$NET/gamed/stocklib.h
ln -s ../../$NET/gamed/webtradesyslib.h
ln -s ../../$NET/gamed/kingelectionsyslib.h
ln -s ../../$NET/gamed/pshopsyslib.h
cd ..
rm lib*
ln -s ../$NET/io/libgsio.a
ln -s ../$NET/gdbclient/libdbCli.a
ln -s ../$NET/gamed/libgsPro2.a
ln -s ../$NET/logclient/liblogCli.a
ln -s ../$SKILL/skill/libskill.a
cd ..

echo ""
echo "======================== modify Rules.make ========================"
echo ""
EPWD=`pwd|sed -e 's/\//\\\\\//g'`;
cd $GS
sed -i -e "s/IOPATH=.*$/IOPATH=$EPWD\/iolib/g" -e "s/BASEPATH=.*$/BASEPATH=$EPWD\/$GS/g" Rules.make

echo ""
echo "====================== softlink libskill.so ======================="
echo ""
cd gs
rm -f libskill.so
ln -s ../../$SKILL/skill/libskill.so 
cd ../../

buildrpcgen()
{
	echo ""
	echo "========================== $NET rpcgen ============================"
	echo ""
	cd $NET
	./rpcgen rpcalls.xml
	cd ..
}

buildgslib() 
{
	echo ""
	echo "========================== build libgsio =============================="
	echo ""
	cd $NET
	cd io
	make lib
	cd ..

	echo ""
	echo "======================= build liblogCli.a ========================="
	echo ""
	cd logclient
	make clean
	make -f Makefile.gs 
	cd ..
	
	echo ""
	echo "======================== build libgsPro2.a ========================="
	echo ""
	cd gamed
	make clean
	make lib 
	cd ..
	
	echo ""
	echo "======================== build libdbCli.a =========================="
	echo ""
	cd gdbclient
	make clean
	make lib 
	cd ..
	cd ..
	
	echo ""
	echo "============================ make libgs ============================"
	echo ""
	cd $GS
	cd libgs
	mkdir -p io
	mkdir -p gs
	mkdir -p db
	mkdir -p sk
	mkdir -p log
	make
	cd ../../
}

buildskill()
{
	echo ""
	echo "============================= ant gen =============================="
	echo ""
	cd $SKILL
	cd gen
	if [ ! -d skills ]; then
		mkdir skills
	fi
	if [ ! -d buffcondition ]; then
		mkdir buffcondition
	fi
	ant
	echo ""
	echo "========================== gen skils =============================="
	echo ""
	chmod a+x gen
#	./gen
	cd ../skill
	echo ""
	echo "======================= build libskills.o ========================="
	echo ""
	make clean
	make 
	cd ../../
}

buildgame()
{

	echo ""
	echo "======================= build cgame ========================="
	echo ""
#	cd ~/
	cd $GS
#	cvs up
	make clean
	make 	
	cd ../
}

buildtask()
{

	echo ""
	echo "======================= build libtask.o ========================="
	echo ""
	cd ~/
	cd $GS
	cd gs
	cd task
	make clean
	make lib 
	cd ../../../
}

builddeliver() # Ç°¸úrpcgen
{
	cd $NET
	echo ""
	echo "========================== build logservice =============================="
	echo ""
	cd logservice
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build gacd =============================="
	echo ""
	cd gacd
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build glinkd =============================="
	echo ""
	cd glinkd
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build gdeliveryd =============================="
	echo ""
	cd gdeliveryd
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build gamedbd =============================="
	echo ""
	cd gamedbd
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build uniquenamed =============================="
	echo ""
	cd uniquenamed
	make clean
	make -j8
	cd ..

	echo ""
	echo "========================== build gfaction =============================="
	echo ""
	cd gfaction
	make clean
	make -j8
	cd ../..
}

buildgs()
{
	echo ""
	echo "========================== build gs =============================="
	echo ""
	cd $GS
	cd gs
	make clean
	make 
	cd ../../
}

rebuilddeliver()
{
	rpcgen;
	builddeliver;
}

rebuildgs()
{
	buildrpcgen;
	buildskill;
	buildgslib;
	buildgame;
}

rebuildall()
{
	echo ""
	echo "========================== build game all =============================="
	echo ""

	buildrpcgen;
	builddeliver;
	buildskill;
	buildgslib;
	buildgame;
}

if [ $# -gt 0 ]; then
	if [ "$1" = "deliver" ]; then
		rebuilddeliver;
	elif [ "$1" = "gs" ]; then
		rebuildgs;
	elif [ "$1" = "all" ]; then
		rebuildall;
	fi
fi
