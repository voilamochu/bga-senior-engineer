<?php
namespace AGR\Cards\Actions;

class ActionGrainUtilization extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionGrainUtilization';
    $this->name = clienttranslate('Grain Utilization');
    $this->desc = ['<SOW> + <BREAD>'];
    $this->tooltipDesc = [
      clienttranslate('[Sow]'),
      '<SOW>',
      clienttranslate('[and/or]'),
      clienttranslate('[Bake bread]'),
      '<BREAD>',
    ];
    $this->tooltip = [
      clienttranslate('How to sow is explained on page 9 of the rule book.'),
      clienttranslate(
        '"Baking bread" (see page 10 of the rule book) means turning grain in your supply (and not from your fields) into 2 and 3 food using a Fireplace and Cooking Hearth, respectively.'
      ),
      clienttranslate('Various oven improvements allow you to turn your grain into even more food'),
    ];

    $this->stage = 1;
    $this->flow = [
      'type' => NODE_OR,
      'forcePassAfterOne' => true,
      'childs' => [
        ['action' => SOW],
        [
          'action' => EXCHANGE,
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];

    $this->accumulate = 'left'; // Useful for SugarBaker
  }
}
