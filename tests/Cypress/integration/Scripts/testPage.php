<?php
use Drupal\node\Entity\Node;

/** @var $args */

$node = Node::create([
  'type' => 'page',
  'title' => 'Testpage',
]);
$node->save();
echo $node->id();
