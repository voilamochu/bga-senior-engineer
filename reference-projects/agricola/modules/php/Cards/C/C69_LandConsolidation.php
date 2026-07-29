<?php
namespace AGR\Cards\C;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Helpers\CardRulings;

class C69_LandConsolidation extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C69_LandConsolidation';
    $this->name = clienttranslate('Land Consolidation');
    $this->deck = 'C';
    $this->number = 69;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate('At any time, if you have a grain field with exactly 3 sown <GRAIN>, you can exchange the <GRAIN> on the field for 1 <VEGETABLE> on the field.'),
    ];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'UPDATED_DIGITAL',
    ]);
  }

  public function isListeningTo($event)
  {
    if (!$this->isAnytime($event)) {
      return false;
    }

    // If there is any pending reaction for Tinsmith Master or Cow Patty,
    // do not offer Land Consolidation as an anytime action.
    if ($this->hasPendingExtraCropReaction()) {
      return false;
    }

    // Only listen if there is at least one eligible field (exactly 3 grain)
    return !empty($this->getEligibleFields());
  }

  /**
   * Return true if the engine tree contains any unresolved node
   * whose args['cardId'] is Tinsmith Master or Cow Patty.
   *
   * That covers both:
   *  - the ACTIVATE_CARD reaction node ("do you want to use this card")
   *  - and its SPECIAL_EFFECT children once you start resolving it.
   */
  private function hasPendingExtraCropReaction()
  {
    if (Engine::$tree === null) {
      return false;
    }

    $stack = [Engine::$tree];

    while (!empty($stack)) {
      $node = array_pop($stack);

      if ($node->isResolved()) {
        continue;
      }

      $args = $node->getArgs();
      if ($args === null) {
        $args = [];
      }

      if (isset($args['cardId']) && in_array($args['cardId'], ['B115_TinsmithMaster', 'E71_CowPatty'])) {
        return true;
      }

      $children = $node->getChilds();
      foreach ($children as $child) {
        $stack[] = $child;
      }
    }

    return false;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'countAsUse' => true,
      'action'   => SPECIAL_EFFECT,
      'optional' => true,
      'args'     => [
        'cardId' => $this->id,
        'method' => 'consolidateField',
      ],
    ];
  }

  public function getConsolidateFieldDescription()
  {
    return clienttranslate('Exchange 3 <GRAIN> in a field for 1 <VEGETABLE>');
  }

  /** Args for the client: provide field zone objects (Crudité pattern) */
  public function argsConsolidateField()
  {
    return [
      'cardId'            => $this->id,
      'description'       => clienttranslate('${actplayer} may exchange 3 <GRAIN> in a field for 1 <VEGETABLE> (Land Consolidation)'),
      'descriptionmyturn' => clienttranslate('Select a field with exactly 3 <GRAIN> (Land Consolidation)'),
      'sources'           => $this->getEligibleFields(), // array of field zone objects
      'min'               => 1,
      'max'               => 1,
    ];
  }

  /** Perform the exchange given an array with one selected field zone */
  public function actConsolidateField($zones)
  {
    if (!is_array($zones) || count($zones) !== 1) {
      throw new \BgaUserException(clienttranslate('Select exactly one field.'));
    }

    // Validate against fresh eligible list
    $allowed = $this->getEligibleFields();
    $zone    = $zones[0];
    if (!in_array($zone, $allowed)) {
      throw new \BgaUserException(clienttranslate('That field is not eligible.'));
    }

    // Must still be exactly 3 grain on top
    if (!isset($zone['crops']) || count($zone['crops']) !== 3 || $zone['crops'][0]['type'] != GRAIN) {
      throw new \BgaVisibleSystemException('Field no longer has exactly 3 <GRAIN>.');
    }

    $player = $this->getPlayer();

    // --- 1) Remove the 3 grain (DB) + Crudité-style removal notification
    $meeples = $zone['crops'];
    Meeples::deleteResources($meeples);

    // IMPORTANT: payResourcesFromFields expects a SINGLE uid string, not an array.
    // Using $this->name here is fine (Crudité does the same).
    Notifications::payResourcesFromFields(
      $player,
      $meeples,
      $this->name,
      [$zone['uid']]
    );

    // --- 2) Create 1 vegetable at the same stack location (DB)
    $location = (($zone['type'] ?? null) === 'fieldCard') ? $zone['id'] : 'board';
    $vegIds = Meeples::createResourceInLocation(
      VEGETABLE,
      $location,
      $zone['pId'],
      $zone['x'],
      $zone['y'],
      1
    );

    // --- 3) Animate the add using the exact Plant/Calcium sow payload
    $sows = [[
      'field' => $zone['id'],
      'type'  => VEGETABLE,
      'seed'  => null,                                        // optional but harmless
      'crops' => Meeples::getMany($vegIds)->toArray(),
    ]];
    Notifications::sow($player, $sows, true);

    // Same post-update used by Plant/Calcium
    Notifications::updateDropZones($player);
  }

  /** Fields with exactly 3 grain (Crudité-style sources payload) */
  private function getEligibleFields()
  {
    $fields = $this->getPlayer()->board()->getGrainFields();

    Utils::filter($fields, function ($field) {
      if (!isset($field['crops'])) {
        return false;
      }
      return count($field['crops']) === 3;
    });

    return $fields;
  }
}
