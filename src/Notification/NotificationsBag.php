<?php namespace Notification;

use Countable;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Session;
use Notification\Message;
use Notification\Collection;

class NotificationsBag implements ArrayableInterface, JsonableInterface, Countable
{

    /**
     * Illuminate application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * NotificationBag container name.
     *
     * @var string
     */
    protected $container;

    /**
     * Messages collections by type.
     *
     * @var array
     */
    protected $collections = array();

    /**
     * Default global format for messages.
     *
     * @var null
     */
    protected $format = null;

    /**
     * Default message formats for individual types.
     *
     * @var array
     */
    protected $formats = array();

    /**
     * Creates new NotificationBag object.
     *
     * @param $container
     * @param $app
     */
    public function __construct($container, $app)
    {
        $this->container = $container;
        $this->app = $app;

        $this->loadFormats();

        $this->load();
    }

    /**
     * Adds new notification message to one of collections.
     * Flashes flashable messages.
     *
     * @param $type
     * @param $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function add($type, $message, $flashable = true, $format = null)
    {
        $this->get($type)->addUnique(new Message($type, $message, $flashable, $this->checkFormat($format, $type)));

        $this->flash();

        return $this;
    }

    /**
     * Shortcut to add success message.
     *
     * @param $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function success($message, $flashable = true, $format = null)
    {
        return $this->add('success', $message, $flashable, $format);
    }

    /**
     * Shortcut to add error message.
     *
     * @param $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function error($message, $flashable = true, $format = null)
    {
        return $this->add('error', $message, $flashable, $format);
    }

    /**
     * Shortcut to add info message.
     *
     * @param $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function info($message, $flashable = true, $format = null)
    {
        return $this->add('info', $message, $flashable, $format);
    }

    /**
     * Shortcut to add warning message.
     *
     * @param $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function warning($message, $flashable = true, $format = null)
    {
        return $this->add('warning', $message, $flashable, $format);
    }

    /**
     * Returns first message object for given type.
     *
     * @param $type
     * @return Message
     */
    public function first($type)
    {
        return $this->get($type)->first();
    }

    /**
     * Returns all messages for given type.
     *
     * @param $type
     * @return Collection
     */
    public function get($type)
    {
        return array_key_exists($type, $this->collections) ? $this->collections[$type] : $this->collections[$type] = new Collection();
    }

    /**
     * Returns all messages in bag.
     *
     * @return Collection
     */
    public function all()
    {
        $all = array();

        foreach($this->collections as $collection)
        {
            $all = array_merge($all, $collection->all());
        }

        return new Collection($all);
    }

    /**
     * Loads default formats for messages.
     */
    protected function loadFormats()
    {
        $this->setFormat($this->app['config']->get('notification::default_format'));

        $formats = isset($this->app['config']->get('notification::default_formats')[$this->container]) ?
            $this->app['config']->get('notification::default_formats')[$this->container] :
            $this->app['config']->get('notification::default_formats')['__'];

        foreach($formats as $type => $format)
        {
            $this->setFormat($format, $type);
        }
    }

    /**
     * Sets global or individual message format.
     *
     * @param $format
     * @param null $type
     * @return NotificationsBag
     */
    public function setFormat($format, $type = null)
    {
        if(!is_null($type))
        {
            $this->formats[$type] = $format;
        }
        else
        {
            $this->format = $format;
        }

        return $this;
    }

    /**
     * Returns message format.
     *
     * @param null $type
     * @return null
     */
    public function getFormat($type = null)
    {
        return !is_null($type) && isset($this->formats[$type]) ? $this->formats[$type] : $this->format;
    }

    /**
     * Returns valid format.
     *
     * @param $format
     * @param null $type
     * @return null
     */
    protected function checkFormat($format, $type = null)
    {
        return !is_null($format) ? $format : $this->getFormat($type);
    }

    /**
     * Loads flashed messages.
     */
    protected function load()
    {
        $flashed = Session::get('notifications_'.$this->container);

        if($flashed)
        {
            $messages = json_decode($flashed);

            if(is_array($messages))
            {
                foreach($messages as $key => $message)
                {
                    $this->get($message->type)->addUnique(new Message($message->type, $message->message, false, $message->format));
                }
            }
        }
    }

    /**
     * Flashes all flashable messages.
     */
    protected function flash()
    {
        Session::flash('notifications_'.$this->container, $this->getFlashable()->toJson());
    }

    /**
     * Returns all flashable messages.
     *
     * @return Collection
     */
    protected function getFlashable()
    {
        $collection = new Collection();

        foreach($this->all() as $message)
        {
            if($message->isFlashable())
            {
                $collection->addUnique($message);
            }
        }

        return $collection;
    }

    /**
     * Returns generated output of non flashable messages.
     *
     * @param null $type
     * @param null $format
     * @return string
     */
    public function show($type = null, $format = null)
    {
        $messages = is_null($type) ? $this->all() : $this->get($type);

        $output = '';

        foreach($messages as $message)
        {
            if(!$message->isFlashable())
            {
                if(!is_null($format)) $message->setFormat($format);

                $output .= $message->render();
            }
        }

        return $output;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = array
        (
            'container'         => $this->container,
            'format'            => $this->format,
            'collections'       => array()
        );

        foreach($this->collections as $type => $collection)
        {
            $arr['collections'][$type] = $collection->toArray();
        }

        return $arr;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    /**
     * Count the number of colections.
     *
     * @return int
     */
    public function count()
    {
        return count($this->collections);
    }

    /**
     * Convert the Bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }


}