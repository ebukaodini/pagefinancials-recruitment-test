<?php

namespace Utils;

class Request
{
  public static function configureRequest() {
    header('accept: applcation/json');
  }
}
