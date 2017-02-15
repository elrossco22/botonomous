<?php

namespace Slackbot;

use Slackbot\utility\FormattingUtility;
use Slackbot\utility\LoggerUtility;
use Slackbot\utility\MessageUtility;
use Slackbot\utility\RequestUtility;

abstract class AbstractBot
{
    /**
     * Dependencies.
     */
    protected $config;
    protected $listener;
    protected $messageUtility;
    protected $commandContainer;
    protected $formattingUtility;
    protected $loggerUtility;
    protected $oauth;
    protected $requestUtility;

    /**
     * @return Config
     */
    public function getConfig()
    {
        if ($this->config === null) {
            $this->config = (new Config());
        }

        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return BaseListener
     */
    public function getListener()
    {
        if (!isset($this->listener)) {
            $rootNamespace = $this->getConfig()->get('rootNamespace');
            $listenerClass = $rootNamespace.'\\'.ucwords($this->getConfig()->get('listenerType')).'Listener';
            $this->setListener(new $listenerClass());
        }

        return $this->listener;
    }

    /**
     * @param BaseListener $listener
     */
    public function setListener(BaseListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @return MessageUtility
     */
    public function getMessageUtility()
    {
        if (!isset($this->messageUtility)) {
            $this->setMessageUtility(new MessageUtility());
        }

        return $this->messageUtility;
    }

    /**
     * @param MessageUtility $messageUtility
     */
    public function setMessageUtility(MessageUtility $messageUtility)
    {
        $this->messageUtility = $messageUtility;
    }

    /**
     * @return CommandContainer
     */
    public function getCommandContainer()
    {
        if (!isset($this->commandContainer)) {
            $this->setCommandContainer(new CommandContainer());
        }

        return $this->commandContainer;
    }

    /**
     * @param CommandContainer $commandContainer
     */
    public function setCommandContainer(CommandContainer $commandContainer)
    {
        $this->commandContainer = $commandContainer;
    }

    /**
     * @return FormattingUtility
     */
    public function getFormattingUtility()
    {
        if (!isset($this->formattingUtility)) {
            $this->setFormattingUtility(new FormattingUtility());
        }

        return $this->formattingUtility;
    }

    /**
     * @param FormattingUtility $formattingUtility
     */
    public function setFormattingUtility(FormattingUtility $formattingUtility)
    {
        $this->formattingUtility = $formattingUtility;
    }

    /**
     * @return LoggerUtility
     */
    public function getLoggerUtility()
    {
        if (!isset($this->loggerUtility)) {
            $this->setLoggerUtility(new LoggerUtility());
        }

        return $this->loggerUtility;
    }

    /**
     * @param LoggerUtility $loggerUtility
     */
    public function setLoggerUtility(LoggerUtility $loggerUtility)
    {
        $this->loggerUtility = $loggerUtility;
    }

    /**
     * @return OAuth
     */
    public function getOauth()
    {
        if (!isset($this->oauth)) {
            $this->setOauth(new OAuth());
        }

        return $this->oauth;
    }

    /**
     * @param OAuth $oauth
     */
    public function setOauth(OAuth $oauth)
    {
        $this->oauth = $oauth;
    }

    /**
     * @return RequestUtility
     */
    public function getRequestUtility()
    {
        if (!isset($this->requestUtility)) {
            $this->setRequestUtility((new RequestUtility()));
        }

        return $this->requestUtility;
    }

    /**
     * @param RequestUtility $requestUtility
     */
    public function setRequestUtility(RequestUtility $requestUtility)
    {
        $this->requestUtility = $requestUtility;
    }
}