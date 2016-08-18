<?php
namespace Ivey\Events;

abstract class EventListener {
	public $event_name;
	public static $should_queue = false;

	abstract public function fire($payload);

	/**
	 * @param mixed $event_name
	 * @return $this
	 */
	public function setEventName($event_name) {
		$this->event_name = $event_name;
		return $this;
	}
}
