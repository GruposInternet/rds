<?php
/**
 * Network debug interface.
 *
 * Copyright (C) 2010  Sérgio Surkamp
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
 * USA.
 *
 * @author Sérgio Surkamp <surkamp(at)gmail(dot)com>
 * @since RDSPHP_0_0_1
 * @package RDS
 */
require_once 'RDS/RDS_Base.php';

/**
 * Network debug interface.
 */
class RDS_Network extends RDS_Base
{
	/**
	 * resource Network socket.
	 */
	protected $socket = null;
	/**
	 * string Hostname.
	 */
	protected $host = null;
	/**
	 * string Host port.
	 */
	protected $port = null;
	/**
	 * string Debug session name.
	 */
	protected $name = null;
	/**
	 * string Connection charset.
	 */
	protected $charset = null;
	/**
	 * string Handshake terminator string.
	 */
	const HANDSHAKE_TERMINATOR = "\n";
	/**
	 * integer Protocol version.
	 */
	const HANDSHAKE_PROTOCOL_VERSION = 1;
	/**
	 * string Protocol message terminator.
	 */
	const MESSAGE_TERMINATOR = '$%$%$%';
	/**
	 * string Protocol newline representation.
	 */
	const NET_NEW_LINE = '$#$#$#';

	/**
	 * Network debug interface.
	 *
	 * @param[in] name string Debug session name.
	 * @param[in] debug_host string Debug interface host.
	 * @param[in] debug_port int Debug interface port.
	 * @param[in] attach_to_error_handler boolean If the debug system should
	 *     overwrite the php's default error_handler.
	 * @param[in] buffered boolean Enable/disable buffered output.
	 * @param[in] charset string Connection charset.
	 */
	public function __construct( $name, $debug_host, $debug_port = 6660, $attach_to_error_handler = true, $buffered = true, $charset = 'iso-8859-1' )
	{
		parent::__construct( $attach_to_error_handler, $buffered );
		$this->name = $name;
		$this->host = $debug_host;
		$this->port = $debug_port;
		$this->charset = $charset;

		if( ! $buffered )
		{
			// open socket on startup, else the socket will be opened only
			// on __destruct for flush
			$this->openSocket();
		}
	}
	/**
	 * Destruct the class
	 */
	public function __destruct()
	{
		$this->debug( RDS::DEBUG, 'Closing debug link.', __FILE__, __LINE__ );
		parent::__destruct();
		$this->closeSocket();
	}
	/**
	 * Flush the buffer (if any) and write profiler(s).
	 */
	public function flush()
	{
		if( is_null( $this->socket ) )
		{
			$this->openSocket();
		}
		parent::flush();
	}
	/**
	 * Write message to the socket link.
	 *
	 * @param[in] level integer Message level (see RDS class)
	 * @param[in] message string Debug message
	 * @param[in] file_name string Origin file name (used by
	 *     RDS_Base::errorHandler)
	 * @param[in] file_line integer Origin file line (used by
	 *     RDS_Base::errorHandler)
	 */
	protected function write( $level, $message, $file_name, $file_line )
	{
		if( is_array( $message ) )
		{
			$message = implode( self::NET_NEW_LINE, $message );
		}
		//TODO: Modify this when the handshake set the charset
		$message = iconv( $this->charset, 'utf-8', str_replace( "\n", self::NET_NEW_LINE, $message ) );
		if( $this->socket )
		{ // write only if the socket was correctly created.
			//POG: Remove whitespaces from file name as it breaks the network protocol
			$file_name = str_replace( ' ', '_', $file_name );
			//TODO: Modify this when the handshake set the charset
			$file_name = iconv( $this->charset, 'utf-8', $file_name );
			socket_write( $this->socket, "1 {$file_name} {$file_line} {$level} {$message}" . self::MESSAGE_TERMINATOR );
		}
	}
	/**
	 * Write profiler information to the output.
	 *
	 * @param[in] profiler RDS_Profiler Profiler object.
	 */
	protected function writeProfiler( RDS_Profiler $profiler )
	{
		if( $this->socket )
		{ // write only if the socket was correctly created.
			//POG: Remove whitespaces from file name as it breaks the network protocol
			$fileName = str_replace( ' ', '_', $profiler->startFileName );
			//TODO: Modify this when the handshake set the charset
			$fileName = iconv( $this->charset, 'utf-8', $fileName );

			$memory = $this->convertSizeToHuman( $profiler->startMemory );
			socket_write( $this->socket, "2 {$profiler->name} {$profiler->startTime} {$fileName} {$profiler->startFileLine} Memory: {$memory}." . self::MESSAGE_TERMINATOR );
			foreach( $profiler->getEvents() as $event )
			{
				//POG: Remove whitespaces from file name as it breaks the network protocol
				$fileName = str_replace( ' ', '_', $event['file_name'] );
				//TODO: Modify this when the handshake set the charset
				$fileName = iconv( $this->charset, 'utf-8', $fileName );
				$message = iconv( $this->charset, 'utf-8', $event['message'] );

				$startMemory = $this->convertSizeToHuman( $event['start_memory'] );
				$endMemory = $this->convertSizeToHuman( $event['end_memory'] );
				$deltaMemory = $this->convertSizeToHuman( $event['end_memory'] - $event['start_memory'] );
				socket_write( $this->socket, "4 {$profiler->name} {$event['start_time']} {$event['end_time']} {$fileName} {$event['file_line']} [{$startMemory} -> {$endMemory} ({$deltaMemory} delta)] {$message}." . self::MESSAGE_TERMINATOR );
			}
			//POG: Remove whitespaces from file name as it breaks the network protocol
			$fileName = str_replace( ' ', '_', $profiler->endFileName );
			//TODO: Modify this when the handshake set the charset
			$fileName = iconv( $this->charset, 'utf-8', $fileName );

			$memory = $this->convertSizeToHuman( $profiler->endMemory );
			$delta = $this->convertSizeToHuman( $profiler->endMemory - $profiler->startMemory );
			socket_write( $this->socket, "3 {$profiler->name} {$profiler->endTime} {$fileName} {$profiler->endFileLine} Memory: {$memory} ({$delta} delta)." . self::MESSAGE_TERMINATOR );
		}
	}
	/**
	 * Open the connection socket.
	 */
	protected function openSocket()
	{
		$this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		if( socket_connect( $this->socket, $this->host, $this->port ) )
		{
			// handshake
			socket_write( $this->socket, "2 {$this->name}" . self::HANDSHAKE_TERMINATOR );
			socket_write( $this->socket, '3 ' . self::HANDSHAKE_PROTOCOL_VERSION . self::HANDSHAKE_TERMINATOR );
			//TODO: set charset
			//socket_write( $this->socket, "4 {$this->charset}" . self::HANDSHAKE_TERMINATOR );
			socket_write( $this->socket, '1' . self::HANDSHAKE_TERMINATOR );
		}
		else
		{
			$this->socket = null;
		}
	}
	/**
	 * Close the connection socket.
	 */
	protected function closeSocket()
	{
		if( $this->socket )
		{
			socket_write( $this->socket, self::MESSAGE_TERMINATOR );
			socket_close( $this->socket );
			$this->socket = null;
		}
	}
}
