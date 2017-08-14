<?php

namespace Chebur\LoginFormBundle;

use Chebur\LoginFormBundle\DependencyInjection\CompilerPass\LoginFormHelperCompilerPass;
use Chebur\LoginFormBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CheburLoginFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var $extension SecurityExtension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new FormLoginFactory);

        $container->addCompilerPass(new LoginFormHelperCompilerPass());
    }

}
