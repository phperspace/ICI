#!/bin/sh
PWD_PATH="{PWD_PATH}"
PHP_PATH="{PHP_PATH}"
LOG_PATH="{LOG_PATH}"

SCRIPT_FLAG_PAYSTATUS="cli Hello start"
paystatus_pid=`ps aux | grep "$SCRIPT_FLAG_PAYSTATUS" | grep -v grep | sed -n  '1P' | awk '{print $2}'`

if [ -z $paystatus_pid ]
then
$PHP_PATH $PWD_PATH/index.php $SCRIPT_FLAG_PAYSTATUS  >>$LOG_PATH/hello.log &
now=`date  +%Y-%m-%d[%H:%M:%S]`
echo "at $now start RideUpdate" >> $LOG_PATH/hello.log
fi

exit
