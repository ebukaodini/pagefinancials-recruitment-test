<?php

namespace Utils;

class Request
{
  public static function configureRequest()
  {
    header('Accept: */*');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Content-Type, X-Custom-Header, AuthToken");
    // AuthToken is the jwt token sent after the user login
  }
}
