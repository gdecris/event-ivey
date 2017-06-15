<?php

namespace Ivey\Events\Exceptions;


class EventListenerDidNotRun extends \Exception
{

    public $listener = null;

}