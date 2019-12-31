<?php

class BootstrapUtil
{
    public static function contentWeb($content) {
        $txt=<<<cin
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Example</title>
  </head>
  <body>
    $content
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>
cin;
        return $txt;
    }
    public static function navigation($base,$id) {
        $txt=<<<cin
<div class="card text-center">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs">
      <li class="nav-item">
        <a class="nav-link" href="{$base}">Home</a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-toggle="dropdown"
         href="{$base}/Customer" role="button" aria-haspopup="true" aria-expanded="false">Customer</a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="{$base}/Customer/Index">Index</a>
          <a class="dropdown-item" href="{$base}/Customer/New">New</a>
          <a class="dropdown-item" href="{$base}/Customer/Update">Update</a>
          <a class="dropdown-item" href="{$base}/Customer/Update/20">Update #20</a>
        </div>
      </li>
    </ul>
  </div>
  <div class="card-body">
    <h5 class="card-title">Name {$id}</h5>
    <p class="card-text">It is an example</p>
    <a href="#" class="btn btn-primary">Okay</a>
  </div>
</div>
cin;
        return $txt;
    }
}