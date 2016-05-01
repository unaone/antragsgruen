<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $adminEdit
 */

/** @var Motion[] $replacedByMotions */
$replacedByMotions = [];
foreach ($motion->replacedByMotions as $replMotion) {
    if (!in_array($replMotion->status, $motion->getConsultation()->getInvisibleMotionStati())) {
        $replacedByMotions[] = $replMotion;
    }
}


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

$policy = $motion->motionType->getAmendmentPolicy();
if ($policy->checkCurrUserAmendment(true, true)) {
    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
    $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
    $title .= \Yii::t('motion', 'amendment_create');
    $html .= Html::a($title, $amendCreateUrl, ['rel' => 'nofollow']) . '</li>';
    $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($amendCreateUrl) . '" ' .
        'rel="nofollow">' . $title . '</a>';
    $sidebarRows++;
} elseif ($policy->getPolicyID() != IPolicy::POLICY_NOBODY) {
    $msg = $policy->getPermissionDeniedAmendmentMsg();
    if ($msg != '') {
        $createLi = '<li class="amendmentCreate">';
        $createLi .= '<span style="font-style: italic;"><span class="icon glyphicon glyphicon-flash"></span>';
        $createLi .= Html::encode(Yii::t('motion', 'amendment_create'));
        $createLi .= '<br><span style="font-size: 13px; color: #dbdbdb; text-transform: none;">';
        $createLi .= Html::encode($msg) . '</span></span></li>';

        $html .= $createLi;
        $layout->menusHtmlSmall[] = $createLi;

        $sidebarRows++;
    }
}

if ($motion->motionType->getPDFLayoutClass() !== null && $motion->isVisible()) {
    $pdfLi = '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        \Yii::t('motion', 'pdf_version');
    $pdfLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'pdf')) . '</li>';
    $html .= $pdfLi;
    $layout->menusHtmlSmall[] = $pdfLi;
    $sidebarRows++;
}

if ($motion->canMergeAmendments()) {
    $mergeLi = '<li class="mergeamendments">';
    $title   = (count($motion->getVisibleAmendments(false)) > 0 ? 'amendments_merge' : 'amendments_merge_noamend');
    $title   = '<span class="icon glyphicon glyphicon-scissors"></span>' .
        Yii::t('motion', $title);
    $mergeLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'mergeamendments')) . '</li>';
    $html .= $mergeLi;
    $layout->menusHtmlSmall[] = $mergeLi;
    $sidebarRows++;
}

if ($motion->canEdit()) {
    $editLi = '<li class="edit">';
    $title  = '<span class="icon glyphicon glyphicon-edit"></span>' .
        str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('motion', 'motion_edit'));
    $editLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'edit')) . '</li>';
    $html .= $editLi;
    $layout->menusHtmlSmall[] = $editLi;
    $sidebarRows++;
}

if ($motion->canWithdraw()) {
    $withdrawLi = '<li class="withdraw">';
    $title      = '<span class="icon glyphicon glyphicon-remove"></span>' .
        str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('motion', 'motion_withdraw'));
    $withdrawLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'withdraw')) . '</li>';
    $html .= $withdrawLi;
    $layout->menusHtmlSmall[] = $withdrawLi;
    $sidebarRows++;
}

if ($adminEdit) {
    $adminLi = '<li class="adminEdit">';
    $title   = '<span class="icon glyphicon glyphicon-wrench"></span>' . \Yii::t('motion', 'motion_admin_edit');
    $adminLi .= Html::a($title, $adminEdit) . '</li>';
    $html .= $adminLi;
    $layout->menusHtmlSmall[] = $adminLi;
    $sidebarRows++;
}

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . \Yii::t('motion', 'back_start');
$html .= Html::a($title, UrlHelper::createUrl('consultation/index')) . '</li>';
$sidebarRows++;


$html .= '</ul>';

if ($motion->isSocialSharable() && count($replacedByMotions) == 0) {
    $layout->loadShariff();
    $shariffBackend = UrlHelper::createUrl('consultation/shariffbackend');
    $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
    $lang           = Yii::$app->language;
    $dataTitle      = $motion->getTitleWithPrefix();
    $html .= '</div><div class="hidden-xs"><div class="shariff" data-backend-url="' . Html::encode($shariffBackend) . '"
           data-theme="white" data-url="' . Html::encode($myUrl) . '"
           data-services="[&quot;twitter&quot;, &quot;facebook&quot;]"
           data-lang="' . Html::encode($lang) . '" data-title="' . Html::encode($dataTitle) . '"></div>';
    $sidebarRows++;
}

$layout->menusHtml[] = $html;



return $sidebarRows;