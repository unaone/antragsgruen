<?php


$params                          = new \app\models\settings\AntragsgruenApp();
$params->dbConnection            = [
    'class'          => 'yii\db\Connection',
    "dsn"            => "mysql:host=localhost;dbname=###DB###",
    "emulatePrepare" => true,
    "username"       => "###USERNAME###",
    "password"       => "###PASSWORD###",
    "charset"        => "utf8mb4",
];
$params->confirmEmailAddresses   = true;
$params->domainPlain             = "http://localhost:8080/index-test.php";
$params->domainSubdomain         = "http://localhost:8080/index-test.php";
$params->prependWWWToSubdomain   = false;
$params->randomSeed              = "123456";
return $params;