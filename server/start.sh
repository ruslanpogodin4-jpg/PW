#!/bin/sh

PW_PATH=/home
if [ ! -d $PW_PATH/logs ]; then
mkdir $PW_PATH/logs
fi
GREEN='\033[0;32m'
NORMAL='\033[0m'
echo ":::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo ":::               ${GREEN}Perfect World Server 1.5.3v145${NORMAL}               :::"
echo ":::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo ":::                        Starting the Server!                        :::"
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo ""
sleep 1
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Starting MySQL :::${NORMAL}"
echo ""
service mysql start
sleep 1
echo "${GREEN}::: Ready :::${NORMAL}"
echo "${GREEN}::: Log Service :::${NORMAL}"
cd $PW_PATH/logservice; ./logservice logservice.conf >$PW_PATH/logs/logservice.log &
sleep 1
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Unique Name Daemon :::${NORMAL}"
cd $PW_PATH/uniquenamed; ./uniquenamed gamesys.conf >$PW_PATH/logs/uniquenamed.log &
sleep 2
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Gauth Daemon :::"
cd $PW_PATH/gauthd/; ./gauthd gamesys.conf &
sleep 3
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Game Data Base Daemon :::${NORMAL}"
cd $PW_PATH/gamedbd; ./gamedbd gamesys.conf >$PW_PATH/logs/gamedbd.log &
sleep 2
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Game Anti Cheat Daemon :::${NORMAL}"
cd $PW_PATH/gacd; ./gacd gamesys.conf >$PW_PATH/logs/gacd.log &
sleep 2
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Game Faction Daemon :::${NORMAL}"
cd $PW_PATH/gfactiond; ./gfactiond gamesys.conf >$PW_PATH/logs/gfactiond.log &
sleep 2
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Game Delivery Daemon :::${NORMAL}"
cd $PW_PATH/gdeliveryd; ./gdeliveryd gamesys.conf >$PW_PATH/logs/gdeliveryd.log &
sleep 3
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "${GREEN}::: Game Link Daemon :::${NORMAL}"
cd $PW_PATH/glinkd; ./glinkd gamesys.conf 1 >$PW_PATH/logs/glink.log &
#cd $PW_PATH/glinkd; ./glinkd gamesys.conf 2 >$PW_PATH/logs/glink2.log &
#cd $PW_PATH/glinkd; ./glinkd gamesys.conf 3 >$PW_PATH/logs/glink3.log &
#cd $PW_PATH/glinkd; ./glinkd gamesys.conf 4 >$PW_PATH/logs/glink4.log &
sleep 2
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo ":::                            ${GREEN}Maps${NORMAL}                            :::"
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo ""
echo "${GREEN}::: Game Service :::${NORMAL}"
cd $PW_PATH/gamed; ./gs gs01 > $PW_PATH/logs/gs01.log &
sleep 2
echo "${GREEN}::: is62 :::${NORMAL}"
cd $PW_PATH/gamed; ./gs is61 > $PW_PATH/logs/is61.log &
sleep 2
echo "${GREEN}::: is37 :::${NORMAL}"
cd $PW_PATH/gamed; ./gs is37 > $PW_PATH/logs/is37.log &
#cd $PW_PATH/gamed; ./gs is62 > $PW_PATH/logs/is62.log &
echo "${GREEN}::: Ready :::${NORMAL}"
echo ""
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
echo "                        ${GREEN}Server Online${NORMAL}   http://romanvip.ru            "       
echo "::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::"
