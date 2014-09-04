<?php

/**
 * A {@link GridFieldBulkActionHandler} for bulk marking products
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueProductBulkAction extends GridFieldBulkActionHandler {

    private static $allowed_actions = array(
        'publish',
        'unpublish'
    );

    private static $url_handlers = array(
        'publish' => 'publish',
        'unpublish' => 'unpublish'
    );

    public function publish(SS_HTTPRequest $request) {
        $ids = array();

        foreach($this->getRecords() as $record) {
            array_push($ids, $record->ID);
            $record->write();
            $record->publish('Stage', 'Live');
        }

        $response = new SS_HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function unpublish(SS_HTTPRequest $request) {
        $ids = array();

        foreach($this->getRecords() as $record) {
            array_push($ids, $record->ID);
            
            $record->deleteFromStage('Live');
        }

        $response = new SS_HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
}
