<?php
/**
 * Created by IntelliJ IDEA.
 * User: nnghi
 * Date: 10/06/2018
 * Time: 4:17 AM
 */

$length=8;

$text= $key;
$captcha = @imagecreatefrompng("images/cat".rand(1,12).".PNG");
$font=30;
for($i=0;$i<20;$i++)
    imageline ( $captcha , rand(0,100) , rand(0,100) , rand(0,100),rand(0,100),rand(0,100000));
$image_width=imagesx($captcha);
$image_height=imagesy($captcha);
$text_width=imagefontwidth($font)*$length;
$text_height=imagefontheight($font);
imagestring($captcha, $font, ($image_width-$text_width)/2 , ($image_height-$text_height)/2, $text, 0);
header("Content-type: image/png");
imagepng($captcha);
imagedestroy($captcha);