<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Tracy;

use Contributte\EventDispatcher\Diagnostics\ListenerDescriber;
use Contributte\EventDispatcher\Diagnostics\TracyDispatcher;
use Tracy\IBarPanel;

class EventPanel implements IBarPanel
{

	private TracyDispatcher $dispatcher;

	private ?int $deep;

	public function __construct(TracyDispatcher $dispatcher, ?int $deep = null)
	{
		$this->dispatcher = $dispatcher;
		$this->deep = $deep;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTab(): string
	{
		$totalCount = count($this->dispatcher->getEvents()); // @phpcs:ignore
		$handledCount = $this->handledCount(); // @phpcs:ignore
		$failedCount = $this->failedCount(); // @phpcs:ignore
		$totalTime = $this->countTotalTime(); // @phpcs:ignore
		$totalTime = number_format($totalTime * 1000, 1, '.', ' ') . ' ms'; // @phpcs:ignore

		ob_start();
		require __DIR__ . '/templates/tab.phtml';

		return (string) ob_get_clean();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPanel(): string
	{
		$handledCount = $this->handledCount(); // @phpcs:ignore
		$failedCount = $this->failedCount(); // @phpcs:ignore
		$totalTime = $this->countTotalTime(); // @phpcs:ignore
		$events = $this->dispatcher->getEvents(); // @phpcs:ignore
		$registeredListeners = $this->collectRegisteredListeners(); // @phpcs:ignore
		$deep = $this->deep; // @phpcs:ignore
		ob_start();
		require __DIR__ . '/templates/panel.phtml';

		return (string) ob_get_clean();
	}

	private function countTotalTime(): float
	{
		$totalTime = 0;
		foreach ($this->dispatcher->getEvents() as $event) {
			$totalTime += $event->duration;
		}

		return $totalTime;
	}

	private function handledCount(): int
	{
		$handled = 0;
		foreach ($this->dispatcher->getEvents() as $event) {
			$handled += $event->handled ? 1 : 0;
		}

		return $handled;
	}

	private function failedCount(): int
	{
		$failed = 0;
		foreach ($this->dispatcher->getEvents() as $event) {
			$failed += $event->exception !== null ? 1 : 0;
		}

		return $failed;
	}

	/**
	 * @return array<string, array<int, array{label: string, priority: int}>>
	 */
	private function collectRegisteredListeners(): array
	{
		$listeners = $this->dispatcher->getListeners();
		ksort($listeners);

		$registeredListeners = [];
		foreach ($listeners as $eventName => $eventListeners) {
			if (!is_iterable($eventListeners)) {
				continue;
			}

			foreach ($eventListeners as $listener) {
				if (!is_callable($listener)) {
					continue;
				}

				$registeredListeners[$eventName][] = [
					'label' => ListenerDescriber::describe($listener),
					'priority' => $this->dispatcher->getListenerPriority($eventName, $listener) ?? 0,
				];
			}
		}

		return $registeredListeners;
	}

}
