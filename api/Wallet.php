<?php

namespace Api;

use Exception;
use PDO;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Validation;
use Utils\Auth;
use Utils\Database;
use Utils\Paystack;
use Utils\Response;

class Wallet
{
  public static function fundWallet()
  {
    // get user data from post
    $amount = $_POST['amount'] ?? '';

    // validate data
    $validator = Validation::createValidator();
    $errors[] = $validator->validate($amount, [
      new Positive([
        'message' => 'Amount must be positive digit'
      ]),
      new NotBlank([
        'message' => 'Amount should not be blank'
      ]),
    ]);

    $errorData = [];
    foreach ($errors as $error) {
      foreach ($error as $violation) {
        $errorData[] = $violation->getMessage();
      }
    }
    if (count($errorData) > 0)
      Response::error(message: 'Validation error', data: $errorData);

    // get user id from auth
    $userId = Auth::$userId;
    // get user email
    $conn = Database::connect();

    $query = "SELECT email, wallet_id FROM users WHERE id = $userId";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();

    $email = $response[0]['email'];
    $walletId = $response[0]['wallet_id'];

    $reference = $walletId . '-' . time();
    $amountInKobo = $amount * 100; // convert amount to kobo

    // store transaction reference so we can update or query in case user never comes back
    // perhaps due to network issue
    $query = "INSERT INTO payments (reference) VALUES (:reference)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':reference', $reference);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      try {
        // initiate paystack payment
        // and auto redirect to page so User can pay
        $host = $_SERVER['HTTP_HOST'];
        $authorization_url = Paystack::initiatePayment($email, $walletId, $amountInKobo, $reference, 'http://' . $host . '/api/wallet/fund/verify');
        Response::success('Payment initiated successfully. Copy this link to your browser to comlete payment: ' . $authorization_url, data: [
          'authorization_url' => $authorization_url
        ]);
      } catch (Exception $e) {
        Response::error(message: "Payment initiation failed. {$e->getMessage()}. Please try again.");
      }
    } else {
      Response::error(message: 'Payment initiation failed. Please try again.');
    }
  }

  public static function completeFundWallet()
  {
    // get data from paystack
    $reference = $_GET['reference'];
    $data = Paystack::verifyPayment($reference);

    // update payment table
    $conn = Database::connect();
    $query = "UPDATE payments SET paystack_ref_id=:paystack_ref_id, domain=:domain, amount=:amount, currency=:currency, payment_channel=:payment_channel, payment_status=:payment_status, ip_address=:ip_address WHERE reference = :reference";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':paystack_ref_id', $data['id']);
    $stmt->bindParam(':domain', $data['domain']);
    $stmt->bindParam(':amount', $data['amount']);
    $stmt->bindParam(':currency', $data['currency']);
    $stmt->bindParam(':payment_channel', $data['channel']);
    $stmt->bindParam(':payment_status', $data['status']);
    $stmt->bindParam(':ip_address', $data['ip_address']);
    $stmt->bindParam(':reference', $reference);
    $stmt->execute();

    // if payment successful
    if ($data['status'] == 'success') {
      // create a wallet transaction with the paystack data
      $query = "INSERT INTO wallet_transactions (wallet_id, email, credit, debit, reference) VALUES (:wallet_id, :email, :credit, :debit, :reference)";
      $stmt = $conn->prepare($query);
      $email = $data['metadata']['email'];
      $walletId = $data['metadata']['wallet_id'];
      $amountInNaira = intval($data['amount']) / 100; // convert back to naira
      $debit = 0;
      $stmt->bindParam(':wallet_id', $walletId);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':credit', $amountInNaira);
      $stmt->bindParam(':debit', $debit);
      $stmt->bindParam(':reference', $reference);
      $stmt->execute();

      // read the wallet balance
      $query = "SELECT SUM(credit) as credit, SUM(debit) as debit FROM wallet_transactions WHERE wallet_id='$walletId'";
      $stmt = $conn->prepare($query);
      $stmt->execute();

      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $response = $stmt->fetchAll();

      $balance = floatval($response[0]['credit']) - floatval($response[0]['debit']);

      // return success response with wallet balance
      Response::success(message: $data['gateway_response'] . '. Wallet funded successfully.', data: [
        'current_balance' => $balance
      ]);
    } else {
      // else, return error response with reason from paystack
      Response::error(message: $data['gateway_response']);
    }
  }

  public static function balanceEnquiry()
  {
    // get user id
    $userId = Auth::$userId;

    // get user's wallet id
    $conn = Database::connect();

    $query = "SELECT wallet_id FROM users WHERE id=$userId";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();

    $walletId = $response[0]['wallet_id'];

    // read the wallet balance
    $query = "SELECT SUM(credit) as credit, SUM(debit) as debit FROM wallet_transactions WHERE wallet_id='$walletId'";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();

    $balance = floatval($response[0]['credit']) - floatval($response[0]['debit']);

    // return success response with wallet balance
    Response::success(message: 'Wallet balance.', data: [
      'current_balance' => $balance
    ]);
  }

  public static function transactionHistory()
  {
    // get pagination data if sent
    $page = intval($_GET['page']) ?? 1;
    $limit = intval($_GET['limit']) ?? 20;

    // validate data
    $validator = Validation::createValidator();
    $errors[] = $validator->validate($page, [
      new Positive([
        'message' => 'Page must be positive digit'
      ]),
      new NotBlank([
        'message' => 'Page should not be blank'
      ]),
    ]);
    $errors[] = $validator->validate($limit, [
      new Positive([
        'message' => 'Limit must be positive digit'
      ]),
      new NotBlank([
        'message' => 'Limit should not be blank'
      ]),
    ]);

    $errorData = [];
    foreach ($errors as $error) {
      foreach ($error as $violation) {
        $errorData[] = $violation->getMessage();
      }
    }
    if (count($errorData) > 0)
      Response::error(message: 'Validation error', data: $errorData);

    // get user id
    $userId = Auth::$userId;

    // get user's wallet id
    $conn = Database::connect();

    $query = "SELECT wallet_id FROM users WHERE id=$userId";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();

    $walletId = $response[0]['wallet_id'];

    // read the wallet transaction history
    $offset = ($page * $limit) - $limit;
    $query = "SELECT credit, debit, reference, date_created FROM wallet_transactions WHERE wallet_id='$walletId' LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();

    $data = [];
    foreach ($response as $transaction) {
      if (floatval($transaction['credit']) > 0) {
        $data[] = [
          'description' => 'Your wallet was credited ' . $transaction['credit'],
          'amount' => $transaction['credit'],
          'type' => 'credit',
          'timestamp' => $transaction['date_created']
        ];
      } else if (floatval($transaction['debit']) > 0) {
        $data[] = [
          'description' => 'Your wallet was debited ' . $transaction['debit'],
          'amount' => $transaction['debit'],
          'type' => 'debit',
          'timestamp' => $transaction['date_created']
        ];
      }
    }

    // return success response with wallet balance
    Response::success(message: 'Wallet transaction history.', data: $data);
  }
}
