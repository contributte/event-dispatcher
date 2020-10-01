<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Tracy;

use Contributte\EventDispatcher\Diagnostics\DiagnosticDispatcher;
use Contributte\EventDispatcher\Diagnostics\EventInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tracy\IBarPanel;

class Panel implements IBarPanel, LoggerInterface
{

	/** @var EventInfo[] */
	private $events = [];

	/** @var EventDispatcherInterface */
	private $dispatcher;

	public function setDispatcher(EventDispatcherInterface $dispatcher): void
	{
		$this->dispatcher = $dispatcher;
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

	/** @inheritDoc */
	public function emergency($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function alert($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function critical($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function error($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function warning($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function notice($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function info($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function debug($message, array $context = [])
	{
		$this->log(null, $message, $context);
	}

	/** @inheritDoc */
	public function log($level, $message, array $context = [])
	{
		if (array_key_exists('event', $context) &&
			$context['event'] instanceof EventInfo &&
			strncmp($message, DiagnosticDispatcher::DISPATCHING, strlen(DiagnosticDispatcher::DISPATCHING)) === 0
		) {
			$this->events[] = $context['event'];
		}
	}

}
