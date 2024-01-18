<?php
namespace AscentCreative\Approval\FineDiff;

/** Port of FineDiff by Raymond Hill 
 * 
 * Copyright (c) 2011 Raymond Hill (http://raymondhill.net/blog/?p=441)
*/

class FineDiffDeleteOp extends FineDiffOp {
	public function __construct($len) {
		$this->fromLen = $len;
		}
	public function getFromLen() {
		return $this->fromLen;
		}
	public function getToLen() {
		return 0;
		}
	public function getOpcode() {
		if ( $this->fromLen === 1 ) {
			return 'd';
			}
		return "d{$this->fromLen}";
		}
	}