<?php
namespace AscentCreative\Approval\FineDiff;

/** Port of FineDiff by Raymond Hill 
 * 
 * Copyright (c) 2011 Raymond Hill (http://raymondhill.net/blog/?p=441)
*/

class FineDiffInsertOp extends FineDiffOp {
	public function __construct($text) {
		$this->text = $text;
		}
	public function getFromLen() {
		return 0;
		}
	public function getToLen() {
		return strlen($this->text);
		}
	public function getText() {
		return $this->text;
		}
	public function getOpcode() {
		$to_len = strlen($this->text);
		if ( $to_len === 1 ) {
			return "i:{$this->text}";
			}
		return "i{$to_len}:{$this->text}";
		}
	}