<?php

namespace Utils;

use Exception;
use PDO;
use PDOException;

class Database
{
  public static function connect()
  {
    try {
      // get db environment data
      $url = $_ENV['JAWSDB_MARIA_URL'];
      $dbparts = parse_url($url);

      $hostname = $dbparts['host'];
      $username = $dbparts['user'];
      $password = $dbparts['pass'];
      $database = ltrim($dbparts['path'], '/');

      $conn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
      // set the PDO error mode to exception
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $conn;
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    } catch (Exception $e) {
      echo "Connection failed: " . $e->getMessage();
    }
  }
}
