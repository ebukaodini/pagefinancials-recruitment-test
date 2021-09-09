<?php

namespace Utils;

class Response
{
  public static function success(string $message = 'success', array $data = [], int $code = 200,)
  {
    http_response_code($code);
    exit(json_encode([
      'code' => $code,
      'message' => $message,
      'data' => $data
    ]));
  }
  public static function error(string $message = 'error', array $data = [], int $code = 400)
  {
    http_response_code($code);
    exit(json_encode([
      'code' => $code,
      'message' => $message,
      'data' => $data
    ]));
  }
}
