<?php
/**
 * Created by IntelliJ IDEA.
 * User: nnghi
 * Date: 01/11/2018
 * Time: 5:24 PM
 */
?>

<html ng-app="cms3c">
<head>

    <title>{{$sitename}}</title>
    <link rel="stylesheet" href="<?=asset('css/bootstrap.min.css')?>"/>
    <script src="<?= asset('js/angular.min.js') ?>"></script>
    <script src="<?= asset('js/angular-route.min.js') ?>"></script>
    <script src="<?= asset('js/cms3c.js') ?>"></script>
    <script src="<?= asset('js/jquery-3.2.1.min.js') ?>"></script>
    <script src="<?= asset('js/bootstrap.min.js') ?>"></script>
    <script src="<?= asset('js/axios.min.js') ?>"></script>
</head>
<body>


    <div ng-view></div>

</body>
</html>
