<?php

namespace app\models\forms;

use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsTag;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;
use yii\base\Model;

class MotionEditForm extends Model
{
    /** @var ConsultationMotionType */
    public $motionType;

    /** @var ConsultationAgendaItem */
    public $agendaItem;

    /** @var MotionSupporter[] */
    public $supporters = [];

    /** @var array */
    public $tags = [];

    /** @var MotionSection[] */
    public $sections = [];

    /** @var null|int */
    public $motionId = null;

    private $adminMode = false;

    /**
     * @param ConsultationMotionType $motionType
     * @param null|ConsultationAgendaItem
     * @param null|Motion $motion
     */
    public function __construct(ConsultationMotionType $motionType, $agendaItem, $motion)
    {
        parent::__construct();
        $this->motionType = $motionType;
        $this->agendaItem = $agendaItem;
        $motionSections   = [];
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            foreach ($motion->tags as $tag) {
                $this->tags[] = $tag->id;
            }
            foreach ($motion->sections as $section) {
                $motionSections[$section->sectionId] = $section;
            }
        }
        $this->sections = [];
        foreach ($motionType->motionSections as $sectionType) {
            if (isset($motionSections[$sectionType->id])) {
                $this->sections[] = $motionSections[$sectionType->id];
            } else {
                $section            = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->data      = '';
                $section->dataRaw   = '';
                $section->cache     = '';
                $section->refresh();
                $this->sections[] = $section;
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'type'], 'number'],
            [['supporters', 'tags'], 'safe'],
        ];
    }

    /**
     * @param bool $set
     */
    public function setAdminMode($set)
    {
        $this->adminMode = $set;
    }

    /**
     * @param Motion $motion
     */
    public function cloneSupporters(Motion $motion)
    {
        foreach ($motion->motionSupporters as $supp) {
            $suppNew = new MotionSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $this->supporters[] = $suppNew;
        }
    }

    /**
     * @param array $data
     * @param bool $safeOnly
     */
    public function setAttributes($data, $safeOnly = true)
    {
        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);
        foreach ($this->sections as $section) {
            if ($section->getSettings()->type == ISectionType::TYPE_TITLE && isset($values['motion']['title'])) {
                $section->getSectionType()->setMotionData($values['motion']['title']);
            }
            if (isset($values['sections'][$section->sectionId])) {
                $section->getSectionType()->setMotionData($values['sections'][$section->sectionId]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->sectionId])) {
                    $data = [];
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->sectionId])) {
                            $data[$key] = $vals[$section->sectionId];
                        }
                    }
                    $section->getSectionType()->setMotionData($data);
                }
            }
        }
    }

    /**
     * @throws FormError
     */
    private function createMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        try {
            $this->motionType->getMotionSupportTypeClass()->validateMotion();
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     * @return Motion
     */
    public function createMotion()
    {
        $consultation = $this->motionType->getConsultation();

        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $motion = new Motion();

        $this->setAttributes([\Yii::$app->request->post(), $_FILES]);
        $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);

        $this->createMotionVerify();

        $motion->status         = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->motionType->consultationId;
        $motion->textFixed      = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title          = '';
        $motion->titlePrefix    = '';
        $motion->dateCreation   = date('Y-m-d H:i:s');
        $motion->motionTypeId   = $this->motionType->id;
        $motion->cache          = '';
        $motion->agendaItemId   = ($this->agendaItem ? $this->agendaItem->id : null);

        if ($motion->save()) {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);

            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            foreach ($this->sections as $section) {
                $section->motionId = $motion->id;
                $section->save();
            }

            $motion->refreshTitle();
            $motion->save();

            return $motion;
        } else {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
    }

    /**
     * @throws FormError
     */
    private function saveMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        $this->motionType->getMotionSupportTypeClass()->validateMotion();

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }
    }

    /**
     * @param Motion $motion
     */
    private function overwriteSections(Motion $motion)
    {
        /** @var MotionSection[] $sectionsById */
        $sectionsById = [];
        foreach ($motion->sections as $section) {
            $sectionsById[$section->sectionId] = $section;
        }
        foreach ($this->sections as $section) {
            if (isset($sectionsById[$section->sectionId])) {
                $section = $sectionsById[$section->sectionId];
            }
            $section->motionId = $motion->id;
            $section->save();
        }
    }


    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function saveMotion(Motion $motion)
    {
        $consultation = $this->motionType->getConsultation();
        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);

        if (!$this->adminMode) {
            $this->saveMotionVerify();
        }

        if ($motion->save()) {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);

            // Tags
            foreach ($motion->tags as $tag) {
                $motion->unlink('tags', $tag, true);
            }
            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            $this->overwriteSections($motion);

            $motion->refreshTitle();
            $motion->save();
        } else {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
    }
}
