#!/bin/bash

if [ ! -z $1 ]
then
	
	BASE=/etc/openvpn/server/easy-rsa
	cd $BASE

	VARS=$BASE/vars
	touch VARS
	
	echo "set_var EASYRSA_BATCH \"yes\"" > $VARS
	echo "set_var EASYRSA_REQ_CN=\"$1\"" >> $VARS

	$BASE/easyrsa gen-req $1 nopass
	$BASE/easyrsa sign-req client $1 

	DIR=/tmp/$1
	FILE=$1.ovpn
	FILENAME=$DIR/$FILE

	mkdir $DIR
	touch $FILENAME

	echo "client" > $FILENAME
	echo "proto udp" >> $FILENAME
	echo "dev tun" >> $FILENAME
	echo "remote 24.150.194.252 1194" >> $FILENAME
	echo "ca ca.crt" >> $FILENAME
	echo "cert $1.crt" >> $FILENAME
	echo "key $1.key" >> $FILENAME
	echo "cipher AES-256-CBC" >> $FILENAME
	echo "auth SHA512" >> $FILENAME
	echo "auth-nocache" >> $FILENAME
	echo "tls-version-min 1.2" >> $FILENAME
	echo "tls-cipher TLS-DHE-RSA-WITH-AES-256-GCM-SHA384:TLS-DHE-RSA-WITH-AES-256-CBC-SHA256:TLS-DHE-RSA-WITH-AES-128-GCM-SHA256:TLS-DHE-RSA-WITH-AES-128-CBC-SHA256" >> $FILENAME
	echo "resolve-retry infinite" >> $FILENAME
	echo "compress lz4" >> $FILENAME
	echo "nobind" >> $FILENAME
	echo "persist-key" >> $FILENAME
	echo "persist-tun" >> $FILENAME
	echo "mute-replay-warning" >> $FILENAME
	echo "verb 3" >> $FILENAME

	
	
	cp $BASE/pki/ca.crt /tmp/$1/
	cp $BASE/pki/issued/$1.crt /tmp/$1
	cp $BASE/pki/private/$1.key /tmp/$1

 
	cd /tmp/
	zip -r /var/www/html/vpn/$1.zip $1
	rm -rf /tmp/$1*
	exit 0
else
	echo "A filename is needed to execute this program."
fi
