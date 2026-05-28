# PWServer

==== MODIFICADO POR ALIEN =====
Alterar caminho do IOPATH e BASEPATH em cgame/Rules.make

Server Source Code Repository

## Build Requirements:
- Ubuntu 20 (recommended) 
- gcc9.3 
- libxml-dom-perl
- libxml2-dev:i386
- libssl-dev:i386
- libpcre3-dev:i386
- libssl1.0-dev:i386
- make
## To install i386 packages in Ubuntu
- sudo dpkg --add-architecture i386
- sudo apt update
## To install libssl1.0-dev:i386 in Ubuntu
- Add the following source to aptitude source list.
- - deb http://security.ubuntu.com/ubuntu bionic-security main
    
## Setup
- chmod 777 build.sh
- chmod 777 setrules.sh
- chmod 777 gfaction/operations/opgen.pl;
- chmod 777 rpcgen
- chmod 777 rpc/xmlcoder.pl

## First time Build:
- make configure

## Afterwards:
- make

## To copy binaries to server folder
- Open makefile and edit 'install' target path as desired, defaults to /pwserver
- run: make install 
