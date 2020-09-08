<?php
/**
 * Profiler class
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
/**
 * Profiler class
 */
class RDS_Profiler
{
	/**
	 * string Profiler name.
	 */
	public $name;
	/**
	 * float Timestamp when the profiler was created.
	 */
	public $startTime;
	/**
	 * float Memory usage when the profiler was created.
	 */
	public $startMemory;
	/**
	 * string File name where the profiler was created.
	 */
	public $startFileName;
	/**
	 * string Line insed file where the profiler was created.
	 */
	public $startFileLine;
	/**
	 * float Timestamp when the profiler was closed.
	 */
	public $endTime;
	/**
	 * float Memory usage when the profiler was closed.
	 */
	public $endMemory;
	/**
	 * string File name where the profiler was closed.
	 */
	public $endFileName;
	/**
	 * string Line insed file where the profiler was closed.
	 */
	public $endFileLine;
	/**
	 * boolean True if the profiler still open.
	 */
	protected $open;
	/**
	 * array Array of events.
	 */
	protected $events;

	/**
	 * Create new profiler.
	 *
	 * @param[in] name string Event name.
	 * @param[in] stack_level integer File name and line stack frame number.
	 *     Default to caller.
	 */
	public function __construct( $name, $stack_level = 0 )
	{
		$this->name = strtoupper( str_replace( ' ', '_', $name ) );
		$this->startTime = microtime( true );
		$this->startMemory = memory_get_usage();
		$stack = debug_backtrace();
		$this->startFileName = $stack[$stack_level]['file'];
		$this->startFileLine = $stack[$stack_level]['line'];
		unset( $stack );
		$this->endTime = null;
		$this->endMemory = function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0;
		$this->endFileName = null;
		$this->endFileLine = null;
		$this->open = true;
		$this->events = array();
	}
	/**
	 * Create event.
	 *
	 * @param[in] message string Event message.
	 * @return integer Event ID
	 */
	public function startEvent( $message )
	{
		if( $this->open )
		{
			$stack = debug_backtrace();
			$this->events[] = array(
				'message' => $message,
				'file_name' => $stack[0]['file'],
				'file_line' => $stack[0]['line'],
				'start_memory' => function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0,
				'start_time' => microtime( true ),
				'end_memory' => null,
				'end_time' => null
			);
			return count( $this->events ) - 1;
		}
		else
		{
			throw new Exception( "Profiler {$this->name} is closed." );
		}
	}
	/**
	 * End event.
	 *
	 * @param[in] id integer Event ID.
	 */
	public function endEvent( $id )
	{
		if( $this->open && array_key_exists( $id, $this->events ) )
		{
			$this->events[$id]['end_memory'] = function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0;
			$this->events[$id]['end_time'] = microtime( true );
		}
		elseif( ! $this->open )
		{
			throw new Exception( "Profiler {$this->name} is closed." );
		}
		else
		{
			throw new Exception( "Event {$id} not found." );
		}
	}
	/**
	 * End the profiler.
	 */
	public function end()
	{
		if( $this->open )
		{
			$stack = debug_backtrace();
			$this->endMemory = function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0;
			$this->endTime = microtime( true );
			$this->endFileName = $stack[0]['file'];
			$this->endFileLine = $stack[0]['line'];
			$this->open = false;
			for( $i = 0; $i < count( $this->events ); $i++ )
			{ // close all events.
				if( is_null( $this->events[$i]['end_memory'] ) )
				{
					$this->events[$i]['end_memory'] = $this->endMemory;
				}
				if( is_null( $this->events[$i]['end_time'] ) )
				{
					$this->events[$i]['end_time'] = $this->endTime;
				}
			}
		}
	}
	/**
	 * Check if the profiler is open.
	 *
	 * @return boolean True if open.
	 */
	public function isOpen()
	{
		return $this->open;
	}
	/**
	 * Get events.
	 *
	 * @return array Array of events.
	 */
	public function getEvents()
	{
		return $this->events;
	}
}
