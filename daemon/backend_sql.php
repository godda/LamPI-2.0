<?php 
require_once( '../daemon/backend_cfg.php' );
// require_once( '../daemon/backend_lib.php' );

/*	------------------------------------------------------------------------------	
	Note: Program to switch klikaanklikuit and coco equipment
	Author: Maarten Westenberg
	Version 1.0 : August 16, 2013
	Version 1.2 : August 30, 2013 removed all init function from file into a separate file
	Version 1.3 : September 6, 2013 Implementing first version of Daemon process
	Version 1.4 : Sep 20, 2013
	Version 1.5 : Oct 20, 2013
	Version 1.6 : Nov 10, 2013
	Version 1.7 : Dec 2013
	Version 1.8 : Jan 18, 2014
	Version 1.9 : Mar 10, 2014
	Version 2.0 : Jun 15, 2014
	Version 2.1	: Jul 31, 2014
	
	-------------------------------------------------------------------------------	*/


// ---------------------------------------------------------------------------------
// load_database()
//
// Load the complete database from mySQL into ONE $config object!
// 
// NOTE: This function is VERY sensitive to the right fields of the objects etc.
//		So make sure you have exactly the right number of argument and if you change
// the record/object definition in the configuration object, make sure that MySQL
// follows (frontend_set.php)
//
function load_database()
{
	// We assume that a database has been created by the user. host/name/passwd in backend_cfg.php
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	$config = array();
	$devices = array();
	$rooms = array();
	$scenes = array();
	$timers = array();
	$handsets = array();
	$settings = array();
	$controllers = array();
	$brands = array();
	$weather = array();
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("load_database:: Failed to connect to MySQL on host ".$dbhost." (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
		return(-1);
	}
	$log->lwrite("load_database:: Connected to MySQL database",3);
	
	$sqlCommand = "SELECT * FROM devices";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $devices[] = $row ; }
	mysqli_free_result($query);
	
	$sqlCommand = "SELECT * FROM rooms";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $rooms[] = $row ; }
	mysqli_free_result($query);
	
	$sqlCommand = "SELECT * FROM scenes";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $scenes[] = $row ; }
	mysqli_free_result($query);

	$sqlCommand = "SELECT * FROM timers";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $timers[] = $row ; }
	mysqli_free_result($query);
	
	$sqlCommand = "SELECT * FROM settings";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $settings[] = $row ; }
	mysqli_free_result($query);	
	
	$sqlCommand = "SELECT * FROM handsets";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $handsets[] = $row ; }
	mysqli_free_result($query);

	$sqlCommand = "SELECT * FROM controllers";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $controllers[] = $row ; }
	mysqli_free_result($query);
	
	$sqlCommand = "SELECT * FROM brands";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $brands[] = $row ; }
	mysqli_free_result($query);
	
	$sqlCommand = "SELECT * FROM weather";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { $weather[] = $row ; }
	mysqli_free_result($query);
	$log->lwrite("load_database:: Done reading tables from MySQL database",3);
	
	$config ['rooms']   = $rooms;
	$config ['devices'] = $devices;
	$config ['scenes']  = $scenes;
	$config ['timers']  = $timers;
	$config ['handsets']  = $handsets;
	$config ['settings']= $settings;
	$config ['controllers']= $controllers;
	$config ['brands']= $brands;
	$config ['weather']= $weather;
	
	mysqli_close($mysqli);
	$apperr = "";										// No error
	return ($config);
}


//	--------------------------------------------------------------------------------
//	Function read device from MySQL
//	Lookup the device with the corresponding name
//	-----------------------------------------------------------------------------------
function read_device($name)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	$res = array();
	// We need to connect to the database for start

	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("read_device:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
	}
	
	$sqlCommand = "SELECT * FROM devices WHERE name='$name' ";

	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 
		$res[] = $row ;
	}

	mysqli_free_result($query);
	mysqli_close($mysqli);
	
	// NOTE: Assuming every device name is unique, we return ONLY the first device
	//	remember for seq only to use result['seq'] for sequence only
	if (count($res) == 0) {
		$apperr .= "ERROR read_device: device $name not found\n";
		return(-1);
	}
	else {
		// Only return ONE device (there should only be one btw)
		return ($res[0]);
	}
}


/* -----------------------------------------------------------------------------------
  Load all devices from the SQL database
  
 -------------------------------------------------------------------------------------*/
function load_devices()
{
	global $log, $debug;
	global $apperr;
	global $dbname, $dbuser, $dbpass, $dbhost;
	
	$devices = array();

	// We need to connect to the database for start
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("load_devices:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		return(-1);
	}
	
	$sqlCommand = "SELECT * FROM devices";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 

		$devices[] = $row ;
	}
	// There will be an array returned, even if we only have ONE result
	mysqli_free_result($query);
	mysqli_close($mysqli);
	
	if (count($devices) == 0) {	
		return(-1);
	}
	return($devices);								// Return all devices
}


// ----------------------------------------------------------------------------------
//
// Add a device object as received from the ajax call and update mySQL
//
// ----------------------------------------------------------------------------------- */
function add_device($device)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr = "add_device:: id: ".$device[id]." room: ".$device[room]." val: ".$device[val]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("add_device:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
		return (-1);
	}
	
	if (!$mysqli->query("INSERT INTO devices (id, gaddr, room, name, type, val, lastval, brand) VALUES ('" 
							. $device[id] . "','" 
							. $device[gaddr] . "','"
							. $device[room] . "','"
							. $device[name] . "','"
							. $device[type] . "','"
							. $device[val] . "','"
							. $device[lastval] . "','"
							. $device[brand] . "')"
							) 
			)
	{
		$apperr .= "mysqli_query INSERT error(" . $mysqli->errno . ") " . $mysqli->error . "\n" ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(6);
}


/*	-----------------------------------------------------------------------------------
	Delete a device record from the database. This is one of the element functions
	needed to synchronize the database with the memory storage in the client, and
	prevents information loss between reloads of the screen.
	
	-----------------------------------------------------------------------------------	*/
function delete_device($device)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr = "delete_device:: id: ".$device[id]." room: ".$device[room]." val: ".$device[val]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("delete_device:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
	}
	
	$msg = "DELETE FROM devices WHERE id='$device[id]' AND room='$device[room]'";
	$apperr .= $msg;
	if (!mysqli_query($mysqli, "DELETE FROM devices WHERE id='$device[id]' AND room='$device[room]'" ))
	{
		$apperr .= "mysqli_query error" ;

	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(4);
}


// ----------------------------------------------------------------------------------
//
// Store a device object as received from the ajax call and update mySQL
//
function store_device($device)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr, $appmsg;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "store_device:: device id: ".$device[id]." room: ".$device[room]." val: ".$device[val]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
		return (-1);
	}
	
	// Update the database
	if (!mysqli_query($mysqli,"UPDATE devices SET gaddr='{$device[gaddr]}', val='{$device[val]}', lastval='{$device[lastval]}', name='{$device[name]}', brand='{$device[brand]}' WHERE room='$device[room]' AND id='$device[id]'" ))
	{
		$apperr .= "mysqli_query error" ;
//		$apperr .= "mysqli_query Error: " . mysqli_error($mysqli) ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(3);
}



// ----------------------------------------------------------------------------------
//
// Add a room object as received from the ajax call and update mySQL
//
// ----------------------------------------------------------------------------------- */
function add_room($room)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr = "add_room:: id: ".$room[id]." name: ".$room[name]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("add_room:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
		return (-1);
	}
	
	if (!$mysqli->query("INSERT INTO rooms (id, name) VALUES ('" 
							. $room[id]	. "','" 
							. $room[name] . "')"
							) 
			)
	{
		$apperr .= "mysqli_query INSERT error(" . $mysqli->errno . ") " . $mysqli->error . "\n" ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(7);
}

/*	-----------------------------------------------------------------------------------
	Delete a room record from the database. This is one of the element functions
	needed to synchronize the database with the memory storage in the client, and
	prevents information loss between reloads of the screen.
	
	-----------------------------------------------------------------------------------	*/
function delete_room($room)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "room id: ".$room[id]." name: ".$room[name]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("delete_room:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
	}
	
	$msg = "DELETE FROM rooms WHERE id='$room[id]' ";
	$apperr .= $msg;
	if (!mysqli_query($mysqli, "DELETE FROM rooms WHERE id='$room[id]' " ))
	{
		$apperr .= "mysqli_query error" ;
	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(10);
}


//	--------------------------------------------------------------------------------
//	Function read scene from MySQL
//
//	Lookup the scene with the corresponding name
//	-----------------------------------------------------------------------------------
function read_scene($name)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	$res = array();
	// We need to connect to the database for start

	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("read_scene:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
	}
	
	$sqlCommand = "SELECT id, val, name, seq FROM scenes WHERE name='$name' ";
	//$sqlCommand = "SELECT seq FROM scenes WHERE name='$name' ";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 
		$res[] = $row ;
	}

	mysqli_free_result($query);
	mysqli_close($mysqli);
	
	// NOTE: Assuming every sequence/scene name is unique, we return ONLY the first scene
	//	remember for seq only to use result['seq'] for sequence only
	if (count($res) == 0) {
		$apperr .= "ERROR read_scene: scene $name not found\n";
		return(-1);
	}
	else {
		// Only return ONE scene (there should only be one btw)
		return ($res[0]);
	}
}


/* -----------------------------------------------------------------------------------
  Load all scene(s_ with 'name' from the SQL database
  
  We start readingthe scene as soon as we determine that it is time to start a
  command based o timer settings. If so, we lookup the scene and its seq(uence)
  element. The scene['seq'] contains the string of commands to be sent to the 
  devices......
  
  We read the database scenes and determine if action need to be taken.
  NOTE: Scene names and timer names need to be unique.
 -------------------------------------------------------------------------------------*/
function load_scenes()
{
	global $log, $debug;
	global $apperr;
	global $dbname, $dbuser, $dbpass, $dbhost;
	
	$config = array();
	$scenes = array();

	// We need to connect to the database for start
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("load_scenes:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		return(-1);
	}
	
	$sqlCommand = "SELECT * FROM scenes";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 

		$scenes[] = $row ;
	}
	// There will be an array returned, even if we only have ONE result
	mysqli_free_result($query);
	mysqli_close($mysqli);
	
	if (count($scenes) == 0) {	
		return(-1);
	}
	return($scenes);								// Return all scenes
}



// ------------------------------------------------------------------------------------
// Find a single name in the scene database array opject.
function load_scene($name)
{
	global $log, $debug;
	$scenes = load_scenes();
	if (count($scenes) == 0) return(-1);

	for ($i=0; $i<count($scenes); $i++) {
		
		if ($scenes[$i]['name'] == $name) return($scenes[$i]);
	}
	// If there is more than 1 result (impossible), we return the first result
	return(-1);
}




// ----------------------------------------------------------------------------------
//
// Add a scene object as received from the ajax call and update mySQL
//
// ----------------------------------------------------------------------------------- */
function add_scene($scene)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "scene id: " . $scene[id] . " name: " . $scene[name] . "\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("add_scene:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
		return (-1);
	}
	
	if (!$mysqli->query("INSERT INTO scenes (id, val, name, seq) VALUES ('" 
							. $scene[id] . "','" 
							. $scene[val]. "','"
							. $scene[name]. "','"
							. $scene[seq]. "')"
							) 
			)
	{
		$apperr .= "mysqli_query INSERT error(" . $mysqli->errno . ") " . $mysqli->error . "\n" ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(9);
}



//	-----------------------------------------------------------------------------------
//	Delete a scene record from the database. This is one of the element functions
//	needed to synchronize the database with the memory storage in the client, and
//	prevents information loss between reloads of the screen.
//	
//	-----------------------------------------------------------------------------------
function delete_scene($scene)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "scene id: " . $scene[id] . " name: " . $scene[name]  . "\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("delete_scene:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
	}
	
	$msg = "DELETE FROM scenes WHERE id='$scene[id]' ";
	$apperr .= $msg;
	if (!mysqli_query($mysqli, "DELETE FROM scenes WHERE id='$scene[id]' " ))
	{
		$apperr .= "mysqli_query error" ;

	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(11);
}



//	-----------------------------------------------------------------------------------
//	Store the scene record in the MySQL database
//	
//	-----------------------------------------------------------------------------------
function store_scene($scene)
{	
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr, $log;
	
	// We need to connect to the database for start
	$apperr .= "Scene id: ".$scene[id]." name: ".$scene[name]." val: ".$scene[val].", seq".$scene[seq]."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("store_scene:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
		return (-1);
	}
//
	$test = "UPDATE scenes SET val='{$scene[val]}', name='{$scene[name]}', seq='{$scene[seq]}' WHERE id='$scene[id]' ";
	$apperr .= $test;
	if (!mysqli_query($mysqli,"UPDATE scenes SET val='{$scene[val]}', name='{$scene[name]}', seq='{$scene[seq]}' WHERE  id='$scene[id]' " ))
	{
		$apperr .= "Error: Store scene, ";
		$apperr .= "mysqli_query error" ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(8);
}



/* -----------------------------------------------------------------------------------
  Load the array of timers from the SQL database into a local array object
  
  Communication between the running program and this backend daemon is done solely
  based on MySQL database content. In a later version, we might work with sockets 
  also.
  
  We read the database timers and determine if action need to be taken.
 -------------------------------------------------------------------------------------*/
function load_timers()
{
	 // We assume that a database has been created by the user
	global $dbname;
	global $dbuser;
	global $dbpass;
	global $dbhost;
	global $log;
	
	$config = array();
	$timers = array();
	
	// We need to connect to the database for start
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("load_timers:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error);
		return(-1);
	}
	
	$sqlCommand = "SELECT * FROM timers";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 
		$timers[] = $row ;
	}
	
	mysqli_free_result($query);
	mysqli_close($mysqli);
	return($timers);
}

// ----------------------------------------------------------------------------------
//
// Add a timer object as received from the ajax call and update mySQL
//
// ----------------------------------------------------------------------------------- */
function add_timer($timer)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "timer id: " . $timer[id] . " name: " . $timer[name] . "\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("add_timeer:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error, 1);
		return (-1);
	}
	if (!$mysqli->query("INSERT INTO timers (id, name, scene, tstart, startd, endd, days, months, skip) VALUES ('" 
							. $timer[id]. "','" 
							. $timer[name]. "','"
							. $timer[scene]. "','"
							. $timer[tstart]. "','"
							. $timer[startd]. "','"
							. $timer[endd]. "','"
							. $timer[days]. "','"
							. $timer[months]. "','"
							. $timer[skip]. "')"
							) 
			)
	{
		$apperr .= "mysqli_query INSERT error(" . $mysqli->errno . ") " . $mysqli->error . "\n" ;
		mysqli_close($mysqli);
		return (-1);
	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(12);
}


//-----------------------------------------------------------------------------------
//	Delete a timer record from the database. This is one of the element functions
//	needed to synchronize the database with the memory storage in the client, and
//	prevents information loss between reloads of the screen.
//	XXX Maybe we shoudl work with addr+unit+val instead of id+unit+val
//	-----------------------------------------------------------------------------------
function delete_timer($timer)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$apperr .= "timer id: ".$timer['id']." name: ".$timer['name']."\n";
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("delete_timer:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
	}
	if (!mysqli_query($mysqli, "DELETE FROM timers WHERE id='$timer[id]' " ))
	{
		$apperr .= "delete_timer:: mysqli_query error for timer: ".$timer['name'] ;
		return(-1);
	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(11);
}


//	-----------------------------------------------------------------------------------
//	Store the scene object in the database
//	
//	-----------------------------------------------------------------------------------
function store_timer($timer)
{	
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("store_timer:: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error , 1);
		return (-1);
	}
	if (!mysqli_query($mysqli,"UPDATE timers SET name='{$timer[name]}', scene='{$timer[scene]}', tstart='{$timer[tstart]}', startd='{$timer[startd]}', endd='{$timer[endd]}', days='{$timer[days]}', months='{$timer[months]}', skip='{$timer[skip]}' WHERE  id='$timer[id]' " ))
	{
		$apperr .= "Error: Store timer, ";
		$apperr .= "mysqli_query error" ;
	//		apperr .= "mysqli_query Error: " . mysqli_error($mysqli) ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(13);
}



/* -----------------------------------------------------------------------------------
  Load the array of handsets from the SQL database into a local array object
  
  Communication between the running program and this backend daemon is done solely
  based on MySQL database content. In a later version, we might work with sockets 
  also.
  
  We read the database handses and determine if action need to be taken.
 -------------------------------------------------------------------------------------*/
function load_handsets()
{
	global $apperr;
	global $debug;
	global $log;
 	// We assume that a database has been created by the user
	global $dbname;
	global $dbuser;
	global $dbpass;
	global $dbhost;
	
	$config = array();
	$handsets = array();
	
	// We need to connect to the database for start
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("load_handsets:: Failed to connect to MySQL: (".$mysqli->connect_errno . ") ".$mysqli->connect_error);
		return(-1);
	}
	
	$sqlCommand = "SELECT * FROM handsets";
	$query = mysqli_query($mysqli, $sqlCommand) or die (mysqli_error());
	while ($row = mysqli_fetch_assoc($query)) { 
		$handsets[] = $row ;
	}
	if ($debug>1) $log->lwrite("load_handsets:: loaded MySQL handsets object");
	
	mysqli_free_result($query);
	mysqli_close($mysqli);
	return($handsets);
}



// ----------------------------------------------------------------------------------
//
// Add a handset object as received from the ajax call and update mySQL
//
// -----------------------------------------------------------------------------------
function add_handset($handset)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $log;
	
	// We need to connect to the database for start
	$log->lwrite("add_handset id: ".$handset[id]." name: ".$handset[name].", addr".$handset[addr]."\n",2);
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("add_handset:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
		mysqli_close($mysqli);
		return (-1);
	}
	
	if (!$mysqli->query("INSERT INTO handsets (id, name, brand, addr, unit, val, type, scene) VALUES ('" 
							. $handset[id] . "','" 
							. $handset[name]. "','"
							. $handset[brand]. "','"
							. $handset[addr]. "','"
							. $handset[unit]. "','"
							. $handset[val]. "','"
							. $handset[type]. "','"
							. $handset[seq]. "')"
							) 
			)
	{
		$log->lwrite("mysqli_query INSERT error(".$mysqli->errno.") ".$mysqli->error."\n",1);
		mysqli_close($mysqli);
		return (-1);
	}
	mysqli_close($mysqli);
	return(17);
}



/*	-----------------------------------------------------------------------------------
	Delete a handset record from the database. This is one of the element functions
	needed to synchronize the database with the memory storage in the client, and
	prevents information loss between reloads of the screen.
	
	-----------------------------------------------------------------------------------	*/
function delete_handset($handset)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $log;
	
	// We need to connect to the database for start
	$log->lwrite("delete_handset:: id: ".$handset[id]." name: ".$handset[name]."\n",2);
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("delete_handset:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
		mysqli_close($mysqli);
		return (-1);
	}

	if (!mysqli_query($mysqli, "DELETE FROM handsets WHERE id='$handset[id]' AND unit='$handset[unit]' AND val='$handset[val]' "))
	{
		mysqli_close($mysqli);
		return (-1);
	}
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(1);
}



// -----------------------------------------------------------------------------------
//	Store the handset record in the MySQL database
//	
//	-----------------------------------------------------------------------------------
function store_handset($handset)
{	
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $log;
	
	$log->lwrite("store_handset:: id: ".$handset[id]." name: ".$handset[name]."\n",2);
	// We need to connect to the database for start
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("store_handset:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
		mysqli_close($mysqli);
		return (-1);
	}

	if (!mysqli_query($mysqli,"UPDATE handsets SET brand='{$handset[brand]}', addr='{$handset[addr]}',  name='{$handset[name]}', type='{$handset[type]}', scene='{$handset[scene]}' WHERE id='$handset[id]' AND unit='$handset[unit]' AND val='$handset[val]' " ))
	{
		mysqli_close($mysqli);
		return (-1);
	}
	mysqli_close($mysqli);
	return(15);
}




// -----------------------------------------------------------------------------------
//	Store the setting object in the database
//	
//	-----------------------------------------------------------------------------------	
function store_setting($setting)
{
	global $dbname, $dbuser, $dbpass, $dbhost;	
	global $apperr;
	global $log;
	
	// We need to connect to the database for start
	$log->lwrite("store_setting id: ".$setting['id']." name: ".$setting['name']." val: ".$setting['val']."\n",2);
	
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if ($mysqli->connect_errno) {
		$log->lwrite("store_settings:: Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error,1);
		return (-1);
	}
	
	$test = "UPDATE settings SET val='{$setting[val]}' WHERE id='$setting[id]' ";
	$apperr .= $test;
	if (!mysqli_query($mysqli,"UPDATE settings SET val='{$setting[val]}' WHERE  id='$setting[id]' " ))
	{
		$apperr .= "mysqli_query error" ;
//		apperr .= "mysqli_query Error: " . mysqli_error($mysqli) ;
		mysqli_close($mysqli);
		return (-1);
	}
	
//	mysqli_free_result($result);
	mysqli_close($mysqli);
	return(5);
}



// -------------------------------------------------------------------------------
// DBASE_PARSE()
//
function dbase_parse($cmd,$message)
{
	global $log;
	global $apperr, $appmsg;
	$log->lwrite("dbase_parse:: received cmd: ".$cmd.", message: ".$message);
	switch($cmd)
	{
		// Device
		case "load_devices":
			$ret= load_devices();
		break;
		case "add_device":
			$ret= add_device($message);
		break;
		case "delete_device":
			$ret= delete_device($message);
		break;
		case "store_device":
			$ret= store_device($message);
		break;
		// Room
		case "add_room":
			$ret= add_room($message);
		break;
		case "delete_room":
			$ret= delete_room($message);
		break;
		// Scene
		case "read_scene":
			$ret= load_scene($message);
		break;
		case "load_scenes":
			$ret= load_scenes();
		break;
		case "add_scene":
			$ret= add_scene($message);
		break;
		case "delete_scene":
			$ret= delete_scene($message);
		break;
		case "upd_scene":
			$ret= upd_scene($message);
		break;
		case "store_scene":
			$ret= store_scene($message);
		break;
		// Timer
		case "add_timer":
			$ret= add_timer($message);
		break;
		case "delete_timer":
			$ret= delete_timer($message);
		break;
		case "store_timer":
			$ret= store_timer($message);
		break;
		// Handset
		case "add_handset":
			$ret= add_handset($message);
		break;
		case "delete_handset":
			$ret= delete_handset($message);
		break;
		case "store_handset":
			$ret= store_handset($message);
		break;
		// Weather
		case "add_weather":
			$ret= add_weather($message);
		break;
		case "delete_weather":
			$ret= delete_weather($message);
		break;
		// Setting
		case "store_setting":
			$ret= store_setting($message);
		break;
		
		default:
	}
	if ($ret >= 0) {								// Prepare structure to send back to the calling ajax client (in stdout)
		$send = array(
    		'tcnt' => $ret,
			'appmsg'=> $appmsg,
    		'status' => 'OK',
			'apperr'=> $apperr,
    	);
		$output=json_encode($send);
	}
	else {											//	Functions need to fill apperr themselves!	
		$send = array(
    		'tcnt' => $ret,
			'appmsg'=> $appmsg,
    		'status' => 'ERR',
			'apperr' => $apperr,
		);
		$output=json_encode($send);
	}
	return $output;
}

?>