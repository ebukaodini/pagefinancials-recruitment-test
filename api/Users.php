<?php

namespace Api;

use PDO;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use Utils\Auth;
use Utils\Database;
use Utils\Response;

class Users
{
  public static function createAccount()
  {
    // get the user data
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // validate the data
    $validator = Validation::createValidator();
    // $violations = 
    $errors[] = $validator->validate($firstname, [
      new Length([
        'min' => 2,
        'minMessage' => 'Your first name must be at least {{ limit }} characters long',
        'max' => 100,
        'maxMessage' => 'Your first name cannot be longer than {{ limit }} characters'
      ]),
      new NotBlank([
        'message' => 'Your first name should not be blank'
      ]),
    ]);
    $errors[] = $validator->validate($lastname, [
      new Length([
        'min' => 2,
        'minMessage' => 'Your last name must be at least {{ limit }} characters long',
        'max' => 100,
        'maxMessage' => 'Your last name cannot be longer than {{ limit }} characters'
      ]),
      new NotBlank([
        'message' => 'Your last name should not be blank'
      ]),
    ]);
    $errors[] = $validator->validate($email, [
      new Email([
        'message' => 'Your email {{ value }} is not a valid email',
      ]),
      new NotBlank([
        'message' => 'Your email should not be blank'
      ]),
    ]);
    $errors[] = $validator->validate($password, [
      new Length([
        'min' => 6,
        'minMessage' => 'Your password must be at least {{ limit }} characters long'
      ]),
      new Regex([
        'pattern' => '/\d/',
        'match' => true,
        'message' => 'Your password must contain a number',
      ]),
      new Regex([
        'pattern' => '/^[a-z]+$/',
        'match' => false,
        'message' => 'Your password must contain a lower case character',
      ]),
      new Regex([
        'pattern' => '/^[A-Z]+$/',
        'match' => false,
        'message' => 'Your password must contain an upper case character',
      ]),
      new Regex([
        'pattern' => '/^[!@#$%^&*()]+$/',
        'match' => false,
        'message' => 'Your password must contain a special character',
      ]),
      new NotBlank([
        'message' => 'Your password should not be blank'
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

    // store the data in the database
    $conn = Database::connect();

    // ensure unique emails within the application
    // check if user exist already
    $query = "SELECT email FROM users WHERE email='$email'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $response = $stmt->fetchAll();
    if ($response[0]['email'] > 0){
      Response::error(message: "Email \"{$email}\" already exist in database");
    }

    // hash password
    $hash = Auth::hashPassword($password);

    // create a wallet Id for the user
    $walletId = time();

    // store the user data in the users table
    $query = "INSERT INTO users (firstname, lastname, email, password, wallet_id) VALUES (:firstname, :lastname, :email, :password, :wallet_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hash);
    $stmt->bindParam(':wallet_id', $walletId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      // account created
      // return response
      Response::success(message: 'Account created successfully. Wallet created.');
    } else {
      Response::error(message: 'Account not created, please try again.', code: 200);
    }
  }

  public static function login()
  {
    
    // get the user data
    // sanitize data
    // get the user data from the database
    // compare db password hash with the submitted password
    // if true,
      // return success response with jwt token
    // else return response error
  }
}
