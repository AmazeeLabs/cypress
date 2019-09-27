<?php
use Drupal\node\Entity\Node;

/** @var string[] $extra */

Node::create([
  'type' => 'page',
  'title' => $extra[0] ?? 'TestPage',
])->save();
