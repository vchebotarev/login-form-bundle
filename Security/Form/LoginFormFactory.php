<?php

namespace Chebur\LoginFormBundle\Security\Form;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

class LoginFormFactory
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param FormFactory $formFactory
     * @param array       $config
     */
    public function __construct(FormFactory $formFactory, array $config)
    {
        $this->formFactory = $formFactory;
        $this->config      = $config;
    }

    /**
     * @param string $firewallName
     * @return FormInterface|null
     */
    public function createForm($firewallName)
    {
        if (!isset($this->config[$firewallName])) {
            return null;
        }

        $firewallConfig = $this->config[$firewallName];

        return $this->formFactory->create($firewallConfig['form']);
    }

}
