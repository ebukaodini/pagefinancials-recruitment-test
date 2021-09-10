<?php

namespace Utils;

use Firebase\JWT\JWT;

class Auth
{
  public static $isAuthenticated = false;
  public static $userId = 0;

  public static function encode(int $userId)
  {
    $issuedAt = time();
    $expirationTime = $issuedAt + 60;  // jwt valid for 60 seconds from the issued time
    $payload = array(
      'userId' => $userId,
      'iat' => $issuedAt,
      'exp' => $expirationTime
    );
    $key = $_ENV['JWT_SECRET'];
    $alg = 'HS256';
    $jwt = JWT::encode($payload, $key, $alg);
    return $jwt;
  }

  public static function authenticateUser()
  {
    try {
      $jwt = $_SERVER['HTTP_AUTHTOKEN'];
      $key = $_ENV['JWT_SECRET'];
      $payload = JWT::decode($jwt, $key, array('HS256'));
      self::$userId = $payload['userId'];
      self::$isAuthenticated = true;

      return true;
    } catch (\Throwable $th) {
      Response::error(message: 'Authentication error. ' . $th->getMessage(), code: 401);
    }
  }

  public static function hashPassword(string $password)
  {
    $options = [
      'cost' => 10
    ];

    $password =  password_hash($password, PASSWORD_BCRYPT, $options);
    return $password;
  }

  public static function verifyPassword(string $password, string $hash)
  {
    return password_verify($password, $hash);
  }
}
