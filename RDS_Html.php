<?php
/**
 * HTML debug interface.
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
require_once 'RDS_Base.php';

/**
 * HTML debug interface.
 */
class RDS_Html extends RDS_Base
{
	/**
	 * HTML debug interface.
	 *
	 * This interface is always buffered.
	 *
	 * @param[in] name string Debug session name.
	 * @param[in] attach_to_error_handler boolean If the debug system should
	 *     overwrite the php's default error_handler.
	 */
	public function __construct( $name, $attach_to_error_handler = true )
	{
		parent::__construct( $attach_to_error_handler, true );
		$this->name = $name;
	}
	/**
	 * Flush the buffer (if any) and write profiler(s).
	 */
	public function flush()
	{
		echo '<div style="border: 1px black solid; background-color: #EAEAEA; padding: 10px; margin: 5px; color: black;">';
		parent::flush();
		echo '</div>';
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
		$levelStyle = $this->levelToStyleColor( $level );

		$message = htmlspecialchars( $message );
		echo "<span {$levelStyle}>{$levelStr}({$level}): {$message} --- {$file_name}({$file_line})</span><br />";
	}
	/**
	 * Write profiler information to the output.
	 *
	 * @param[in] profiler RDS_Profiler Profiler object.
	 */
	protected function writeProfiler( RDS_Profiler $profiler )
	{
		$memory = $this->convertSizeToHuman( $profiler->startMemory );
		echo "<h1>{$profiler->name} START {$profiler->startTime}.</h1><br />Memory: {$memory}. --- {$profiler->startFileName} {$profiler->startFileLine}<br /><ol style='padding: 20px;'>";

		foreach( $profiler->getEvents() as $count => $event )
		{
			$startTime = round( $event['start_time'] - $profiler->startTime, 3 );
			$endTime = round( $event['end_time'] - $profiler->startTime, 3 );
			$deltaTime = round( $endTime - $startTime, 3 );
			$startMemory = $this->convertSizeToHuman( $event['start_memory'] );
			$endMemory = $this->convertSizeToHuman( $event['end_memory'] );
			$deltaMemory = $this->convertSizeToHuman( $event['end_memory'] - $event['start_memory'] );
			$message = htmlspecialchars( $event['message'] );
			echo "<li>Event #{$count}: [{$startTime} -&gt; {$endTime} ({$deltaTime} delta)] [{$startMemory} -&gt; {$endMemory} ({$deltaMemory} delta)] {$event['message']}.--- {$event['file_name']} {$event['file_line']}</li>";
		}
		$memory = $this->convertSizeToHuman( $profiler->endMemory );
		$delta = $this->convertSizeToHuman( $profiler->endMemory - $profiler->startMemory );
		$totalTime = round( $profiler->endTime - $profiler->startTime, 3 );
		echo "</ol>{$profiler->name} END {$profiler->endTime} ({$totalTime} since start). Memory: {$memory} ({$delta} delta). --- {$profiler->endFileName} {$profiler->endFileLine}<br />";
	}
	/**
	 * Get color based on level.
	 *
	 * @param[in] level integer Message level.
	 * @return string style="[definition]" string or empty string.
	 */
	public function levelToStyleColor( $level )
	{
		switch( $level )
		{
			case RDS::ERROR:
				return 'style="color: red; font-weight: bold;"';
			case RDS::WARNING:
				return 'style="color: brown;"';
			case RDS::NOTICE:
				return 'style="color: blue;"';
			case RDS::DEBUG:
				return 'style="color: darkgray; font-style: italic;"';
			default:
				return '';
		}
	}
}
