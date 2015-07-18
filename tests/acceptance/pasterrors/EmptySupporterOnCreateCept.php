<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());
$I->fillField('#typeMinSupporters', 0);
$motionTypePage->saveForm();
$I->seeOptionIsSelected('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());


$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->gotoMotion();
$I->click('.amendmentCreate a');

$I->seeInField('#initiatorName', 'Testuser');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testuser');



$I->gotoConsultationHome();
$I->click('.createMotion');

$I->seeInField('#initiatorName', 'Testuser');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testuser');
