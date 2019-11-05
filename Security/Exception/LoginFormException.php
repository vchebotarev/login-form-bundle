<?php

namespace Chebur\LoginFormBundle\Security\Exception;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginFormException extends AuthenticationException
{
    /**
     * @var FormInterface
     */
    protected $formErrors;

    /**
     * @param FormErrorIterator $formErrors
     * @return $this
     */
    public function setErrors(FormErrorIterator $formErrors)
    {
        $toArray = function(FormErrorIterator $errorIterator) use ( &$toArray ) {
            $errorsArray = array();
            foreach ($errorIterator as $error) {
                if ($error instanceof FormErrorIterator) {
                    $errorsArray[$error->getForm()->getName()] = $toArray($error);
                } else {
                    $errorsArray[] = $error->getMessage();
                }
            }
            return $errorsArray;
        };
        $this->formErrors = $toArray($formErrors);
        return $this;
    }

    /**
     * @return FormInterface
     */
    public function getFormErrors()
    {
        return $this->formErrors;
    }

    public function serialize()
    {
        return serialize(array(
            $this->formErrors,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list($this->formErrors, $parentData) = unserialize($str);
        parent::unserialize($parentData);
    }

    public function getMessageKey()
    {
        return 'Invalid credentials.';
    }
}