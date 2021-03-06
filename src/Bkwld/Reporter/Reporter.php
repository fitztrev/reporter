<?php namespace Bkwld\Reporter;

// Dependencies
use Monolog\Logger;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\WebProcessor;
use DB;
use Log;
use URI;
use Request;
use Input;
use Timer;

// Assemble stats and write them to the file
class Reporter {
	
	/**
	 * Private vars
	 */
	private $logger;
	private $buffered = array();
	
	/**
	 * Init
	 */
	public function __construct() {
		
		// Create a new log file for reporter
		$this->logger = new Logger('reporter');
		$stream = new StreamHandler(storage_path().'/logs/reporter.log', Logger::DEBUG);
		$this->logger->pushHandler($stream);
		
		// Apply the Reporter formatter
		$formatter = new Formatter();
		$stream->setFormatter($formatter);
		
		// Add custom and built in processors
		$this->logger->pushProcessor(Timer::getFacadeRoot());
		$this->logger->pushProcessor(new MemoryUsageProcessor());
		$this->logger->pushProcessor(new MemoryPeakUsageProcessor());
		$this->logger->pushProcessor(new WebProcessor());
		
	}
	
	/**
	 * Buffer other Laravel log messages
	 */
	public function buffer($level, $message, $context = array()) {
		$this->buffered[] = (object) array(
			'level' => $level,
			'message' => $message,
			'context' => $context,
		);
	}
	
	/**
	 * Write a new report
	 */
	public function write($params = array()) {
		$defaults = array();

		// Test for DB, in case it's not able to connect yet
		try {
			$defaults['database'] = Db::connection()->getQueryLog();
		} catch (\Exception $e) {
			
			// Continue running even if DB could not be logged, but display
			// a note in the log
			$this->buffer('error', 'Reporter could not connect to the database');
		}

		// Default params
		$defaults['input'] = Input::get();
		$defaults['logs'] = $this->buffered;

		// Apply default params
		$params = array_merge($defaults, $params);

		// Do a debug log, passing it all the extra data that it needs.  This will ultimately
		// write to the log file
		$this->logger->addDebug('Reporter', $params);

	}	
}