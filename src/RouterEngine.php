<?php

/**
 * This class routes the URL to corrosponding controller.
 *
 * @author : Pranjal Pandey
 */

namespace Scrawler\Router;
use Symfony\Component\HttpFoundation\Request;


class RouterEngine {
    //---------------------------------------------------------------//

    /**
     * Stores the URL broken logic wise.
     *
     * @var array
     */
    private $path_info = [];

    /**
     * Stores the request method i.e get,post etc.
     *
     * @var string
     */
    private $request_method;

    /**
     * Stores the Request Object
     */
     private $request;

    /**
     * Stores the RouterCollection object.
     */
    private $collection;

    /**
     * Stores the controller being dispatched
     */
    private $controller;

    /**
     * Store the method being dispatched
     */
    private $method;

//---------------------------------------------------------------//

    /**
     * constructor overloading for auto routing.
     */
    public function __construct(Request $request, RouteCollection $collection) {
        $this->request = $request;
        $this->collection = $collection;
    }

//---------------------------------------------------------------//


    /**
     * Detects the URL and call the corrosponding method
     * of corrosponding controller.
     */
    public function route() {
        // Get URL and request method.
        $this->request_method = strtolower($this->request->getMethod());

        //Break URL into segments
        $this->path_info = explode('/', $this->request->getPathInfo());
        array_shift($this->path_info);

        //Set corrosponding controller
        if (isset($this->path_info[0]) && !empty($this->path_info[0]))
            $this->controller = $this->collection->getController(ucfirst($this->path_info[0]));
        else
            $this->controller = $this->collection->getNamespace().'\Main';


        //Sets the Request attribute according to the route
        if (!class_exists($this->controller)) {

            $this->controller = $this->collection->getNamespace().'\Main';
            if(class_exists($this->controller)){
            array_unshift($this->path_info, '');
            }else{
            $this->error('No Controller could be resolved');
            }

        }

            $this->method = $this->getMethod($this->controller);
            $this->request->attributes->set('_controller',$this->controller.'::'.$this->method);

            $this->setArguments();
    }

//---------------------------------------------------------------//

    /**
     * Function to throw 404 error.
     *
     *@param string $message
     */
    protected function error($message) {

        throw new NotFoundException('Oops its an 404 error! :'.$message);
    }

//---------------------------------------------------------------//

    /**
     * Function to dispach the method if method exist.
     *
     */
    private function setArguments() {
        $controller = new $this->controller;

        $arguments = [];
        for ($j = 2; $j < count($this->path_info); $j++) {
        array_push($arguments, $this->path_info[$j]);
        }
            //Check weather arguments are passed else throw a 404 error
            $classMethod = new \ReflectionMethod($controller, $this->method);
            if (count($arguments) < count($classMethod->getParameters()))
                $this->error('Not enough arguments given to the method');
            else
                $this->request->attributes->set('_arguments',implode(",",$arguments));

    }

//---------------------------------------------------------------//

    /**
     * Returns the method to be called according to URL.
     *
     * @param string $controller
     *
     * @return string
     */
    private function getMethod($controller) {

        //Set Method from second argument from URL
            if (method_exists($controller, $function =  $this->request_method . ucfirst($this->path_info[1])))
                return $function;
            if (method_exists($controller, $function = 'all' . ucfirst($this->path_info[1])))
                return $function;
        //If second argument not set switch to Index function
            if (method_exists($controller, $function = $this->request_method . 'Index')){
                $last=end($this->path_info);
                array_pop($this->path_info);
                array_push($this->path_info,"",$last);
                return $function;
              }
            if (method_exists($controller, $function = 'allIndex')){
                $last=end($this->path_info);
                array_pop($this->path_info);
                array_push($this->path_info,"",$last);
                return $function;
              }

        $this->error('The '.$function.' method you are looking for is not found in '.$controller.' controller');

    }

//---------------------------------------------------------------//



}
