<?php
use Drupal\node\Entity\Node;

/** @var $args object */

$node = Node::create([
  'type' => 'page',
  'title' => $args->title,
]);
$node->save();
echo $node->id();
