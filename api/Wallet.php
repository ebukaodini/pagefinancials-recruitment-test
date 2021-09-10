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
        Paystack::initiatePayment($email, $amountInKobo, $reference, 'http://localhost:8080/api/wallet/fund/verify');
      } catch (Exception $e) {
        Response::error(message: "Payment initiation failed. {$e->getMessage()}. Please try again.");
      }
    } else {
      Response::error(message: 'Payment initiation failed. Please try again.');
    }
  }

  public static function completeFundWallet()
  {
    // exit('/api/wallet/fund/complete');

    // get data from paystack
    // update payment table
    // if payment successful
    // create a wallet transaction with the paystack data
    // read the wallet balance
    // return success response with wallet balance
    // else, return error response with reason from paystack
  }

  public static function balanceEnquiry()
  {
    exit('/api/wallet/balance-enquiry');
  }

  public static function transactionHistory()
  {
    exit('/api/wallet/transaction-history');
  }
}
