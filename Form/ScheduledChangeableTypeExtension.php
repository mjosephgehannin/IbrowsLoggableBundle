<?php
/**
 * Created by iBROWS AG.
 * User: marcsteiner
 * Date: 11.03.14
 * Time: 13:05
 */

namespace Ibrows\LoggableBundle\Form;


use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ScheduledChangeableTypeExtension extends AbstractTypeExtension
{


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['scheduledchangeable'] === false) {
            return;
        }


        $propertyName = 'scheduledChangeDate';
        if ($options['scheduledchangeable'] === 'auto') {
            if (!$this->checkIsScheduledChangeable($builder, $propertyName)) {
                return;
            }
        } else {
            if ($options['scheduledchangeable'] !== true) {
                $propertyName = $options['scheduledchangeable'];
            }
        }

        $builder->add($propertyName, 'date', array('widget' => 'single_text', 'format' => 'yyyy-MM-dd','required'=>false));

    }

    /**
     * Add the image_path option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('scheduledchangeable' => 'auto'));
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'form';
    }

    protected function checkIsScheduledChangeable(FormBuilderInterface $builder, $property)
    {
        $entity = $builder->getData();
        if ($entity == null || !is_object($entity) || (method_exists($entity,'getId') && $entity->getId() == null) )  {
            return;
        }
        $class = get_class($entity);
        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->implementsInterface('\Ibrows\LoggableBundle\Model\ScheduledChangeable')) {
            return false;
        }
        if (property_exists($class, $property)) {
            return true;
        }

        return false;
    }
}