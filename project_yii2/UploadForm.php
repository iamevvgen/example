<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;
use Yii;

class UploadForm extends Model
{
    public $imageFile;

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'html'],
//            [['imageFile'], 'required', 'message' => 'Поле не заполнено!'],

        ];
    }

//    public function upload()
//    {
//        if ($this->validate()) {
////            $this->imageFile->saveAs(Yii::getAlias('/uploads/') . $this->imageFile->baseName . '.' . $this->imageFile->extension);
//            $this->imageFile->saveAs("uploads/{$this->imageFile->baseName}.{$this->imageFile->extension}");
//            return true;
//        } else {
//            return false;
//        }
//    }
}