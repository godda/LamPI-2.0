//--------------------------------------------------------------------------------------------------
//                                  _            _     
//                                 | |          | |    
//      ___ _ __ ___  ___ _   _ ___| |_ ___  ___| |__  
//     / _ \ '_ ` _ \/ __| | | / __| __/ _ \/ __| '_ \. 
//    |  __/ | | | | \__ \ |_| \__ \ ||  __/ (__| | | |
//     \___|_| |_| |_|___/\__, |___/\__\___|\___|_| |_|
//                         __/ |                       
//                        |___/    Engineering (www.emsystech.de)
//
// Filename:    main.c
// Description: 
//
// Open Source Licensing 
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// Dieses Programm ist Freie Software: Sie können es unter den Bedingungen
// der GNU General Public License, wie von der Free Software Foundation,
// Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren
// veröffentlichten Version, weiterverbreiten und/oder modifizieren.
//
// Dieses Programm wird in der Hoffnung, dass es nützlich sein wird, aber
// OHNE JEDE GEWÄHRLEISTUNG, bereitgestellt; sogar ohne die implizite
// Gewährleistung der MARKTFÄHIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK.
// Siehe die GNU General Public License für weitere Details.
//
// Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
// Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
//                   
// Author:      Martin Steppuhn
// History:     05.09.2012 Initial version "Quick and Dirty" 
//				01.11.2012 Flexible PIN Configuration for I2C and Hardwaredetection for Raspberry
//				19.11.2012 3.0.0 I2C access via driver
//--------------------------------------------------------------------------------------------------

//=== Includes =====================================================================================

#include "std_c.h"
#include <stdio.h>
#include <linux/i2c-dev.h>
#include <stdlib.h>
#include <unistd.h>
#include <fcntl.h>
#include <netdb.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <string.h>

#include "i2c.h"
#include "sht21.h"
#include "raspi.h"
#include "sensor.h"

//=== Preprocessing directives (#define) ===========================================================

//=== Type definitions (typedef) ===================================================================

//=== Global constants =============================================================================

//=== Global variables =============================================================================

//=== Local constants  =============================================================================

//=== Local variables ==============================================================================

//=== Local function prototypes ====================================================================

//--------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------
//
// Please check /home/pi/log/LamPI-daemon.log for messages
//
//--------------------------------------------------------------------------------------------------

int main(int argc, char **argv)
{ 
	int verbose;						// -v is the only commandline parameter allowed
	
	uint32 Counter;
	int16 Temperature;
	uint8 Humidity;
	uint8 HwRev;
	
	// Socket declarationa
	int mode = SOCK_DGRAM;					// Datagram is standard
	char *hostname = "255.255.255.255";		// Default setting for our host == broadcast
	char *port = UDPPORT;					// default port, 5001
	int sockfd;
	char buf[256];
	
	Counter = 0;
	
	HwRev = GetRaspberryHwRevision();
	
	if(HwRev < 2) 	I2C_Open("/dev/i2c-0");	 // Hardware Revision 1.0
		else		I2C_Open("/dev/i2c-1");  // Hardware Revision 2.0
	I2C_Setup(I2C_SLAVE, 0x40);
	
	if(I2cError)
	{	
		I2C_PrintError();
		exit(1);
	}
			
	SHT21_Read(&Temperature,&Humidity);
	if(Sht21Error != 0) {
		PrintSht21Error();	
		I2cError = 0;	
	}
	
	printf("%lu\t%.1f\t%u\n",Counter++,((float)Temperature)/10,Humidity);			
	sockfd = socket_open(hostname, port, mode);
	
	// Make a jSon message to send to the server
		sprintf(buf,"{\"tcnt\":\"21\",\"action\":\"weather\",\"brand\":\"sht21\",\"type\":\"json\",\"address\":\"40\",\"channel\":\"1\",\"temperature\":\"%2.1f\",\"humidity\":\"%d\"}", (float)(Temperature)/10, Humidity );
	
	buf_2_server(sockfd, 
				hostname,			// HostIP, eg 255.255.255.255
				port,				// Port number, eg 5001
				mode,
				buf);
				
	if (close(sockfd) == -1) {
			perror("Error closing socket to daemon");
	}
	
	// Should wait for confirmation of the daemon before closing
	
	I2C_Close();
	return(0);
}
