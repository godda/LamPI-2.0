#!/bin/bash
# Progam for starting i2c daemon process to manage i2c bus sensors.
# July 2014
# ---------------------------------------------------------------------------

PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/home/pi:/home/pi/scripts:/home/pi/exe"; export PATH
export DISPLAY=:0
#
EXEDIR="/home/pi/exe"; export EXEDIR
SCRIPTDIR="/home/pi/scripts"; export SCRIPTDIR
LOGDIR="/home/pi/log"; export LOGDIR
LOGFILE="$LOGDIR/$0.log"; export LOGFILE

HOSTNAME="255.255.255.255"; export HOSTNAME
PORTNUMBER="5001"; export PORTNUMBER
#
PID=""; export PID

SENSOR="bmp085-sensor"; export SENSOR

# Load the required module for the 1-wire sensor program
#
cat /proc/modules | fgrep i2c > /dev/null
if [ $? = 0 ]; then
	echo "Module loaded"
else
	sudo modprobe i2c_dev
    sudo modprobe i2c_bcm2708
fi

# Wait a few moments, as more of these daemons are starting at the same time
#
sleep 10

#
# We run all i2c sensors with this script. Starting BMP085 Temperature + Airpressure
#
echo "---------------------------------------------------" >> $LOGFILE 2>&1
echo "`date`:: Starting $SENSOR" >> $LOGFILE 2>&1
cd $EXEDIR
nohup ./$SENSOR -d -b -h $HOSTNAME -p $PORTNUMBER >> $LOGFILE 2>&1 &

# Wait a few moments
#
sleep 10

#
# SHT21 is a temperature + Humidity Sensor
#
SENSOR="sht21-sensor"
echo "---------------------------------------------------" >> $LOGFILE 2>&1
echo "`date`:: Starting $SENSOR" >> $LOGFILE 2>&1
cd $EXEDIR
nohup ./$SENSOR -d -b -h $HOSTNAME -p $PORTNUMBER >> $LOGFILE 2>&1 &