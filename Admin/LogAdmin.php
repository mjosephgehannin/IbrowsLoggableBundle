<?php
namespace Ibrows\LoggableBundle\Admin;


use Ibrows\LoggableBundle\Entity\Log;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Validator\Constraints as Assert;


class LogAdmin extends Admin
{
    protected $datagridValues = array(
        '_page'       => 1,
        '_per_page'   => 25,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id'
    );


    protected function configureListFields(ListMapper $mapper)
    {
        $mapper->addIdentifier('id', null, array('route' => array('name' => 'show')));
        $mapper->add('action');
        $mapper->add('objectId');
        $mapper->add('objectClass');
        $mapper->add('loggedAt');
        $mapper->add('username');
    }

    protected function configureShowFields(ShowMapper $mapper)
    {
        $mapper->add('id');
        $mapper->add('action');
        $mapper->add('objectId');
        $mapper->add('objectClass');
        $mapper->add('data','array');
        $mapper->add('oldData','array');
        $mapper->add('loggedAt');
        $mapper->add('username');
    }

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter->add('objectClass');
        $filter->add('objectId', 'doctrine_orm_number', array(), 'text', array());
        $filter->add('username');
    }


    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
    }


}
