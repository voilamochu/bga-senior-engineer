<?php
namespace AGR\Cards\E;

class E24_Ambition extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E24_Ambition';
    $this->name = clienttranslate('Ambition');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 24;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'Each time you get a __Minor Improvement__ action on an action space, you can build a major improvement instead of playing a minor one.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  function changeToOptionalMajorRecursively(&$flow)
  {
    if (isset($flow['childs'])) {
      foreach ($flow['childs'] as &$child) {
        $this->changeToOptionalMajorRecursively($child);
      }
    } else if (isset($flow['action'])) {
      if ($flow['action'] == IMPROVEMENT) {
        if (isset($flow['args']) && is_array($flow['args'])) {
          if (isset($flow['args']['types']) && is_array($flow['args']['types']) && in_array(MINOR, $flow['args']['types'])) {
            $flow['args']['types'] = [MINOR, MAJOR];
          }
        }
      }
    }
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if ($args['actionCardType'] == 'MeetingPlace' || $args['actionCardId'] == 'ActionWishChildren') {
      $this->changeToOptionalMajorRecursively($args['flow']);
    }
  }
}