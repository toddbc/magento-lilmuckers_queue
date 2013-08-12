<?php 

/**
 * Queue task handler
 *
 * @category   Lilmuckers
 * @package    Lilmuckers_Queue
 * @author     Patrick McKinley <contact@patrick-mckinley.com>
 */
class Lilmuckers_Queue_Model_Queue_Task extends Varien_Object
{
    /**
     * Callback config path pattern
     */
    const WORKER_CALLBACK_PATH = 'queues/%s/workers/%s';
    
    /**
     * Task status codes
     */
    const TASK_IDLE         = 0;
    const TASK_SUCCESS      = 200;
    const TASK_RETRY        = 201;
    const TASK_HOLD         = 202;
    const TASK_ERROR        = 500;
    const TASK_CONFIG_ERROR = 501;
    
    /**
     * The raw job object from the adapter
     * 
     * @var mixed
     */
    protected $_job;
    
    /**
     * The task identifier
     * 
     * @var string
     */
    protected $_task;
    
    /**
     * The store context that this shall be run in
     * 
     * @var Mage_Core_Model_Store
     */
    protected $_store;
    
    /**
     * The queue context for the task to be run in
     * 
     * @var Lilmuckers_Queue_Model_Queue_Abstract
     */
    protected $_queue;
    
    /**
     * The status that is assigned to the task by the worker
     * 
     * @var int
     */
    protected $_status = 0;
    
    /**
     * The priority for the task
     * 
     * @var int
     */
    protected $_priority;
    
    /**
     * The ttr (or expected lifespan) for the task
     * 
     * @var int
     */
    protected $_ttr;
    
    /**
     * The time to delay a task for
     * 
     * @var int
     */
    protected $_delay;
    
    /**
     * Set the priority of the task
     * 
     * @param int $priority
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;
        return $this;
    }
    
    /**
     * Get the priority of the task
     * 
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }
    
    /**
     * Set the ttr of the task
     * 
     * @param int $ttr
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setTtr($ttr)
    {
        $this->_ttr = $ttr;
        return $this;
    }
    
    /**
     * Get the ttr of the task
     * 
     * @return int
     */
    public function getTtr()
    {
        return $this->_ttr;
    }
    
    /**
     * Set the delay of the task
     * 
     * @param int $delay
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setDelay($delay)
    {
        $this->_delay = $delay;
        return $this;
    }
    
    /**
     * Get the delay of the task
     * 
     * @return int
     */
    public function getDelay()
    {
        return $this->_delay;
    }
    
    /**
     * Set the status of the task for the queue to read
     * 
     * @param int $status
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }
    
    /**
     * Get the status of the task
     * 
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }
    
    /**
     * Flag this task for retry
     * 
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function retry()
    {
        return $this->setStatus(self::TASK_RETRY);
    }
    
    /**
     * Flag this task as success and removal from queue
     * 
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function success()
    {
        return $this->setStatus(self::TASK_SUCCESS);
    }
    
    /**
     * Flag this task for holding
     * 
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function hold()
    {
        return $this->setStatus(self::TASK_HOLD);
    }
    
    /**
     * Assign the queue context for this task
     * 
     * @param Lilmuckers_Queue_Model_Queue_Abstract $queue
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setQueue(Lilmuckers_Queue_Model_Queue_Abstract $queue)
    {
        $this->_queue = $queue;
        return $this;
    }
    
    /**
     * Get the queue context for this task - defaults to the default queue
     * 
     * @return Lilmuckers_Queue_Model_Queue_Abstract
     */
    public function getQueue()
    {
        if(!$this->_queue){
            $this->_queue = Mage::helper('lilqueue')->getQueue(Lilmuckers_Queue_Model_Queue::DEFAULT_QUEUE_CODE);
        }
        return $this->_queue;
    }
    
    /**
     * Set the store context
     * 
     * @param Mage_Core_Model_Store|int|string $store
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setStore($store)
    {
        //if the store ID or the store code provided - load the store
        if (!($store instanceof Mage_Core_Model_Store)) {
            $store = Mage::app()->getStore($store);
        }
        
        $this->_store = $store;
        return $this;
    }
    
    /**
     * Get the store context 
     * 
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        //if a context isn't set, assume the current store
        if (!$this->_store) {
            $this->_store = Mage::app()->getStore();
        }
        return $this->_store;
    }
    
    /**
     * Set the task identifier
     * 
     * @param string $task
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setTask($task)
    {
        $this->_task = $task;
        return $this;
    }
    
    /**
     * Get the task identifier 
     * 
     * @return string
     */
    public function getTask()
    {
        return $this->_task;
    }
    
    /**
     * The raw job object from the queue backend
     * 
     * @param mixed $job
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function setJob($job)
    {
        $this->_job = $job;
        return $this;
    }
    
    /**
     * Get the raw job object for the queue backend
     * 
     * @return mixed
     */
    public function getJob()
    {
        return $this->_job;
    }
    
    /**
     * Export the job data as JSON
     * 
     * @return string
     */
    public function exportData()
    {
        //create the array of data needed for the task
        $_output = array(
            'task'     => $this->getTask(),
            'store'    => $this->getStore()->getId(),
            'data'     => $this->getData()
        );
        
        //json encode this array for storage in the queue
        return json_encode($_output);
    }
    
    /**
     * Import the json job data
     * 
     * @param string $data
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function importData($data)
    {
        //decode the job data
        $_input = json_decode($data, true);
        
        //set the task, store context, and data
        $this->setTask($_input['task'])
            ->setStore($_input['store'])
            ->setData($_input['data']);
        
        return $this;
    }
    
    /**
     * Run the worker for this task
     * 
     * @TODO Exception handling to log errors
     * @return bool
     */
    public function run()
    {
        try{
            //get the worker callback
            $_callback = $this->_getWorkerCallback();
            
            //split the array because of reasons
            $_className = $_callback['class'];
            $_methodName = $_callback['method'];
            
            //get a singleton instance of the class
            $_classInstance = Mage::getSingleton($_className);
            
            //run the method with the task as the argument
            $_classInstance->$_methodName($this);
            
            return $this->getStatus();
        } catch (Lilmuckers_Queue_Model_Queue_Task_Exception $e) {
            //there's been an error with the worker config
            return self::TASK_CONFIG_ERROR;
        } catch (Exception $e) {
            //there's a generic error with the worker itself
            return self::TASK_ERROR;
        }
    }
    
    /**
     * Touch the task to keep it reserved
     * 
     * @return Lilmuckers_Queue_Model_Queue_Task
     */
    public function touch()
    {
        Mage::helper('lilqueue')->getAdapter()->touch($this);
        return $this;
    }
    
    /**
     * Load the worker callback from the queue and task identifier
     * 
     * @throws Lilmuckers_Queue_Model_Queue_Task_Exception
     * @return array
     */
    public function _getWorkerCallback()
    {
        //build the path to the config element for the worker
        $_workerPath = sprintf(self::WORKER_CALLBACK_PATH, $this->getQueue()->getName(), $this->getTask());
        
        //get the worker config element
        $_worker = Mage::getConfig()->getNode($_workerPath);
        
        //if there's no worker config element, throw an error
        if(!$_worker){
            $_message = sprintf('Invalid queue or worker: %s - %s', $this->getQueue()->getName(), $this->getTask());
            throw new Lilmuckers_Queue_Model_Queue_Task_Exception($_message);
        }
        
        //translate it to an array for ease of use
        return $_worker->asArray();
    }
}