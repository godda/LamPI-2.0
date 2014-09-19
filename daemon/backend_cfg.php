<?php

/* -------------------------------------------------------------------------------
	This file contains the settings for the backend_xxxxxx.php files
	that are used by the LamPI-daemon.php Controller program
	
	(c) Author: Maarten Westenberg
	mw12554@hotmail.com

*/	
// Use rrdtool for making graphs
	$use_rrd = 1;
	
// Use this var as a way to determine what details are logged and what not...
// At the moment, the user has to kill the daemon and restart with new debug value.   
// Val 0: No Debug, Only Errors and logging of events in queue
// Val 1: Verbose mode
// Val 2: Normal Debug level
// Val 3: Detail debugging info. Will fill up the logfile Quickly
	$debug = 1;

// MySQL DATABASE SETTINGS
// Default server is localhost for most situations. However, should you want to run the
// database on a separate server, please specify it's host and access details below.
// Specify the database name, username, password and host
	$dbname = "coco";						// This one is not easy to guess
	$dbuser = "xxxx";
	$dbpass = "yyyy";
	$dbhost = "localhost";					// standard this should be "localhost"

// USER ADMIN SETTINGS (could be in database too, but this is easy as well)
// In future we could add a function to the database, in the user CLASS
	$u_admin = array (
					array (
						'login' => 'aap',
						'password' => '0000' , 
						'server' => '' ,
						'trusted' => '2'
					),
					array (
						'login' => 'noot',
						'password' => '0000' , 
						'server' => '' ,
						'trusted' => '1'
					),
					array (
						'login' => 'mies',
						'password' => '0000' , 
						'server' => '' ,
						'trusted' => '1'
					),
					array (
						'login' => 'teun',
						'password' => '0000' , 
						'server' => '' ,
						'trusted' => '1'
					) 
			);
	
// Are we still debugging or testing or operational? 
// $_GET is not allowed in operational!!
// Set to 0 for operational, and to 1 for testing
	$testing=1;
	
// Set to 1 in order to fake any communication to devices
	$fake=0;					
//
// Looking from the webhost directory, where are other important directories.
// But also works for LamPI-daemon.php in the daemon directory
	$base_dir	="/home/pi/";
	$rrd_dir	=$base_dir."rrd/";
	$config_dir=$base_dir."config/";
	$skin_dir  =$base_dir."styles/";
	$log_dir	="../log/";

// Port Settings for the LamPI Daemon (!) LamPI-daemon.php file
	$rcv_daemon_port = "5000";										
    $udp_daemon_port = "5001";
	
// Pin number of the GPIO (We use the wiringPi number scheme). As we move the actual receiver handling
// outide the PHP files in a faster c-based daemon LamPI-receiver, the settings below might be obsolete,
// But as these settings are so important, we should probably put them in a dedicated config file 
// (and read it on startup).
	$wiringPi_snd = "15";
	$wiringPi_rcv = "1";
//
// Server listens to ALL incoming hosts. So if we want to limit access
// XXX We have to build an accept/authorization mechanism in the daemon
// Address 0.0.0.0 works for the daemon!
	$serverIP = "0.0.0.0";
	
//
// The IP address of the Razberry machine. The Razberry is used to
// relay and handle all Z-Wave 868MHz messaging to connected Z-Wave
// devices and sensors
	$razberry = "192.168.2.52";

?>