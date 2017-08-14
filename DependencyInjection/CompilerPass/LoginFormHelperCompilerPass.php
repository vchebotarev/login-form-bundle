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
        $helperDef = $container->getDefinition('chebur.login_form.form.helper');

        if ($container->hasDefinition('chebur.login_form.form.registry')) {
            $helperDef->addMethodCall('setLoginFormRegistry', [new Reference('chebur.login_form.form.registry')]);
            $helperDef->addMethodCall('setLoginFormConfig', [$container->getParameter('chebur.login_form.security.config')]);
        }
    }

}
