<?php
defined('ABSPATH') || exit;

class Safe_Pay_Loader
{

    protected $actions;

    protected $filters;

    /**
     * Safe_Pay_Loader constructor.
     */
    public function __construct()
    {

        $this->actions = array();
        $this->filters = array();

    }

    /**
     * Добавление новых действий Wordpress
     *
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int int $priority
     * @param int $accepted_args
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Добавление новых фильтров Wordpress
     *
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Служебная функция, используется для регистрации действий и фильтров Wordpress
     *
     * @param array $hooks
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {

        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;

    }

    /**
     * Регистрация действий и фильтров Wordpress
     */
    public function run()
    {

        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'],
                $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'],
                $hook['accepted_args']);
        }

    }

}
