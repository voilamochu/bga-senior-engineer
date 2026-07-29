<?php
namespace AGR\Cards\D;

class D131_CraftsmanshipPromoter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D131_CraftsmanshipPromoter';
    $this->name = clienttranslate('Craftsmanship Promoter');
    $this->deck = 'D';
    $this->number = 131;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <STONE>. You can build any of the major improvements in the bottom row of the supply board even when taking a __Minor Improvement__ action.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([STONE => 1]);
  }
}
