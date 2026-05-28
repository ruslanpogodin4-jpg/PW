#! /bin/bash
###############################################################################
###############################################################################

    echo "Iniciando a Config do Firewall"

    echo "Regras Zeradas"
     iptables -t nat -F
   	 iptables -t mangle -F
  	 iptables -F
  	 iptables -X

    echo "Fechando tudo"
      iptables -P INPUT DROP
      iptables -P FORWARD DROP
      iptables -P OUTPUT DROP

    echo "Liberando conex estabelecidas"
      iptables -A INPUT -m state --state RELATED,ESTABLISHED -j ACCEPT
      iptables -A FORWARD -m state --state RELATED,ESTABLISHED,NEW -j ACCEPT
      iptables -A OUTPUT -m state --state RELATED,ESTABLISHED,NEW -j ACCEPT
      iptables -A INPUT -i lo -j ACCEPT

    echo "Liberando o SSH"
     iptables -I INPUT -p tcp --dport 22 -i eth2 -m state --state NEW -m recent --update --seconds 2 --hitcount 2 -j DROP
     iptables -A INPUT -p tcp --dport 22 -j ACCEPT
     iptables -A INPUT -p udp --dport 22 -j ACCEPT

  	echo "Bloqueando o IP de Estrangeiros"  
     iptables -A INPUT -s 37.0.0.0/24 -j DROP 


    echo "Liberando servidor"
    iptables -A INPUT -m tcp -p tcp --dport 29000 -j ACCEPT
    iptables -A INPUT -m tcp -p tcp --dport 29001 -j ACCEPT
    iptables -A INPUT -m tcp -p tcp --dport 29002 -j ACCEPT
    iptables -A INPUT -m tcp -p tcp --dport 80 -j ACCEPT


	echo "Liberando ram"
	echo 3 > /proc/sys/vm/drop_caches
    echo "Configuracao do Firewall Concluida."

