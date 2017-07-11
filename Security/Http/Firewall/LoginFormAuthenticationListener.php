<?php

namespace Chebur\LoginFormBundle\Security\Http\Firewall;

use Chebur\LoginFormBundle\Security\Exception\LoginFormException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class LoginFormAuthenticationListener extends AbstractAuthenticationListener
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @param TokenStorageInterface                  $tokenStorage
     * @param AuthenticationManagerInterface         $authenticationManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param HttpUtils                              $httpUtils
     * @param string                                 $providerKey
     * @param AuthenticationSuccessHandlerInterface  $successHandler
     * @param AuthenticationFailureHandlerInterface  $failureHandler
     * @param array                                  $options
     * @param LoggerInterface                        $logger
     * @param EventDispatcherInterface               $dispatcher
     * @param CsrfTokenManager                       $csrfTokenManager todo избавиться
     * @param FormInterface                          $form
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        $csrfTokenManager,
        FormInterface $form = null
    ) {
        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher
        );
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $form = $this->form;

        $this->form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return new RedirectResponse($this->options['login_path']);
        }

        if ($form->isValid()) {
            $formData = $form->getData() ? : array();
        } else {
            $exception = new LoginFormException();
            $exception->setErrors($form->getErrors(true, false)); //todo test
            throw $exception;
        }

        $username = $formData[$this->options['username_parameter']];
        $password = $formData[$this->options['password_parameter']];

        if (strlen($username) > Security::MAX_USERNAME_LENGTH) { //На случай если не будут валидировать
            throw new BadCredentialsException('Invalid username.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
    }

}
