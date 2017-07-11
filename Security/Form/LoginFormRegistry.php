<?php

namespace Chebur\LoginFormBundle\Security\Form;

use Symfony\Component\Form\Form;

class LoginFormRegistry
{
    /**
     * @var Form[]
     */
    protected $forms;

    /**
     * @param Form   $form
     * @param string $firewallName
     * @return $this
     */
    public function add(Form $form, string $firewallName)
    {
        $this->forms[$firewallName] = $form;
        return $this;
    }

    /**
     * @param string $firewallName
     * @return null|Form
     */
    public function getByFirewallName(string $firewallName)
    {
        if (!isset($this->forms[$firewallName])) {
            return null;
        }
        return $this->forms[$firewallName];
    }

}
