<?php

namespace Contributte\EventDispatcher\Events\UI;

use Nette\Application\UI\Control;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class UI
{

	/** @var array */
	private $messages = [];

	/** @var array */
	private $snippets = [];

	/** @var mixed */
	private $redirect;

	/**
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * @param array $messages
	 * @return void
	 */
	public function setMessages($messages)
	{
		$this->messages = $messages;
	}

	/**
	 * @param string $message
	 * @param string $type
	 * @return void
	 */
	private function addMessage($message, $type)
	{
		$this->messages[] = (object) ['message' => $message, 'type' => $type];
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function addDangerMessage($message)
	{
		$this->addMessage($message, 'danger');
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function addInfoMessage($message)
	{
		$this->addMessage($message, 'info');
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function addWarningMessage($message)
	{
		$this->addMessage($message, 'warning');
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function addSuccessMessage($message)
	{
		$this->addMessage($message, 'success');
	}

	/**
	 * @return array
	 */
	public function getSnippets()
	{
		return $this->snippets;
	}

	/**
	 * @param array $snippets
	 * @return void
	 */
	public function setSnippets($snippets)
	{
		$this->snippets = $snippets;
	}

	/**
	 * @param string $snippet
	 * @return void
	 */
	public function redrawSnippet($snippet)
	{
		$this->snippets[] = $snippet;
	}

	/**
	 * @return mixed
	 */
	public function getRedirect()
	{
		return $this->redirect;
	}

	/**
	 * @param string $destination
	 * @param array $args
	 * @return void
	 */
	public function setRedirect($destination, array $args = [])
	{
		$this->redirect = (object) ['destination' => $destination, 'args' => $args];
	}

	/**
	 * @return void
	 */
	public function setRefresh()
	{
		$this->setRedirect('this');
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param Control $control
	 * @return void
	 */
	public function apply(Control $control)
	{
		// Apply messages
		if ($this->messages) {
			foreach ($this->messages as $message) {
				$control->flashMessage($message->message, $message->type);
			}
		}

		// Apply snippets
		if ($this->snippets) {
			foreach ($this->snippets as $snippet) {
				$control->redrawControl($snippet);
			}
		}

		// Apply redirect
		if ($this->redirect) {
			$control->redirect($this->redirect->destination, $this->redirect->args);
		}
	}

}
