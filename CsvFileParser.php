<?php

class CsvFileParser implements Iterator {
	
	const INDEX_UNINITIALIZED = -1;
	
	/**
	 * @var CsvFileParserParams
	 */
	private $parse_params;
	private $filename;
	private $file_handle;
	private $current_row;
	private $index_of_current_row;
	private $end_of_file_reached;

	public function __construct($filename, CsvFileParserParams $parse_params) {
		$this->filename = $filename;
		$this->parse_params = $parse_params;
	}
	
	public function __destruct() {
		if ($this->isOpen()) {
			$this->close();
		}
	}
	
	public function current() {
		return $this->current_row;
	}

	public function key() {
		return $this->index_of_current_row;
	}

	public function next() {
		$this->current_row = $this->getNextParsedRow();
	}
	
	private function getNextParsedRow() {
		$parsed_row = fgetcsv(
			$this->file_handle,
			$this->parse_params->getMaxLineLength(),
			$this->parse_params->delimiter,
			$this->parse_params->enclosure,
			$this->parse_params->escape
		);
		
		if ($parsed_row === false) {
			$this->end_of_file_reached = true;
		} else {
			$this->index_of_current_row++;
		}
		
		return $parsed_row === false ? array() : $parsed_row;
	}

	public function rewind() {
		$this->end_of_file_reached = false;
		if ($this->isOpen()) {
			$this->close();
		}
		$this->open($this->filename);
		$this->current_row = $this->getNextParsedRow();
	}

	public function valid() {
		return !$this->end_of_file_reached;
	}

	private function open($filename) {
		try {
			$this->tryToOpen($filename);
		} catch (Exception $e) {
			throw $e;
		}
		$this->index_of_current_row = self::INDEX_UNINITIALIZED;
	}
	
	private function close() {
		fclose($this->file_handle);
	}
	
	private function tryToOpen($filename) {
		if (is_file($filename)) {
			$this->file_handle = fopen($filename, 'r');
			if ($this->file_handle === false) {
				throw new Exception($filename . ' cannot be opened');
			}
		} else {
			throw new Exception($filename . ' is not a file');
		}
	}
	
	private function isOpen() {
		return is_resource($this->file_handle);
	}
}
