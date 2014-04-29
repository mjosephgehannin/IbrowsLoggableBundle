<?php
namespace Ibrows\LoggableBundle\Admin;


use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;


class ChangeSetAdmin extends Admin
{

    protected function configureListFields(ListMapper $mapper)
    {
        $mapper->addIdentifier('id', null, array('route' => array('name' => 'show')));
        $mapper->add('action');
        $mapper->add('objectId');
        $mapper->add('objectClass');
        $mapper->add('changeAt');
        $mapper->add('username');
    }

    protected function configureShowFields(ShowMapper $mapper)
    {
        $mapper->add('id');
        $mapper->add('action');
        $mapper->add('objectId');
        $mapper->add('objectClass');
        $mapper->add('data');
        $mapper->add('oldData');
        $mapper->add('changeAt');
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
    }


}
