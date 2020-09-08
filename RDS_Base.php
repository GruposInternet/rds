<?php
/**
 * Abstract base class for debug system.
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
require_once 'RDS/RDS_Dummy.php';
require_once 'RDS/RDS_Profiler.php';

// compatibility PHP 5.2.0
if( ! defined( 'E_RECOVERABLE_ERROR' ) )
{
	define( 'E_RECOVERABLE_ERROR', 4096 );
}
// compatibility PHP 5.3.0
if( ! defined( 'E_DEPRECATED' ) )
{
	define( 'E_DEPRECATED', 8192 );
	define( 'E_USER_DEPRECATED', 16384 );
}

/**
 * Abstract base class for debug system.
 *
 * All classes that output something, use this class as it's base.
 *
 * @see http://br.php.net/manual/en/errorfunc.constants.php
 */
abstract class RDS_Base extends RDS_Dummy
{
	/**
	 * array Message buffer.
	 */
	protected $buffer = array();
	/**
	 * array Profilers.
	 */
	protected $profilers = array();
	/**
	 * function Default error handler.
	 */
	protected $old_error_handler = null;
	/**
	 * boolean Use buffer.
	 */
	protected $buffered = true;
	/**
	 * integer Debug start time (assumed to be start processing time).
	 */
	protected $start_time = 0;
	/**
	 * integer Start memory (assumed to be the minimum memory allocated).
	 */
	protected $start_memory = 0;
	/**
	 * Create a base debug class.
	 *
	 * @param[in] attach_to_error_handler boolean If the debug system should
	 *     overwrite the php's default error_handler.
	 * @param[in] buffered boolean Enable/disable buffered output.
	 */
	public function __construct( $attach_to_error_handler = true, $buffered = true )
	{
		$this->start_time = microtime( true );
		$this->start_memory = function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0;
		$this->buffered = $buffered;
		if( $attach_to_error_handler )
		{
			// catch all errors
			$this->old_error_handler = set_error_handler( array( $this, 'errorHandler' ), E_ALL | E_STRICT );
			if( $this->old_error_handler === false )
			{
				$this->debug( RDS::ERROR, 'Could not attach to error_handler' );
			}
			else
			{
				$this->debug( RDS::DEBUG, 'Attached to error handler, using error messages of type E_ALL | E_STRICT' );
			}
			register_shutdown_function( array( $this, 'shutdownHandler' ) );
		}
	}
	/**
	 * Object destructor.
	 *
	 * Restore the old error handler (if any), flush the buffer (if any) and
	 * profiler(s).
	 *
	 * WARNING: The flush call in this method is a failsafe option to ensure
	 * that the buffer will always be flushed when the object is destructed. It
	 * can produce some buffer problems with the RDS_Html plugin, as it will be
	 * executed before PHP's buffer flush.
	 * Its recommended that you manuly destruct the object at the end of your
	 * script processing to ensure that the output, if any, will be flushed
	 * before the PHP virtual machine halt instruction.
	 *
	 * @see http://www.php.net/manual/en/language.oop5.decon.php
	 */
	public function __destruct()
	{
		$this->flush();

		if( is_null( $this->old_error_handler ) )
		{
			restore_error_handler();
		}
	}
	/**
	 * Flush the buffer (if any) and write profiler(s).
	 */
	public function flush()
	{
		// write buffer to stream
		if( count( $this->buffer ) > 0 )
		{
			foreach( $this->buffer as $data )
			{
				$this->write( $data[0], $data[1], $data[2], $data[3] );
			}
			$this->buffer = null;
		}
		// write profilers to stream
		if( count( $this->profilers ) > 0 )
		{
			foreach( $this->profilers as $profiler )
			{
				if( $profiler->isOpen() )
				{
					$profiler->end();
					$this->write(
						RDS::WARNING,
						"Forced profiler end for name '{$profiler->name}'.",
						__FILE__,
						__LINE__
					);
				}
				$this->writeProfiler( $profiler );
			}
			$this->profilers = array();
		}

		// assumed to be the execution end
		$end_time = microtime( true );
		$end_memory = function_exists( 'memory_get_usage' ) ? memory_get_usage() : 0;

		// write information about memory usage and execution time
		$this->write( RDS::DEBUG,
			'Total processing time: ' .
			( round( ( $end_time - $this->start_time ) , 3 ) ) .
			'. Memory used: ' .
			( $this->convertSizeToHuman( $end_memory ) ) .
			'(' .
			( $this->convertSizeToHuman( $end_memory - $this->start_memory ) ) .
			' delta). Memory peak: ' .
			$this->convertSizeToHuman( function_exists( 'memory_get_peak_usage' ) ? memory_get_peak_usage() : 0 ),
			__FILE__,
			__LINE__
		);
	}
	/**
	 * Check for errors on shutdown.
	 *
	 * This function is called when the PHP shutdown, if it has errors, then
	 * send it to debug stream and flush everything.
	 *
	 * @return boolean Always false.
	 */
	public function shutdownHandler()
	{
		if( $error = error_get_last() )
		{
			switch( $error['type'] )
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$this->errorHandler( $error['type'], $error['message'], $error['file'], $error['line'] );
					$this->flush();
					break;
            }
        }
        return false;
	}
	/**
	 * Error handler.
	 *
	 * This function will overwrite the default error handler and route them
	 * to debug system.
	 *
	 * @param errno integer Error number.
	 * @param errmsg string Error message.
	 * @param file_name string File where the error occoured.
	 * @param file_line int File line where the error occoured.
	 * @return boolean Always true.
	 */
	public function errorHandler( $errno, $errmsg, $file_name, $file_line )
	{
		list( $level, $error ) = $this->convertPhpError( $errno );
		$message = "[{$error}:{$errno}]: {$errmsg}";

		$this->debug( $level, $message, $file_name, $file_line );
		return true;
	}
	/**
	 * Map error number to tuple.
	 *
	 * @param[in] errno integer Error number.
	 * @return array( integer, string ) Array with the message RDS level and
	 *     PHP error as string.
	 */
	public function convertPhpError( $errno )
	{
		// first of all, map the error
		switch( $errno )
		{
			case E_NOTICE:
			case E_USER_NOTICE:
				$error_map = array( RDS::NOTICE, 'PHP_NOTICE' );
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_CORE_WARNING:
				$error_map = array( RDS::WARNING, 'PHP_WARNING' );
				break;
			case E_PARSE:
				$error_map = array( RDS::ERROR, 'PHP_PARSE' );
				break;
			case E_RECOVERABLE_ERROR:
			case E_ERROR:
			case E_COMPILE_ERROR:
			case E_CORE_ERROR:
			case E_USER_ERROR:
				$error_map = array( RDS::ERROR, 'PHP_FATAL' );
				break;
			case E_STRICT:
				$error_map = array( RDS::ERROR, 'PHP_STRICT' );
				break;
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$error_map = array( RDS::ERROR, 'PHP_DEPRECATED' );
				break;
			default:
				$error_map = array( RDS::ERROR, 'PHP_UNKNOWN' );
				break;
		}
		return $error_map;
	}
	/**
	 * Create debug message.
	 *
	 * @param[in] level integer Message level (see RDS class).
	 * @param[in] message string Debug message.
	 * @param[in] file_name string Origin file name (used by
	 *     RDS_Base::errorHandler)
	 * @param[in] file_line integer Origin file line (used by
	 *     RDS_Base::errorHandler)
	 */
	public function debug( $level, $message, $file_name = null, $file_line = null )
	{
		if( is_null( $file_name ) )
		{
			$stack = debug_backtrace();
			$file_name = $stack[0]['file'];
		}
		if( is_null( $file_line ) )
		{
			if( ! isset( $stack ) )
			{
				$stack = debug_backtrace();
			}
			$file_line = $stack[0]['line'];
		}

		if( ! $this->buffered )
		{
			$this->write( $level, $message, $file_name, $file_line );
		}
		else
		{
			$this->buffer[] = array( $level, $message, $file_name, $file_line );
		}
		if( $level == RDS::ERROR )
		{
			$this->dumpStackTrace( 5, 2 );
		}
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
	abstract protected function write( $level, $message, $file_name, $file_line );
	/**
	 * Write profiler information to the output.
	 *
	 * @param[in] profiler RDS_Profiler Profiler object.
	 */
	abstract protected function writeProfiler( RDS_Profiler $profiler );
	/**
	 * Dump object content to debug stream.
	 *
	 * WARNING: This method can lead to deep neasted recursion that produces
	 * a PHP_FATAL error.
	 *
	 * @param[in] object mixed Object to be dumped.
	 */
	public function dump( $object )
	{
		$message = var_export( $object, true );
		$this->debug( RDS::DEBUG, $message );
	}
	/**
	 * Dump stack trace to debug system.
	 *
	 * @param[in] limit integer Stack dig limit (0 for infinnity).
	 * @param[in] stack_level integer Start stack trace from this level.
	 */
	public function dumpStackTrace( $limit = 3, $stack_level = 1 )
	{
		// stack_level cant be negative.
		if( $stack_level < 0 )
		{
			throw new Exception( 'stack_level should be positive or zero.' );
		}
		$current = 0;
		$stack = debug_backtrace();
		if( $limit > 0 )
		{
			$lastFrame = $stack_level + $limit;
		}
		else
		{
			$lastFrame = count( $stack );
		}

		$file = array_key_exists( 'file', $stack[$stack_level] ) ? $stack[$stack_level]['file'] : 'unknown';
		$line = array_key_exists( 'line', $stack[$stack_level] ) ? $stack[$stack_level]['line'] : 0;

		for( $current = $stack_level; $current < $lastFrame; $current++ )
		{
			if( ! array_key_exists( $current, $stack ) )
			{
				break;
			}
			// format origin function
			$origin = '';
			if( array_key_exists( 'class', $stack[$current] ) )
			{
				$origin .= "{$stack[$current]['class']}::";
			}
			if( array_key_exists( 'function', $stack[$current] ) )
			{
				$origin .= "{$stack[$current]['function']}(";
				if( array_key_exists( 'args', $stack[$current] ) )
				{
					$argsArray = array();
					foreach( $stack[$current]['args'] as $arg )
					{
						if( is_object( $arg ) )
						{
							$type = get_class( $arg );
							$argsArray[] = "object [{$type}]";
						}
						else
						{
							if( is_string( $arg ) )
							{
								$argsArray[] = "'{$arg}'";
							}
							elseif( is_array( $arg ) )
							{
								$size = count( $arg );
								$argsArray[] = "array [size {$size}]";
							}
							else
							{
								$argsArray[] = $arg;
							}
						}
					}
					$origin .= implode( ', ', $argsArray );
					unset( $argsArray );
				}
				$origin .= ')';
			}
			if( ! empty( $origin ) )
			{
				$origin = ": {$origin}";
			}

			$file_src = array_key_exists( 'file', $stack[$current] ) ? $stack[$current]['file'] : 'unknown';
			$line_src = array_key_exists( 'line', $stack[$current] ) ? $stack[$current]['line'] : '0';

			$stackItem = $current - $stack_level;
			$this->debug( RDS::DEBUG, "[{$stackItem}] --> {$file_src}({$line_src}){$origin}", $file, $line );
		}
	}
	/**
	 * Create new profiler.
	 *
	 * @param[in] name string Profiler name
	 * @return RDSProfiler profiler instance
	 */
	public function newProfiler( $name )
	{
/*		if( array_key_exists( $name, $this->profilers ) )
		{
			return $this->profilers[$name];
		}*/
		$profiler = new RDS_Profiler( $name, 1 );
		$this->profilers[$name] = $profiler;
		return $profiler;
	}
	/**
	 * Get profilers.
	 *
	 * @return array of RDS_Profiler objects or null.
	 */
	public function getProfilers()
	{
		return $this->profilers;
	}
	/**
	 * Get message buffer.
	 *
	 * @return array 4-uple ( level, message, file_name, file_line ) or null.
	 */
	public function getMessageBuffer()
	{
		return $this->buffer;
	}
	/**
	 * Convert level number to string.
	 *
	 * @param[in] level integer The level number (see RDS class).
	 * @return string The level name.
	 */
	public function levelToString( $level )
	{
		switch( $level )
		{
			case RDS::ERROR:
				return 'ERROR';
			case RDS::WARNING:
				return 'WARNING';
			case RDS::NOTICE;
				return 'NOTICE';
			case RDS::DEBUG;
				return 'DEBUG';
			default:
				return 'UNKNOWN';
		}
	}
	/**
	 * Convert size (in bytes) to human readable size.
	 *
	 * @param[in] size integer The size.
	 * @return string The converted size with unit.
	 */
	public function convertSizeToHuman( $size )
	{
		if( $size < 0 )
		{
			$negative = true;
			$size = $size * -1;
		}
		else
		{
			$negative = false;
		}
		$unit = ' byte(s)';
		$size_tmp = $size / 1024;
		if( $size_tmp > 1 )
		{
			$unit = ' Kbyte(s)';
			$size = $size_tmp;
			$size_tmp = $size / 1024;
			if( $size_tmp > 1 )
			{
				$unit = ' Mbyte(s)';
				$size = $size_tmp;
				$size_tmp = $size / 1024;
				if( $size_tmp > 1 )
				{
					$unit = ' Gbyte(s)';
					$size = $size_tmp;
					$size_tmp = $size / 1024;
					if( $size_tmp > 1 )
					{
						$unit = ' Tbyte(s)';
						$size = $size_tmp;
						$size_tmp = $size / 1024;
					}
				}
			}
		}

		if( $negative )
		{
			$size = $size * -1;
		}

		return number_format( $size, 3, ',', '.' ) . $unit;
	}
}
