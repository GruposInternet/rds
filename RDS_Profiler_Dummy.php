<?php
/**
 * Dummy profiler class.
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
 * Dummy profiler class.
 */
class RDS_Profiler_Dummy
{
	/**
	 * Create a new dummy profiler.
	 */
	public function __construct() { ; }
	/**
	 * Create event.
	 *
	 * @return integer Always zero.
	 */
	public function startEvent() { return 0; }
	/**
	 * End event.
	 */
	public function endEvent() { ; }
	/**
	 * End this profiler.
	 */
	public function end() { ; }
	/**
	 * Check if the profiler is open.
	 *
	 * @return boolean Always false.
	 */
	public function isOpen() { return false; }
	/**
	 * Get events.
	 *
	 * @return array Always empty.
	 */
	public function getEvents() { return array(); }
}
