<?php
namespace AGR\Cards\E;
use AGR\Managers\PlayerCards;

class E97_Beneficiary extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E97_Beneficiary';
    $this->name = clienttranslate('Beneficiary');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 97;
    $this->category = 'ACTION_-_OCCUPATION';
    $this->desc = [
      clienttranslate(
        'If this is your 3rd occupation, you can immediately play another occupation for an occupation cost of 1 <FOOD> and/or play 1 minor improvement by paying its cost.'
      ),
    ];
    $this->players = '1+';
    $this->implemented = true;
  }

  public function onBuy($player)
  {
    if ($player->countOccupations() != 3) {
      return;
    }

    $pId = $player->getId();

    $childs = [];
    if ($player->hasPlayedCard('D42_EducationBonus')) {
      $childs[] = PlayerCards::get('D42_EducationBonus')->flagCardNode();
    }

    $childs[] = [
      'action' => OCCUPATION,
      'args' => ['max' => 1, 'cost' => [FOOD => 1]],
    ];

    return [
      'type' => NODE_OR,
      'pId' => $pId,
      'optional' => true,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'pId' => $pId,
          'childs' => $childs
        ],
        [
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
            'trueAction' => false,
          ],
        ],
      ],
    ];
  }
}
