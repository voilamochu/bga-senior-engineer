<?php
namespace AGR\Cards\B;

class B37_Grange extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B37_Grange';
    $this->name = clienttranslate('Grange');
    $this->deck = 'B';
    $this->number = 37;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate('When you play this card, you immediately get 1 <FOOD>.')];
    $this->vp = 3;
    $this->prerequisite = clienttranslate('6 Field Tiles and All Animal Types');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fields = $player->board()->getFieldTiles();
    if (count($fields) < 6 ||
        $player->countAnimalsOnBoard()[SHEEP] == 0 ||
        $player->countAnimalsOnBoard()[PIG] == 0 ||
        $player->countAnimalsOnBoard()[CATTLE] == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }  
}
