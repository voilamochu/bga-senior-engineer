<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;

class E70_CropRotationField extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E70_CropRotationField';
    $this->name = clienttranslate('Crop Rotation Field');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 70;
    $this->category = 'CROPS_-_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'This card is a field. Each time you remove the last <GRAIN> or <VEGETABLE> from this card, you can immediately sow <VEGETABLE> or <GRAIN> on this card, respectively.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
    $this->field = true;
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => null,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap');
  }

  public function onPlayerAfterReap($player, $event)
  {
    $harvested = false;
    $sowType = null;

    foreach ($event['crops'] as $meeple) {
      if ($meeple['originalLocation'] == $this->id) {
        $harvested = true;

        if ($meeple['type'] == VEGETABLE) {
          $sowType = GRAIN;
        }
        if ($meeple['type'] == GRAIN) {
          $sowType = VEGETABLE;
        }
        break;
      }
    }

    if (!$harvested || 
      is_null($sowType) ||
      (Meeples::getResourcesOnCard($this->id, null, GRAIN)->count() > 0) || 
      (Meeples::getResourcesOnCard($this->id, null, VEGETABLE)->count() > 0)) 
    {
      return;
    }

    return [
      'countAsUse' => true,
      'action' => SOW,
      'optional' => true,
      'args' => [
        'max' => 1,
        'type' => $sowType,
        'location' => [$this->id],
        'auto' => true,
      ]
    ];
  }
}
