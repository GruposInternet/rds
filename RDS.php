<?php
/**
 * Error level definnition class.
 *
 * Copyright (C) 2010  S�rgio Surkamp
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
 * @author S�rgio Surkamp <surkamp(at)gmail(dot)com>
 * @since RDSPHP_0_0_1
 * @package RDS
 */
/**
 * Error level definnition class.
 *
 * All classes decend from this.
 */
abstract class RDS
{
	/*
	 * Available message level
	 */
	/**
	 * Error messages.
	 */
	const ERROR = 1;
	/**
	 * Warnings.
	 */
	const WARNING = 2;
	/**
	 * Notices.
	 */
	const NOTICE = 3;
	/**
	 * Debug.
	 */
	const DEBUG = 4;
}
