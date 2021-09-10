<?php

namespace Utils;

use Exception;

class Paystack
{

  public static function initiatePayment(string $email, string $walletId, int $amountInKobo, string $reference, string $callbackUrl)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'amount' => $amountInKobo,
        'email' => $email,
        'reference' => $reference,
        'callback_url' => $callbackUrl,
        'metadata' => [
          'email' => $email,
          'wallet_id' => $walletId
        ]
      ]),
      CURLOPT_HTTPHEADER => [
        "authorization: Bearer " . $_ENV['PAYSTACK_SECRET_KEY'],
        "content-type: application/json",
        "cache-control: no-cache"
      ],
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    if ($err) {
      // there was an error contacting the Paystack API
      throw new Exception($err);
    }

    $tranx = json_decode($response, true);

    if (!$tranx['status']) {
      // there was an error from the API
      throw new Exception($tranx['message']);
    }
    // redirect to page so User can pay
    return $tranx['data']['authorization_url'];
  }

  public static function verifyPayment(string $reference)
  {
    $result = array();
    //The parameter after verify/ is the transaction reference to be verified
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      [
        'Authorization: Bearer ' . $_ENV['PAYSTACK_SECRET_KEY']
      ]
    );
    $request = curl_exec($ch);
    curl_close($ch);

    if ($request) {
      $result = json_decode($request, true);
      // print_r($result);
      if ($result) {
        if ($result['data']) {
          //something came in
          if ($result['data']['status'] == 'success') {
            // the transaction was successful, you can deliver value
            /* 
						@ also remember that if this was a card transaction, you can store the 
						@ card authorization to enable you charge the customer subsequently. 
						@ The card authorization is in: 
						@ $result['data']['authorization']['authorization_code'];
						@ PS: Store the authorization with this email address used for this transaction. 
						@ The authorization will only work with this particular email.
						@ If the user changes his email on your system, it will be unusable
						*/
            return $result['data'];
          } else {
            // the transaction was not successful, do not deliver value'
            throw new Exception("Transaction was not successful: Last gateway response was: " . $result['data']['gateway_response']);
          }
        } else {
          throw new Exception($result['message']);
        }
      } else {
        //print_r($result);
        throw new Exception("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
      }
    } else {
      //var_dump($request);
      throw new Exception("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
    }
  }
}
