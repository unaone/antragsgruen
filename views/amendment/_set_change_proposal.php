<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 * @var string $context
 */

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

$collidingAmendments = $amendment->collidesWithOtherProposedAmendments(true);

$saveUrl = \app\components\UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'data-antragsgruen-widget' => 'backend/AmendmentChangeProposal',
    'data-context'             => $context,
    'class'                    => 'fuelux',
]);
if ($amendment->proposalStatus == Amendment::STATUS_REFERRED) {
    $preReferredTo = $amendment->proposalComment;
} else {
    $preReferredTo = '';
}
if ($amendment->proposalStatus == Amendment::STATUS_OBSOLETED_BY) {
    $preObsoletedBy = $amendment->proposalComment;
} else {
    $preObsoletedBy = '';
}

$votingBlocks = $amendment->getMyConsultation()->votingBlocks;
?>
    <h2><?= \Yii::t('amend', 'proposal_amend_title') ?></h2>
    <div class="holder">
        <section class="statusForm">
            <h3><?= \Yii::t('amend', 'proposal_status_title') ?></h3>

            <?php
            $foundStatus = false;
            foreach (\app\models\db\Amendment::getProposedChangeStati() as $statusId) {
                ?>
                <label>
                    <input type="radio" name="proposalStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->proposalStatus == $statusId) {
                        $foundStatus = true;
                        echo 'checked';
                    }
                    ?>> <?= \app\models\db\IMotion::getStati()[$statusId] ?>
                </label><br>
                <?php
            }
            ?>
            <label>
                <?= Html::radio('proposalStatus', !$foundStatus, ['value' => '0']) ?>
                - <?= \Yii::t('amend', 'proposal_status_na') ?> -
            </label>
        </section>
        <div class="middleCol">
            <div class="visibilitySettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_visibility') ?></h3>
                <label>
                    <?= Html::checkbox('proposalVisible', ($amendment->proposalVisibleFrom !== null)) ?>
                    <?= \Yii::t('amend', 'proposal_visible') ?>
                </label>
            </div>
            <div class="notificationSettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_noti') ?></h3>
                <?php
                if ($amendment->proposalUserStatus !== null) {
                    if ($amendment->proposalUserStatus == Amendment::STATUS_ACCEPTED) {
                        echo \Yii::t('amend', 'proposal_user_accepted');
                    } elseif ($amendment->proposalUserStatus == Amendment::STATUS_REJECTED) {
                        echo \Yii::t('amend', 'proposal_user_rejected');
                    } else {
                        echo 'Error: unknown response of the proposer';
                    }
                } elseif ($amendment->proposalNotification !== null) {
                    $msg = \Yii::t('amend', 'proposal_notified');
                    echo str_replace('%DATE%', Tools::formatMysqlDate($amendment->proposalNotification), $msg);
                    if ($amendment->proposalStatusNeedsUserFeedback()) {
                        echo ' ' . \Yii::t('amend', 'proposal_no_feedback');
                    }
                } elseif ($amendment->proposalStatus !== null) {
                    if ($amendment->proposalStatusNeedsUserFeedback()) {
                        $msg = \Yii::t('amend', 'proposal_notify_w_feedback');
                    } else {
                        $msg = \Yii::t('amend', 'proposal_notify_o_feedback');
                    }
                    ?>
                    <label>
                        <input type="checkbox" name="notifyProposer"> <?= $msg ?>
                    </label>
                    <?php
                }
                ?>
            </div>
            <div class="votingBlockSettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_voteblock') ?></h3>
                <?php
                $options = ['-'];
                foreach ($votingBlocks as $votingBlock) {
                    $options[$votingBlock->id] = $votingBlock->title;
                }
                $options['NEW'] = '- ' . \Yii::t('amend', 'proposal_voteblock_newopt') . ' -';
                $attrs          = ['id' => 'votingBlockId', 'class' => 'form-control'];
                echo HTMLTools::fueluxSelectbox('votingBlockId', $options, $amendment->votingBlockId, $attrs);
                ?>
                <div class="newBlock">
                    <label for="newBlockTitle" class="control-label">
                        <?= \Yii::t('amend', 'proposal_voteblock_new') ?>:
                    </label>
                    <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
                </div>
            </div>
        </div>
        <section class="proposalCommentForm">
            <h3><?= \Yii::t('amend', 'proposal_comment_title') ?></h3>
            <ol class="commentList">
                <?php
                foreach ($amendment->adminComments as $adminComment) {
                    $user = $adminComment->user;
                    ?>
                    <li>
                        <div class="header">
                            <div class="date"><?= Tools::formatMysqlDateTime($adminComment->dateCreation) ?></div>
                            <div class="name"><?= Html::encode($user ? $user->name : '-') ?></div>
                        </div>
                        <div class="comment"><?= Html::encode($adminComment->text) ?></div>
                    </li>
                    <?php
                }
                ?>
            </ol>

            <textarea name="text" placeholder="<?= Html::encode(\Yii::t('amend', 'proposal_comment_placeh')) ?>"
                      class="form-control" rows="1"></textarea>
            <button class="btn btn-default btn-xs"><?= \Yii::t('amend', 'proposal_comment_write') ?></button>
        </section>
    </div>
<?php
if ($context !== 'edit') {
    ?>
    <section class="statusDetails status_<?= Amendment::STATUS_MODIFIED_ACCEPTED ?>">
        <h3><?= \Yii::t('amend', 'proposal_modified_accepted') ?></h3>
        <?= Html::a('Bearbeiten', UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change')) ?>
    </section>
    <?php
}
?>
    <section class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY ?>">
        <label class="headingLabel"><?= \Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
        <?php
        $options = ['-'];
        foreach ($amendment->getMyMotion()->getVisibleAmendmentsSorted() as $otherAmend) {
            if ($otherAmend->id != $amendment->id) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        $attrs = ['id' => 'obsoletedByAmendment', 'class' => 'form-control'];
        echo HTMLTools::fueluxSelectbox('obsoletedByAmendment', $options, $preObsoletedBy, $attrs);
        ?>
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_REFERRED ?>">
        <label class="headingLabel" for="referredTo"><?= \Yii::t('amend', 'proposal_refer_to') ?>...</label>
        <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_VOTE ?>">
        <div class="votingStatus">
            <h3><?= \Yii::t('amend', 'proposal_voting_status') ?></h3>
            <?php
            foreach (Amendment::getVotingStati() as $statusId => $statusName) {
                ?>
                <label>
                    <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->votingStatus == $statusId) {
                        echo 'checked';
                    }
                    ?>> <?= Html::encode($statusName) ?>
                </label><br>
                <?php
            }
            ?>
        </div>
    </section>
    <section class="collissions <?= (count($collidingAmendments) === 0 ? 'hidden' : '') ?>">
        <h3><?= \Yii::t('amend', 'proposal_conflict_title') ?>:</h3>
        <ul>
            <?php
            foreach ($collidingAmendments as $collidingAmendment) {
                $title = $collidingAmendment->getShortTitle();
                $url   = UrlHelper::createAmendmentUrl($collidingAmendment);
                if ($collidingAmendment->proposalStatus == Amendment::STATUS_VOTE) {
                    echo ' (' . \Yii::t('amend', 'proposal_voting') . ')';
                }
                echo '<li>' . Html::a($title, $url);
                echo '</li>';
            }
            ?>
        </ul>
    </section>
    <section class="saving">
        <button class="btn btn-default btn-sm">
            <?= \Yii::t('amend', 'proposal_save_changes') ?>
        </button>
    </section>
    <section class="saved">
        <?= \Yii::t('base', 'saved') ?>
    </section>
<?= Html::endForm() ?>