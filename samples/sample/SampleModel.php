<?php

/**
 * @model SampleModel
 */
class SampleModel {
    /**
     * @property id:string the id
     */
    public $id;

    /**
     * @property range:1-100 the valid range is from 1-100
     */
    public $range;

    /**
     * @property enum:string(enum1|enum2|enum3)
     */
    public $enum;

    /**
     * @property sample:string
     */
    public $sample;
}
