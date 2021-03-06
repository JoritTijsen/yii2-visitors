<?php

/**
 * This file is part of the Yii2 extension module, yii2-visitor
 *
 * @author John Snook
 * @date 2018-06-28
 * @license https://github.com/johnsnook/yii2-visitor/LICENSE
 * @copyright 2018 John Snook Consulting
 */

namespace jorittijsen\visitors\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "access".
 *
 * @property integer $id
 * @property string $ip
 * @property string $created_at
 * @property string $createdBy
 * @property string $request
 * @property string $referer
 * @property string $user_agent
 *
 * @property Visitor $visitor
 * @property VisitorAgent $userAgent
 */
class Visits extends ModuleActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'visitor_log';
    }

    /**
     * Set up timestamp behavior here
     *
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Logs the visitor's request, browser type and referrer
     *
     * @param string $ip The ip address to associate this record with
     */
    public static function log($ip, $save = true) {
        $log = new Visits([
            'ip' => $ip,
            'request' => $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : 'unknown',
        ]);
        if ($save) {
            $log->save(false);
        }
        Visitor::incrementCount($ip);
        return $log;
    }

    /**
     * {@inheritdoc}
     * @return VisitsQuery the active query used by this AR class.
     */
    public static function find() {
        return new VisitsQuery(get_called_class());
    }

    /**
     * Converts db timestamp to formatted string
     * @return string
     */
    public function getCreatedAt() {
        $dt = new \DateTime($this->created_at);
        return $dt->format('Y-m-d g:i A');
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['ip', 'request', 'referer', 'user_agent'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'ip' => 'Ip Address',
            'created_at' => 'Visit Time',
            'request' => 'Request',
            'referer' => 'Referer',
            'user_agent' => 'User Agent',
        ];
    }

    public static function getMostRecentVisit($ip) {
        $sql = "select max(created_at) from visits where ip = '$ip'";
        return \Yii::$app->db->createCommand($sql)->queryScalar();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVisitor() {
        return $this->hasOne(Visitor::className(), ['ip' => 'ip']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAgent() {
        return $this->hasOne(VisitorAgent::className(), ['user_agent' => 'user_agent']);
    }

}
