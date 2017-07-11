<?php

namespace Chebur\LoginFormBundle\Security\Form;

use Chebur\LoginFormBundle\Security\Exception\LoginFormException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginFormHelper
{
    /**
     * @var AuthenticationUtils
     */
    protected $authUtils;

    /**
     * @var LoginFormRegistry
     */
    protected $loginFormRegistry;

    /**
     * @var array
     */
    protected $formLoginConfig;

    /**
     * @param AuthenticationUtils $authUtils
     * @param LoginFormRegistry   $loginFormRegistry
     * @param array               $formLoginConfig
     */
    public function __construct(AuthenticationUtils $authUtils, LoginFormRegistry $loginFormRegistry, array $formLoginConfig)
    {
        $this->authUtils         = $authUtils;
        $this->loginFormRegistry = $loginFormRegistry;
        $this->formLoginConfig   = $formLoginConfig;
    }

    /**
     * @param string $firewallName
     * @return FormInterface
     */
    public function getLoginForm($firewallName)
    {
        $form = $this->loginFormRegistry->getByFirewallName($firewallName);
        if (!$form) {
            throw new \RuntimeException('Login form for firewall `'.$firewallName.'` does not exist');
        }

        $lastAuthException  = $this->authUtils->getLastAuthenticationError(); //todo оптимизировать работу с этими ошибками
        if ($lastAuthException) {
            if ($lastAuthException instanceof LoginFormException) {
                $this->setLoginFormErrors($form , $lastAuthException);
            } else {
                $this->setAuthError($form, $lastAuthException);
            }
        }

        $this->setLastUsername($form, $firewallName);

        return $form;
    }

    /**
     * @param FormInterface           $form
     * @param AuthenticationException $exception
     */
    protected function setAuthError(FormInterface $form, AuthenticationException $exception)
    {
        $form->addError(new FormError($exception->getMessageKey()));
    }

    /**
     * @param FormInterface      $form
     * @param LoginFormException $exception
     */
    protected function setLoginFormErrors(FormInterface $form, LoginFormException $exception)
    {
        $setter = function(FormInterface $form, $errors) use (&$setter){
            foreach ($errors as $k => $error) {
                if (is_array($error)) {
                    $setter($form->get($k), $error);
                } else {
                    $form->addError(new FormError($error));
                }
            }
        };

        $setter($form, $exception->getFormErrors());
    }

    /**
     * @param FormInterface $form
     * @param string        $firewallName
     */
    protected function setLastUsername(FormInterface $form, $firewallName)
    {
        $username          = $this->authUtils->getLastUsername();
        $usernameParameter = $this->formLoginConfig[$firewallName]['username_parameter'];
        if ($username) {
            $form->get($usernameParameter)->setData($username);
        }
    }

}
