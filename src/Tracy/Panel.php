<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Tracy;

use Contributte\EventDispatcher\Diagnostics\DiagnosticDispatcher;
use Contributte\EventDispatcher\Diagnostics\EventInfo;
use Tracy\IBarPanel;

class Panel implements IBarPanel
{

	/** @var EventInfo[] */
	private $events = [];

	/** @var DiagnosticDispatcher */
	private $dispatcher;

	public function setDispatcher(DiagnosticDispatcher $dispatcher): void
	{
		$this->dispatcher = $dispatcher;
	}

	public function dispatch(EventInfo $eventInfo): void
	{
		$this->events[] = $eventInfo;
	}

	private function countTotalTime(): float
	{
		$totalTime = 0;
		foreach ($this->events as $event) {
			$totalTime += $event->duration;
		}
		return $totalTime;
	}

	private function handledCount(): int
	{
		$handled = 0;
		foreach ($this->events as $event) {
			$handled += $event->handled ? 1 : 0;
		}
		return $handled;
	}

	/**
	 * @inheritDoc
	 */
	public function getTab()
	{
		$totalCount = count($this->events);
		$handledCount = $this->handledCount();
		$totalTime = $this->countTotalTime();
		$totalTime = ($totalTime > 0 ? ' / ' . number_format($totalTime * 1000, 1, '.', ' ') . ' ms' : '');

		ob_start();
		require __DIR__ . '/templates/tab.phtml';
		return (string) ob_get_clean();
	}

	/**
	 * @inheritDoc
	 */
	public function getPanel()
	{
		$handledCount = $this->handledCount();
		$totalTime = $this->countTotalTime();
		$events = $this->events;
		$listeners = $this->dispatcher->getListeners();
		ksort($listeners);
		ob_start();
		require __DIR__ . '/templates/panel.phtml';

		return (string) ob_get_clean();
	}

}
