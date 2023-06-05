<?php
/**
 * Created by IntelliJ IDEA.
 * User: nnghi
 * Date: 03/14/2018
 * Time: 10:13 AM
 */


Validator::extend('alpha_spaces', function($attribute, $value)
{
    return preg_match('/^[0-9\,\-\+\.\pL\s]+$/u', $value);
});

Validator::extend('number_dash', function($attribute, $value)
{
    return preg_match('/^[0-9\,]+$/u', $value);
});


Validator::extend('sql_char', function($attribute, $value)
{
    return preg_match('/^[0-9a-zA-Z\,\+\-\_\s]+$/u', $value);
});


Validator::extend('ipV4Port', function($attribute, $value)
{
    return preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]):[0-9]+$/u', $value);
});



Validator::extend('unicode_valid', function($attribute, $value)
{
  return preg_match('/^[0-9a-zA-Z\,\+\-\_\.\/\@\.\pL\s]+$/u', $value);
});



Validator::extend('phone_valid', function($attribute, $value)
{
  return preg_match('/^[0-1][0-9]{7,11}$/m', $value);
});

Validator::extend('prefix_valid', function($attribute, $value)
{
  return preg_match('/^[0-9\+\,]+$/m', $value);
});
