<?php

namespace Chebur\LoginFormBundle\DependencyInjection\Security\Factory;

use Chebur\LoginFormBundle\Security\Form\LoginFormFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory as BaseFormLoginFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FormLoginFactory extends BaseFormLoginFactory
{
    public function __construct()
    {
        parent::__construct();

        //Этим займется форма
        unset($this->options['csrf_parameter']);
        unset($this->options['csrf_token_id']);
        unset($this->options['post_only']);
        unset($this->options['csrf_token_generator']);
    }

    public function getKey()
    {
        return 'chebur-form-login';
    }

    public function addConfiguration(NodeDefinition $node)
    {
        parent::addConfiguration($node);

        $node
            ->children()
                ->scalarNode('form')
                    ->cannotBeEmpty()
                    ->beforeNormalization()
                        ->ifTrue(function ($e){
                            return !class_exists($e);
                        })
                        ->thenInvalid('Login FormType class does not exist')
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        list($authProviderId, $listenerId, $entryPointId) =  parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);

        $this->addContainerParameters($container, $config, $id);

        $this->addLoginFormFactoryToListener($container, $config, $id, $listenerId);

        return array($authProviderId, $listenerId, $entryPointId);
    }

    public function getListenerId()
    {
        return 'chebur.login_form.authentication.listener';
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param string           $firewallName
     * @param string           $listenerId
     */
    protected function addLoginFormFactoryToListener(ContainerBuilder $container, array $config, $firewallName, $listenerId)
    {
        $loginFormFactoryId = 'chebur.login_form.form.factory';
        if (!$container->hasDefinition($loginFormFactoryId)) {
            //создаем если таковой ещё нет
            $def = $container->setDefinition($loginFormFactoryId, new Definition(LoginFormFactory::class));
            $def->addArgument(new Reference('form.factory'));
        }

        $container->getDefinition($listenerId)->addArgument(new Reference($loginFormFactoryId));
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param string           $firewallName
     */
    protected function addContainerParameters(ContainerBuilder $container, array $config, $firewallName)
    {
        $paramName = 'chebur.login_form.security.config';
        if ($container->hasParameter($paramName)) {
            $val = $container->getParameter($paramName);
        } else {
            $val = [];
        }
        $val[$firewallName] = $config;

        $container->setParameter($paramName, $val);
    }
}
