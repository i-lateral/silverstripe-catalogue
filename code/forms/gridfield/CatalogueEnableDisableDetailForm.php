<?php
/**
 * Base class for editing a catlogue object.
 * 
 * Currently allows enabling or disabling of an object via additional buttons
 * added to the gridfield.
 * 
 * NOTE: The object being edited must implement a "Disabled" parameter
 * on it's DB fields.
 *
 * @author ilateral
 */

class CatalogueEnableDisableDetailForm extends GridFieldDetailForm
{
}

class CatalogueEnableDisableDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();

		if ($form && $this->record->ID !== 0 && $this->record->canEdit()) {
			$fields = $form->Fields();
			$actions = $form->Actions();
        
            // Remove the disabled field
            $fields->removeByName("Disabled");
            
            if ($this->record->isEnabled()) {
                $actions->insertBefore(
                    FormAction::create(
                        'doDisable',
                        _t('Catalogue.Disable', 'Disable')
                    )->setUseButtonTag(true)
                    ->addExtraClass('ss-ui-action-destructive'),
                    "action_doDelete"
                );
            } elseif ($this->record->isDisabled()) {
                $actions->insertBefore(
                    FormAction::create(
                        'doEnable',
                        _t('Catalogue.Enable', 'Enable')
                    )->setUseButtonTag(true),
                    "action_doDelete"
                );
            }
        }
        
		$this->extend("updateItemEditForm", $form);
        
        return $form;
    }


    public function doEnable($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->Disabled = 0;
        $record->write();
        $this->gridField->getList()->add($record);

        $message = sprintf(
            _t('Catalogue.Enabled', 'Enabled %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $form->sessionMessage($message, 'good');
        return $this->edit(Controller::curr()->getRequest());
    }
    
    
    public function doDisable($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->Disabled = 1;
        $record->write();
        $this->gridField->getList()->add($record);

        $message = sprintf(
            _t('Catalogue.Disabled', 'Disabled %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $form->sessionMessage($message, 'good');
        return $this->edit(Controller::curr()->getRequest());
    }
}
