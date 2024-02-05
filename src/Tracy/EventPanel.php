<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Tracy;

use Contributte\EventDispatcher\Diagnostics\TracyDispatcher;
use Tracy\IBarPanel;

class EventPanel implements IBarPanel
{

	private TracyDispatcher $dispatcher;

	public function __construct(TracyDispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTab(): string
	{
		$totalCount = count($this->dispatcher->getEvents()); // @phpcs:ignore
		$handledCount = $this->handledCount(); // @phpcs:ignore
		$totalTime = $this->countTotalTime(); // @phpcs:ignore
		$totalTime = ($totalTime > 0 ? ' / ' . number_format($totalTime * 1000, 1, '.', ' ') . ' ms' : ''); // @phpcs:ignore

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
		$totalTime = $this->countTotalTime(); // @phpcs:ignore
		$events = $this->dispatcher->getEvents(); // @phpcs:ignore
		$listeners = $this->dispatcher->getListeners();
		ksort($listeners);
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

}
