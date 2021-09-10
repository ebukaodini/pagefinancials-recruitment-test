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

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      http_response_code(200);
      exit();
    }
  }

  public static function get()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'GET')
      Response::error(code: 405, message: 'This resource does not support this request method. check the documentation');
  }

  public static function post()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    Response::error(code: 405, message: 'This resource does not support this request method. check the documentation');
  }
}
