<?php

/**
 * A {@link GridFieldBulkActionHandler} for bulk marking products
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueProductBulkAction extends GridFieldBulkActionHandler {

    private static $allowed_actions = array(
        'disable',
        'enable'
    );

    private static $url_handlers = array(
        "disable" => "disable",
        "enable" => "enable"
    );

    public function disable(SS_HTTPRequest $request) {
        $ids = array();

        foreach($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->Disabled = 1;
            $record->write();
        }

        $response = new SS_HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function enable(SS_HTTPRequest $request) {
        $ids = array();

        foreach($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->Disabled = 0;
            $record->write();
        }

        $response = new SS_HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
}
