<?php

/**
 * This file is part of the Ai Tool Bundle
 *
 * (c) 2011 Mark Brennand, ACTiVEiNGREDiENT
 * 
 */

namespace Ai\ToolBundle\Service;

/**
 * PHP bean mapper inspired by Dozer Bean Mapper
 *
 * @see Dozer project - http://dozer.sourceforge.net
 *
 * @author Mark Brennand <mark@activeingredient.com.au>
 * 
 */

class Dozer
{
  /**
   * Map bean properties to target
   *
   * @param $bean - object to map from (or null to extract from an array)
   * @param $target - (empty) array, namespaced class or object to map to
   * @param $mappings - optional array of field mappings
   * @param $properties - optional array of defaults
   * 
   * @return $bean - object
   */
  public function map($bean, $target, $mappings=array(), $properties=array())
  {
    # get a set of properties, if bean is a valid object
    if($bean !== null && is_object($bean))
    {
      # we have to search all parents for private members
      $seen = array();
      
      # this will be our sentinal
      $className = get_class($bean);
      do
      {
        $reflectionClass = new ReflectionClass($className);
        foreach($reflectionClass->getProperties() as $property)
        {
          # extract name
          $name = $property->getName();
          
          # don't duplicate from parent
          if(isset($seen[$name]))
          {
            continue;
          }
          
          # make sure we don't get it next time
          $seen[$name] = 1;
          
          # check for getter
          $getter = 'get'.ucfirst($name);
          if(method_exists($bean, $getter))
          {
            $properties[$name] = $object->$getter();
          }
          
          # use direct property access
          else
          {
            $property->setAccessible(true);
            $properties[$name] = $property->getValue();
          }
          
          # check for field mapping
          if(isset($mappings[$name]))
          {
            # copy value and unset
            $properties[$mappings[$name]] = $properties[$name];
            unset($properties[$name]);
          }
        }
        
        # check for parent
        $className = $reflectionClass->getParentClass()?$reflectionClass->getParentClass()->getName():'';
        
      } while($className);
    }
    
    # now inject
    return $this->inject($target, $properties);
  }
  
  /**
   * Inject an array, new instance or existing obj with specified properties
   *
   * @param $bean - array, namespaced class or object
   * @param $properties - array
   * 
   * @return $bean - object
   */
  public function inject($bean, $properties)
  {
    # are we mapping to an array
    if(is_array($bean))
    {
      return $properties;
    }
    
    # do we have an instance up and running
    elseif(is_object($bean))
    {
      # get relection class
      $reflectionClass = new ReflectionClass(get_class($bean));
    }
    
    # get relection class and instance of bean
    else
    {
      $reflectionClass = new ReflectionClass($bean);
      $bean = $reflectionClass->newInstance();
    }
    
    # now populate
    return $this->populate($reflectionClass, $bean, $properties);
  }
  
  /**
   * Populate object with specified properties
   *
   * @param $object - object
   * @param $reflectionClass - relection helper
   * @param $properties - array
   * 
   * @return $object - object
   */
  private function populate(ReflectionClass $reflectionClass, $object, $properties)
  {
    # now loop and build
    foreach($properties as $name=>$value)
    {
      # check for setter
      $setter = 'set'.ucfirst($name);
      if(method_exists($object, $setter))
      {
        $object->$setter($value);
      }
      
      # use direct property access
      else
      {
        $property = $reflectionClass->getProperty($name);
        if($property)
        {
          $property->setAccessible(true);
          $object->$property = $value;
        }
      }
    }
    
    # return populated object
    return $object;
  }
}