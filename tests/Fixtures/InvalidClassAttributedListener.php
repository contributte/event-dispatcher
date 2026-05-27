<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'listener.invalid.class')]
final class InvalidClassAttributedListener
{

}
