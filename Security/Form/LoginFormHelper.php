<?php

namespace Chebur\LoginFormBundle\Security\Form;

use Chebur\LoginFormBundle\Security\Exception\LoginFormException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $formLoginConfig;

    /**
     * @param AuthenticationUtils $authUtils
     * @param LoginFormRegistry   $loginFormRegistry
     * @param TranslatorInterface $translator
     * @param array               $formLoginConfig
     */
    public function __construct(AuthenticationUtils $authUtils, LoginFormRegistry $loginFormRegistry, TranslatorInterface $translator, array $formLoginConfig)
    {
        $this->authUtils         = $authUtils;
        $this->loginFormRegistry = $loginFormRegistry;
        $this->translator        = $translator;
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

        $this->setLastUsername($form, $firewallName);

        $lastAuthException  = $this->authUtils->getLastAuthenticationError();
        if ($lastAuthException) {
            if ($lastAuthException instanceof LoginFormException) {
                $this->setLoginFormErrors($form , $lastAuthException);
            } else {
                $this->setAuthError($form, $lastAuthException, $firewallName);
            }
        }

        return $form;
    }

    /**
     * @param FormInterface           $form
     * @param AuthenticationException $exception
     * @param string                  $firewallName
     */
    protected function setAuthError(FormInterface $form, AuthenticationException $exception, string $firewallName)
    {
        $formError = new FormError($this->translator->trans($exception->getMessageKey(), [], 'security'));

        switch (true) {
            case $exception instanceof AccountStatusException:
            case $exception instanceof UsernameNotFoundException:
                $usernameParameter = $this->formLoginConfig[$firewallName]['username_parameter'];
                $form->get($usernameParameter)->addError($formError);
                break;
            case $exception instanceof BadCredentialsException:
                $passwordParameter = $this->formLoginConfig[$firewallName]['username_parameter'];
                $form->get($passwordParameter)->addError($formError);
                break;
            default:
                $form->addError($formError);
        }
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
