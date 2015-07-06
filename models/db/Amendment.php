<?php

namespace app\models\db;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changeMetatext
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property Motion $motion
 * @property AmendmentComment[] $comments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 */
class Amendment extends IMotion implements IRSSItem
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(AmendmentSection::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['id', 'motionId', 'status', 'textFixed'], 'number'],
        ];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($this->motion->titlePrefix != '') {
            return $this->titlePrefix . ' zu ' . $this->motion->titlePrefix . ': ' . $this->motion->title;
        } else {
            return $this->titlePrefix . ' zu ' . $this->motion->title;
        }

    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        return $this->motion->consultation;
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getMySections()
    {
        return $this->motion->motionType->motionSections;
    }

    /**
     * @return int
     */
    public function getFirstDiffLine()
    {
        // @TODO
        return 0;
        /*
        if ($this->cacheFirstLineChanged > -1) return $this->cacheFirstLineChanged;

        $text_vorher = $this->motion->text;
        $paragraphs  = $this->motion->getParagraphs(false, false);
        $text_neu    = array();
        $diff        = $this->getDiffParagraphs();
        foreach ($paragraphs as $i => $para) {
            if (isset($diff[$i]) && $diff[$i] != "") $text_neu[] = $diff[$i];
            else $text_neu[] = $para->str_bbcode;
        }
        $diff = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)),
        $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);

        $this->aenderung_first_line_cache = DiffUtils::getFistDiffLine($diff, $this->antrag->getFirstLineNo());
        $this->save();
        return $this->aenderung_first_line_cache;
        */
    }

    /**
     * @return int
     */
    public function getFirstAffectedLineOfParagraphAbsolute()
    {
        return 0; // @TODO
    }

    /**
     * @param Amendment $ae1
     * @param Amendment $ae2
     * @return int
     */
    public static function sortVisibleByLineNumbersSort($ae1, $ae2)
    {
        $first1 = $ae1->getFirstDiffLine();
        $first2 = $ae2->getFirstDiffLine();

        if ($first1 < $first2) {
            return -1;
        }
        if ($first1 > $first2) {
            return 1;
        }

        $tit1 = explode("-", $ae1->titlePrefix);
        $tit2 = explode("-", $ae2->titlePrefix);
        if (count($tit1) == 3 && count($tit2) == 3) {
            if ($tit1[2] < $tit2[2]) {
                return -1;
            }
            if ($tit1[2] > $tit2[2]) {
                return 1;
            }
            return 0;
        } else {
            return strcasecmp($ae1->titlePrefix, $ae2->titlePrefix);
        }
    }


    /**
     * @param Consultation $consultation
     * @param Amendment[] $amendments
     * @return Amendment[]
     */
    public static function sortVisibleByLineNumbers(Consultation $consultation, $amendments)
    {
        $ams = array();
        foreach ($amendments as $am) {
            if (!in_array($am->status, $consultation->getInvisibleAmendmentStati())) {
                $ams[] = $am;
            }
        }

        usort($ams, array(Amendment::className(), 'sortVisibleByLineNumbersSort'));

        return $ams;
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
        $query          = Amendment::find();
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->joinWith(
            [
                'motion' => function ($query) use ($invisibleStati, $consultation) {
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy("amendment.dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }


    /**
     * @return AmendmentSupporter[]
     */
    public function getInitiators()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getSupporters()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getLikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getDislikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }


    /**
     * @return bool
     */
    public function iAmInitiator()
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return bool
     */
    public function canEdit()
    {
        if ($this->status == static::STATUS_DRAFT) {
            return true;
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->motion->consultation->getSettings()->adminsMayEdit) {
            if (User::currentUserHasPrivilege($this->motion->consultation, User::PRIVILEGE_SCREENING)) {
                return true;
            }
        }

        if ($this->motion->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->motion->motionType->amendmentDeadlineIsOver()) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        return 0; // @TODO
    }

    /**
     * @return int
     */
    public function getFirstLineNumber()
    {
        return 1; // @TODO
    }

    /**
     *
     */
    public function onPublish()
    {
        $this->flushCaches();
        /*
        // @TODO Prevent duplicate Calls
        $notified = [];
        foreach ($this->consultation->subscriptions as $sub) {
            if ($sub->motions && !in_array($sub->userId, $notified)) {
                $sub->user->notifyMotion($this);
                $notified[] = $sub->userId;
            }
        }
        */
    }

    /**
     *
     */
    public function flushCaches()
    {
        $this->cache = '';
        $this->motion->flushCaches();
    }

    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
        // @TODO Inline styling
        $content = '';
        foreach ($this->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_INLINE);
            $diffGroups = $formatter->getInlineDiffGroupedLines();

            if (count($diffGroups) > 0) {
                $content .= '<h2>' . Html::encode($section->consultationSetting->title) . '</h2>';
                $content .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
                $content .= \app\models\sectionTypes\TextSimple::formatDiffGroup($diffGroups);
                $content .= '</div>';
                $content .= '</section>';
            }
        }

        if ($this->changeExplanation) {
            $content .= '<h2>Begründung</h2>';
            $content .= '<div class="paragraph"><div class="text">';
            $content .= $this->changeExplanation;
            $content .= '</div></div>';
        }

        $feed->addEntry(
            UrlHelper::createAmendmentUrl($this),
            $this->getTitle(),
            $this->getInitiatorsStr(),
            $content,
            Tools::dateSql2timestamp($this->dateCreation)
        );
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    /**
     * @return array
     */
    public function getDataTable()
    {
        $return = [];

        $initiators = [];
        foreach ($this->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        if (count($initiators) == 1) {
            $return['Antragsteller/in'] = implode("\n", $initiators);
        } else {
            $return['Antragsteller/innen'] = implode("\n", $initiators);
        }

        return $return;
    }

    /**
     * @return Content
     */
    public function getTexContent()
    {
        $content              = new Content();
        $content->template    = $this->motion->motionType->texTemplate->texContent;
        $content->title       = $this->motion->title;
        $content->titlePrefix = $this->titlePrefix . ' zu ' . $this->motion->titlePrefix;
        $content->titleLong   = $this->getTitle();

        $intro                    = explode("\n", $this->motion->consultation->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        } else {
            $content->introductionSmall = '';
        }

        $initiators = [];
        foreach ($this->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        foreach ($this->getDataTable() as $key => $val) {
            $content->motionDataTable = Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        $content->text = '';

        foreach ($this->getSortedSections(true) as $section) {
            $content->text .= $section->getSectionType()->getAmendmentTeX();
        }

        $title = Exporter::encodePlainString('Begründung');
        $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $lines = LineSplitter::motionPara2lines($this->changeExplanation, false, PHP_INT_MAX);
        $content->text .= TextSimple::getMotionLinesToTeX($lines) . "\n";

        return $content;
    }
}
