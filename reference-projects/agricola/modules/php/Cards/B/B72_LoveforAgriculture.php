<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;

class B72_LoveforAgriculture extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B72_LoveforAgriculture';
    $this->name = clienttranslate('Love for Agriculture');
    $this->deck = 'B';
    $this->number = 72;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can sow crops in pastures covering 1 or 2 farmyard spaces. If you do, these pastures are also considered fields and hold 1 and 2 animals less, respectively.'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
    $this->rulings = [
      clienttranslate('Pastures are only considered fields for as long as they have crops sown in them. Therefore they cannot score points for __Greening Plan__ (C033) or __Garden Designer__ (C099), and cannot receive crops from __Clearing Spade__ (A071).'),
    ];
  }

  public function onPlayerComputeSowableFields($player, &$args)
  {
    $fields = $args['fields'] ?? [];
    $boardCropsByNode = $this->getBoardCropsByNode($player);

    foreach ($this->getEligiblePastures($player) as $pasture) {
      $field = $this->getPastureField($player, $pasture, $boardCropsByNode);

      if (!empty($field['crops'])) {
        continue;
      }

      $fields[$field['uid']] = $field;
    }

    $args['fields'] = $fields;
  }

  public function onPlayerComputeFieldsAndCrops($player, &$args)
  {
    $fields = $args['fields'] ?? [];
    $boardCropsByNode = $this->getBoardCropsByNode($player);

    foreach ($this->getEligiblePastures($player) as $pasture) {
      $field = $this->getPastureField($player, $pasture, $boardCropsByNode);

      if (empty($field['crops'])) {
        continue;
      }

      $fields[$field['uid']] = $field;
    }

    $args['fields'] = $fields;
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $sownPastureKeys = [];
    $boardCropsByNode = $this->getBoardCropsByNode($player);

    foreach ($this->getEligiblePastures($player) as $pasture) {
      $field = $this->getPastureField($player, $pasture, $boardCropsByNode);
      if (!empty($field['crops'])) {
        $sownPastureKeys[$field['uid']] = true;
      }
    }

    foreach ($args['zones'] as &$zone) {
      if (($zone['type'] ?? null) !== 'pasture') {
        continue;
      }

      $size = count($zone['locations'] ?? []);
      if (!in_array($size, [1, 2], true)) {
        continue;
      }

      $pastureUid = $this->getPastureFieldUidFromNodes($zone['locations']);
      if (!isset($sownPastureKeys[$pastureUid])) {
        continue;
      }

      $zone['capacity'] = max(0, $zone['capacity'] - $size);
    }
  }

  public function orderComputeDropZones()
  {
    $cards = PlayerCards::getCardsHasMethod('onPlayerComputeDropZones', $this->getPlayer()->getId());
    $constraints = [];

    foreach ($cards as $cardId) {
      if ($cardId === $this->id) {
        continue;
      }

      $constraints[] = ['>', $cardId];
    }

    return $constraints;
  }

  private function getEligiblePastures($player)
  {
    $pastures = $player->board()->getPastures();

    Utils::filter($pastures, function ($pasture) {
      $size = count($pasture['nodes'] ?? []);
      return $size == 1 || $size == 2;
    });

    return $pastures;
  }

  private function getBoardCropsByNode($player)
  {
    $boardCropsByNode = [];

    foreach (Meeples::getGrowingCrops($player->getId()) as $crop) {
      if (($crop['location'] ?? null) !== 'board') {
        continue;
      }

      $key = $crop['x'] . '_' . $crop['y'];
      if (!isset($boardCropsByNode[$key])) {
        $boardCropsByNode[$key] = [];
      }
      $boardCropsByNode[$key][] = $crop;
    }

    return $boardCropsByNode;
  }

  private function getPastureField($player, $pasture, $boardCropsByNode)
  {
    $crops = [];
    foreach ($pasture['nodes'] as $node) {
      $key = $node['x'] . '_' . $node['y'];
      if (isset($boardCropsByNode[$key])) {
        $crops = array_merge($crops, $boardCropsByNode[$key]);
      }
    }

    $anchor = $this->getPastureAnchor($pasture);
    $uid = $this->getPastureFieldUid($pasture);

    return [
      'uid' => $uid,
      'id' => $uid,
      'pId' => $player->getId(),
      'location' => 'board',
      'type' => 'pastureField',
      'constraints' => null,
      'x' => $anchor['x'],
      'y' => $anchor['y'],
      'locations' => $pasture['nodes'],
      'pastureSize' => count($pasture['nodes']),
      'crops' => $crops,
      'fieldType' => empty($crops) ? null : $crops[0]['type'],
    ];
  }

  private function getPastureAnchor($pasture)
  {
    $nodes = $pasture['nodes'];
    usort($nodes, function ($a, $b) {
      if ($a['y'] == $b['y']) {
        return $a['x'] <=> $b['x'];
      }
      return $a['y'] <=> $b['y'];
    });

    $stableMap = [];
    foreach ($pasture['stables'] as $stable) {
      $stableMap[$stable['x'] . '_' . $stable['y']] = true;
    }

    foreach ($nodes as $node) {
      $key = $node['x'] . '_' . $node['y'];
      if (!isset($stableMap[$key])) {
        return [
          'x' => $node['x'],
          'y' => $node['y'],
        ];
      }
    }

    if (empty($nodes)) {
      return ['x' => 1, 'y' => 1];
    }

    return [
      'x' => $nodes[0]['x'],
      'y' => $nodes[0]['y'],
    ];
  }

  private function getPastureFieldUid($pasture)
  {
    return $this->getPastureFieldUidFromNodes($pasture['nodes']);
  }

  private function getPastureFieldUidFromNodes($nodes)
  {
    $coords = array_map(function ($node) {
      return $node['x'] . '_' . $node['y'];
    }, $nodes);
    sort($coords, SORT_STRING);

    return $this->id . '_Pasture_' . implode('-', $coords);
  }
}
