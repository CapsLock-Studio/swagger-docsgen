<?php

/**
 * @resource /sampleAPI
 */
class SampleAPI {

    /**
     * @api api1
     *
     * @url /sampleAPI/api1
     *
     * @desc this is a foo API
     *
     * @method POST
     *
     * @param SampleModel required. this is a bar
     *
     * @return SampleModel(sample:SampleModel)
     */
    public function api1() {

    }

    /**
     * @api api2
     *
     * @url /sampleAPI/api2
     *
     * @desc this is a foo API
     *
     * @method GET
     *
     * @param int foo required, multiple. this is a foo
     *
     * @return LIST[SampleModel]
     */
    public function api2() {

    }
}
