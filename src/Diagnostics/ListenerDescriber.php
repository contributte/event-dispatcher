<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Closure;
use Contributte\EventDispatcher\LazyListener;

class ListenerDescriber
{

	public static function describe(mixed $listener): string
	{
		if ($listener instanceof LazyListener) {
			return $listener->toString();
		}

		if (is_array($listener) && isset($listener[0], $listener[1]) && is_string($listener[1])) {
			if (is_object($listener[0])) {
				$class = $listener[0]::class;
			} elseif (is_string($listener[0])) {
				$class = $listener[0];
			} else {
				return get_debug_type($listener);
			}

			return $class . '::' . $listener[1];
		}

		if ($listener instanceof Closure) {
			return 'Closure';
		}

		if (is_string($listener)) {
			return $listener;
		}

		if (is_object($listener)) {
			return $listener::class . '::__invoke';
		}

		return get_debug_type($listener);
	}

}
