<?php
/**
 * Text console debug interface.
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
 * Text console debug interface.
 */
class RDS_Console extends RDS_Base
{
	/**
	 * boolean Enable/disable colored output.
	 */
	public $colors = true;
	/**
	 * Text console debug interface.
	 *
	 * @param[in] name string Debug session name.
	 * @param[in] attach_to_error_handler boolean If the debug system should
	 *     overwrite the php's default error_handler.
	 * @param[in] buffered boolean Enable/disable buffered output.
	 * @param[in] colors boolean Enable/disable colored output.
	 */
	public function __construct( $name, $attach_to_error_handler = true, $buffered = true, $colors = true )
	{
		parent::__construct( $attach_to_error_handler, $buffered );
		$this->name = $name;
		$this->colors = $colors;
	}
	/**
	 * Write message to the output.
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
		$levelStr = $this->levelToString( $level );
		if( $this->colors )
		{
			switch( $level )
			{
				case RDS::ERROR:
					$color = "\033[1;31m";
					break;
				case RDS::WARNING:
					$color = "\033[1;33m";
					break;
				case RDS::NOTICE;
					$color = "\033[1;36m";
					break;
				case RDS::DEBUG;
					$color = "\033[1;32m";
					break;
				default:
					$color = "\033[1;34m";
					break;
			}
			echo "{$color}{$file_name}({$file_line}): {$levelStr}: {$message}\033[0m\n";
		}
		else
		{
			echo "{$file_name}({$file_line}): {$levelStr}: {$message}\033[0m\n";
		}
	}
	/**
	 * Write profiler information to the output.
	 *
	 * @param[in] profiler RDS_Profiler Profiler object.
	 */
	protected function writeProfiler( RDS_Profiler $profiler )
	{
		$memory = $this->convertSizeToHuman( $profiler->startMemory );
		$this->write( RDS::DEBUG, "{$profiler->name} START {$profiler->startTime}. Memory: {$memory}.", $profiler->startFileName, $profiler->startFileLine );

		foreach( $profiler->getEvents() as $count => $event )
		{
			$startTime = round( $event['start_time'] - $profiler->startTime, 3 );
			$endTime = round( $event['end_time'] - $profiler->startTime, 3 );
			$deltaTime = round( $endTime - $startTime, 3 );
			$startMemory = $this->convertSizeToHuman( $event['start_memory'] );
			$endMemory = $this->convertSizeToHuman( $event['end_memory'] );
			$deltaMemory = $this->convertSizeToHuman( $event['end_memory'] - $event['start_memory'] );
			$this->write( RDS::DEBUG, "Event #{$count}: [{$startTime} -> {$endTime} ({$deltaTime} delta)] [{$startMemory} -> {$endMemory} ({$deltaMemory} delta)] {$event['message']}.", $event['file_name'], $event['file_line'] );
		}
		$memory = $this->convertSizeToHuman( $profiler->endMemory );
		$delta = $this->convertSizeToHuman( $profiler->endMemory - $profiler->startMemory );
		$totalTime = round( $profiler->endTime - $profiler->startTime, 3 );
		$this->write( RDS::DEBUG, "{$profiler->name} END {$profiler->endTime} ({$totalTime} since start). Memory: {$memory} ({$delta} delta).", $profiler->endFileName, $profiler->endFileLine );
	}
}
