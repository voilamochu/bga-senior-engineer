<?php
namespace AGR\Helpers;

class UserException extends \BgaUserException
{
  public function __construct($str)
  {
    parent::__construct($str);
  }
}
?>
