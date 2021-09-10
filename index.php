<?php

namespace App;

use Api\Users;
use Api\Wallet;
use Symfony\Component\Dotenv\Dotenv;
use Utils\Auth;
use Utils\Request;
use Utils\Response;

// autoload
include_once 'vendor/autoload.php';

try {

  // load environment data if on local device
  if ($_SERVER['HTTP_HOST'] == 'localhost:8080') {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
  }

  exit(json_encode($_SERVER));

  // set timezone
  date_default_timezone_set($_ENV['TIMEZONE']);

  // configure request headers
  Request::configureRequest();

  // get request uri
  $uri = $_SERVER['REQUEST_URI'];

  // remove query string from uri
  if (isset($_SERVER['QUERY_STRING']))
    $uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri);

  // define application routes
  switch ($uri) {

    case '/':
      Request::get();
      Response::success(message: 'Welcome to the Forsterfields & Griffins Technology Wallet API.');
      break;

    case '/api/users/create':
      Request::post();
      Users::createAccount();
      break;

    case '/api/users/login':
      Request::post();
      Users::login();
      break;

    case '/api/wallet/fund':
      Request::post();
      Auth::authenticateUser();
      Wallet::fundWallet();
      break;

    case '/api/wallet/fund/verify':
      Request::get();
      Wallet::completeFundWallet();
      break;

    case '/api/wallet/balance-enquiry':
      Request::get();
      Auth::authenticateUser();
      Wallet::balanceEnquiry();
      break;

    case '/api/wallet/transaction-history':
      Request::get();
      Auth::authenticateUser();
      Wallet::transactionHistory();
      break;

    default:
      Response::error(code: 404, message: 'Page not found');
      break;
  }
} catch (\Throwable $th) {

  // application wide error handler
  error_log($th->getMessage() . "\r\n", 0, 'error.log');
  error_log(json_encode(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)) . "\r\n", 3, 'error.log');
  Response::error(code: 500, message: 'Server Error');
}
