<?php

namespace Chebur\LoginFormBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoginFormHelperCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        //todo сделать практически все здесь, оставив в фабрике минимум

        $helperDef = $container->getDefinition('chebur.login_form.form.helper');

        if ($container->hasDefinition('chebur.login_form.form.factory')) {
            $config = $container->getParameter('chebur.login_form.security.config');

            $helperDef->addArgument(new Reference('chebur.login_form.form.factory'));
            $helperDef->addArgument($config);

            $container->getDefinition('chebur.login_form.form.factory')->addArgument($config);
        }
    }

}
