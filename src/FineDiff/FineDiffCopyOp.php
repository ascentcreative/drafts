<?php
namespace AscentCreative\Approval\FineDiff;

/** Port of FineDiff by Raymond Hill 
 * 
 * Copyright (c) 2011 Raymond Hill (http://raymondhill.net/blog/?p=441)
*/

class FineDiffCopyOp extends FineDiffOp {
	public function __construct($len) {
		$this->len = $len;
		}
	public function getFromLen() {
		return $this->len;
		}
	public function getToLen() {
		return $this->len;
		}
	public function getOpcode() {
		if ( $this->len === 1 ) {
			return 'c';
			}
		return "c{$this->len}";
		}
	public function increase($size) {
		return $this->len += $size;
		}
}