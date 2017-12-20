#!/bin/bash
STATUS=$1
USER=$2
EMAIL=$3
HOST=104.197.15.255
PW=`cat ../sql/db.pw`

if [ "$STATUS" != "S" -a "$STATUS" != "G" -a "$STATUS" != "P" ]; then
  echo Status $STATUS must be one of S, G or P
  exit
fi

VERIFY=`echo "select name from users where name='$USER';" | mysql -h $HOST -u openflights --password=$PW --skip-column-names flightdb2`
if [ -z "$VERIFY" ]; then
  echo No user called $USER found
  exit
fi

echo "set sql_safe_updates=0; update users set elite='$STATUS', validity=date_add(now(), interval 1 year) where name='$USER';" | mysql -h $HOST -u openflights --password=$PW --skip-column-names flightdb2

if [ -z "$EMAIL" ]; then
  EMAIL=`echo "select email from users where name='$USER';" | mysql -h $HOST -u openflights --password=$PW --skip-column-names flightdb2`
  if [ -z "$EMAIL" ]; then
    echo User $USER set to $STATUS, but no email found for user $USER
    exit
  fi
fi

echo User $USER set to $STATUS, sending mail to $EMAIL
cat $STATUS-message.txt | sed "s/%EMAIL%/$EMAIL/" | sendmail $EMAIL

