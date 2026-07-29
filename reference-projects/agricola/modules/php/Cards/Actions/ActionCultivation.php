<?php
namespace AGR\Cards\Actions;

class ActionCultivation extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionCultivation';
    $this->name = clienttranslate('Cultivation');
    $this->desc = ['<FIELD> + <SOW>'];
    $this->tooltipDesc = [
      clienttranslate('[Plow a field]'),
      '<FIELD>',
      clienttranslate('[and/or]'),
      clienttranslate('[Sow]'),
      '<SOW>',
    ];
    $this->tooltip = [
      clienttranslate(
        'You can plow one field and then immediately sow grain or vegetables in all of your empty fields, including the one you just plowed'
      ),
    ];

    $this->stage = 5;
    $this->flow = [
      'type' => NODE_OR,
      'childs' => [['action' => PLOW], ['action' => SOW, 'optional' => true]],
    ];
  }
}
