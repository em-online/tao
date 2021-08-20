<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 * 
 */

/**
 * Validator to ensure a property value is unique
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package tao
 */
class tao_helpers_form_validators_UniqueToDestination
    extends tao_helpers_form_Validator
{
    private $property;
    /**
     * (non-PHPdoc)
     * @see tao_helpers_form_Validator::getDefaultMessage()
     */
    protected function getDefaultMessage()
    {
        return __('The value for the property "%s" must be unique this destination.', $this->getProperty()->getLabel());
    }

    public function setOptions(array $options)
    {
        unset($this->property);

        parent::setOptions($options);
    }


    /**
     * @return core_kernel_classes_Property
     * @throws common_exception_Error
     */
    protected function getProperty()
    {
        if( !isset($this->property) || empty($this->property) ){
            if (!$this->hasOption('property')) {
                throw new common_exception_Error('Property not set');
            }

            $this->property = ($this->getOption('property') instanceof core_kernel_classes_Property)
                ? $this->getOption('property')
                : new core_kernel_classes_Property($this->getOption('property'));
        }

        return $this->property;
    }

    /**
     * (non-PHPdoc)
     * @see tao_helpers_form_Validator::evaluate()
     */
    public function evaluate($values)
    {
        $destination = $this->getOption('resourceClass');
        //common_Logger::d("${destination}");
        $domain = $this->getProperty()->getDomain();
        foreach ($domain as $class) {
            $resources = $class->searchInstances(array($this->getProperty()->getUri() => $values), array('recursive' => true, 'like' => false));
            $matches = 0;
            if (count($resources) > 0) {
                $destinationResources = $destination->getInstances(false, array($this->getProperty()->getUri() => $values));
                foreach($resources as $resource) {
                    $resource_uri = $resource->getUri();
                    //common_Logger::d("${resource_uri}");
                    
                    foreach($destinationResources as $dest_resource) {
                        $dest_resource_uri = $dest_resource->getUri();
                        //common_Logger::d("${dest_resource_uri}");
                        if($dest_resource_uri == $resource_uri){
                            $matches++;
                        }
                    }
                }
                // If the match is not in the destination directory
                if($matches < count($resources)){
                    return false;
                }
            }
        }
        return true;
    }

}
