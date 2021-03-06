<?php namespace Notification;

use Notification\NotificationsBag;
use Session;
use Closure;

class Notification
{
    /**
     * Illuminate application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * List of instantiated containers.
     *
     * @var array
     */
    protected $containers = array();

    /**
     * Creates new instance.
     *
     * @param null $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
    }

    /**
     * Adds success message to default container.
     *
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    public function success($message, $flash = true, $format = null)
    {
        $this->addMessage(null, 'success', $message, $flash, $format);
    }

    /**
     * Adds error message to default container.
     *
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    public function error($message, $flash = true, $format = null)
    {
        $this->addMessage(null, 'error', $message, $flash, $format);
    }

    /**
     * Adds warning message to default container.
     *
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    public function warning($message, $flash = true, $format = null)
    {
        $this->addMessage(null, 'warning', $message, $flash, $format);
    }

    /**
     * Adds info message to default container.
     *
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    public function info($message, $flash = true, $format = null)
    {
        $this->addMessage(null, 'info', $message, $flash, $format);
    }

    /**
     * Returns container.
     *
     * @param $container
     * @return mixed
     */
    public function get($container)
    {
        return $this->container($container);
    }

    /**
     * Adds message to container.
     *
     * @param $container
     * @param $type
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    protected function addMessage($container, $type, $message, $flash = true, $format = null)
    {
        $this->container($container)->add($type, $message, $flash, $format);
    }

    /**
     * Returns container instance.
     *
     * @param null $container
     * @param callable $callback
     * @return mixed
     */
    public function container($container = null, Closure $callback = null)
    {
        $container = is_null($container) ? $this->app['config']->get('notification::default_container') : $container;

        if(!isset($this->containers[$container]))
        {
            $this->containers[$container] = new NotificationsBag($container, $this->app);
        }

        if(is_callable($callback))
        {
            $callback($this->containers[$container]);
        }

        return $this->containers[$container];
    }

    /**
     * Renders each message by given type (or all) in container.
     *
     * @param null $type
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function show($type = null, $container = null, $format = null)
    {
        return $this->container($container)->show($type, $format);
    }

}