<?php

namespace Chebur\LoginFormBundle\DependencyInjection\Security\Factory;

use Chebur\LoginFormBundle\Security\Form\LoginFormRegistry;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory as BaseFormLoginFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Form;

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

    /**
     * @inheritdoc
     */
    public function getKey()
    {
        return 'chebur-form-login';
    }

    /**
     * @inheritdoc
     */
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
                ->booleanNode('hide_user_not_found')
                    ->defaultValue(true)
                ->end()
            ->end()
        ;
    }

    /**
     * @inheritdoc
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        list($authProviderId, $listenerId, $entryPointId) =  parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);

        $this->addContainerParameters($container, $config, $id);

        $this->createLoginForm($container, $config, $id, $listenerId);

        return array($authProviderId, $listenerId, $entryPointId);
    }

    /**
     * @inheritdoc
     */
    public function getListenerId()
    {
        return 'chebur.login_form.authentication.listener';
    }

    /**
     * @inheritdoc
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $daoProviderId = parent::createAuthProvider($container, $id, $config, $userProviderId);

        $container->getDefinition($daoProviderId)->replaceArgument(4, $config['hide_user_not_found']);

        return $daoProviderId;
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param string           $firewallName
     * @param string           $listenerId
     * @return string
     */
    protected function createLoginForm(ContainerBuilder $container, array $config, $firewallName, $listenerId)
    {
        $loginFromId = 'chebur.login_form.security.form.' . $firewallName;
        $container->setDefinition($loginFromId, new Definition(Form::class))
            ->setFactory(array(
                new Reference('form.factory'),
                'create'
            ))
            ->addArgument($config['form'])
        ;

        //Добавляем форму к listener
        $container->getDefinition($listenerId)->addArgument(new Reference($loginFromId));

        //Добавим в реестр форм
        $loginFormRegistryId = 'chebur.login_form.form.registry';
        if (!$container->hasDefinition($loginFormRegistryId)) {
            $def = $container->setDefinition('chebur.login_form.form.registry', new Definition(LoginFormRegistry::class));
        } else {
            $def = $container->getDefinition($loginFormRegistryId);
        }
        $def->addMethodCall('add', array(
            new Reference($loginFromId),
            $firewallName,
        ));


        return $loginFromId;
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
