<?php
namespace AscentCreative\Approval\FineDiff;

/** Port of FineDiff by Raymond Hill 
 * 
 * Copyright (c) 2011 Raymond Hill (http://raymondhill.net/blog/?p=441)
*/

abstract class FineDiffOp {
	abstract public function getFromLen();
	abstract public function getToLen();
	abstract public function getOpcode();
}