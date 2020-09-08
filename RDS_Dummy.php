<?php
/**
 * Dummy debug class.
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
require_once 'RDS/RDS.php';
require_once 'RDS/RDS_Profiler_Dummy.php';

/**
 * Dummy debug class.
 *
 * This class should be used to disable the debug system.
 *
 * Example:
 *
 * <?php
 * define( 'DEBUG_ENABLE', true );
 * define( 'DEBUG_HOST', '192.168.1.99' );
 *
 * if( DEBUG_ENABLE )
 * {
 *		require_once DEBUG_ROOT . 'RDS_Network.php';
 *		$debug = new RDS_Network( $_SERVER['REQUEST_URI'], DEBUG_HOST );
 * }
 * else
 * {
 *		require_once DEBUG_ROOT . 'RDS_Dummy.php';
 *		$debug = new RDS_Dummy();
 * }
 */
class RDS_Dummy extends RDS
{
	/**
	 * Create a dummy debug class.
	 *
	 * Real implementation in GDSDebugBase class. This one is used when the
	 * system debug isn't enabled.
	 *
	 * @see RDS_Base::__construct
	 * @param[in] attach_to_error_handler boolean If the debug system should
	 *     overwrite the php's default error_handler.
	 * @param[in] buffered boolean Enable/disable buffered output.
	 */
	public function __construct( $attach_to_error_handler = true, $buffered = true ) { ; }
	/**
	 * Flush buffer.
	 *
	 * @see RDS_Base::flush
	 */
	public function flush() { ; }
	/**
	 * Send a debug message by network link
	 *
	 * @see RDS_Base::debug
	 * @param[in] level integer Message level (see RDS constants).
	 * @param[in] message string Debug message.
	 * @param[in] file_name string Origin file name (used by
	 *     RDS_Base::errorHandler).
	 * @param[in] file_line integer Origin file line (used by
	 *     RDS_Base::errorHandler).
	 */
	public function debug( $level, $message, $file_name = null, $file_line = null ) { ; }
	/**
	 * Dump object content to debug stream.
	 *
	 * @see RDS_Base::dump
	 * @param[in] object mixed Object to be dumped.
	 */
	public function dump( $object ) { ; }
	/**
	 * Dump stack trace to debug system.
	 *
	 * @param[in] limit integer Stack dig limit.
	 * @param[in] stack_level integer Start stack trace from this level.
	 */
	public function dumpStackTrace( $limit = 3, $stack_level = 1 ) { ; }
	/**
	 * Create new profiler.
	 *
	 * @see RDS_Base::newProfiler
	 * @param[in] name string Profiler name.
	 * @return RDS_Profiler_Dummy Dummy profiler object.
	 */
	public function newProfiler( $name ) { return new RDS_Profiler_Dummy(); }
	/**
	 * Get message buffer.
	 *
	 * Always returns null if unbuffered mode is activated.
	 *
	 * @see RDS_Base::getMessageBuffer
	 * @return array 4-uple ( level, message, file_name, file_line ) or null.
	 */
	public function getMessageBuffer() { ; }
}
